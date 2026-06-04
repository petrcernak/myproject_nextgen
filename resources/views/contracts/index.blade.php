@extends('layouts.app')
@section('title', __('Contracts'))

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="page-header">
    <h1>{{ __('Contracts') }} — {{ $currentProject->name }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $contracts->total() }} / {{ $totalCount }}</span>
    </h1>
    @if($currentProject && $canEdit)
        <a href="{{ route('projects.contracts.create', $currentProject) }}" class="btn btn-primary">+ {{ __('New contract') }}</a>
    @endif
</div>

@if($contracts->isEmpty() && !request()->hasAny(['search','direction','currency','company_id','file_filter']))
    <div class="card"><div class="empty"><strong>{{ __('No contracts') }}</strong><p>{{ __('Create the first contract using the button above.') }}</p></div></div>
@else

<form method="GET" id="lf">
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:90px">{{ __('Code') }}</th>
            <th style="text-align:left;min-width:220px{{ request('search') ? ';background:#dbeafe' : '' }}">
                {{ __('Name') }}
                <input class="{{ $fa('search') }}" type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search code / name…') }}">
            </th>
            <th style="text-align:left;min-width:160px{{ request('company_id') ? ';background:#dbeafe' : '' }}">
                {{ __('Company') }}
                @if($companies->count() > 1)
                <select class="{{ $fa('company_id') }}" name="company_id" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected(request('company_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @endif
            </th>
            <th style="min-width:80px{{ request('currency') ? ';background:#dbeafe' : '' }}">
                {{ __('Currency') }}
                <select class="{{ $fa('currency') }}" name="currency" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($currencies as $c)
                        <option value="{{ $c }}" @selected(request('currency') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </th>
            <th style="text-align:left;min-width:110px{{ request('direction') ? ';background:#dbeafe' : '' }}">
                {{ __('Direction') }}
                <select class="{{ $fa('direction') }}" name="direction" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="1"  @selected(request('direction')==='1')>{{ __('Income') }}</option>
                    <option value="-1" @selected(request('direction')==='-1')>{{ __('Expense') }}</option>
                </select>
            </th>
            <th style="min-width:100px">{{ __('Date') }}</th>
            <th style="min-width:70px">{{ __('Invoices') }}</th>
            <th style="min-width:60px{{ request('file_filter') !== null && request('file_filter') !== '' ? ';background:#dbeafe' : '' }}">
                {{ __('Files') }}
                <select class="{{ $fa('file_filter') }}" name="file_filter" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="0" @selected(request('file_filter')==='0')>{{ __('No files') }}</option>
                    <option value="1" @selected(request('file_filter')==='1')>{{ __('Has files') }}</option>
                </select>
            </th>
            <th style="min-width:80px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search','direction','currency','company_id','file_filter']))
                    <a href="{{ route('contracts.index') }}" class="btn btn-secondary btn-sm" title="{{ __('Clear') }}">×</a>
                @endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($contracts as $contract)
        <tr>
            <td><code style="font-size:12px;color:#6b7280">{{ $contract->code }}</code></td>
            <td style="text-align:left"><a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a></td>
            <td style="text-align:left">{{ $contract->company?->name ?? '—' }}</td>
            <td style="font-size:12px;color:#6b7280;text-align:right">{{ $contract->currency }}</td>
            <td style="text-align:left">{{ $contract->direction === 1 ? __('Income') : __('Expense') }}</td>
            <td style="color:#6b7280;text-align:right">{{ $contract->date?->format('d.m.Y') ?? '—' }}</td>
            <td style="text-align:right">{{ $contract->invoices()->count() }}</td>
            <td style="text-align:center">
                @if($contract->files_count)
                    <a href="{{ route('contracts.show', $contract) }}#files" style="display:inline-flex;align-items:center;gap:.25rem;font-size:12px;color:#6b7280;text-decoration:none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ $contract->files_count }}
                    </a>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No contracts match the filters.') }}</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</form>

<div>{{ $contracts->withQueryString()->links() }}</div>
@endif
@endsection
