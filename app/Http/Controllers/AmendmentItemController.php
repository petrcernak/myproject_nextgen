<?php

namespace App\Http\Controllers;

use App\Models\Amendment;
use App\Models\AmendmentItem;
use App\Models\ContractItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmendmentItemController extends Controller
{
    public function store(Request $request, Amendment $amendment): RedirectResponse
    {
        $project = $amendment->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $data = $request->validate([
            'contract_item_id' => ['required', 'exists:contract_items,id'],
            'amount'           => ['required', 'numeric'],
            'description'      => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless(
            ContractItem::where('id', $data['contract_item_id'])
                ->where('contract_id', $amendment->contract_id)->exists(),
            422
        );

        $data['amendment_id'] = $amendment->id;
        $data['sort'] = ($amendment->items()->max('sort') ?? 0) + 1;

        AmendmentItem::create($data);

        return back()->with('success', __('Item added.'));
    }

    public function edit(AmendmentItem $item): View
    {
        $amendment = $item->amendment;
        $project   = $amendment->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);
        $amendment->load(['contract.items', 'items.contractItem']);
        return view('amendments.content', compact('amendment'))->with('editItem', $item);
    }

    public function update(Request $request, AmendmentItem $item): RedirectResponse
    {
        $project = $item->amendment->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $data = $request->validate([
            'amount'      => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $item->update($data);

        return redirect()->route('amendments.content', $item->amendment)->with('success', __('Item saved.'));
    }

    public function destroy(AmendmentItem $item): RedirectResponse
    {
        $amendment = $item->amendment;
        $project   = $amendment->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $item->delete();

        return back()->with('success', __('Item deleted.'));
    }
}
