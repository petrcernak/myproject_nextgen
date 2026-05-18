@extends('layouts.app')
@section('title', __('Projects'))

@section('content')
<div class="page-header">
    <h1>{{ __('Projects') }}</h1>
    @if($user->canCreateProject())<a href="{{ route('projects.create') }}" class="btn btn-primary">+ {{ __('New project') }}</a>@endif
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;margin-bottom:1rem">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search...') }}" style="max-width:280px">
            <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
            @if(request('search')) <a href="{{ route('projects.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a> @endif
        </form>
    </div>

    @if($projects->isEmpty())
        <div class="empty">
            <strong>{{ __('No projects') }}</strong>
            <p>{{ __('Create the first project using the button above.') }}</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Company') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Contracts') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($projects as $project)
                <tr>
                    <td><code>{{ $project->code }}</code></td>
                    <td><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></td>
                    <td>{{ $project->company?->name ?? '—' }}</td>
                    <td>
                        @if($project->status === 'active')
                            <span class="badge badge-green">{{ __('Active') }}</span>
                        @elseif($project->status === 'finished')
                            <span class="badge badge-gray">{{ __('Finished') }}</span>
                        @else
                            <span class="badge badge-red">{{ __('Cancelled') }}</span>
                        @endif
                    </td>
                    <td>{{ $project->contracts_count ?? $project->contracts()->count() }}</td>
                    <td style="text-align:right">
                        @if($user->isGroupAdmin())<a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>@endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:1rem">{{ $projects->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
