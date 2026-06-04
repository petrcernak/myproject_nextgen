<?php

namespace App\Http\Controllers;

use App\Models\ChangeOrder;
use App\Models\ChangeRequest;
use App\Models\Company;
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
        $totalCount  = ChangeRequest::whereIn('contract_id', $contractIds)->count();

        $changeRequests = ChangeRequest::with(['contract.company', 'items.latestRevision', 'convertedChangeOrder'])
            ->whereIn('contract_id', $contractIds)
            ->when($request->contract_id, fn ($q) => $q->where('contract_id', $request->contract_id))
            ->when($request->company_id,  fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('company_id', $request->company_id)))
            ->when($request->currency,    fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('currency', $request->currency)))
            ->when($request->date_from,   fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->date_to,     fn ($q) => $q->where('date', '<=', $request->date_to))
            ->when($request->status,      fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('date')
            ->paginate(20)->withQueryString();

        return view('change_requests.global', compact('changeRequests', 'contracts', 'companies', 'currencies', 'totalCount'));
    }

    public function indexForContract(Contract $contract, Request $request): View
    {
        $this->authorizeForContract($contract);
        $changeRequests = $contract->changeRequests()
            ->with(['items.latestRevision', 'convertedChangeOrder'])
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('code', 'ilike', "%{$request->search}%")
                   ->orWhere('name', 'ilike', "%{$request->search}%");
            }))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('date')
            ->paginate(20)->withQueryString();
        $totalCount = $contract->changeRequests()->count();
        $canEdit = $this->currentUser()->canWrite($contract->project);
        return view('change_requests.index', compact('contract', 'changeRequests', 'canEdit', 'totalCount'));
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
            'code'   => ['required', 'string', 'max:50'],
            'name'   => ['required', 'string', 'max:255'],
            'date'   => ['required', 'date'],
            'status' => ['nullable', 'in:open,closed,rejected,on_hold,converted'],
            'note'   => ['nullable', 'string'],
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
            'code'   => ['required', 'string', 'max:50'],
            'name'   => ['required', 'string', 'max:255'],
            'date'   => ['required', 'date'],
            'status' => ['nullable', 'in:open,closed,rejected,on_hold,converted'],
            'note'   => ['nullable', 'string'],
        ]);
        $changeRequest->update($data);
        return redirect()->route('change-requests.show', $changeRequest)->with('success', __('Change request saved.'));
    }

    public function convert(ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeCr($changeRequest, write: true);
        abort_if($changeRequest->status === 'converted', 422, __('Already converted.'));

        $contract = $changeRequest->contract;
        $nextCode = 'CO' . str_pad($contract->changeOrders()->count() + 1, 2, '0', STR_PAD_LEFT);

        $co = ChangeOrder::create([
            'contract_id'       => $contract->id,
            'change_request_id' => $changeRequest->id,
            'code'              => $nextCode,
            'name'              => $changeRequest->name,
            'date'              => $changeRequest->date,
            'note'              => $changeRequest->note,
        ]);

        foreach ($changeRequest->items()->with('latestRevision')->get() as $crItem) {
            $amount = $crItem->latestRevision?->amount_report ?? 0;
            $co->items()->create([
                'contract_item_id' => $crItem->contract_item_id,
                'amount'           => $amount,
                'description'      => $crItem->description,
                'sort'             => $crItem->sort,
            ]);
        }

        $changeRequest->update(['status' => 'converted']);

        return redirect()->route('change-orders.show', $co)
            ->with('success', __('Change request converted to change order.'));
    }

    public function destroy(ChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorizeCr($changeRequest, write: true);
        $contract = $changeRequest->contract;
        $changeRequest->delete();
        return redirect()->route('contracts.show', $contract)->with('success', __('Change request deleted.'));
    }
}
