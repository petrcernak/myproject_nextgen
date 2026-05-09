@extends('layouts.app')
@section('title', $contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <span>{{ $contract->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $contract->name }} <code style="font-size:.8em;color:#6b7280">{{ $contract->code }}</code></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ $contract->direction === 1 ? __('Income') : __('Expense') }} · {{ $contract->currency }}
            @if($contract->date) · {{ $contract->date->format('d.m.Y') }} @endif
        </div>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contracts.content', $contract) }}" class="btn btn-primary">{{ __('Edit items') }}</a>
            <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-secondary">{{ __('Settings') }}</a>
            @if($contract->isDeletable())
                <form method="POST" action="{{ route('contracts.destroy', $contract) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">{{ __('Delete') }}</button>
                </form>
            @endif
        @endif
    </div>
</div>

@php
    $hasCos        = $contract->changeOrders->isNotEmpty();
    $hasAmendments = $contract->amendments->isNotEmpty();
    $hasCoChanges  = $hasCos || $hasAmendments;
    $coChanges     = $hasCoChanges ? $contract->total_co_changes : 0;

    // Per-item aggregation: sum of latest CR revision report amounts
    $crPerItem = [];
    foreach ($contract->changeRequests as $cr) {
        foreach ($cr->items as $crItem) {
            $id = $crItem->contract_item_id;
            $crPerItem[$id] = ($crPerItem[$id] ?? 0) + ($crItem->latestRevision?->amount_report ?? 0);
        }
    }

    // Per-item aggregation: sum of all CA deltas
    $caPerItem = [];
    foreach ($contract->anticipateds as $ca) {
        foreach ($ca->items as $caItem) {
            $id = $caItem->contract_item_id;
            $caPerItem[$id] = ($caPerItem[$id] ?? 0) + $caItem->amount;
        }
    }

    $hasFuture    = !empty($crPerItem) || !empty($caPerItem);
    $crTotal      = array_sum($crPerItem);
    $caTotal      = array_sum($caPerItem);
    $expectedFinal = $contract->revised_total + $crTotal + $caTotal;
@endphp

