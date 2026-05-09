<?php

namespace App\Http\Controllers;

use App\Models\Amendment;
use App\Models\Company;
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

    public function index(Request $request): View|RedirectResponse
    {
        $projectId = session('current_project_id');
        if (!$projectId) {
            return redirect()->route('projects.index')->with('error', __('Please select a project first.'));
        }
        $contracts  = Contract::where('project_id', $projectId)->orderBy('name')->get(['id', 'code', 'name', 'company_id', 'currency']);
        $companies  = Company::whereIn('id', $contracts->pluck('company_id')->filter()->unique())->orderBy('name')->pluck('name', 'id');
        $currencies = $contracts->pluck('currency')->unique()->sort()->values();

        $amendments = Amendment::with(['contract.company', 'items', 'changeOrders.items'])
            ->whereIn('contract_id', $contracts->pluck('id'))
            ->when($request->contract_id, fn ($q) => $q->where('contract_id', $request->contract_id))
            ->when($request->company_id,  fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('company_id', $request->company_id)))
            ->when($request->currency,    fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('currency', $request->currency)))
            ->when($request->date_from,   fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->date_to,     fn ($q) => $q->where('date', '<=', $request->date_to))
            ->orderByDesc('date')
            ->paginate(50)->withQueryString();

        return view('amendments.global', compact('amendments', 'contracts', 'companies', 'currencies'));
    }

    public function indexForContract(Contract $contract, Request $request): View
    {
        $this->authorizeForContract($contract);
        $amendments = $contract->amendments()
            ->with(['items', 'changeOrders.items'])
            ->withCount('files')
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('code', 'ilike', "%{$request->search}%")
                   ->orWhere('name', 'ilike', "%{$request->search}%");
            }))
            ->when($request->file_filter === '0', fn ($q) => $q->doesntHave('files'))
            ->when($request->file_filter === '1', fn ($q) => $q->has('files'))
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
        $amendment->load(['contract', 'items.contractItem', 'changeOrders.items.contractItem', 'files.tags']);
        $canEdit      = $this->currentUser()->canWrite($amendment->contract->project);
        $existingTags = \App\Models\FileTag::where('id_group', $this->currentGroupId())->orderBy('name')->pluck('name');
        return view('amendments.show', compact('amendment', 'canEdit', 'existingTags'));
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
