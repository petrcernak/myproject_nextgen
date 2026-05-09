<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetAdjustment;
use App\Models\BudgetAdjustmentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetAdjustmentController extends Controller
{
    private function authorizeBudget(Budget $budget, bool $requireWrite = false): void
    {
        abort_unless($budget->project->id_group == $this->currentGroupId(), 403);
        if ($requireWrite) {
            abort_unless($this->currentUser()->canWrite($budget->project), 403);
        }
    }

    private function authorizeAdjustment(BudgetAdjustment $adjustment, bool $requireWrite = false): void
    {
        $this->authorizeBudget($adjustment->budget, $requireWrite);
    }

    public function create(Budget $budget): View
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $budget->load([
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
        ]);
        return view('budget_adjustments.create', compact('budget'));
    }

    public function store(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $data = $request->validate([
            'date'        => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'items'       => ['nullable', 'array'],
            'items.*'     => ['nullable', 'numeric'],
        ]);

        $adjustment = BudgetAdjustment::create([
            'budget_id'   => $budget->id,
            'date'        => $data['date'],
            'description' => $data['description'],
        ]);

        foreach ($data['items'] ?? [] as $itemId => $amount) {
            if ($amount !== null && $amount != 0) {
                BudgetAdjustmentItem::create([
                    'budget_adjustment_id' => $adjustment->id,
                    'budget_item_id'       => (int) $itemId,
                    'amount'               => $amount,
                ]);
            }
        }

        return redirect()->route('budgets.show', $budget)->with('success', __('Adjustment saved.'));
    }

    public function show(BudgetAdjustment $adjustment): View
    {
        $this->authorizeAdjustment($adjustment);
        $adjustment->load(['items.budgetItem.category.budget']);
        return view('budget_adjustments.show', ['adjustment' => $adjustment, 'budget' => $adjustment->budget]);
    }

    public function edit(BudgetAdjustment $adjustment): View
    {
        $this->authorizeAdjustment($adjustment, requireWrite: true);
        $budget = $adjustment->budget;
        $budget->load([
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
        ]);
        $adjustment->load('items');
        return view('budget_adjustments.edit', compact('adjustment', 'budget'));
    }

    public function update(Request $request, BudgetAdjustment $adjustment): RedirectResponse
    {
        $this->authorizeAdjustment($adjustment, requireWrite: true);
        $data = $request->validate([
            'date'        => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'items'       => ['nullable', 'array'],
            'items.*'     => ['nullable', 'numeric'],
        ]);

        $adjustment->update([
            'date'        => $data['date'],
            'description' => $data['description'],
        ]);

        $adjustment->items()->delete();
        foreach ($data['items'] ?? [] as $itemId => $amount) {
            if ($amount !== null && $amount != 0) {
                BudgetAdjustmentItem::create([
                    'budget_adjustment_id' => $adjustment->id,
                    'budget_item_id'       => (int) $itemId,
                    'amount'               => $amount,
                ]);
            }
        }

        return redirect()->route('budgets.show', $adjustment->budget)->with('success', __('Adjustment updated.'));
    }

    public function destroy(BudgetAdjustment $adjustment): RedirectResponse
    {
        $this->authorizeAdjustment($adjustment, requireWrite: true);
        $budget = $adjustment->budget;
        $adjustment->delete();
        return redirect()->route('budgets.show', $budget)->with('success', __('Adjustment deleted.'));
    }
}
