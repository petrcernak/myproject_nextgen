<?php

namespace App\Http\Controllers;

use App\Models\Amendment;
use App\Models\ChangeOrder;
use App\Models\Company;
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

    public function index(Request $request): View|RedirectResponse
    {
        $projectId = session('current_project_id');
        if (!$projectId) {
            return redirect()->route('projects.index')->with('error', __('Please select a project first.'));
        }
        $contracts  = Contract::where('project_id', $projectId)->orderBy('name')->get(['id', 'code', 'name', 'company_id', 'currency']);
        $companies  = Company::whereIn('id', $contracts->pluck('company_id')->filter()->unique())->orderBy('name')->pluck('name', 'id');
        $currencies = $contracts->pluck('currency')->unique()->sort()->values();

        $contractIds = $contracts->pluck('id');
        $totalCount  = ChangeOrder::whereIn('contract_id', $contractIds)->count();

        $changeOrders = ChangeOrder::with(['contract.company', 'amendment', 'items'])
            ->withCount('files')
            ->whereIn('contract_id', $contractIds)
            ->when($request->contract_id, fn ($q) => $q->where('contract_id', $request->contract_id))
            ->when($request->company_id,  fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('company_id', $request->company_id)))
            ->when($request->currency,    fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('currency', $request->currency)))
            ->when($request->date_from,   fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->date_to,     fn ($q) => $q->where('date', '<=', $request->date_to))
            ->orderByDesc('date')
            ->paginate(20)->withQueryString();

        return view('change_orders.global', compact('changeOrders', 'contracts', 'companies', 'currencies', 'totalCount'));
    }

    public function indexForContract(Contract $contract, Request $request): View
    {
        $this->authorizeForContract($contract);
        $changeOrders = $contract->changeOrders()
            ->with(['amendment', 'items'])
            ->withCount('files')
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('code', 'ilike', "%{$request->search}%")
                   ->orWhere('name', 'ilike', "%{$request->search}%");
            }))
            ->when($request->amendment_id === '0', fn ($q) => $q->whereNull('amendment_id'))
            ->when($request->amendment_id && $request->amendment_id !== '0', fn ($q) => $q->where('amendment_id', $request->amendment_id))
            ->when($request->file_filter === '0', fn ($q) => $q->doesntHave('files'))
            ->when($request->file_filter === '1', fn ($q) => $q->has('files'))
            ->orderByDesc('date')
            ->paginate(20)->withQueryString();
        $totalCount  = $contract->changeOrders()->count();
        $amendments  = $contract->amendments()->orderBy('code')->get(['id', 'code', 'name']);
        $canEdit     = $this->currentUser()->canWrite($contract->project);
        return view('change_orders.index', compact('contract', 'changeOrders', 'amendments', 'canEdit', 'totalCount'));
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
        $changeOrder->load(['contract', 'amendment', 'sourceChangeRequest', 'items.contractItem', 'files.tags']);
        $canEdit      = $this->currentUser()->canWrite($changeOrder->contract->project);
        $existingTags = \App\Models\FileTag::where('id_group', $this->currentGroupId())->orderBy('name')->pluck('name');
        return view('change_orders.show', compact('changeOrder', 'canEdit', 'existingTags'));
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
