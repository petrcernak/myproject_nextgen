@extends('layouts.app')
@section('title', __('Change requests'))

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Change requests') }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $changeRequests->total() }} / {{ $totalCount }}</span>
    </h1>
</div>

@if($changeRequests->isEmpty() && !request()->hasAny(['contract_id','company_id','currency','status','date_from','date_to']))
    <div class="card"><div class="empty"><strong>{{ __('No change requests') }}</strong></div></div>
@else

<form method="GET" id="lf">
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:110px">{{ __('Code') }}</th>
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
            <th style="min-width:110px{{ request('status') ? ';background:#dbeafe' : '' }}">
                {{ __('Status') }}
                <select class="{{ $fa('status') }}" name="status" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="open"      @selected(request('status')==='open')>{{ __('Open') }}</option>
                    <option value="on_hold"   @selected(request('status')==='on_hold')>{{ __('On hold') }}</option>
                    <option value="closed"    @selected(request('status')==='closed')>{{ __('Closed') }}</option>
                    <option value="rejected"  @selected(request('status')==='rejected')>{{ __('Rejected') }}</option>
                    <option value="converted" @selected(request('status')==='converted')>{{ __('Converted') }}</option>
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
            <th style="min-width:60px">{{ __('Items') }}</th>
            <th style="min-width:120px;background:#fef9c3">{{ __('Assumed') }}</th>
            <th style="min-width:120px;background:#eff6ff">{{ __('Report') }}</th>
            <th style="min-width:70px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['contract_id','company_id','currency','status','date_from','date_to']))<a href="{{ route('change-requests.index') }}" class="btn btn-secondary btn-sm">×</a>@endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($changeRequests as $cr)
        <tr>
            <td><code style="font-size:12px;color:#6b7280">{{ $cr->code }}</code></td>
            <td style="text-align:left">
                <a href="{{ route('change-requests.show', $cr) }}">{{ $cr->name }}</a>
            </td>
            <td style="text-align:left">
                <a href="{{ route('contracts.show', $cr->contract) }}" style="color:#6b7280;font-size:12px">{{ $cr->contract->code }} {{ $cr->contract->name }}</a>
            </td>
            <td style="text-align:right;font-size:12px;color:#6b7280">{{ $cr->contract->currency }}</td>
            <td style="text-align:left;color:#6b7280">{{ $cr->contract->company?->name ?? '—' }}</td>
            <td>
                <span class="badge {{ $cr->status_badge_class }}">{{ $cr->status_label }}</span>
                @if($cr->status === 'converted' && $cr->convertedChangeOrder)
                    <a href="{{ route('change-orders.show', $cr->convertedChangeOrder) }}" style="font-size:11px;color:#6b7280;display:block">→ {{ $cr->convertedChangeOrder->code }}</a>
                @endif
            </td>
            <td style="text-align:right;color:#6b7280">{{ $cr->date?->format('d.m.Y') }}</td>
            <td></td>
            <td style="text-align:right;color:#6b7280">{{ $cr->items->count() }}</td>
            <td style="text-align:right;font-weight:600;background:#fef9c3;color:{{ $cr->countsInReport() ? '#854d0e' : '#9ca3af' }}">{{ number_format($cr->total_report, 2, ',', ' ') }}</td>
            <td style="text-align:right;font-weight:600;background:#eff6ff;color:{{ $cr->countsInReport() ? '#2563eb' : '#9ca3af' }}">{{ $cr->countsInReport() ? number_format($cr->total_report, 2, ',', ' ') : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="11" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No results.') }}</td></tr>
        @endforelse
        @if($changeRequests->isNotEmpty())
        <tr style="font-weight:600;border-top:2px solid #cbd5e1;background:#f1f5f9">
            <td colspan="9" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total (this page)') }}</td>
            <td style="text-align:right;color:#854d0e;background:#fef9c3">{{ number_format($changeRequests->sum('total_report'), 2, ',', ' ') }}</td>
            <td style="text-align:right;color:#2563eb;background:#eff6ff">{{ number_format($changeRequests->sum('total_effective_report'), 2, ',', ' ') }}</td>
            <td></td>
        </tr>
        @endif
    </tbody>
</table>
</div>
</form>
<div>{{ $changeRequests->withQueryString()->links() }}</div>
@endif
@endsection
