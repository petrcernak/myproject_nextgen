@extends('layouts.app')
@section('title', __('Companies'))

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="page-header">
    <h1>{{ __('Companies') }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $companies->total() }} / {{ $totalCount }}</span>
    </h1>
    @if($canEdit)<a href="{{ route('companies.create') }}" class="btn btn-primary">+ {{ __('New company') }}</a>@endif
</div>

@if($companies->isEmpty() && !request('search'))
    <div class="card"><div class="empty"><strong>{{ __('No companies') }}</strong></div></div>
@else

<form method="GET" id="lf">
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:200px{{ request('search') ? ';background:#dbeafe' : '' }}">
                {{ __('Name') }}
                <input class="{{ $fa('search') }}" type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search…') }}">
            </th>
            <th style="text-align:left;min-width:110px">{{ __('Reg. No.') }}</th>
            <th style="text-align:left;min-width:110px">{{ __('Tax ID') }}</th>
            <th style="text-align:left;min-width:160px">{{ __('Email') }}</th>
            <th style="text-align:left;min-width:120px">{{ __('Phone') }}</th>
            <th style="min-width:80px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request('search'))<a href="{{ route('companies.index') }}" class="btn btn-secondary btn-sm">×</a>@endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($companies as $company)
        <tr>
            <td style="text-align:left"><a href="{{ route('companies.show', $company) }}">{{ $company->name }}</a></td>
            <td style="text-align:left;color:#6b7280">{{ $company->regno ?? '—' }}</td>
            <td style="text-align:left;color:#6b7280">{{ $company->taxregno ?? '—' }}</td>
            <td style="text-align:left">{{ $company->email ?? '—' }}</td>
            <td style="text-align:left">{{ $company->phone ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No results.') }}</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</form>
<div>{{ $companies->withQueryString()->links() }}</div>
@endif
@endsection
