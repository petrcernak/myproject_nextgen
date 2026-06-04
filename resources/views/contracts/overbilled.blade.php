@extends('layouts.app')
@section('title', __('Overbilled contracts'))

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Overbilled contracts') }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $contracts->count() }}</span>
    </h1>
</div>

@if($contracts->isEmpty() && !request()->hasAny(['company_id','currency','date_from','date_to']))
    <div class="card"><div class="empty"><strong>{{ __('No overbilled contracts') }}</strong></div></div>
@else

<form method="GET" id="lf">
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:90px">{{ __('Code') }}</th>
            <th style="text-align:left;min-width:220px">{{ __('Name') }}</th>
            <th style="text-align:left;min-width:160px{{ request('company_id') ? ';background:#dbeafe' : '' }}">
                {{ __('Company') }}
                <select class="{{ $fa('company_id') }}" name="company_id" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected(request('company_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </th>
            <th style="min-width:70px{{ request('currency') ? ';background:#dbeafe' : '' }}">
                {{ __('Curr.') }}
                <select class="{{ $fa('currency') }}" name="currency" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($currencies as $c)
                        <option value="{{ $c }}" @selected(request('currency') === $c)>{{ $c }}</option>
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
            <th style="min-width:130px">{{ __('Contract value') }}</th>
            <th style="min-width:120px">{{ __('Invoiced') }}</th>
            <th style="min-width:55px">{{ __('%') }}</th>
            <th style="min-width:130px">{{ __('Excess') }}</th>
            <th style="min-width:100px">{{ __('Type') }}</th>
            <th style="min-width:70px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['company_id','currency','date_from','date_to']))<a href="{{ route('contracts.overbilled') }}" class="btn btn-secondary btn-sm">×</a>@endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($contracts as $contract)
        @php
            $totalOver = $contract->stat_invoiced > $contract->stat_revised_total;
            $itemOver  = $contract->stat_overbilled_items->isNotEmpty();
        @endphp
        <tr>
            <td><a href="{{ route('contracts.show', $contract) }}"><code style="font-size:12px;color:#6b7280">{{ $contract->code }}</code></a></td>
            <td style="text-align:left">
                <a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a>
                @if($itemOver && !$totalOver)
                    <div style="font-size:11px;color:#dc2626;margin-top:.15rem">
                        {{ $contract->stat_overbilled_items->count() }} {{ __('item(s) overbilled') }}:
                        {{ $contract->stat_overbilled_items->map(fn($i) => $i->code ?: $i->description)->implode(', ') }}
                    </div>
                @endif
            </td>
            <td style="text-align:left;color:#6b7280">{{ $contract->company?->name ?? '—' }}</td>
            <td style="text-align:right;font-size:12px;color:#6b7280">{{ $contract->currency }}</td>
            <td style="text-align:right;color:#6b7280">{{ $contract->date?->format('d.m.Y') ?? '—' }}</td>
            <td></td>
            <td style="text-align:right">{{ number_format($contract->stat_revised_total, 2, ',', ' ') }}</td>
            <td style="text-align:right;color:#dc2626;font-weight:600">{{ number_format($contract->stat_invoiced, 2, ',', ' ') }}</td>
            <td style="text-align:right"><span style="font-size:11px;font-weight:600;color:#dc2626">{{ $contract->stat_pct }} %</span></td>
            <td style="text-align:right;font-weight:600;color:#dc2626">{{ $totalOver ? number_format($contract->stat_diff, 2, ',', ' ') : '—' }}</td>
            <td>
                @if($totalOver)<span class="badge badge-red">{{ __('Total') }}</span>@endif
                @if($itemOver)<span class="badge badge-yellow">{{ __('Item') }}</span>@endif
            </td>
            <td></td>
        </tr>
        @empty
        <tr><td colspan="12" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No overbilled contracts match the filters.') }}</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</form>
@endif
@endsection
