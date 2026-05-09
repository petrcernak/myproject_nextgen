<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestItemRevision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangeRequestItemRevisionController extends Controller
{
    public function edit(ChangeRequestItemRevision $revision): View
    {
        $item        = $revision->item;
        $changeRequest = $item->changeRequest;
        $project     = $changeRequest->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);
        $changeRequest->load(['contract.items', 'items.revisions.item', 'items.contractItem']);
        return view('change_requests.content', compact('changeRequest'))->with('editRevision', $revision);
    }

    public function store(Request $request, ChangeRequestItem $item): RedirectResponse
    {
        $project = $item->changeRequest->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $data = $request->validate([
            'date'            => ['required', 'date'],
            'amount_supplier' => ['required', 'numeric'],
            'amount_pm'       => ['required', 'numeric'],
            'amount_report'   => ['required', 'numeric'],
            'note'            => ['nullable', 'string', 'max:500'],
        ]);

        $data['change_request_item_id'] = $item->id;
        ChangeRequestItemRevision::create($data);

        return back()->with('success', __('Revision added.'));
    }

    public function update(Request $request, ChangeRequestItemRevision $revision): RedirectResponse
    {
        $project = $revision->item->changeRequest->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $data = $request->validate([
            'date'            => ['required', 'date'],
            'amount_supplier' => ['required', 'numeric'],
            'amount_pm'       => ['required', 'numeric'],
            'amount_report'   => ['required', 'numeric'],
            'note'            => ['nullable', 'string', 'max:500'],
        ]);

        $revision->update($data);

        return back()->with('success', __('Revision saved.'));
    }

    public function destroy(ChangeRequestItemRevision $revision): RedirectResponse
    {
        $project = $revision->item->changeRequest->contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);

        $revision->delete();

        return back()->with('success', __('Revision deleted.'));
    }
}
