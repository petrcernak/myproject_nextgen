<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user     = $this->currentUser();
        $groupId  = $this->currentGroupId();

        $projects = \App\Models\Project::where('id_group', $groupId)
            ->when($user->level < 5, fn ($q) => $q->whereHas('userRights', fn ($q2) => $q2->where('user_id', $user->id)))
            ->orderBy('locality_id')->orderBy('name')->get();

        $previewProjectId = $request->input('project_id', $user->default_project_id);
        $budgets   = collect();
        $contracts = collect();

        if ($previewProjectId) {
            $proj = \App\Models\Project::where('id', $previewProjectId)->where('id_group', $groupId)->first();
            if ($proj) {
                $budgets   = $proj->budgets()->orderBy('name')->get();
                $contracts = $proj->contracts()->orderBy('name')->get();
            }
        }

        return view('settings.index', compact('user', 'projects', 'budgets', 'contracts', 'previewProjectId'));
    }

    public function password(): View
    {
        return view('settings.password');
    }

    public function saveDefaults(Request $request): RedirectResponse
    {
        if ($request->boolean('clear')) {
            $this->currentUser()->update([
                'default_project_id' => null,
                'default_page_type'  => null,
                'default_page_id'    => null,
            ]);
            return redirect()->route('settings')->with('success', __('Default cleared.'));
        }

        $data = $request->validate([
            'default_project_id' => ['nullable', 'exists:projects,id'],
            'page_type'          => ['nullable', 'in:project_show,budget,contract'],
            'budget_id'          => ['nullable', 'exists:budgets,id'],
            'contract_id'        => ['nullable', 'exists:contracts,id'],
        ]);

        $projectId = $data['default_project_id'] ?: null;
        $pageType  = 'projects_index';
        $pageId    = null;

        if ($projectId) {
            $pageType = $request->input('page_type', 'project_show');
            if ($pageType === 'budget') {
                $pageId = (int) $request->input('budget_id') ?: null;
            } elseif ($pageType === 'contract') {
                $pageId = (int) $request->input('contract_id') ?: null;
            } else {
                $pageType = 'project_show';
            }
        }

        $this->currentUser()->update([
            'default_project_id' => $projectId,
            'default_page_type'  => $pageType,
            'default_page_id'    => $pageId,
        ]);

        return redirect()->route('settings')->with('success', __('Settings saved.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->currentUser();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => __('Current password is incorrect.')]);
        }

        $user->update(['password' => $request->password]);

        return back()->with('success', __('Password changed.'));
    }

    public function toggleDarkMode(): RedirectResponse
    {
        $user = $this->currentUser();
        $user->update(['dark_mode' => !$user->dark_mode]);
        return back();
    }
}
