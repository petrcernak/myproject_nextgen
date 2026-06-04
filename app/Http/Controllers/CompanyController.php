<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->id_group == $this->currentGroupId(), 403);
    }

    public function index(Request $request): View
    {
        $totalCount = Company::where('id_group', $this->currentGroupId())->count();

        $companies = Company::where('id_group', $this->currentGroupId())
            ->when($request->search, fn ($q) => $q->where('name', 'ilike', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(20);

        $canEdit = $this->currentUser()->canCreateProject();

        return view('companies.index', compact('companies', 'canEdit', 'totalCount'));
    }

    public function show(Company $company): View
    {
        $this->authorizeCompany($company);

        $user             = $this->currentUser();
        $currentProjectId = session('current_project_id');
        $groupId          = $this->currentGroupId();

        // Contracts for the currently active project
        $currentContracts = $company->contracts()
            ->where('project_id', $currentProjectId)
            ->withCount('invoices')
            ->withSum('items', 'amount')
            ->orderBy('name')
            ->get();

        // Other projects in the group the user can at least read
        $otherAccessibleIds = \App\Models\Project::where('id_group', $groupId)
            ->where('id', '!=', $currentProjectId)
            ->get()
            ->filter(fn ($p) => $user->canRead($p))
            ->pluck('id');

        // Contracts from those other projects, grouped by project
        $otherContractsByProject = $company->contracts()
            ->whereIn('project_id', $otherAccessibleIds)
            ->with('project')
            ->withCount('invoices')
            ->withSum('items', 'amount')
            ->orderBy('name')
            ->get()
            ->groupBy('project_id');

        $canEdit = $user->canCreateProject();

        return view('companies.show', compact(
            'company', 'canEdit',
            'currentContracts', 'otherContractsByProject', 'currentProjectId'
        ));
    }

    public function create(): View
    {
        return view('companies.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'code'     => ['nullable', 'string', 'max:50'],
            'regno'    => ['nullable', 'string', 'max:50'],
            'taxregno' => ['nullable', 'string', 'max:50'],
            'email'    => ['nullable', 'email'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'url'      => ['nullable', 'url'],
        ]);

        $data['id_group'] = $this->currentGroupId();
        Company::create($data);

        return redirect()->route('companies.index')->with('success', __('Company created.'));
    }

    public function edit(Company $company): View
    {
        $this->authorizeCompany($company);
        return view('companies.form', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorizeCompany($company);
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'code'     => ['nullable', 'string', 'max:50'],
            'regno'    => ['nullable', 'string', 'max:50'],
            'taxregno' => ['nullable', 'string', 'max:50'],
            'email'    => ['nullable', 'email'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'url'      => ['nullable', 'url'],
        ]);

        $company->update($data);

        return redirect()->route('companies.show', $company)->with('success', __('Company saved.'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorizeCompany($company);
        $company->delete();
        return redirect()->route('companies.index')->with('success', __('Company deleted.'));
    }

}
