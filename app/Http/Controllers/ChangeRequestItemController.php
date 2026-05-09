<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestItemRevision;
use App\Models\ContractItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChangeRequestItemController extends Controller
{
    public function store(Request $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $project = $changeRequest->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $data = $request->validate([
            'contract_item_id' => ['required', 'exists:contract_items,id'],
            'description'      => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless(
            ContractItem::where('id', $data['contract_item_id'])
                ->where('contract_id', $changeRequest->contract_id)->exists(),
            422
        );

        $data['change_request_id'] = $changeRequest->id;
        $data['sort'] = ($changeRequest->items()->max('sort') ?? 0) + 1;

        ChangeRequestItem::create($data);

        return back()->with('success', __('Item added.'));
    }

    public function destroy(ChangeRequestItem $item): RedirectResponse
    {
        $project = $item->changeRequest->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $item->delete();

        return back()->with('success', __('Item deleted.'));
    }
}