{{-- Summary cards --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr {{ $hasFuture ? '1fr' : '' }};gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <table style="font-size:13px">
            <tr><td style="color:#6b7280;border:none;padding:.25rem .5rem .25rem 0">{{ __('Company') }}</td><td style="border:none">{{ $contract->company?->name ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.25rem .5rem .25rem 0">{{ __('Maturity') }}</td><td style="border:none">{{ $contract->maturity }} {{ __('days') }}</td></tr>
            @if($contract->retention_short || $contract->retention_long)
            <tr>
                <td style="color:#6b7280;border:none;padding:.25rem .5rem .25rem 0;vertical-align:top">{{ __('Retention') }}</td>
                <td style="border:none">
                    @if($contract->retention_short)<span style="font-size:12px">{{ __('Short-term') }}: <strong>{{ $contract->retention_short }} %</strong></span><br>@endif
                    @if($contract->retention_long)<span style="font-size:12px">{{ __('Long-term') }}: <strong>{{ $contract->retention_long }} %</strong></span>@endif
                </td>
            </tr>
            @endif
            @if($contract->description)
            <tr><td style="color:#6b7280;border:none;padding:.25rem .5rem;vertical-align:top">{{ __('Description') }}</td><td style="border:none">{{ $contract->description }}</td></tr>
            @endif
        </table>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Contract value') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($contract->total, 2, ',', ' ') }} {{ $contract->currency }}</div>
        @if($hasCoChanges)
        <div style="font-size:12px;margin-top:.4rem">
            <span style="color:#6b7280">{{ __('Changes') }}: </span>
            <span style="font-weight:600;color:{{ $coChanges >= 0 ? '#1d4ed8' : '#dc2626' }}">{{ $coChanges >= 0 ? '+' : '' }}{{ number_format($coChanges, 2, ',', ' ') }}</span>
        </div>
        <div style="font-size:13px;font-weight:700;margin-top:.3rem;padding-top:.3rem;border-top:1px solid #e5e7eb">
            {{ __('Revised total') }}: {{ number_format($contract->revised_total, 2, ',', ' ') }} {{ $contract->currency }}
        </div>
        @else
        <div style="font-size:12px;color:#6b7280;margin-top:.4rem">{{ $contract->items->count() }} {{ __('items') }}</div>
        @endif
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Invoiced') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($contract->invoiced, 2, ',', ' ') }} {{ $contract->currency }}</div>
        @php $pct = $contract->revised_total > 0 ? round($contract->invoiced / $contract->revised_total * 100) : 0; @endphp
        <div style="margin-top:.5rem;height:4px;background:#e5e7eb;border-radius:2px">
            <div style="height:4px;background:#2563eb;border-radius:2px;width:{{ min($pct,100) }}%"></div>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:.3rem">{{ $pct }} %</div>
    </div>
    @if($hasFuture)
    <div class="card card-body" style="border:2px solid #dbeafe">
        <div style="font-size:11px;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Expected final') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($expectedFinal, 2, ',', ' ') }} {{ $contract->currency }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:.4rem">
            @if($crTotal != 0)<span>CR: <strong style="color:{{ $crTotal > 0 ? '#dc2626' : '#16a34a' }}">{{ $crTotal >= 0 ? '+' : '' }}{{ number_format($crTotal, 2, ',', ' ') }}</strong></span>@endif
            @if($crTotal != 0 && $caTotal != 0) &nbsp;·&nbsp; @endif
            @if($caTotal != 0)<span>CA: <strong style="color:{{ $caTotal > 0 ? '#dc2626' : '#16a34a' }}">{{ $caTotal >= 0 ? '+' : '' }}{{ number_format($caTotal, 2, ',', ' ') }}</strong></span>@endif
        </div>
    </div>
    @endif
</div>

{{-- Contract Items --}}
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Contract items') }}</h2>
    @if($canEdit)
        <a href="{{ route('contracts.content', $contract) }}" class="btn btn-secondary btn-sm">{{ __('Edit items') }}</a>
    @endif
</div>
<div class="card" style="margin-bottom:1.5rem">
    @if($contract->items->isEmpty())
        <div class="empty" style="padding:1.5rem">
            <strong>{{ __('No items') }}</strong>
            @if($canEdit)<p><a href="{{ route('contracts.content', $contract) }}">{{ __('Go to edit mode') }}</a></p>@endif
        </div>
    @else
        <div style="overflow-x:auto">
        <table style="font-size:13px">
            <thead>
                <tr>
                    <th style="width:80px">{{ __('Code') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="text-align:right;white-space:nowrap;width:110px">{{ __('Amount') }}</th>
                    @if($hasCos)
                    <th style="text-align:right;white-space:nowrap;width:90px">{{ __('CO changes') }}</th>
                    @endif
                    @if($hasAmendments)
                    <th style="text-align:right;white-space:nowrap;width:90px">{{ __('Amendments') }}</th>
                    @endif
                    @if($hasCoChanges)
                    <th style="text-align:right;white-space:nowrap;width:110px">{{ __('Effective amount') }}</th>
                    @endif
                    <th style="text-align:right;white-space:nowrap;width:100px">{{ __('Invoiced') }}</th>
                    <th style="text-align:right;white-space:nowrap;width:100px">{{ __('Remaining') }}</th>
                    @if($hasFuture)
                    <th style="text-align:right;white-space:nowrap;width:90px;background:#fef2f2">{{ __('CR') }}</th>
                    <th style="text-align:right;white-space:nowrap;width:90px;background:#fef2f2">{{ __('CA') }}</th>
                    <th style="text-align:right;white-space:nowrap;width:110px;background:#dbeafe">{{ __('Expected total') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($contract->items as $item)
                @php
                    $invoiced    = $item->invoiceItems->sum('amount');
                    $coChange    = $item->changeOrderItems->filter(fn($coi) => $coi->changeOrder?->amendment_id === null)->sum('amount');
                    $amdChange   = $item->amendmentItems->sum('amount')
                                 + $item->changeOrderItems->filter(fn($coi) => $coi->changeOrder?->amendment_id !== null)->sum('amount');
                    $effective   = $item->amount + $coChange + $amdChange;
                    $remaining = $effective - $invoiced;
                    $pct       = $effective > 0 ? min(100, round($invoiced / $effective * 100)) : 0;
                    $crVal     = $crPerItem[$item->id] ?? null;
                    $caVal     = $caPerItem[$item->id] ?? null;
                    $expected  = $effective + ($crVal ?? 0) + ($caVal ?? 0);
                @endphp
                <tr>
                    <td><code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code></td>
                    <td>
                        {{ $item->description }}
                        <div style="margin-top:.3rem;height:3px;background:#e5e7eb;border-radius:2px">
                            <div style="height:3px;border-radius:2px;width:{{ $pct }}%;background:{{ $pct >= 100 ? '#22c55e' : '#3b82f6' }}"></div>
                        </div>
                    </td>
                    <td style="text-align:right;white-space:nowrap">{{ number_format($item->amount, 2, ',', ' ') }}</td>
                    @if($hasCos)
                    <td style="text-align:right;white-space:nowrap;color:{{ $coChange > 0 ? '#1d4ed8' : ($coChange < 0 ? '#dc2626' : '#9ca3af') }}">
                        {{ $coChange != 0 ? ($coChange > 0 ? '+' : '') . number_format($coChange, 2, ',', ' ') : '—' }}
                    </td>
                    @endif
                    @if($hasAmendments)
                    <td style="text-align:right;white-space:nowrap;color:{{ $amdChange > 0 ? '#1d4ed8' : ($amdChange < 0 ? '#dc2626' : '#9ca3af') }}">
                        {{ $amdChange != 0 ? ($amdChange > 0 ? '+' : '') . number_format($amdChange, 2, ',', ' ') : '—' }}
                    </td>
                    @endif
                    @if($hasCoChanges)
                    <td style="text-align:right;white-space:nowrap;font-weight:600">{{ number_format($effective, 2, ',', ' ') }}</td>
                    @endif
                    <td style="text-align:right;white-space:nowrap;color:#6b7280">{{ number_format($invoiced, 2, ',', ' ') }}</td>
                    <td style="text-align:right;white-space:nowrap;font-weight:600;color:{{ $remaining > 0 ? '#1d4ed8' : ($remaining < 0 ? '#dc2626' : '#6b7280') }}">
                        {{ number_format($remaining, 2, ',', ' ') }}
                    </td>
                    @if($hasFuture)
                    <td style="text-align:right;white-space:nowrap;background:#fef2f2;color:{{ $crVal !== null ? ($crVal > 0 ? '#dc2626' : ($crVal < 0 ? '#16a34a' : '#6b7280')) : '#d1d5db' }}">
                        @if($crVal !== null){{ $crVal >= 0 ? '+' : '' }}{{ number_format($crVal, 2, ',', ' ') }}@else—@endif
                    </td>
                    <td style="text-align:right;white-space:nowrap;background:#fef2f2;color:{{ $caVal !== null ? ($caVal > 0 ? '#dc2626' : ($caVal < 0 ? '#16a34a' : '#6b7280')) : '#d1d5db' }}">
                        @if($caVal !== null){{ $caVal >= 0 ? '+' : '' }}{{ number_format($caVal, 2, ',', ' ') }}@else—@endif
                    </td>
                    <td style="text-align:right;font-weight:600;background:#dbeafe;color:#1d4ed8">
                        {{ number_format($expected, 2, ',', ' ') }}
                    </td>
                    @endif
                </tr>
                @endforeach
                <tr style="background:#f9fafb;font-weight:600">
                    <td colspan="2" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                    <td style="text-align:right">{{ number_format($contract->total, 2, ',', ' ') }}</td>
                    @if($hasCos)
                    @php $totalCoOnly = $contract->standaloneChangeOrders->sum(fn($co) => $co->items->sum('amount')); @endphp
                    <td style="text-align:right;font-size:12px;color:{{ $totalCoOnly >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $totalCoOnly != 0 ? ($totalCoOnly >= 0 ? '+' : '') . number_format($totalCoOnly, 2, ',', ' ') : '—' }}
                    </td>
                    @endif
                    @if($hasAmendments)
                    @php $totalAmdOnly = $contract->amendments->sum('total'); @endphp
                    <td style="text-align:right;font-size:12px;color:{{ $totalAmdOnly >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $totalAmdOnly != 0 ? ($totalAmdOnly >= 0 ? '+' : '') . number_format($totalAmdOnly, 2, ',', ' ') : '—' }}
                    </td>
                    @endif
                    @if($hasCoChanges)
                    <td style="text-align:right">{{ number_format($contract->revised_total, 2, ',', ' ') }}</td>
                    @endif
                    <td colspan="2"></td>
                    @if($hasFuture)
                    <td style="text-align:right;font-size:12px;background:#fef2f2;color:{{ $crTotal > 0 ? '#dc2626' : ($crTotal < 0 ? '#16a34a' : '#9ca3af') }}">
                        {{ $crTotal != 0 ? ($crTotal >= 0 ? '+' : '') . number_format($crTotal, 2, ',', ' ') : '—' }}
                    </td>
                    <td style="text-align:right;font-size:12px;background:#fef2f2;color:{{ $caTotal > 0 ? '#dc2626' : ($caTotal < 0 ? '#16a34a' : '#9ca3af') }}">
                        {{ $caTotal != 0 ? ($caTotal >= 0 ? '+' : '') . number_format($caTotal, 2, ',', ' ') : '—' }}
                    </td>
                    <td style="text-align:right;background:#dbeafe;color:#1d4ed8">{{ number_format($expectedFinal, 2, ',', ' ') }}</td>
                    @endif
                </tr>
            </tbody>
        </table>
        </div>
    @endif
</div>

{{-- Sub-entity summary tiles --}}
@php
    $amendmentsCount = $contract->amendments->count();
    $amendmentsTotal = $contract->amendments->sum('total');
    $cosCount        = $contract->standaloneChangeOrders->count();
    $cosTotal        = $contract->standaloneChangeOrders->sum(fn($co) => $co->items->sum('amount'));
    $casCount        = $contract->anticipateds->count();
    $crsCount        = $contract->changeRequests->count();
    $invoicesCount   = $contract->invoices->count();
    $invoicesTotal   = $contract->invoiced;
@endphp
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem;margin-bottom:1.5rem">

    {{-- Change Orders --}}
    <div class="card card-body" style="padding:.75rem 1rem">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">{{ __('Change orders') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $cosCount }}</div>
        @if($cosCount)
        <div style="font-size:12px;margin-top:.2rem;color:{{ $cosTotal >= 0 ? '#1d4ed8' : '#dc2626' }}">
            {{ $cosTotal >= 0 ? '+' : '' }}{{ number_format($cosTotal, 2, ',', ' ') }}
        </div>
        @endif
        <div style="margin-top:.6rem;display:flex;gap:.4rem;flex-wrap:wrap">
            <a href="{{ route('contracts.change-orders.index', $contract) }}" class="btn btn-secondary btn-sm">{{ __('View all') }}</a>
            @if($canEdit)<a href="{{ route('contracts.change-orders.create', $contract) }}" class="btn btn-primary btn-sm">+</a>@endif
        </div>
    </div>

    {{-- Amendments --}}
    <div class="card card-body" style="padding:.75rem 1rem">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">{{ __('Amendments') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $amendmentsCount }}</div>
        @if($amendmentsCount)
        <div style="font-size:12px;margin-top:.2rem;color:{{ $amendmentsTotal >= 0 ? '#1d4ed8' : '#dc2626' }}">
            {{ $amendmentsTotal >= 0 ? '+' : '' }}{{ number_format($amendmentsTotal, 2, ',', ' ') }}
        </div>
        @endif
        <div style="margin-top:.6rem;display:flex;gap:.4rem;flex-wrap:wrap">
            <a href="{{ route('contracts.amendments.index', $contract) }}" class="btn btn-secondary btn-sm">{{ __('View all') }}</a>
            @if($canEdit)<a href="{{ route('contracts.amendments.create', $contract) }}" class="btn btn-primary btn-sm">+</a>@endif
        </div>
    </div>

    {{-- Change Requests --}}
    <div class="card card-body" style="padding:.75rem 1rem">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">{{ __('Change requests') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $crsCount }}</div>
        @if($crsCount)
        <div style="font-size:12px;margin-top:.2rem;color:#2563eb">
            {{ number_format($crTotal, 2, ',', ' ') }}
        </div>
        @endif
        <div style="margin-top:.6rem;display:flex;gap:.4rem;flex-wrap:wrap">
            <a href="{{ route('contracts.change-requests.index', $contract) }}" class="btn btn-secondary btn-sm">{{ __('View all') }}</a>
            @if($canEdit)<a href="{{ route('contracts.change-requests.create', $contract) }}" class="btn btn-primary btn-sm">+</a>@endif
        </div>
    </div>

    {{-- Anticipated --}}
    <div class="card card-body" style="padding:.75rem 1rem">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">{{ __('Contract anticipated') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $casCount }}</div>
        @if($casCount && $caTotal != 0)
        <div style="font-size:12px;margin-top:.2rem;color:{{ $caTotal > 0 ? '#dc2626' : '#16a34a' }}">
            {{ $caTotal >= 0 ? '+' : '' }}{{ number_format($caTotal, 2, ',', ' ') }}
        </div>
        @endif
        <div style="margin-top:.6rem;display:flex;gap:.4rem;flex-wrap:wrap">
            <a href="{{ route('contracts.anticipateds.index', $contract) }}" class="btn btn-secondary btn-sm">{{ __('View all') }}</a>
            @if($canEdit)<a href="{{ route('contracts.anticipateds.create', $contract) }}" class="btn btn-primary btn-sm">+</a>@endif
        </div>
    </div>

    {{-- Invoices --}}
    <div class="card card-body" style="padding:.75rem 1rem">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">{{ __('Invoices') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $invoicesCount }}</div>
        @if($invoicesCount)
        <div style="font-size:12px;margin-top:.2rem;color:#6b7280">
            {{ number_format($invoicesTotal, 2, ',', ' ') }}
        </div>
        @endif
        <div style="margin-top:.6rem;display:flex;gap:.4rem;flex-wrap:wrap">
            <a href="{{ route('invoices.index', ['contract_id' => $contract->id]) }}" class="btn btn-secondary btn-sm">{{ __('View all') }}</a>
            @if($canEdit)<a href="{{ route('contracts.invoices.create', $contract) }}" class="btn btn-primary btn-sm">+</a>@endif
        </div>
    </div>

</div>
@endsection
