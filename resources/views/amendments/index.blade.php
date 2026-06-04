@extends('layouts.app')
@section('title', __('Amendments') . ' — ' . $contract->name)

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <span>{{ __('Amendments') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Amendments') }}: {{ $contract->name }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $amendments->total() }} / {{ $totalCount }}</span>
    </h1>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contracts.amendments.create', $contract) }}" class="btn btn-primary">+ {{ __('New amendment') }}</a>
        @endif
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back') }}</a>
    </div>
</div>

@if($amendments->isEmpty() && !request()->hasAny(['search','file_filter']))
    <div class="card"><div class="empty"><strong>{{ __('No amendments') }}</strong></div></div>
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
            <th style="min-width:100px">{{ __('Date') }}</th>
            <th style="min-width:60px">{{ __('COs') }}</th>
            <th style="min-width:140px">{{ __('Value') }}</th>
            <th style="min-width:60px{{ request('file_filter') !== null && request('file_filter') !== '' ? ';background:#dbeafe' : '' }}">
                {{ __('Files') }}
                <select class="{{ $fa('file_filter') }}" name="file_filter" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="0" @selected(request('file_filter')==='0')>{{ __('No files') }}</option>
                    <option value="1" @selected(request('file_filter')==='1')>{{ __('Has files') }}</option>
                </select>
            </th>
            <th style="min-width:60px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search','file_filter']))<a href="{{ route('contracts.amendments.index', $contract) }}" class="btn btn-secondary btn-sm">×</a>@endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($amendments as $amendment)
        <tr>
            <td><code style="font-size:12px;color:#6b7280">{{ $amendment->code }}</code></td>
            <td style="text-align:left">
                <a href="{{ route('amendments.show', $amendment) }}">{{ $amendment->name }}</a>
                @if($amendment->note)<div style="font-size:12px;color:#6b7280">{{ $amendment->note }}</div>@endif
            </td>
            <td style="text-align:right;color:#6b7280">{{ $amendment->date?->format('d.m.Y') }}</td>
            <td style="text-align:right;color:#6b7280">{{ $amendment->changeOrders->count() }}</td>
            <td style="text-align:right;font-weight:600;color:{{ $amendment->total >= 0 ? '#1d4ed8' : '#dc2626' }}">
                {{ $amendment->total >= 0 ? '+' : '' }}{{ number_format($amendment->total, 2, ',', ' ') }}
            </td>
            <td style="text-align:center">
                @if($amendment->files_count)
                    <a href="{{ route('amendments.show', $amendment) }}#files" style="display:inline-flex;align-items:center;gap:.25rem;font-size:12px;color:#6b7280;text-decoration:none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ $amendment->files_count }}
                    </a>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No results.') }}</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</form>
<div>{{ $amendments->withQueryString()->links() }}</div>
@endif
@endsection
