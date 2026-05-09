<?php

namespace App\Http\Controllers;

use App\Models\Amendment;
use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmendmentController extends Controller
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

    private function authorizeAmendment(Amendment $amendment, bool $write = false): void
    {
        $this->authorizeForContract($amendment->contract, $write);
    }

    public function indexForContract(Contract $contract, Request $request): View
    {
        $this->authorizeForContract($contract);
        $amendments = $contract->amendments()
            ->with(['items', 'changeOrders.items'])
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('code', 'ilike', "%{$request->search}%")
                   ->orWhere('name', 'ilike', "%{$request->search}%");
            }))
            ->orderByDesc('date')
            ->paginate(50)->withQueryString();
        $canEdit = $this->currentUser()->canWrite($contract->project);
        return view('amendments.index', compact('contract', 'amendments', 'canEdit'));
    }

    public function create(Contract $contract): View
    {
        $this->authorizeForContract($contract);
        abort_unless($this->currentUser()->canWrite($contract->project), 403);
        $nextCode = 'A' . str_pad($contract->amendments()->count() + 1, 2, '0', STR_PAD_LEFT);
        return view('amendments.form', compact('contract', 'nextCode'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeForContract($contract, write: true);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $data['contract_id'] = $contract->id;
        $amendment = Amendment::create($data);
        return redirect()->route('amendments.show', $amendment)->with('success', __('Amendment created.'));
    }

    public function editContent(Amendment $amendment): View|RedirectResponse
    {
        $this->authorizeAmendment($amendment);
        if (!$this->currentUser()->canWrite($amendment->contract->project)) {
            return redirect()->route('amendments.show', $amendment)
                ->with('error', __('You do not have permission to edit amendment content.'));
        }
        $amendment->load(['contract.items', 'items.contractItem']);
        return view('amendments.content', compact('amendment'));
    }

    public function show(Amendment $amendment): View
    {
        $this->authorizeAmendment($amendment);
        $amendment->load(['contract', 'items.contractItem', 'changeOrders.items.contractItem']);
        $canEdit = $this->currentUser()->canWrite($amendment->contract->project);
        return view('amendments.show', compact('amendment', 'canEdit'));
    }

    public function edit(Amendment $amendment): View
    {
        $this->authorizeAmendment($amendment);
        abort_unless($this->currentUser()->canWrite($amendment->contract->project), 403);
        $contract = $amendment->contract;
        return view('amendments.form', compact('amendment', 'contract'));
    }

    public function update(Request $request, Amendment $amendment): RedirectResponse
    {
        $this->authorizeAmendment($amendment, write: true);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $amendment->update($data);
        return redirect()->route('amendments.show', $amendment)->with('success', __('Amendment saved.'));
    }

    public function destroy(Amendment $amendment): RedirectResponse
    {
        $this->authorizeAmendment($amendment, write: true);
        $contract = $amendment->contract;
        $amendment->delete();
        return redirect()->route('contracts.show', $contract)->with('success', __('Amendment deleted.'));
    }
}
