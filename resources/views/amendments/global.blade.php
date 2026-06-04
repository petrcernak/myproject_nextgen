@extends('layouts.app')
@section('title', __('Amendments'))

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Amendments') }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $amendments->total() }} / {{ $totalCount }}</span>
    </h1>
</div>

@if($amendments->isEmpty() && !request()->hasAny(['contract_id','company_id','currency','date_from','date_to']))
    <div class="card"><div class="empty"><strong>{{ __('No amendments') }}</strong></div></div>
@else

<form method="GET" id="lf">
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:90px">{{ __('Code') }}</th>
            <th style="text-align:left;min-width:200px">{{ __('Name') }}</th>
            <th style="text-align:left;min-width:200px{{ request('contract_id') ? ';background:#dbeafe' : '' }}">
                {{ __('Contract') }}
                <select class="{{ $fa('contract_id') }}" name="contract_id" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($contracts as $c)
                        <option value="{{ $c->id }}" @selected(request('contract_id') == $c->id)>{{ $c->code ? $c->code.' — ' : '' }}{{ $c->name }}</option>
                    @endforeach
                </select>
            </th>
            <th style="min-width:80px{{ request('currency') ? ';background:#dbeafe' : '' }}">
                {{ __('Currency') }}
                <select class="{{ $fa('currency') }}" name="currency" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($currencies as $cur)
                        <option value="{{ $cur }}" @selected(request('currency') === $cur)>{{ $cur }}</option>
                    @endforeach
                </select>
            </th>
            <th style="text-align:left;min-width:160px{{ request('company_id') ? ';background:#dbeafe' : '' }}">
                {{ __('Company') }}
                <select class="{{ $fa('company_id') }}" name="company_id" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected(request('company_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </th>
            <th style="min-width:100px{{ request('date_from') ? ';background:#dbeafe' : '' }}">
                {{ __('From') }}
                <input class="{{ $fa('date_from') }}" type="date" name="date_from" value="{{ request('date_from') }}">
            </th>
            <th style="min-width:100px{{ request('date_to') ? ';background:#dbeafe' : '' }}">
                {{ __('To') }}
                <input class="{{ $fa('date_to') }}" type="date" name="date_to" value="{{ request('date_to') }}">
            </th>
            <th style="min-width:120px">{{ __('Total') }}</th>
            <th style="min-width:60px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['contract_id','company_id','currency','date_from','date_to']))<a href="{{ route('amendments.index') }}" class="btn btn-secondary btn-sm">×</a>@endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($amendments as $amd)
        <tr>
            <td><code style="font-size:12px;color:#6b7280">{{ $amd->code }}</code></td>
            <td style="text-align:left">
                <a href="{{ route('amendments.show', $amd) }}">{{ $amd->name }}</a>
            </td>
            <td style="text-align:left">
                <a href="{{ route('contracts.show', $amd->contract) }}" style="color:#6b7280;font-size:12px">{{ $amd->contract->code }} {{ $amd->contract->name }}</a>
            </td>
            <td style="font-size:12px;color:#6b7280;text-align:right">{{ $amd->contract->currency }}</td>
            <td style="text-align:left;color:#6b7280">{{ $amd->contract->company?->name ?? '—' }}</td>
            <td style="text-align:right;color:#6b7280">{{ $amd->date?->format('d.m.Y') }}</td>
            <td></td>
            <td style="text-align:right;font-weight:600;color:{{ $amd->total >= 0 ? '#1d4ed8' : '#dc2626' }}">
                {{ ($amd->total >= 0 ? '+' : '').number_format($amd->total, 2, ',', ' ') }}
            </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No results.') }}</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</form>
<div>{{ $amendments->withQueryString()->links() }}</div>
@endif
@endsection
