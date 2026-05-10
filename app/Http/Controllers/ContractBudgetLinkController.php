<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Contract;
use App\Models\ContractBudgetLink;
use App\Models\ContractItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractBudgetLinkController extends Controller
{
    private function authorizeContract(Contract $contract, bool $requireWrite = false): void
    {
        abort_unless($contract->project->id_group == $this->currentGroupId(), 403);
        if ($requireWrite) {
            abort_unless($this->currentUser()->canWrite($contract->project), 403);
        }
    }

    private function authorizeLink(ContractBudgetLink $link, bool $requireWrite = false): void
    {
        $this->authorizeContract($link->contract, $requireWrite);
    }

    public function create(Contract $contract): View
    {
        $this->authorizeContract($contract, requireWrite: true);
        $existingBudgetIds = $contract->budgetLinks()->pluck('budget_id');
        $budgets = Budget::where('project_id', $contract->project_id)
            ->whereNotIn('id', $existingBudgetIds)
            ->orderBy('name')
            ->get();
        return view('contract_budget_links.create', compact('contract', 'budgets'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($contract, requireWrite: true);
        $data = $request->validate([
            'budget_id' => ['required', 'integer', 'exists:budgets,id'],
            'fx_rate'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $budget = Budget::findOrFail($data['budget_id']);
        abort_unless($budget->project_id == $contract->project_id, 422);

        $link = ContractBudgetLink::firstOrCreate(
            ['contract_id' => $contract->id, 'budget_id' => $data['budget_id']],
            ['fx_rate' => $data['fx_rate'] ?: null]
        );

        return redirect()->route('contract-budget-links.show', $link)
            ->with('success', __('Budget linked.'));
    }

    public function show(ContractBudgetLink $contractBudgetLink): View
    {
        $this->authorizeLink($contractBudgetLink);
        $link = $contractBudgetLink;
        $link->load(['contract.items', 'budget.categories.children.children.items', 'budget.categories.children.items', 'budget.categories.items']);

        // Build flat list of budget items
        $budgetItems = collect();
        $flatten = function ($categories) use (&$flatten, &$budgetItems) {
            foreach ($categories as $cat) {
                foreach ($cat->items as $item) {
                    $budgetItems->push($item);
                }
                $flatten($cat->children);
            }
        };
        $flatten($link->budget->categories);

        // Map contract_item_id → budget_item_id for items linked to THIS budget
        $linkedMap = $link->contract->items
            ->filter(fn ($ci) => $ci->budget_item_id && $budgetItems->contains('id', $ci->budget_item_id))
            ->pluck('budget_item_id', 'id');

        $canEdit = $this->currentUser()->canWrite($link->contract->project);

        return view('contract_budget_links.show', compact('link', 'budgetItems', 'linkedMap', 'canEdit'));
    }

    public function update(Request $request, ContractBudgetLink $contractBudgetLink): RedirectResponse
    {
        $this->authorizeLink($contractBudgetLink, requireWrite: true);
        $data = $request->validate([
            'fx_rate' => ['nullable', 'numeric', 'min:0'],
            'items'   => ['nullable', 'array'],
            'items.*' => ['nullable', 'integer', 'exists:budget_items,id'],
        ]);

        $contractBudgetLink->update(['fx_rate' => $data['fx_rate'] ?: null]);

        $link = $contractBudgetLink;

        // Clear existing assignments for contract items linked to this budget
        $link->load(['budget.categories.children.children.items', 'budget.categories.children.items', 'budget.categories.items']);
        $allBudgetItemIds = collect();
        $flatten = function ($categories) use (&$flatten, &$allBudgetItemIds) {
            foreach ($categories as $cat) {
                $allBudgetItemIds = $allBudgetItemIds->merge($cat->items->pluck('id'));
                $flatten($cat->children);
            }
        };
        $flatten($link->budget->categories);

        // Reset contract items that were linked to this budget
        ContractItem::where('contract_id', $link->contract_id)
            ->whereIn('budget_item_id', $allBudgetItemIds)
            ->update(['budget_item_id' => null]);

        // Save new assignments
        foreach ($data['items'] ?? [] as $contractItemId => $budgetItemId) {
            if ($budgetItemId && $allBudgetItemIds->contains((int) $budgetItemId)) {
                ContractItem::where('id', (int) $contractItemId)
                    ->where('contract_id', $link->contract_id)
                    ->update(['budget_item_id' => (int) $budgetItemId]);
            }
        }

        return redirect()->route('contract-budget-links.show', $link)
            ->with('success', __('Assignments saved.'));
    }

    public function destroy(ContractBudgetLink $contractBudgetLink): RedirectResponse
    {
        $this->authorizeLink($contractBudgetLink, requireWrite: true);
        $contract = $contractBudgetLink->contract;

        // Clear budget_item_id for all contract items linked to this budget
        $link = $contractBudgetLink;
        $link->load(['budget.categories.children.children.items', 'budget.categories.children.items', 'budget.categories.items']);
        $allBudgetItemIds = collect();
        $flatten = function ($categories) use (&$flatten, &$allBudgetItemIds) {
            foreach ($categories as $cat) {
                $allBudgetItemIds = $allBudgetItemIds->merge($cat->items->pluck('id'));
                $flatten($cat->children);
            }
        };
        $flatten($link->budget->categories);

        ContractItem::where('contract_id', $link->contract_id)
            ->whereIn('budget_item_id', $allBudgetItemIds)
            ->update(['budget_item_id' => null]);

        $contractBudgetLink->delete();

        return redirect()->route('contracts.show', $contract)
            ->with('success', __('Budget link removed.'));
    }
}
