@extends('layouts.app')
@section('title', __('Project rights').' — '.$user->full_name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('users.index') }}">{{ __('Users') }}</a>
    <a href="{{ route('users.edit', $user) }}">{{ $user->full_name }}</a>
    <span>{{ __('Project rights') }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $user->full_name }}</h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">{{ __('Project rights') }}</div>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">{{ __('← Back') }}</a>
</div>

@if($user->level >= 5)
<div class="card card-body" style="margin-bottom:1rem;background:#f0fdf4;border-color:#86efac;color:#166534;font-size:13px">
    @if($user->level >= 7)
        {{ __('Group admins have full access to all projects — individual project rights are not applicable.') }}
    @else
        {{ __('Project creators have full read/write access to all projects by default — individual project rights are not applicable.') }}
    @endif
</div>
@endif

<div class="card card-body" style="max-width:700px">
    <form method="POST" action="{{ route('users.rights.update', $user) }}">
        @csrf

        @if($projects->isEmpty())
            <p style="color:#6b7280">{{ __('No projects in this group.') }}</p>
        @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('Project') }}</th>
                    <th style="text-align:center;width:110px">{{ __('No access') }}</th>
                    <th style="text-align:center;width:110px">{{ __('Read only') }}</th>
                    <th style="text-align:center;width:110px">{{ __('Read and write') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($projects as $project)
                @php $current = $rights[$project->id] ?? null; @endphp
                <tr>
                    <td>
                        <div style="font-weight:500">{{ $project->name }}</div>
                        <code style="font-size:11px;color:#9ca3af">{{ $project->code }}</code>
                    </td>
                    <td style="text-align:center">
                        <input type="radio" name="rights[{{ $project->id }}]" value="none"
                               {{ $current === null ? 'checked' : '' }}>
                    </td>
                    <td style="text-align:center">
                        <input type="radio" name="rights[{{ $project->id }}]" value="r"
                               {{ $current === 'r' ? 'checked' : '' }}>
                    </td>
                    <td style="text-align:center">
                        <input type="radio" name="rights[{{ $project->id }}]" value="w"
                               {{ $current === 'w' ? 'checked' : '' }}>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="form-actions" style="margin-top:1rem">
            <button type="submit" class="btn btn-primary">{{ __('Save rights') }}</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
