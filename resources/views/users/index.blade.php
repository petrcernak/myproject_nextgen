@extends('layouts.app')
@section('title', __('Users'))

@section('content')
<div class="breadcrumb">
    <span>{{ __('Users') }}</span>
</div>

<div class="page-header">
    <h1>{{ __('Users') }}</h1>
    <a href="{{ route('users.create') }}" class="btn btn-primary">{{ __('+ New user') }}</a>
</div>

<div class="card">
    @if($users->isEmpty())
        <div class="empty"><strong>{{ __('No users') }}</strong></div>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Username') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Level') }}</th>
                <th style="text-align:center">{{ __('Active') }}</th>
                <th style="width:160px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->full_name }}</td>
                <td><code style="font-size:12px">{{ $user->username }}</code></td>
                <td>{{ $user->email }}</td>
                <td>
                    @if($user->level >= 9)
                        <span style="color:#7c3aed;font-size:12px;font-weight:600">{{ __('Super admin') }}</span>
                    @elseif($user->level >= 7)
                        <span style="color:#2563eb;font-size:12px;font-weight:600">{{ __('Group admin') }}</span>
                    @elseif($user->level >= 5)
                        <span style="color:#059669;font-size:12px;font-weight:600">{{ __('Project creator') }}</span>
                    @else
                        <span style="color:#6b7280;font-size:12px">{{ __('Member') }}</span>
                    @endif
                </td>
                <td style="text-align:center">{{ $user->active ? '✓' : '—' }}</td>
                <td>
                    <div style="display:flex;gap:.3rem;justify-content:flex-end">
                        <a href="{{ route('users.rights', $user) }}" class="btn btn-secondary btn-sm">{{ __('Rights') }}</a>
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">✕</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
