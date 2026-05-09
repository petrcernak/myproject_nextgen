<?php

namespace App\Http\Controllers;

use App\Models\Amendment;
use App\Models\ChangeOrder;
use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChangeOrderController extends Controller
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

    private function authorizeChangeOrder(ChangeOrder $co, bool $write = false): void
    {
        $this->authorizeForContract($co->contract, $write);
    }

    public function indexForContract(Contract $contract, Request $request): View
    {
        $this->authorizeForContract($contract);
        $changeOrders = $contract->changeOrders()
            ->with(['amendment', 'items'])
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('code', 'ilike', "%{$request->search}%")
                   ->orWhere('name', 'ilike', "%{$request->search}%");
            }))
            ->when($request->amendment_id === '0', fn ($q) => $q->whereNull('amendment_id'))
            ->when($request->amendment_id && $request->amendment_id !== '0', fn ($q) => $q->where('amendment_id', $request->amendment_id))
            ->orderByDesc('date')
            ->paginate(50)->withQueryString();
        $amendments  = $contract->amendments()->orderBy('code')->get(['id', 'code', 'name']);
        $canEdit     = $this->currentUser()->canWrite($contract->project);
        return view('change_orders.index', compact('contract', 'changeOrders', 'amendments', 'canEdit'));
    }

    public function create(Contract $contract, Request $request): View
    {
        $this->authorizeForContract($contract);
        abort_unless($this->currentUser()->canWrite($contract->project), 403);
        $nextCode = 'CO' . str_pad($contract->changeOrders()->count() + 1, 2, '0', STR_PAD_LEFT);
        $amendments = $contract->amendments()->orderBy('code')->pluck('name', 'id');
        $selectedAmendmentId = $request->amendment_id;
        return view('change_orders.form', compact('contract', 'nextCode', 'amendments', 'selectedAmendmentId'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeForContract($contract, write: true);
        $data = $request->validate([
            'code'         => ['required', 'string', 'max:20'],
            'name'         => ['required', 'string', 'max:255'],
            'date'         => ['required', 'date'],
            'amendment_id' => ['nullable', 'exists:amendments,id'],
            'note'         => ['nullable', 'string'],
        ]);
        if (!empty($data['amendment_id'])) {
            abort_unless(
                Amendment::where('id', $data['amendment_id'])->where('contract_id', $contract->id)->exists(),
                422
            );
        }
        $data['contract_id'] = $contract->id;
        $co = ChangeOrder::create($data);
        return redirect()->route('change-orders.show', $co)->with('success', __('Change order created.'));
    }

    public function show(ChangeOrder $changeOrder): View
    {
        $this->authorizeChangeOrder($changeOrder);
        $changeOrder->load(['contract', 'amendment', 'items.contractItem']);
        $canEdit = $this->currentUser()->canWrite($changeOrder->contract->project);
        return view('change_orders.show', compact('changeOrder', 'canEdit'));
    }

    public function editContent(ChangeOrder $changeOrder): View|RedirectResponse
    {
        $this->authorizeChangeOrder($changeOrder);
        if (!$this->currentUser()->canWrite($changeOrder->contract->project)) {
            return redirect()->route('change-orders.show', $changeOrder)
                ->with('error', __('You do not have permission to edit change order content.'));
        }
        $changeOrder->load(['contract.items', 'items.contractItem']);
        return view('change_orders.content', compact('changeOrder'));
    }

    public function edit(ChangeOrder $changeOrder): View
    {
        $this->authorizeChangeOrder($changeOrder);
        abort_unless($this->currentUser()->canWrite($changeOrder->contract->project), 403);
        $contract = $changeOrder->contract;
        $amendments = $contract->amendments()->orderBy('code')->pluck('name', 'id');
        return view('change_orders.form', compact('changeOrder', 'contract', 'amendments'));
    }

    public function update(Request $request, ChangeOrder $changeOrder): RedirectResponse
    {
        $this->authorizeChangeOrder($changeOrder, write: true);
        $data = $request->validate([
            'code'         => ['required', 'string', 'max:20'],
            'name'         => ['required', 'string', 'max:255'],
            'date'         => ['required', 'date'],
            'amendment_id' => ['nullable', 'exists:amendments,id'],
            'note'         => ['nullable', 'string'],
        ]);
        if (!empty($data['amendment_id'])) {
            abort_unless(
                Amendment::where('id', $data['amendment_id'])->where('contract_id', $changeOrder->contract_id)->exists(),
                422
            );
        }
        $changeOrder->update($data);
        return redirect()->route('change-orders.show', $changeOrder)->with('success', __('Change order saved.'));
    }

    public function destroy(ChangeOrder $changeOrder): RedirectResponse
    {
        $this->authorizeChangeOrder($changeOrder, write: true);
        $contract = $changeOrder->contract;
        $changeOrder->delete();
        return redirect()->route('contracts.show', $contract)->with('success', __('Change order deleted.'));
    }
}
