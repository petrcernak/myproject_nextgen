<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
    }

    public function index(): View
    {
        $this->authorizeSuperAdmin();
        $groups = Group::withCount(['users', 'projects'])->orderBy('name')->get();
        return view('groups.index', compact('groups'));
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();
        return view('groups.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:groups,code'],
        ]);
        $group = Group::create($data);
        return redirect()->route('groups.show', $group)->with('success', __('Group created.'));
    }

    public function show(Group $group): View
    {
        $this->authorizeSuperAdmin();
        $group->load(['users', 'projects']);
        return view('groups.show', compact('group'));
    }

    public function edit(Group $group): View
    {
        $this->authorizeSuperAdmin();
        return view('groups.form', compact('group'));
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:groups,code,' . $group->id],
        ]);
        $group->update($data);
        return redirect()->route('groups.show', $group)->with('success', __('Group saved.'));
    }

    public function destroy(Group $group): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        if ($group->users()->exists() || $group->projects()->exists()) {
            return back()->with('error', __('Group cannot be deleted — it has users or projects.'));
        }
        $group->delete();
        return redirect()->route('groups.index')->with('success', __('Group deleted.'));
    }
}
