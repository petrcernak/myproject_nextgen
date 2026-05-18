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

        $currentProject = \App\Models\Project::find($projectId);
        $canEdit = $currentProject && $this->currentUser()->canWrite($currentProject);

        return view('budgets.index', compact('budgets', 'canEdit'));
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

        // VTP entries per item
        $vtpManualSums = \App\Models\BudgetItemVtp::whereIn('budget_item_id', $allItemIds)
            ->selectRaw('budget_item_id, sum(amount) as total')
            ->groupBy('budget_item_id')
            ->pluck('total', 'budget_item_id');
        $vtpAutoFlags = \App\Models\BudgetItem::whereIn('id', $allItemIds)
            ->pluck('vtp_auto', 'id');
        foreach ($allItemIds as $id) {
            $entry = $costData->get($id) ?? [
                'contracts' => 0, 'changes_co' => 0, 'changes_amd' => 0,
                'changes' => 0, 'current_commitments' => 0, 'invoiced' => 0,
                'fx_impact' => 0, 'anticipated_ca' => 0, 'anticipated_cr' => 0,
                'anticipated_contracts' => 0,
            ];
            $entry['vtp_manual_sum'] = (float) ($vtpManualSums[$id] ?? 0);
            $entry['vtp_auto']       = (bool) ($vtpAutoFlags[$id] ?? true);
            $costData->put($id, $entry);
        }

        // Anticipated manual entries per item
        $antManualSums = \App\Models\BudgetAnticipatedEntry::whereIn('budget_item_id', $allItemIds)
            ->selectRaw('budget_item_id, sum(amount) as total')
            ->groupBy('budget_item_id')
            ->pluck('total', 'budget_item_id');
        foreach ($allItemIds as $id) {
            $e = $costData->get($id) ?? [
                'contracts' => 0, 'changes_co' => 0, 'changes_amd' => 0,
                'changes' => 0, 'current_commitments' => 0, 'invoiced' => 0,
                'fx_impact' => 0, 'anticipated_ca' => 0, 'anticipated_cr' => 0,
                'anticipated_contracts' => 0, 'vtp_manual_sum' => 0, 'vtp_auto' => true,
            ];
            $e['ant_manual_sum'] = (float) ($antManualSums[$id] ?? 0);
            $costData->put($id, $e);
        }

        // Grand supplementary totals for tiles
        $invoiceCount = \App\Models\InvoiceItem::whereIn('contract_item_id', $ciIds)
                            ->distinct('invoice_id')->count('invoice_id');

        return view('budgets.show', compact(
            'budget', 'canEdit', 'costData', 'invoiceCount'
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
        $costData = $this->buildCostData($budget);
        return view('budgets.anticipated', compact('budget', 'costData'));
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

        // Anticipated manual entries per item
        $antManualSums = \App\Models\BudgetAnticipatedEntry::whereIn('budget_item_id', $allItemIds)
            ->selectRaw('budget_item_id, sum(amount) as total')
            ->groupBy('budget_item_id')
            ->pluck('total', 'budget_item_id');

        // VTP entries
        $vtpManualSums = \App\Models\BudgetItemVtp::whereIn('budget_item_id', $allItemIds)
            ->selectRaw('budget_item_id, sum(amount) as total')
            ->groupBy('budget_item_id')
            ->pluck('total', 'budget_item_id');
        $vtpAutoFlags = \App\Models\BudgetItem::whereIn('id', $allItemIds)
            ->pluck('vtp_auto', 'id');
        foreach ($allItemIds as $id) {
            $entry = $costData->get($id) ?? [
                'contracts' => 0, 'changes_co' => 0, 'changes_amd' => 0,
                'invoiced' => 0, 'fx_impact' => 0,
                'anticipated_ca' => 0, 'anticipated_cr' => 0,
            ];
            $entry['ant_manual_sum'] = (float) ($antManualSums[$id] ?? 0);
            $entry['vtp_manual_sum'] = (float) ($vtpManualSums[$id] ?? 0);
            $entry['vtp_auto']       = (bool) ($vtpAutoFlags[$id] ?? true);
            $costData->put($id, $entry);
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

    public function itemTransfers(BudgetItem $item): View|RedirectResponse
    {
        $this->authorizeItem($item);
        $budget = $item->category->budget;
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }

        $transfersIn  = $item->transfersIn()->with(['fromItem.category', 'budget'])->orderByDesc('date')->get();
        $transfersOut = $item->transfersOut()->with(['toItem.category', 'budget'])->orderByDesc('date')->get();

        $rows = collect();
        foreach ($transfersIn as $tr) {
            $rows->push(['dir' => 'in',  'transfer' => $tr, 'amount' => $tr->amount]);
        }
        foreach ($transfersOut as $tr) {
            $rows->push(['dir' => 'out', 'transfer' => $tr, 'amount' => -$tr->amount]);
        }
        $rows = $rows->sortByDesc(fn ($r) => $r['transfer']->date);

        $net = $rows->sum('amount');

        return view('budget_items.transfers', compact('item', 'budget', 'rows', 'net'));
    }

    public function itemAdjustments(BudgetItem $item): View|RedirectResponse
    {
        $this->authorizeItem($item);
        $budget = $item->category->budget;
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }

        $adjItems = $item->adjustmentItems()
            ->with(['adjustment'])
            ->get()
            ->sortByDesc(fn ($ai) => $ai->adjustment->date?->format('Y-m-d') ?? '');

        $total = $adjItems->sum('amount');

        return view('budget_items.adjustments', compact('item', 'budget', 'adjItems', 'total'));
    }

    public function itemInvoiced(\Illuminate\Http\Request $request, BudgetItem $item): View|RedirectResponse
    {
        $this->authorizeItem($item);
        $budget = $item->category->budget;
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }

        $contractItems = ContractItem::where('budget_item_id', $item->id)
            ->with('contract')
            ->get();
        $ciIds = $contractItems->pluck('id');

        $base = \App\Models\InvoiceItem::whereIn('contract_item_id', $ciIds)
            ->with(['invoice', 'contractItem.contract']);

        // Dropdown: contracts that have invoices, narrowed by invoice_no if set (but NOT by contract_id)
        $contractsQuery = $base->clone();
        if ($request->filled('invoice_no')) {
            $no = $request->invoice_no;
            $contractsQuery->whereHas('invoice', fn ($q) => $q->where('no', 'ilike', '%'.$no.'%'));
        }
        $contracts = $contractsQuery->get()
            ->map(fn ($ii) => $ii->contractItem->contract)
            ->unique('id')
            ->sortBy('name')
            ->values();

        // Rows: both filters applied
        $query = $base->clone();
        if ($request->filled('contract_id')) {
            $filterCiIds = $contractItems
                ->where('contract_id', (int) $request->contract_id)
                ->pluck('id');
            $query->whereIn('contract_item_id', $filterCiIds);
        }
        if ($request->filled('invoice_no')) {
            $no = $request->invoice_no;
            $query->whereHas('invoice', fn ($q) => $q->where('no', 'ilike', '%'.$no.'%'));
        }

        $invoiceItems = $query->get()->sortBy(fn ($ii) => [
            $ii->contractItem->contract->name ?? '',
            $ii->invoice->issued?->format('Y-m-d') ?? '',
        ]);

        return view('budget_items.invoiced', compact('item', 'budget', 'invoiceItems', 'contracts'));
    }

    public function itemCommitments(BudgetItem $item): View|RedirectResponse
    {
        $this->authorizeItem($item);
        $budget = $item->category->budget;
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $contractItems = ContractItem::where('budget_item_id', $item->id)
            ->with([
                'contract',
                'changeOrderItems.changeOrder',
                'amendmentItems.amendment',
            ])
            ->get();
        return view('budget_items.commitments', compact('item', 'budget', 'contractItems'));
    }

    private function buildItemCostBreakdown(BudgetItem $item): array
    {
        $contractItems = ContractItem::where('budget_item_id', $item->id)
            ->with(['contract', 'invoiceItems.invoice', 'changeOrderItems', 'amendmentItems'])
            ->get();
        $ciIds = $contractItems->pluck('id');
        $contractIds = $contractItems->pluck('contract_id')->unique()->values();

        $latestAnticipatedIds = \App\Models\ContractAnticipated::whereIn('contract_id', $contractIds)
            ->orderByDesc('date')->orderByDesc('id')
            ->get()->groupBy('contract_id')->map(fn ($g) => $g->first()->id);

        $anticipatedByContractItem = \App\Models\ContractAnticipatedItem::whereIn('contract_anticipated_id', $latestAnticipatedIds->values())
            ->whereIn('contract_item_id', $ciIds)->get()
            ->groupBy('contract_item_id')->map(fn ($items) => (float) $items->sum('amount'));

        $crByContractItem = \App\Models\ChangeRequestItem::whereIn('contract_item_id', $ciIds)
            ->whereHas('changeRequest', fn ($q) => $q->whereIn('status', ['open', 'closed']))
            ->with('latestRevision')->get()->groupBy('contract_item_id')
            ->map(fn ($items) => (float) $items->sum(fn ($i) => (float) ($i->latestRevision?->amount_report ?? 0)));

        $contracts  = (float) $contractItems->sum('amount');
        $changesCo  = (float) $contractItems->sum(fn ($ci) => $ci->changeOrderItems->sum('amount'));
        $changesAmd = (float) $contractItems->sum(fn ($ci) => $ci->amendmentItems->sum('amount'));
        $currComm   = $contracts + $changesCo + $changesAmd;

        $fxImpact = 0.0;
        foreach ($contractItems as $ci) {
            $contractRate = (float) ($ci->contract->fx_rate ?? 0);
            if (!$contractRate) continue;
            foreach ($ci->invoiceItems as $ii) {
                $invoiceRate = (float) ($ii->invoice->fx_rate ?? $contractRate);
                if (!$invoiceRate) continue;
                $fxImpact += (float) $ii->amount / $invoiceRate - (float) $ii->amount / $contractRate;
            }
        }

        $antCa = 0.0;
        $antCr = 0.0;
        foreach ($contractItems as $ci) {
            if (isset($anticipatedByContractItem[$ci->id])) {
                $antCa += (float) $anticipatedByContractItem[$ci->id];
            } else {
                $antCa += (float) $ci->amount
                         + (float) $ci->changeOrderItems->sum('amount')
                         + (float) $ci->amendmentItems->sum('amount');
            }
            $antCr += $crByContractItem->get($ci->id, 0.0);
        }

        $amount      = (float) $item->amount;
        $adj         = (float) $item->adjustment;
        $trans       = (float) $item->transfer;
        $actual      = $amount + $adj + $trans;
        $anticipated = $antCa + $antCr + (float) $item->anticipated_manual;

        return compact('actual', 'currComm', 'anticipated', 'fxImpact');
    }

    public function itemValueToPlace(\Illuminate\Http\Request $request, BudgetItem $item): View|RedirectResponse
    {
        $this->authorizeItem($item);
        $budget = $item->category->budget;
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load(['project']);
        $canEdit = $this->currentUser()->canWrite($budget->project);

        ['actual' => $actual, 'currComm' => $currComm, 'anticipated' => $anticipated, 'fxImpact' => $fxImpact]
            = $this->buildItemCostBreakdown($item);

        $vtpAutoResidual = max(0.0, $actual - $currComm - $anticipated - $fxImpact);

        $search = $request->input('search', '');
        $vtpQuery = \App\Models\BudgetItemVtp::where('budget_item_id', $item->id);
        if ($search !== '') {
            $vtpQuery->where('description', 'ilike', '%'.$search.'%');
        }
        $vtpEntries = $vtpQuery->orderBy('date', 'desc')->orderByDesc('id')->get();

        $vtpManualSum = (float) \App\Models\BudgetItemVtp::where('budget_item_id', $item->id)->sum('amount');
        $vtpAutoEnabled = (bool) $item->vtp_auto;
        $autoResidual = $vtpAutoEnabled ? max(0.0, $actual - $currComm - $anticipated - $fxImpact - $vtpManualSum) : 0.0;
        $vtpTotal = $vtpManualSum + $autoResidual;

        return view('budget_items.value_to_place', compact(
            'item', 'budget', 'canEdit', 'search',
            'actual', 'currComm', 'anticipated', 'fxImpact',
            'vtpAutoResidual', 'vtpEntries', 'vtpManualSum', 'vtpAutoEnabled', 'autoResidual', 'vtpTotal'
        ));
    }

    public function storeItemVtp(\Illuminate\Http\Request $request, BudgetItem $item): RedirectResponse
    {
        $this->authorizeItem($item, requireWrite: true);
        $request->validate([
            'date'        => 'required|date',
            'amount'      => 'required|numeric',
            'description' => 'nullable|string|max:500',
        ]);
        \App\Models\BudgetItemVtp::create([
            'budget_item_id' => $item->id,
            'date'           => $request->date,
            'amount'         => (float) $request->amount,
            'description'    => $request->description,
        ]);
        return redirect()->route('budget-items.value-to-place', $item)->with('success', __('Entry added.'));
    }

    public function updateItemVtp(\Illuminate\Http\Request $request, \App\Models\BudgetItemVtp $vtp): RedirectResponse
    {
        $this->authorizeItem($vtp->budgetItem, requireWrite: true);
        $request->validate([
            'date'        => 'required|date',
            'amount'      => 'required|numeric',
            'description' => 'nullable|string|max:500',
        ]);
        $vtp->update([
            'date'        => $request->date,
            'amount'      => (float) $request->amount,
            'description' => $request->description,
        ]);
        return redirect()->route('budget-items.value-to-place', $vtp->budget_item_id)->with('success', __('Entry updated.'));
    }

    public function destroyItemVtp(\App\Models\BudgetItemVtp $vtp): RedirectResponse
    {
        $this->authorizeItem($vtp->budgetItem, requireWrite: true);
        $itemId = $vtp->budget_item_id;
        $vtp->delete();
        return redirect()->route('budget-items.value-to-place', $itemId)->with('success', __('Entry deleted.'));
    }

    public function toggleItemVtpAuto(\Illuminate\Http\Request $request, BudgetItem $item): RedirectResponse
    {
        $this->authorizeItem($item, requireWrite: true);
        $item->update(['vtp_auto' => $request->boolean('vtp_auto')]);
        return redirect()->route('budget-items.value-to-place', $item)->with('success', __('Auto-calculation updated.'));
    }

    public function itemAnticipated(\Illuminate\Http\Request $request, BudgetItem $item): View|RedirectResponse
    {
        $this->authorizeItem($item);
        $budget = $item->category->budget;
        if ($budget->project_id != session('current_project_id')) {
            return redirect()->route('budgets.index')->with('info', __('Project switched.'));
        }
        $budget->load(['project']);
        $canEdit = $this->currentUser()->canWrite($budget->project);

        $typeFilter = $request->input('type', '');
        $textFilter = $request->input('search', '');

        // Load contract items once — needed for CA and CR
        $contractItems = ContractItem::where('budget_item_id', $item->id)
            ->with(['contract'])
            ->get();
        $ciIds = $contractItems->pluck('id');

        // 1. Manual entries (description filter only)
        $manualRows = collect();
        $q = \App\Models\BudgetAnticipatedEntry::where('budget_item_id', $item->id);
        if ($textFilter) {
            $q->where('description', 'ilike', '%'.$textFilter.'%');
        }
        foreach ($q->orderBy('date', 'desc')->get() as $e) {
            $manualRows->push([
                'type'         => 'anticipated',
                'id'           => $e->id,
                'date'         => $e->date,
                'description'  => $e->description,
                'amount_orig'  => null,
                'fx_rate'      => null,
                'amount_budget'=> (float) $e->amount,
                'currency'     => null,
                'entry'        => $e,
            ]);
        }

        // 2. Contract Anticipated (description filter only)
        $caRows = collect();
        if ($ciIds->isNotEmpty()) {
            $latestIds = ContractAnticipated::whereIn('contract_id', $contractItems->pluck('contract_id')->unique())
                ->orderByDesc('date')->orderByDesc('id')
                ->get()->groupBy('contract_id')->map(fn ($g) => $g->first());

            $caItems = ContractAnticipatedItem::whereIn('contract_item_id', $ciIds)
                ->whereIn('contract_anticipated_id', $latestIds->map(fn ($ca) => $ca->id)->values())
                ->with(['anticipated', 'contractItem.contract'])
                ->get();

            foreach ($caItems as $cai) {
                $desc = $cai->anticipated->name ?? $cai->anticipated->code ?? '';
                if ($cai->description) {
                    $desc = $desc ? $desc.' — '.$cai->description : $cai->description;
                }
                if ($textFilter && stripos($desc, $textFilter) === false) continue;
                $fxRate = (float) ($cai->contractItem->contract->fx_rate ?? 0);
                $amtOrig = (float) $cai->amount;
                $caRows->push([
                    'type'         => 'contract anticipated',
                    'id'           => $cai->id,
                    'date'         => $cai->anticipated->date,
                    'description'  => $desc,
                    'amount_orig'  => $amtOrig,
                    'fx_rate'      => $fxRate ?: null,
                    'amount_budget'=> $fxRate > 0 ? $amtOrig / $fxRate : $amtOrig,
                    'currency'     => $cai->contractItem->contract->currency,
                    'entry'        => null,
                ]);
            }
        }

        // 3. Change Requests (description filter only)
        $crRows = collect();
        if ($ciIds->isNotEmpty()) {
            $crItems = \App\Models\ChangeRequestItem::whereIn('contract_item_id', $ciIds)
                ->whereHas('changeRequest', fn ($q) => $q->whereIn('status', ['open', 'closed']))
                ->with(['changeRequest', 'contractItem.contract', 'latestRevision'])
                ->get();

            foreach ($crItems as $cri) {
                $desc = $cri->changeRequest->name ?? $cri->changeRequest->code ?? '';
                if ($cri->description) {
                    $desc = $desc ? $desc.' — '.$cri->description : $cri->description;
                }
                if ($textFilter && stripos($desc, $textFilter) === false) continue;
                $fxRate = (float) ($cri->contractItem->contract->fx_rate ?? 0);
                $amtOrig = (float) ($cri->latestRevision?->amount_report ?? 0);
                $crRows->push([
                    'type'         => 'change request',
                    'id'           => $cri->id,
                    'date'         => $cri->changeRequest->date,
                    'description'  => $desc,
                    'amount_orig'  => $amtOrig,
                    'fx_rate'      => $fxRate ?: null,
                    'amount_budget'=> $fxRate > 0 ? $amtOrig / $fxRate : $amtOrig,
                    'currency'     => $cri->contractItem->contract->currency,
                    'entry'        => null,
                ]);
            }
        }

        // Available types come from description-filtered results (before type filter)
        $allRows = $manualRows->concat($caRows)->concat($crRows);
        $availableTypes = $allRows->pluck('type')->unique()->values()->toArray();

        // Apply type filter
        $rows = ($typeFilter && in_array($typeFilter, $availableTypes))
            ? $allRows->filter(fn ($r) => $r['type'] === $typeFilter)
            : $allRows;

        $rows = $rows->sortByDesc(fn ($r) => $r['date']?->format('Y-m-d') ?? '');

        return view('budget_items.anticipated', compact(
            'item', 'budget', 'canEdit', 'rows',
            'typeFilter', 'textFilter', 'availableTypes'
        ));
    }

    public function storeItemAnticipated(\Illuminate\Http\Request $request, BudgetItem $item): RedirectResponse
    {
        $this->authorizeItem($item, requireWrite: true);
        $request->validate([
            'date'        => 'required|date',
            'amount'      => 'required|numeric',
            'description' => 'nullable|string|max:500',
        ]);
        \App\Models\BudgetAnticipatedEntry::create([
            'budget_item_id' => $item->id,
            'date'           => $request->date,
            'amount'         => (float) $request->amount,
            'description'    => $request->description,
        ]);
        return redirect()->route('budget-items.anticipated', $item)->with('success', __('Entry added.'));
    }

    public function updateItemAnticipated(\Illuminate\Http\Request $request, \App\Models\BudgetAnticipatedEntry $entry): RedirectResponse
    {
        $this->authorizeItem($entry->budgetItem, requireWrite: true);
        $request->validate([
            'date'        => 'required|date',
            'amount'      => 'required|numeric',
            'description' => 'nullable|string|max:500',
        ]);
        $entry->update([
            'date'        => $request->date,
            'amount'      => (float) $request->amount,
            'description' => $request->description,
        ]);
        return redirect()->route('budget-items.anticipated', $entry->budget_item_id)->with('success', __('Entry updated.'));
    }

    public function destroyItemAnticipated(\App\Models\BudgetAnticipatedEntry $entry): RedirectResponse
    {
        $this->authorizeItem($entry->budgetItem, requireWrite: true);
        $itemId = $entry->budget_item_id;
        $entry->delete();
        return redirect()->route('budget-items.anticipated', $itemId)->with('success', __('Entry deleted.'));
    }
}
