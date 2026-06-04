@extends('layouts.app')
@section('title', __('Change requests') . ' — ' . $contract->name)

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <span>{{ __('Change requests') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Change requests') }}: {{ $contract->name }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $changeRequests->total() }} / {{ $totalCount }}</span>
    </h1>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contracts.change-requests.create', $contract) }}" class="btn btn-primary">+ {{ __('New change request') }}</a>
        @endif
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back') }}</a>
    </div>
</div>

@if($changeRequests->isEmpty() && !request()->hasAny(['search','status']))
    <div class="card"><div class="empty"><strong>{{ __('No change requests') }}</strong></div></div>
@else

<form method="GET" id="lf">
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:120px">{{ __('Code') }}</th>
            <th style="text-align:left;min-width:220px{{ request('search') ? ';background:#dbeafe' : '' }}">
                {{ __('Name') }}
                <input class="{{ $fa('search') }}" type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search code / name…') }}">
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
            <th style="min-width:100px">{{ __('Date') }}</th>
            <th style="min-width:60px">{{ __('Items') }}</th>
            <th style="min-width:120px">{{ __('Supplier') }}</th>
            <th style="min-width:120px">{{ __('PM') }}</th>
            <th style="min-width:120px;background:#fef9c3">{{ __('Assumed') }}</th>
            <th style="min-width:120px;background:#eff6ff">{{ __('Report') }}</th>
            <th style="min-width:70px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search','status']))<a href="{{ route('contracts.change-requests.index', $contract) }}" class="btn btn-secondary btn-sm">×</a>@endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($changeRequests as $cr)
        <tr>
            <td><code style="font-size:12px;color:#6b7280">{{ $cr->code }}</code></td>
            <td style="text-align:left">
                <a href="{{ route('change-requests.show', $cr) }}">{{ $cr->name }}</a>
                @if($cr->note)<div style="font-size:12px;color:#6b7280">{{ $cr->note }}</div>@endif
            </td>
            <td>
                <span class="badge {{ $cr->status_badge_class }}">{{ $cr->status_label }}</span>
                @if($cr->status === 'converted' && $cr->convertedChangeOrder)
                    <a href="{{ route('change-orders.show', $cr->convertedChangeOrder) }}" style="font-size:11px;color:#6b7280;margin-left:.3rem">→ {{ $cr->convertedChangeOrder->code }}</a>
                @endif
            </td>
            <td style="text-align:right;color:#6b7280">{{ $cr->date?->format('d.m.Y') }}</td>
            <td style="text-align:right;color:#6b7280">{{ $cr->items->count() }}</td>
            <td style="text-align:right;color:#6b7280">{{ number_format($cr->total_supplier, 2, ',', ' ') }}</td>
            <td style="text-align:right;color:#6b7280">{{ number_format($cr->total_pm, 2, ',', ' ') }}</td>
            <td style="text-align:right;font-weight:600;background:#fef9c3;color:{{ $cr->countsInReport() ? '#854d0e' : '#9ca3af' }}">{{ number_format($cr->total_report, 2, ',', ' ') }}</td>
            <td style="text-align:right;font-weight:600;background:#eff6ff;color:{{ $cr->countsInReport() ? '#2563eb' : '#9ca3af' }}">
                {{ $cr->countsInReport() ? number_format($cr->total_report, 2, ',', ' ') : '—' }}
            </td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No results.') }}</td></tr>
        @endforelse
        @if($changeRequests->isNotEmpty())
        <tr style="font-weight:600;border-top:2px solid #cbd5e1;background:#f1f5f9">
            <td colspan="5" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total (this page)') }}</td>
            <td style="text-align:right;color:#6b7280">{{ number_format($changeRequests->sum('total_supplier'), 2, ',', ' ') }}</td>
            <td style="text-align:right;color:#6b7280">{{ number_format($changeRequests->sum('total_pm'), 2, ',', ' ') }}</td>
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
