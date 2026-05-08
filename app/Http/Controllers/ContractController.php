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

        $contracts = Contract::with(['company'])
            ->where('project_id', $projectId)
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'ilike', "%{$request->search}%")
                   ->orWhere('code', 'ilike', "%{$request->search}%");
            }))
            ->when($request->direction, fn ($q) => $q->where('direction', $request->direction))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('contracts.index', compact('contracts'));
    }

    public function show(Contract $contract): View
    {
        $this->authorizeContract($contract);
        $contract->load(['project', 'company', 'items', 'invoices']);
        $canEdit = $this->currentUser()->canWrite($contract->project);
        return view('contracts.show', compact('contract', 'canEdit'));
    }

    public function editContent(Contract $contract): View|RedirectResponse
    {
        $this->authorizeContract($contract);
        if (!$this->currentUser()->canWrite($contract->project)) {
            return redirect()->route('contracts.show', $contract)
                ->with('error', __('You do not have permission to edit contract content.'));
        }
        $contract->load(['items']);
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
            'maturity'    => ['nullable', 'integer', 'min:0'],
            'note'        => ['nullable', 'string'],
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
            'maturity'    => ['nullable', 'integer', 'min:0'],
            'note'        => ['nullable', 'string'],
        ]);

        $contract->update($data);

        return redirect()->route('contracts.show', $contract)->with('success', __('Contract saved.'));
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
