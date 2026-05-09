<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangeRequestController extends Controller
{
    private function authorizeForContract(Contract $contract, bool $write = false): void
    {
        abort_unless($contract->project && $contract->project->id_group == $this->currentGroupId(), 403);
        $user = $this->currentUser();
        if ($write) {
            abort_unless($user->canWrite($contract->project), 403);
        } else {
            abort_unless($user->canRead($contract->project), 403);
        }
    }

    private function authorizeCr(ChangeRequest $cr, bool $write = false): void
    {
        $this->authorizeForContract($cr->contract, $write);
    }

    public function indexForContract(Contract $contract, Request $request): View
    {
        $this->authorizeForContract($contract);
        $changeRequests = $contract->changeRequests()
            ->with(['items.latestRevision'])
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('code', 'ilike', "%{$request->search}%")
                   ->orWhere('name', 'ilike', "%{$request->search}%");
            }))
            ->orderByDesc('date')
            ->paginate(50)->withQueryString();
        $canEdit = $this->currentUser()->canWrite($contract->project);
        return view('change_requests.index', compact('contract', 'changeRequests', 'canEdit'));
    }

    public function create(Contract $contract): View
    {
        $this->authorizeForContract($contract);
        abort_unless($this->currentUser()->canWrite($contract->project), 403);
        $count    = $contract->changeRequests()->count();
        $nextCode = $contract->code . '/CR' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
        return view('change_requests.form', compact('contract', 'nextCode'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeForContract($contract, write: true);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $data['contract_id'] = $contract->id;
        $cr = ChangeRequest::create($data);
        return redirect()->route('change-requests.show', $cr)->with('success', __('Change request created.'));
    }

    public function show(ChangeRequest $changeRequest): View
    {
        $this->authorizeCr($changeRequest);
        $changeRequest->load(['contract', 'items.contractItem', 'items.revisions', 'items.latestRevision']);
        $canEdit = $this->currentUser()->canWrite($changeRequest->contract->project);
        return view('change_requests.show', compact('changeRequest', 'canEdit'));
    }

    public function editContent(ChangeRequest $changeRequest): View|RedirectResponse
    {
        $this->authorizeCr($changeRequest);
        if (!$this->currentUser()->canWrite($changeRequest->contract->project)) {
            return redirect()->route('change-requests.show', $changeRequest)
                ->with('error', __('You do not have permission to edit change request content.'));
        }
        $changeRequest->load(['contract.items', 'items.contractItem']);
        return view('change_requests.content', compact('changeRequest'));
    }

    public function edit(ChangeRequest $changeRequest): View
    {
        $this->authorizeCr($changeRequest);
        abort_unless($this->currentUser()->canWrite($changeRequest->contract->project), 403);
        $contract = $changeRequest->contract;
        return view('change_requests.form', compact('changeRequest', 'contract'));
    }

    public function update(Request $request, ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeCr($changeRequest, write: true);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $changeRequest->update($data);
        return redirect()->route('change-requests.show', $changeRequest)->with('success', __('Change request saved.'));
    }

    public function destroy(ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeCr($changeRequest, write: true);
        $contract = $changeRequest->contract;
        $changeRequest->delete();
        return redirect()->route('contracts.show', $contract)->with('success', __('Change request deleted.'));
    }
}
