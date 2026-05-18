<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    private function authorizeGroupAdmin(?User $target = null): void
    {
        $me = $this->currentUser();
        abort_unless($me->isGroupAdmin(), 403);
        if ($target && !$me->isSuperAdmin()) {
            abort_unless($target->id_group == $this->currentGroupId(), 403);
        }
    }

    public function index(): View
    {
        $this->authorizeGroupAdmin();
        $users = User::where('id_group', $this->currentGroupId())
            ->orderBy('surname')->orderBy('firstname')
            ->get();
        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorizeGroupAdmin();
        return view('users.form');
    }

    private function allowedLevels(): array
    {
        return $this->currentUser()->isSuperAdmin() ? [1, 5, 7, 9] : [1, 5, 7];
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeGroupAdmin();
        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'surname'   => ['required', 'string', 'max:100'],
            'username'  => ['required', 'string', 'max:100', 'unique:users,username'],
            'email'     => ['nullable', 'email', 'max:255'],
            'password'  => ['required', 'string', 'min:8'],
            'level'     => ['required', 'integer', \Illuminate\Validation\Rule::in($this->allowedLevels())],
            'active'    => ['boolean'],
        ]);

        $data['id_group'] = $this->currentGroupId();
        $data['active'] = $request->boolean('active', true);
        User::create($data);

        return redirect()->route('users.index')->with('success', __('User created.'));
    }

    public function edit(User $user): View
    {
        $this->authorizeGroupAdmin($user);
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeGroupAdmin($user);
        $me = $this->currentUser();

        // Prevent a super admin from demoting themselves — they'd be immediately locked out
        if ($user->id === $me->id && $me->isSuperAdmin() && (int) $request->input('level') < 9) {
            return back()->withErrors(['level' => __('You cannot demote your own super admin account.')]);
        }

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'surname'   => ['required', 'string', 'max:100'],
            'username'  => ['required', 'string', 'max:100', 'unique:users,username,' . $user->id],
            'email'     => ['nullable', 'email', 'max:255'],
            'password'  => ['nullable', 'string', 'min:8'],
            'level'     => ['required', 'integer', \Illuminate\Validation\Rule::in($this->allowedLevels())],
            'active'    => ['boolean'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['active'] = $request->boolean('active');

        $user->update($data);

        return redirect()->route('users.index')->with('success', __('User saved.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeGroupAdmin($user);
        abort_if($user->id === $this->currentUser()->id, 403);
        $user->delete();
        return redirect()->route('users.index')->with('success', __('User deleted.'));
    }

    public function editRights(User $user): View
    {
        $this->authorizeGroupAdmin($user);
        $projects = Project::where('id_group', $this->currentGroupId())
            ->orderBy('name')->get();
        $rights = $user->rights()
            ->whereIn('project_id', $projects->pluck('id'))
            ->pluck('pright', 'project_id');
        return view('users.rights', compact('user', 'projects', 'rights'));
    }

    public function updateRights(Request $request, User $user): RedirectResponse
    {
        $this->authorizeGroupAdmin($user);
        $groupProjectIds = Project::where('id_group', $this->currentGroupId())->pluck('id');

        $user->rights()->whereIn('project_id', $groupProjectIds)->delete();

        foreach ($request->input('rights', []) as $projectId => $level) {
            if (in_array($level, ['r', 'w']) && $groupProjectIds->contains((int) $projectId)) {
                $user->rights()->create(['project_id' => $projectId, 'pright' => $level]);
            }
        }

        return back()->with('success', __('Rights saved.'));
    }
}
