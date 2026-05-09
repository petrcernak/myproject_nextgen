@extends('layouts.app')
@section('title', __('Groups'))

@section('content')
<div class="breadcrumb">
    <span>{{ __('Groups') }}</span>
</div>

<div class="page-header">
    <h1>{{ __('Groups') }}</h1>
    <a href="{{ route('groups.create') }}" class="btn btn-primary">+ {{ __('New group') }}</a>
</div>

<div class="card">
    @if($groups->isEmpty())
        <div class="empty"><strong>{{ __('No groups') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Default currency') }}</th>
                    <th style="text-align:right">{{ __('Users') }}</th>
                    <th style="text-align:right">{{ __('Projects') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $group)
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $group->code }}</code></td>
                    <td><a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a></td>
                    <td><code style="font-size:12px;color:#374151">{{ $group->currency }}</code></td>
                    <td style="text-align:right">{{ $group->users_count }}</td>
                    <td style="text-align:right">{{ $group->projects_count }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('groups.edit', $group) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
