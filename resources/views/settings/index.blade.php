@extends('layouts.app')
@section('title', __('Settings'))

@section('content')
<div class="breadcrumb">
    <span>{{ __('Settings') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Settings') }}</h1>
</div>

@if(session('success'))
    <div class="alert alert-success" style="max-width:520px">{{ session('success') }}</div>
@endif

{{-- Dark mode --}}
<div class="card card-body" style="max-width:520px;margin-bottom:1.5rem">
    <h2 style="font-size:.95rem;font-weight:600;margin-bottom:1rem">{{ __('Appearance') }}</h2>
    <div style="display:flex;align-items:center;justify-content:space-between">
        <div>
            <div style="font-weight:500;font-size:13px">{{ __('Dark mode') }}</div>
            <div style="font-size:12px;color:#6b7280;margin-top:.2rem">{{ __('Switch between light and dark interface.') }}</div>
        </div>
        <form method="POST" action="{{ route('settings.dark-mode') }}">
            @csrf
            <button type="submit" class="dark-toggle" title="{{ __('Toggle dark mode') }}">
                @if(auth()->user()->dark_mode)
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    {{ __('Light mode') }}
                @else
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    {{ __('Dark mode') }}
                @endif
            </button>
        </form>
    </div>
</div>

{{-- Password --}}
<div class="card card-body" style="max-width:520px;margin-bottom:1.5rem">
    <h2 style="font-size:.95rem;font-weight:600;margin-bottom:.5rem">{{ __('Password') }}</h2>
    <div style="font-size:12px;color:#6b7280;margin-bottom:1rem">{{ __('Change your login password.') }}</div>
    <a href="{{ route('settings.password.form') }}" class="btn btn-secondary">{{ __('Change password') }}</a>
</div>

{{-- Default start page --}}
<div class="card card-body" style="max-width:520px">
    <h2 style="font-size:.95rem;font-weight:600;margin-bottom:.25rem">{{ __('Default start page') }}</h2>
    <p style="font-size:12px;color:#6b7280;margin-bottom:1.25rem">{{ __('Where to go after logging in. Leave project empty to show the projects list.') }}</p>

    @if($projects->isEmpty())
        <p style="font-size:13px;color:#6b7280">{{ __('No projects available.') }}</p>
    @else

    {{-- Project picker — GET reload to load budgets & contracts --}}
    <div class="form-group">
        <label>{{ __('Project') }}</label>
        @php $settingsUrl = route('settings'); @endphp
        <select onchange="window.location.href='{{ $settingsUrl }}?project_id='+this.value">
            <option value="">{{ __('— projects list —') }}</option>
            @foreach($projects as $p)
                <option value="{{ $p->id }}" @selected($previewProjectId == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Save form --}}
    <form method="POST" action="{{ route('settings.defaults') }}">
        @csrf
        <input type="hidden" name="default_project_id" value="{{ $previewProjectId }}">

        @if($previewProjectId)
        @php
            $isSameProject = $user->default_project_id == $previewProjectId;
            $currentType   = $isSameProject ? $user->default_page_type : 'project_show';
        @endphp
        <div class="form-group">
            <label>{{ __('Show') }}</label>
            <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:.25rem">

                {{-- Option 1: Project dashboard --}}
                <label style="display:flex;align-items:center;gap:.6rem;font-weight:400;font-size:13px">
                    <input type="radio" name="page_type" value="project_show"
                           @checked($currentType === 'project_show')
                           onchange="document.getElementById('budgetRow').style.display='none';document.getElementById('contractRow').style.display='none'">
                    {{ __('Project dashboard') }}
                </label>

                {{-- Option 2: Budget --}}
                @if($budgets->isNotEmpty())
                <label style="display:flex;align-items:center;gap:.6rem;font-weight:400;font-size:13px">
                    <input type="radio" name="page_type" value="budget"
                           @checked($currentType === 'budget')
                           onchange="document.getElementById('budgetRow').style.display='flex';document.getElementById('contractRow').style.display='none'">
                    {{ __('Budget') }}
                </label>
                <div id="budgetRow" style="display:{{ $currentType === 'budget' ? 'flex' : 'none' }};padding-left:1.6rem">
                    <select name="budget_id" style="max-width:100%">
                        @foreach($budgets as $b)
                        <option value="{{ $b->id }}" @selected($isSameProject && $user->default_page_type === 'budget' && $user->default_page_id == $b->id)>
                            {{ $b->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Option 3: Contract --}}
                @if($contracts->isNotEmpty())
                <label style="display:flex;align-items:center;gap:.6rem;font-weight:400;font-size:13px">
                    <input type="radio" name="page_type" value="contract"
                           @checked($currentType === 'contract')
                           onchange="document.getElementById('contractRow').style.display='flex';document.getElementById('budgetRow').style.display='none'">
                    {{ __('Contract') }}
                </label>
                <div id="contractRow" style="display:{{ $currentType === 'contract' ? 'flex' : 'none' }};padding-left:1.6rem">
                    <select name="contract_id" style="max-width:100%">
                        @foreach($contracts as $c)
                        <option value="{{ $c->id }}" @selected($isSameProject && $user->default_page_type === 'contract' && $user->default_page_id == $c->id)>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

            </div>
        </div>
        @endif

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @if($user->default_page_type)
                <button type="submit" name="clear" value="1" class="btn btn-secondary">{{ __('Clear default') }}</button>
            @endif
        </div>
    </form>

    @if($user->default_page_type)
    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280">
        {{ __('Current default:') }}
        <strong style="color:#374151">
            @switch($user->default_page_type)
                @case('projects_index') {{ __('Projects list') }} @break
                @case('project_show')
                    {{ __('Project dashboard') }}
                    @if($user->default_project_id)
                        — {{ $projects->find($user->default_project_id)?->name ?? '#'.$user->default_project_id }}
                    @endif
                    @break
                @case('budget')   {{ __('Budget') }} #{{ $user->default_page_id }} @break
                @case('contract') {{ __('Contract') }} #{{ $user->default_page_id }} @break
            @endswitch
        </strong>
    </div>
    @endif

    @endif
</div>
@endsection
