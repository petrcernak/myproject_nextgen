<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->currentUser();
        $projects = Project::with('company')
            ->where('id_group', $this->currentGroupId())
            ->when($request->search, fn ($q) => $q->where('name', 'ilike', "%{$request->search}%")
                ->orWhere('code', 'ilike', "%{$request->search}%"))
            ->when($user->level < 5, fn ($q) => $q->whereHas('userRights', fn ($q2) => $q2->where('user_id', $user->id)))
            ->orderBy('name')
            ->paginate(25);

        return view('projects.index', compact('projects', 'user'));
    }

    public function show(Project $project): View
    {
        abort_unless($project->id_group == $this->currentGroupId(), 403);
        $user = $this->currentUser();
        abort_unless($user->canRead($project), 403);
        $project->load(['company', 'contracts.company', 'budgets']);
        $canEdit = $user->canWrite($project);
        return view('projects.show', compact('project', 'canEdit'));
    }

    public function create(): View
    {
        abort_unless($this->currentUser()->canCreateProject(), 403);
        $companies = Company::where('id_group', $this->currentGroupId())->orderBy('name')->pluck('name', 'id');
        return view('projects.form', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->currentUser()->canCreateProject(), 403);
        $data = $request->validate([
            'code'       => ['required', 'string', 'max:50'],
            'name'       => ['required', 'string', 'max:255'],
            'id_company' => ['required', 'exists:companies,id'],
            'status'     => ['required', 'in:active,finished,cancelled'],
            'note'       => ['nullable', 'string'],
        ]);

        $data['id_group'] = $this->currentGroupId();
        $project = Project::create($data);

        // auto-assign write rights to non-admin creator
        $user = $this->currentUser();
        if (!$user->isGroupAdmin()) {
            $user->rights()->create(['project_id' => $project->id, 'pright' => 'w']);
        }

        return redirect()->route('projects.show', $project)->with('success', __('Project created.'));
    }

    public function edit(Project $project): View
    {
        abort_unless($project->id_group == $this->currentGroupId() && $this->currentUser()->isGroupAdmin(), 403);
        $companies = Company::where('id_group', $this->currentGroupId())->orderBy('name')->pluck('name', 'id');
        return view('projects.form', compact('project', 'companies'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->id_group == $this->currentGroupId() && $this->currentUser()->isGroupAdmin(), 403);
        $data = $request->validate([
            'code'       => ['required', 'string', 'max:50'],
            'name'       => ['required', 'string', 'max:255'],
            'id_company' => ['required', 'exists:companies,id'],
            'status'     => ['required', 'in:active,finished,cancelled'],
            'note'       => ['nullable', 'string'],
        ]);

        $project->update($data);

        return redirect()->route('projects.show', $project)->with('success', __('Project saved.'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        abort_unless($project->id_group == $this->currentGroupId() && $this->currentUser()->isGroupAdmin(), 403);
        if (!$project->isDeletable()) {
            return back()->with('error', __('Project cannot be deleted — it has contracts.'));
        }

        $project->delete();

        return redirect()->route('projects.index')->with('success', __('Project deleted.'));
    }
}
