<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetTransferController extends Controller
{
    private function authorizeBudget(Budget $budget, bool $requireWrite = false): void
    {
        abort_unless($budget->project->id_group == $this->currentGroupId(), 403);
        if ($requireWrite) {
            abort_unless($this->currentUser()->canWrite($budget->project), 403);
        }
    }

    private function authorizeTransfer(BudgetTransfer $transfer, bool $requireWrite = false): void
    {
        $this->authorizeBudget($transfer->budget, $requireWrite);
    }

    private function loadBudgetItems(Budget $budget): void
    {
        $budget->load([
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
        ]);
    }

    public function index(Budget $budget): View
    {
        $this->authorizeBudget($budget);
        $budget->load(['transfers.fromItem.category', 'transfers.toItem.category']);
        $canEdit = $this->currentUser()->canWrite($budget->project);
        return view('budget_transfers.index', compact('budget', 'canEdit'));
    }

    public function create(Budget $budget): View
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $this->loadBudgetItems($budget);
        return view('budget_transfers.create', compact('budget'));
    }

    public function store(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $data = $request->validate([
            'date'               => ['required', 'date'],
            'description'        => ['required', 'string', 'max:500'],
            'from_budget_item_id'=> ['required', 'integer', 'exists:budget_items,id', 'different:to_budget_item_id'],
            'to_budget_item_id'  => ['required', 'integer', 'exists:budget_items,id'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
        ]);

        BudgetTransfer::create(['budget_id' => $budget->id] + $data);

        return redirect()->route('budgets.transfers.index', $budget)->with('success', __('Transfer saved.'));
    }

    public function show(BudgetTransfer $transfer): View
    {
        $this->authorizeTransfer($transfer);
        $transfer->load(['fromItem.category', 'toItem.category']);
        return view('budget_transfers.show', ['transfer' => $transfer, 'budget' => $transfer->budget]);
    }

    public function edit(BudgetTransfer $transfer): View
    {
        $this->authorizeTransfer($transfer, requireWrite: true);
        $budget = $transfer->budget;
        $this->loadBudgetItems($budget);
        return view('budget_transfers.edit', compact('transfer', 'budget'));
    }

    public function update(Request $request, BudgetTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($transfer, requireWrite: true);
        $data = $request->validate([
            'date'               => ['required', 'date'],
            'description'        => ['required', 'string', 'max:500'],
            'from_budget_item_id'=> ['required', 'integer', 'exists:budget_items,id', 'different:to_budget_item_id'],
            'to_budget_item_id'  => ['required', 'integer', 'exists:budget_items,id'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
        ]);

        $transfer->update($data);

        return redirect()->route('budgets.transfers.index', $transfer->budget)->with('success', __('Transfer updated.'));
    }

    public function destroy(BudgetTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($transfer, requireWrite: true);
        $budget = $transfer->budget;
        $transfer->delete();
        return redirect()->route('budgets.transfers.index', $budget)->with('success', __('Transfer deleted.'));
    }
}
