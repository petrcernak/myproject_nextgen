@extends('layouts.app')
@section('title', $group->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('groups.index') }}">{{ __('Groups') }}</a>
    <span>{{ $group->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $group->name }} <code style="font-size:.8em;color:#6b7280">{{ $group->code }}</code></h1>
    </div>
    <div style="display:flex;gap:.5rem">
        <a href="{{ route('groups.edit', $group) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
        <form method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('{{ __('Really delete group?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger">{{ __('Delete') }}</button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">

    <div class="card">
        <div style="padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:13px">
            {{ __('Users') }} ({{ $group->users->count() }})
        </div>
        @if($group->users->isEmpty())
            <div class="empty" style="padding:1.5rem"><strong>{{ __('No users') }}</strong></div>
        @else
            <table>
                <thead>
                    <tr><th>{{ __('Name') }}</th><th>{{ __('Login name') }}</th><th style="text-align:right">{{ __('Level') }}</th></tr>
                </thead>
                <tbody>
                    @foreach($group->users->sortBy('surname') as $user)
                    <tr>
                        <td>{{ $user->full_name }}</td>
                        <td style="color:#6b7280;font-size:13px">{{ $user->username }}</td>
                        <td style="text-align:right">
                            <span class="badge badge-gray">{{ $user->level }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <div style="padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:13px">
            {{ __('Projects') }} ({{ $group->projects->count() }})
        </div>
        @if($group->projects->isEmpty())
            <div class="empty" style="padding:1.5rem"><strong>{{ __('No projects') }}</strong></div>
        @else
            <table>
                <thead>
                    <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Status') }}</th></tr>
                </thead>
                <tbody>
                    @foreach($group->projects->sortBy('name') as $project)
                    <tr>
                        <td><code style="color:#6b7280;font-size:12px">{{ $project->code }}</code></td>
                        <td><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></td>
                        <td>
                            @if($project->active)
                                <span class="badge badge-green">{{ __('active') }}</span>
                            @else
                                <span class="badge badge-gray">{{ __('inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
@endsection
