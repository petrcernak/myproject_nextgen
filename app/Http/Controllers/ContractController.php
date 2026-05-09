<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    private function authorizeContract(Contract $contract, bool $requireWrite = false): void
    {
        $project = $contract->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        $user = $this->currentUser();
        if ($requireWrite) {
            abort_unless($user->canWrite($project), 403);
        } else {
            abort_unless($user->canRead($project), 403);
        }
    }

    public function index(Request $request): View|RedirectResponse
    {
        $projectId = session('current_project_id');

        if (!$projectId) {
            return redirect()->route('projects.index')
                ->with('error', __('Please select a project first.'));
        }

        $currencies = Contract::where('project_id', $projectId)
            ->distinct()->orderBy('currency')->pluck('currency');

        $companies = \App\Models\Company::whereIn('id',
            Contract::where('project_id', $projectId)->whereNotNull('company_id')->pluck('company_id')
        )->orderBy('name')->pluck('name', 'id');

        $contracts = Contract::with(['company'])->withCount('files')
            ->where('project_id', $projectId)
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'ilike', "%{$request->search}%")
                   ->orWhere('code', 'ilike', "%{$request->search}%");
            }))
            ->when($request->direction,  fn ($q) => $q->where('direction', $request->direction))
            ->when($request->currency,   fn ($q) => $q->where('currency', $request->currency))
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->file_filter === '0', fn ($q) => $q->doesntHave('files'))
            ->when($request->file_filter === '1', fn ($q) => $q->has('files'))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('contracts.index', compact('contracts', 'currencies', 'companies'));
    }

    public function show(Contract $contract): View|RedirectResponse
    {
        $this->authorizeContract($contract);
        if ($contract->project_id != session('current_project_id')) {
            return redirect()->route('contracts.index')
                ->with('info', __('Project switched — showing contracts for the current project.'));
        }
        $contract->load([
            'project', 'company',
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
            'items.invoiceItems', 'items.changeOrderItems.changeOrder', 'items.amendmentItems', 'invoices',
            'amendments.items.contractItem', 'amendments.changeOrders.items.contractItem',
            'standaloneChangeOrders.items.contractItem',
            'changeRequests.items.latestRevision',
            'anticipateds.items',
            'retentionReleases',
        ]);
        $contract->loadCount('files');
        $canEdit = $this->currentUser()->canWrite($contract->project);
        return view('contracts.show', compact('contract', 'canEdit'));
    }

    public function showFiles(Contract $contract): View|RedirectResponse
    {
        $this->authorizeContract($contract);
        if ($contract->project_id != session('current_project_id')) {
            return redirect()->route('contracts.index')
                ->with('info', __('Project switched — showing contracts for the current project.'));
        }
        $contract->load(['project', 'files.tags', 'files.uploader']);
        $canEdit      = $this->currentUser()->canWrite($contract->project);
        $existingTags = \App\Models\FileTag::where('id_group', $this->currentGroupId())
            ->orderBy('name')->pluck('name');
        return view('contracts.files', compact('contract', 'canEdit', 'existingTags'));
    }

    public function showRetention(Contract $contract): View|RedirectResponse
    {
        $this->authorizeContract($contract);
        if ($contract->project_id != session('current_project_id')) {
            return redirect()->route('contracts.index')
                ->with('info', __('Project switched — showing contracts for the current project.'));
        }
        $contract->load([
            'project',
            'retentionReleases.files.tags',
            'retentionBankGuarantees.files.tags',
        ]);
        $canEdit      = $this->currentUser()->canWrite($contract->project);
        $existingTags = \App\Models\FileTag::where('id_group', $this->currentGroupId())
            ->orderBy('name')->pluck('name');
        return view('contracts.retention', compact('contract', 'canEdit', 'existingTags'));
    }

    public function editContent(Contract $contract): View|RedirectResponse
    {
        $this->authorizeContract($contract);
        if ($contract->project_id != session('current_project_id')) {
            return redirect()->route('contracts.index')
                ->with('info', __('Project switched — showing contracts for the current project.'));
        }
        if (!$this->currentUser()->canWrite($contract->project)) {
            return redirect()->route('contracts.show', $contract)
                ->with('error', __('You do not have permission to edit contract content.'));
        }
        $contract->load([
            'categories.children.children.items',
            'categories.children.items',
            'categories.items',
            'items',
        ]);
        return view('contracts.content', compact('contract'));
    }

    public function create(Project $project): View
    {
        abort_unless($project->id_group == $this->currentGroupId(), 403);
        $companies = Company::where('id_group', $this->currentGroupId())->orderBy('name')->pluck('name', 'id');
        return view('contracts.form', compact('project', 'companies'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:50'],
            'name'        => ['required', 'string', 'max:255'],
            'company_id'  => ['required', 'exists:companies,id'],
            'direction'   => ['required', 'in:1,-1'],
            'currency'    => ['required', 'string', 'max:10'],
            'date'        => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'maturity'         => ['nullable', 'integer', 'min:0'],
            'retention_short'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'retention_long'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note'             => ['nullable', 'string'],
        ]);

        $data['project_id'] = $project->id;
        $contract = Contract::create($data);

        return redirect()->route('contracts.show', $contract)->with('success', __('Contract created.'));
    }

    public function edit(Contract $contract): View
    {
        $this->authorizeContract($contract);
        $companies = Company::where('id_group', $this->currentGroupId())->orderBy('name')->pluck('name', 'id');
        $project = $contract->project;
        return view('contracts.form', compact('contract', 'project', 'companies'));
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($contract, requireWrite: true);
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:50'],
            'name'        => ['required', 'string', 'max:255'],
            'company_id'  => ['required', 'exists:companies,id'],
            'direction'   => ['required', 'in:1,-1'],
            'currency'    => ['required', 'string', 'max:10'],
            'date'        => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'maturity'         => ['nullable', 'integer', 'min:0'],
            'retention_short'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'retention_long'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note'             => ['nullable', 'string'],
        ]);

        $contract->update($data);

        return redirect()->route('contracts.show', $contract)->with('success', __('Contract saved.'));
    }

    private function contractsWithStats(int $projectId, \Illuminate\Http\Request $request): \Illuminate\Support\Collection
    {
        return Contract::with(['company', 'items.invoiceItems', 'items.changeOrderItems', 'items.amendmentItems'])
            ->where('project_id', $projectId)
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->currency,   fn ($q) => $q->where('currency', $request->currency))
            ->when($request->date_from,  fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->date_to,    fn ($q) => $q->where('date', '<=', $request->date_to))
            ->orderBy('code')
            ->get()
            ->map(function (Contract $contract) {
                $revisedTotal = $contract->items->sum('amount')
                    + $contract->items->flatMap->changeOrderItems->sum('amount')
                    + $contract->items->flatMap->amendmentItems->sum('amount');
                $invoiced = $contract->items->flatMap->invoiceItems->sum('amount');

                $contract->stat_revised_total = $revisedTotal;
                $contract->stat_invoiced      = $invoiced;
                $contract->stat_diff          = $invoiced - $revisedTotal;
                $contract->stat_pct           = $revisedTotal != 0 ? round($invoiced / $revisedTotal * 100) : 0;
                $contract->stat_overbilled_items = $contract->items->filter(function ($item) {
                    $eff  = $item->amount + $item->changeOrderItems->sum('amount') + $item->amendmentItems->sum('amount');
                    return $eff > 0 && $item->invoiceItems->sum('amount') > $eff;
                });
                return $contract;
            });
    }

    public function underbilled(\Illuminate\Http\Request $request): View|RedirectResponse
    {
        $projectId = session('current_project_id');
        if (!$projectId) {
            return redirect()->route('projects.index')->with('error', __('Please select a project first.'));
        }
        $companies  = Company::whereIn('id', Contract::where('project_id', $projectId)->whereNotNull('company_id')->pluck('company_id'))->orderBy('name')->pluck('name', 'id');
        $currencies = Contract::where('project_id', $projectId)->distinct()->orderBy('currency')->pluck('currency');
        $contracts  = $this->contractsWithStats($projectId, $request)
            ->filter(fn ($c) => $c->stat_revised_total > 0 && $c->stat_invoiced < $c->stat_revised_total)
            ->values();
        return view('contracts.underbilled', compact('contracts', 'companies', 'currencies'));
    }

    public function overbilled(\Illuminate\Http\Request $request): View|RedirectResponse
    {
        $projectId = session('current_project_id');
        if (!$projectId) {
            return redirect()->route('projects.index')->with('error', __('Please select a project first.'));
        }
        $companies  = Company::whereIn('id', Contract::where('project_id', $projectId)->whereNotNull('company_id')->pluck('company_id'))->orderBy('name')->pluck('name', 'id');
        $currencies = Contract::where('project_id', $projectId)->distinct()->orderBy('currency')->pluck('currency');
        $contracts  = $this->contractsWithStats($projectId, $request)
            ->filter(fn ($c) => $c->stat_invoiced > $c->stat_revised_total || $c->stat_overbilled_items->isNotEmpty())
            ->values();
        return view('contracts.overbilled', compact('contracts', 'companies', 'currencies'));
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->authorizeContract($contract, requireWrite: true);
        $project = $contract->project;

        if (!$contract->isDeletable()) {
            return back()->with('error', __('Contract cannot be deleted — it has invoices.'));
        }

        $contract->delete();

        return redirect()->route('projects.show', $project)->with('success', __('Contract deleted.'));
    }
}
