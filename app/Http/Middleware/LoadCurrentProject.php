<?php

namespace App\Http\Middleware;

use App\Models\Group;
use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class LoadCurrentProject
{
    public function handle(Request $request, Closure $next): Response
    {
        $currentProject = null;
        $currentGroup = null;

        if (auth()->check()) {
            $user = auth()->user();

            // Superadmin může mít v session přepnutou skupinu, ostatní mají fixní skupinu
            if ($user->isSuperAdmin() && session('current_group_id')) {
                $currentGroup = Group::select('id', 'name')->find(session('current_group_id'));
            }
            if (!$currentGroup) {
                $currentGroup = Group::select('id', 'name')->find($user->id_group);
                if ($currentGroup && $user->isSuperAdmin()) {
                    session(['current_group_id' => $currentGroup->id]);
                }
            }

            $projectId = session('current_project_id');

            if ($projectId) {
                $currentProject = Project::select('id', 'name', 'code')
                    ->when($currentGroup, fn ($q) => $q->where('id_group', $currentGroup->id))
                    ->find($projectId);
            }

            // Pokud session projekt neexistuje nebo nepatří do aktuální skupiny, nastav první dostupný
            if (!$currentProject) {
                $currentProject = Project::select('id', 'name', 'code')
                    ->where('active', true)
                    ->when($currentGroup, fn ($q) => $q->where('id_group', $currentGroup->id))
                    ->first();

                if ($currentProject) {
                    session(['current_project_id' => $currentProject->id]);
                }
            }
        }

        View::share('currentProject', $currentProject);
        View::share('currentGroup', $currentGroup);

        return $next($request);
    }
}
