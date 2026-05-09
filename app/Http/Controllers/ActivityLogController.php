<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->currentUser();
        abort_unless($user->isGroupAdmin(), 403);

        $query = ActivityLog::with('user')
            ->orderByDesc('created_at');

        // Super admin sees all groups; group admin sees only their group
        if (!$user->isSuperAdmin()) {
            $query->where('id_group', $user->id_group);
        }

        $query
            ->when($request->user_id,  fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->action,   fn ($q) => $q->where('action', $request->action))
            ->when($request->subject,  fn ($q) => $q->where('subject_type', $request->subject))
            ->when($request->from,     fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,       fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->search,   fn ($q) => $q->where('subject_label', 'ilike', "%{$request->search}%"));

        $logs = $query->paginate(100)->withQueryString();

        // Users for filter — group admin sees own group, super admin sees all
        $users = User::when(!$user->isSuperAdmin(), fn ($q) => $q->where('id_group', $user->id_group))
            ->orderBy('surname')->get(['id', 'firstname', 'surname', 'username']);

        return view('activity_logs.index', compact('logs', 'users'));
    }
}
