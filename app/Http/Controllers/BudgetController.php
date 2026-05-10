<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\ContractItem;
use App\Models\ContractAnticipated;
use App\Models\ContractAnticipatedItem;
use App\Models\ChangeRequestItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    private function authorizeBudget(Budget $budget, bool $requireWrite = false): void
    {
        $project = $budget->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        $user = $this->currentUser();
        if ($requireWrite) {
            abort_unless($user->canWrite($project), 403);
        } else {
            abort_unless($user->canRead($project), 403);
        }
    }

    private function authorizeCategory(BudgetCategory $category, bool $requireWrite = false): void
    {
        $this->authorizeBudget($category->budget, $requireWrite);
    }

    private function authorizeItem(BudgetItem $item, bool $requireWrite = false): void
    {
        $this->authorizeCategory($item->category, $requireWrite);
    }

    public function index(): View|RedirectResponse
    {
        $projectId = session('current_project_id');

        if (!$projectId) {
            return redirect()->route('projects.index')
                ->with('error', __('Please select a project first.'));
        }

        $budgets = Budget::where('project_id', $projectId)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('budgets.index', compact('budgets'));
    }

    public function show(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')
                ->with('info', __('Project switched — showing budgets for the current project.'));
        }
        $budget->load([
            'project',
            'categories.children.children.items.adjustmentItems',
            'categories.children.children.items.transfersIn',
            'categories.children.children.items.transfersOut',
            'categories.children.items.adjustmentItems',
            'categories.children.items.transfersIn',
            'categories.children.items.transfersOut',
            'categories.items.adjustmentItems',
            'categories.items.transfersIn',
            'categories.items.transfersOut',
            'adjustments.items',
            'transfers.fromItem.category',
            'transfers.toItem.category',
        ]);
        $canEdit = $this->currentUser()->canWrite($budget->project);

        // Flatten all budget item IDs across all category levels
        $allItemIds = collect();
        $flatten = function ($categories) use (&$flatten, &$allItemIds) {
            foreach ($categories as $cat) {
                $allItemIds = $allItemIds->merge($cat->items->pluck('id'));
                if ($cat->children->isNotEmpty()) {
                    $flatten($cat->children);
                }
            }
        };
        $flatten($budget->categories);

        // Load contract items linked to budget items
        $contractItems = ContractItem::whereIn('budget_item_id', $allItemIds)
            ->with([
                'contract',
                'invoiceItems.invoice',
                'changeOrderItems',
                'amendmentItems',
            ])
            ->get();

        $ciIds = $contractItems->pluck('id');
        $contractIds = $contractItems->pluck('contract_id')->unique()->values();

        // Latest ContractAnticipated per contract → anticipated amounts per contract item
        $latestAnticipatedIds = ContractAnticipated::whereIn('contract_id', $contractIds)
            ->orderByDesc('date')->orderByDesc('id')
            ->get()
            ->groupBy('contract_id')
            ->map(fn ($group) => $group->first()->id);

        $anticipatedByContractItem = ContractAnticipatedItem::whereIn('contract_anticipated_id', $latestAnticipatedIds->values())
            ->whereIn('contract_item_id', $ciIds)
            ->get()
            ->groupBy('contract_item_id')
            ->map(fn ($items) => (float) $items->sum('amount'));

        // Open/closed Change Request amounts (latest revision amount_report)
        $crByContractItem = ChangeRequestItem::whereIn('contract_item_id', $ciIds)
            ->whereHas('changeRequest', fn ($q) => $q->whereIn('status', ['open', 'closed']))
            ->with('latestRevision')
            ->get()
            ->groupBy('contract_item_id')
            ->map(fn ($items) => (float) $items->sum(
                fn ($i) => (float) ($i->latestRevision?->amount_report ?? 0)
            ));

        // Build per-item cost data map
        $costData = collect();
        foreach ($contractItems->groupBy('budget_item_id') as $itemId => $items) {
            $contracts  = (float) $items->sum('amount');
            $changesCo  = (float) $items->sum(fn ($ci) => $ci->changeOrderItems->sum('amount'));
            $changesAmd = (float) $items->sum(fn ($ci) => $ci->amendmentItems->sum('amount'));
            $changes    = $changesCo + $changesAmd;
            $invoiced   = (float) $items->sum(fn ($ci) => $ci->invoiceItems->sum('amount'));

            $fxImpact = 0.0;
            foreach ($items as $ci) {
                $contractRate = (float) ($ci->contract->fx_rate ?? 0);
                if (!$contractRate) continue;
                foreach ($ci->invoiceItems as $ii) {
                    $invoiceRate = (float) ($ii->invoice->fx_rate ?? $contractRate);
                    if (!$invoiceRate) continue;
                    $fxImpact += (float) $ii->amount / $invoiceRate
                               - (float) $ii->amount / $contractRate;
                }
            }

            $anticipatedCa = 0.0;
            $anticipatedCr = 0.0;
            foreach ($items as $ci) {
                if (isset($anticipatedByContractItem[$ci->id])) {
                    $anticipatedCa += (float) $anticipatedByContractItem[$ci->id];
                } else {
                    $anticipatedCa += (float) $ci->amount + (float) $ci->changeOrderItems->sum('amount')
                                    + (float) $ci->amendmentItems->sum('amount');
                }
                $anticipatedCr += $crByContractItem->get($ci->id, 0.0);
            }

            $costData[$itemId] = [
                'contracts'            => $contracts,
                'changes_co'           => $changesCo,
                'changes_amd'          => $changesAmd,
                'changes'              => $changes,
                'current_commitments'  => $contracts + $changes,
                'invoiced'             => $invoiced,
                'fx_impact'            => $fxImpact,
                'anticipated_ca'       => $anticipatedCa,
                'anticipated_cr'       => $anticipatedCr,
                'anticipated_contracts'=> $anticipatedCa + $anticipatedCr,
            ];
        }

        // Grand supplementary totals for tiles
        $invoiceCount    = \App\Models\InvoiceItem::whereIn('contract_item_id', $ciIds)
                               ->distinct('invoice_id')->count('invoice_id');
        $anticipatedManualTotal = \App\Models\BudgetItem::whereIn('id', $allItemIds)
                                      ->sum('anticipated_manual');

        return view('budgets.show', compact(
            'budget', 'canEdit', 'costData', 'invoiceCount', 'anticipatedManualTotal'
        ));
    }

    public function editContent(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')
                ->with('info', __('Project switched — showing budgets for the current project.'));
        }
        if (!$this->currentUser()->canWrite($budget->project)) {
            return redirect()->route('budgets.show', $budget)
                ->with('error', __('You do not have permission to edit budget content.'));
        }
        $budget->load([
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
        ]);
        return view('budgets.content', compact('budget'));
    }

    public function create(Project $project): View
    {
        abort_unless($project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);
        return view('budgets.form', compact('project'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);
        $data = $request->validate([
            'code'     => ['required', 'string', 'max:50'],
            'name'     => ['required', 'string', 'max:255'],
            'date'     => ['required', 'date'],
            'currency' => ['required', 'string', 'max:10'],
            'note'     => ['nullable', 'string'],
        ]);

        $data['project_id'] = $project->id;
        $budget = Budget::create($data);

        return redirect()->route('budgets.show', $budget)->with('success', __('Budget created.'));
    }

    public function edit(Budget $budget): View
    {
        $this->authorizeBudget($budget);
        return view('budgets.form', ['project' => $budget->project, 'budget' => $budget]);
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $data = $request->validate([
            'code'     => ['required', 'string', 'max:50'],
            'name'     => ['required', 'string', 'max:255'],
            'date'     => ['required', 'date'],
            'currency' => ['required', 'string', 'max:10'],
            'note'     => ['nullable', 'string'],
        ]);

        $budget->update($data);

        return redirect()->route('budgets.show', $budget)->with('success', __('Budget saved.'));
    }

    // --- Sub-pages: Anticipated, Value to Place, Contract Anticipated, Change Requests ---

    public function anticipated(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load([
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
        ]);
        $canEdit = $this->currentUser()->canWrite($budget->project);
        return view('budgets.anticipated', compact('budget', 'canEdit'));
    }

    public function saveAnticipated(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $data = $request->validate(['items' => ['array'], 'items.*' => ['nullable', 'numeric']]);
        foreach ($data['items'] ?? [] as $itemId => $value) {
            BudgetItem::where('id', $itemId)
                ->whereHas('category', fn ($q) => $q->where('budget_id', $budget->id))
                ->update(['anticipated_manual' => $value ?? 0]);
        }
        return redirect()->route('budgets.anticipated', $budget)->with('success', __('Anticipated saved.'));
    }

    public function valueToPlace(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load([
            'categories.children.children.items.adjustmentItems',
            'categories.children.children.items.transfersIn',
            'categories.children.children.items.transfersOut',
            'categories.children.items.adjustmentItems',
            'categories.children.items.transfersIn',
            'categories.children.items.transfersOut',
            'categories.items.adjustmentItems',
            'categories.items.transfersIn',
            'categories.items.transfersOut',
        ]);
        $canEdit = $this->currentUser()->canWrite($budget->project);
        $costData = $this->buildCostData($budget);
        return view('budgets.value_to_place', compact('budget', 'canEdit', 'costData'));
    }

    public function saveValueToPlace(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $data = $request->validate(['items' => ['array'], 'items.*' => ['nullable', 'numeric']]);
        foreach ($data['items'] ?? [] as $itemId => $value) {
            BudgetItem::where('id', $itemId)
                ->whereHas('category', fn ($q) => $q->where('budget_id', $budget->id))
                ->update(['value_to_place_manual' => $value !== '' && $value !== null ? $value : null]);
        }
        return redirect()->route('budgets.value-to-place', $budget)->with('success', __('Value to Place saved.'));
    }

    public function contractAnticipated(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load([
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
        ]);
        $costData = $this->buildCostData($budget);
        return view('budgets.contract_anticipated', compact('budget', 'costData'));
    }

    public function changeRequests(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load([
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
        ]);
        $costData = $this->buildCostData($budget);
        return view('budgets.change_requests', compact('budget', 'costData'));
    }

    public function contracts(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load(['categories.children.children.items', 'categories.children.items', 'categories.items']);
        $costData = $this->buildCostData($budget);
        return view('budgets.contracts', compact('budget', 'costData'));
    }

    public function amendments(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load(['categories.children.children.items', 'categories.children.items', 'categories.items']);
        $costData = $this->buildCostData($budget);
        return view('budgets.amendments', compact('budget', 'costData'));
    }

    public function changeOrders(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load(['categories.children.children.items', 'categories.children.items', 'categories.items']);
        $costData = $this->buildCostData($budget);
        return view('budgets.change_orders', compact('budget', 'costData'));
    }

    private function buildCostData(Budget $budget): \Illuminate\Support\Collection
    {
        $allItemIds = collect();
        $flatten = function ($categories) use (&$flatten, &$allItemIds) {
            foreach ($categories as $cat) {
                $allItemIds = $allItemIds->merge($cat->items->pluck('id'));
                if ($cat->children->isNotEmpty()) {
                    $flatten($cat->children);
                }
            }
        };
        $flatten($budget->categories);

        $contractItems = ContractItem::whereIn('budget_item_id', $allItemIds)
            ->with(['contract', 'invoiceItems.invoice', 'changeOrderItems', 'amendmentItems'])
            ->get();

        $ciIds       = $contractItems->pluck('id');
        $contractIds = $contractItems->pluck('contract_id')->unique()->values();

        $latestAnticipatedIds = ContractAnticipated::whereIn('contract_id', $contractIds)
            ->orderByDesc('date')->orderByDesc('id')
            ->get()
            ->groupBy('contract_id')
            ->map(fn ($g) => $g->first()->id);

        $anticipatedByContractItem = ContractAnticipatedItem::whereIn('contract_anticipated_id', $latestAnticipatedIds->values())
            ->whereIn('contract_item_id', $ciIds)
            ->get()
            ->groupBy('contract_item_id')
            ->map(fn ($items) => (float) $items->sum('amount'));

        $crByContractItem = ChangeRequestItem::whereIn('contract_item_id', $ciIds)
            ->whereHas('changeRequest', fn ($q) => $q->whereIn('status', ['open', 'closed']))
            ->with('latestRevision')
            ->get()
            ->groupBy('contract_item_id')
            ->map(fn ($items) => (float) $items->sum(
                fn ($i) => (float) ($i->latestRevision?->amount_report ?? 0)
            ));

        $costData = collect();
        foreach ($contractItems->groupBy('budget_item_id') as $itemId => $items) {
            $contracts  = (float) $items->sum('amount');
            $changesCo  = (float) $items->sum(fn ($ci) => $ci->changeOrderItems->sum('amount'));
            $changesAmd = (float) $items->sum(fn ($ci) => $ci->amendmentItems->sum('amount'));
            $invoiced   = (float) $items->sum(fn ($ci) => $ci->invoiceItems->sum('amount'));

            $fxImpact = 0.0;
            foreach ($items as $ci) {
                $contractRate = (float) ($ci->contract->fx_rate ?? 0);
                if (!$contractRate) continue;
                foreach ($ci->invoiceItems as $ii) {
                    $invoiceRate = (float) ($ii->invoice->fx_rate ?? $contractRate);
                    if (!$invoiceRate) continue;
                    $fxImpact += (float) $ii->amount / $invoiceRate
                               - (float) $ii->amount / $contractRate;
                }
            }

            $anticipatedCa = 0.0;
            $anticipatedCr = 0.0;
            foreach ($items as $ci) {
                if (isset($anticipatedByContractItem[$ci->id])) {
                    $anticipatedCa += (float) $anticipatedByContractItem[$ci->id];
                } else {
                    $anticipatedCa += (float) $ci->amount
                                    + (float) $ci->changeOrderItems->sum('amount')
                                    + (float) $ci->amendmentItems->sum('amount');
                }
                $anticipatedCr += $crByContractItem->get($ci->id, 0.0);
            }

            $costData[$itemId] = [
                'contracts'   => $contracts,
                'changes_co'  => $changesCo,
                'changes_amd' => $changesAmd,
                'invoiced'    => $invoiced,
                'fx_impact'   => $fxImpact,
                'anticipated_ca' => $anticipatedCa,
                'anticipated_cr' => $anticipatedCr,
            ];
        }

        return $costData;
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $project = $budget->project;
        $budget->delete();

        return redirect()->route('projects.show', $project)->with('success', __('Budget deleted.'));
    }

    // --- Categories ---

    public function storeCategory(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:budget_categories,id'],
        ]);

        $data['budget_id'] = $budget->id;
        $data['sort'] = $budget->categories()->max('sort') + 1;

        BudgetCategory::create($data);

        return back()->with('success', __('Category added.'));
    }

    public function destroyCategory(BudgetCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category, requireWrite: true);
        $budget = $category->budget;
        abort_if(
            $category->items()->exists() || $category->children()->exists(),
            422,
            __('Cannot delete a category that contains items or subcategories.')
        );
        $category->delete();

        return redirect()->route('budgets.content', $budget)->with('success', __('Category deleted.'));
    }

    public function editCategory(BudgetCategory $category): View
    {
        $this->authorizeCategory($category, requireWrite: true);
        return view('budget_categories.edit', ['category' => $category, 'budget' => $category->budget]);
    }

    public function updateCategory(Request $request, BudgetCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category, requireWrite: true);
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->update($data);

        return redirect()->route('budgets.content', $category->budget)->with('success', __('Category saved.'));
    }

    // --- Items ---

    public function storeItem(Request $request, BudgetCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category, requireWrite: true);
        $data = $request->validate([
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'amount'      => ['required', 'numeric'],
        ]);

        $data['budget_category_id'] = $category->id;
        $data['sort'] = $category->items()->max('sort') + 1;

        BudgetItem::create($data);

        return back()->with('success', __('Item added.'));
    }

    public function editItem(BudgetItem $item): View
    {
        $this->authorizeItem($item);
        return view('budget_items.edit', ['item' => $item, 'budget' => $item->category->budget]);
    }

    public function updateItem(Request $request, BudgetItem $item): RedirectResponse
    {
        $this->authorizeItem($item, requireWrite: true);
        $data = $request->validate([
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'amount'      => ['required', 'numeric'],
        ]);

        $item->update($data);

        return redirect()->route('budgets.show', $item->category->budget)->with('success', __('Item saved.'));
    }

    public function destroyItem(BudgetItem $item): RedirectResponse
    {
        $this->authorizeItem($item, requireWrite: true);
        $budget = $item->category->budget;
        $item->delete();

        return redirect()->route('budgets.show', $budget)->with('success', __('Item deleted.'));
    }
}
