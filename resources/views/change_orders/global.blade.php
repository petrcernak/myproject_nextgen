@extends('layouts.app')
@section('title', __('Change orders'))

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Change orders') }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $changeOrders->total() }} / {{ $totalCount }}</span>
    </h1>
</div>

@if($changeOrders->isEmpty() && !request()->hasAny(['contract_id','company_id','currency','date_from','date_to']))
    <div class="card"><div class="empty"><strong>{{ __('No change orders') }}</strong></div></div>
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
            <th style="text-align:left;min-width:120px">{{ __('Amendment') }}</th>
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
            <th style="min-width:40px"></th>
            <th style="min-width:70px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['contract_id','company_id','currency','date_from','date_to']))<a href="{{ route('change-orders.index') }}" class="btn btn-secondary btn-sm">×</a>@endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($changeOrders as $co)
        <tr>
            <td><code style="font-size:12px;color:#6b7280">{{ $co->code }}</code></td>
            <td style="text-align:left">
                <a href="{{ route('change-orders.show', $co) }}">{{ $co->name }}</a>
            </td>
            <td style="text-align:left">
                <a href="{{ route('contracts.show', $co->contract) }}" style="color:#6b7280;font-size:12px">{{ $co->contract->code }} {{ $co->contract->name }}</a>
            </td>
            <td style="text-align:left;font-size:12px;color:#6b7280">
                @if($co->amendment)<a href="{{ route('amendments.show', $co->amendment) }}" style="color:#6b7280">{{ $co->amendment->code }}</a>
                @else<span style="color:#d1d5db">{{ __('standalone') }}</span>@endif
            </td>
            <td style="text-align:right;font-size:12px;color:#6b7280">{{ $co->contract->currency }}</td>
            <td style="text-align:left;color:#6b7280">{{ $co->contract->company?->name ?? '—' }}</td>
            <td style="text-align:right;color:#6b7280">{{ $co->date?->format('d.m.Y') }}</td>
            <td></td>
            <td style="text-align:right;font-weight:600;color:{{ $co->total >= 0 ? '#1d4ed8' : '#dc2626' }}">
                {{ ($co->total >= 0 ? '+' : '').number_format($co->total, 2, ',', ' ') }}
            </td>
            <td style="text-align:center">
                @if($co->files_count)
                    <a href="{{ route('change-orders.show', $co) }}#files" style="display:inline-flex;align-items:center;gap:.2rem;font-size:12px;color:#6b7280;text-decoration:none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ $co->files_count }}
                    </a>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="11" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No results.') }}</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</form>
<div>{{ $changeOrders->withQueryString()->links() }}</div>
@endif
@endsection
