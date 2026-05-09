<?php

namespace App\Http\Controllers;

use App\Models\ChangeOrder;
use App\Models\ChangeOrderItem;
use App\Models\ContractItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangeOrderItemController extends Controller
{
    public function store(Request $request, ChangeOrder $changeOrder): RedirectResponse
    {
        $project = $changeOrder->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $data = $request->validate([
            'contract_item_id' => ['required', 'exists:contract_items,id'],
            'amount'           => ['required', 'numeric'],
            'description'      => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless(
            ContractItem::where('id', $data['contract_item_id'])
                ->where('contract_id', $changeOrder->contract_id)->exists(),
            422
        );

        $data['change_order_id'] = $changeOrder->id;
        $data['sort'] = ($changeOrder->items()->max('sort') ?? 0) + 1;

        ChangeOrderItem::create($data);

        return back()->with('success', __('Item added.'));
    }

    public function edit(ChangeOrderItem $item): View
    {
        $changeOrder = $item->changeOrder;
        $project = $changeOrder->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);
        $changeOrder->load(['contract.items', 'items.contractItem']);
        return view('change_orders.content', compact('changeOrder'))->with('editItem', $item);
    }

    public function update(Request $request, ChangeOrderItem $item): RedirectResponse
    {
        $project = $item->changeOrder->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $data = $request->validate([
            'amount'      => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $item->update($data);

        return redirect()->route('change-orders.content', $item->changeOrder)->with('success', __('Item saved.'));
    }

    public function destroy(ChangeOrderItem $item): RedirectResponse
    {
        $co = $item->changeOrder;
        $project = $co->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $item->delete();

        return back()->with('success', __('Item deleted.'));
    }
}
