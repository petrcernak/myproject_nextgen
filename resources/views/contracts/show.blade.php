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
    <div style="display:flex;gap:.5rem;align-items:center">
        <a href="{{ route('contracts.files', $contract) }}" class="btn btn-secondary" title="{{ __('Files') }}" style="display:flex;align-items:center;gap:.3rem">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            @if($contract->files_count) <span style="font-size:12px;font-weight:600">{{ $contract->files_count }}</span> @endif
        </a>
        @if($canEdit)
        <a href="{{ route('contracts.content', $contract) }}" class="btn btn-primary">{{ __('Edit content') }}</a>
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

{{-- Budget links bar --}}
@php
    $allLinked = $contract->items->isNotEmpty() && $contract->items->every(fn($i) => $i->budget_item_id !== null);
    $anyLinked = $contract->items->contains(fn($i) => $i->budget_item_id !== null);
@endphp
<div style="display:flex;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;padding:.5rem .75rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;font-size:12px">
    <span style="color:#6b7280;font-weight:600;margin-right:.25rem">{{ __('Budget links:') }}</span>

    @if($contract->budgetLinks->isEmpty())
        <span style="color:#f59e0b;font-weight:500">{{ __('No budget linked') }}</span>
    @else
        @foreach($contract->budgetLinks as $bl)
            @php
                $blLinkedCount = $contract->items->filter(fn($ci) =>
                    $ci->budget_item_id && $bl->budget->categories->flatMap(fn($c) =>
                        $c->items->merge($c->children->flatMap(fn($cc) =>
                            $cc->items->merge($cc->children->flatMap->items)
                        ))
                    )->contains('id', $ci->budget_item_id)
                )->count();
            @endphp
            <a href="{{ route('contract-budget-links.show', $bl) }}"
               style="display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .6rem;border-radius:999px;text-decoration:none;font-weight:600;background:{{ $blLinkedCount >= $contract->items->count() && $contract->items->count() > 0 ? '#d1fae5' : '#fef3c7' }};color:{{ $blLinkedCount >= $contract->items->count() && $contract->items->count() > 0 ? '#065f46' : '#92400e' }};border:1px solid {{ $blLinkedCount >= $contract->items->count() && $contract->items->count() > 0 ? '#a7f3d0' : '#fde68a' }}">
                <span style="font-size:10px">{{ $blLinkedCount >= $contract->items->count() && $contract->items->count() > 0 ? '●' : '●' }}</span>
                {{ $bl->budget->name }}
                @if($bl->fx_rate)
                    <span style="font-weight:400;opacity:.75">{{ number_format($bl->fx_rate, 2, '.', '') }}</span>
                @endif
            </a>
        @endforeach
    @endif

    @if($canEdit)
        <a href="{{ route('contracts.budget-links.create', $contract) }}"
           style="display:inline-flex;align-items:center;gap:.25rem;padding:.25rem .5rem;border-radius:999px;font-size:11px;color:#6b7280;border:1px dashed #d1d5db;text-decoration:none">
            + {{ __('Link budget') }}
        </a>
    @endif
</div>

@php
    $hasCos        = $contract->changeOrders->isNotEmpty();
    $hasAmendments = $contract->amendments->isNotEmpty();
    $hasCoChanges  = $hasCos || $hasAmendments;
    $coChanges     = $hasCoChanges ? $contract->total_co_changes : 0;
    $cosChanges    = $contract->standaloneChangeOrders->sum(fn($co) => $co->items->sum('amount'));
    $amdChanges    = $contract->amendments->sum('total');

    // Per-item aggregation: sum of latest CR revision report amounts (exclude rejected/converted)
    $crPerItem = [];
    foreach ($contract->changeRequests->filter(fn($cr) => $cr->countsInReport()) as $cr) {
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

    $regularInvoices = $contract->invoices->where('is_advance', false);
    $invoicesCount   = $regularInvoices->count();
    $invoicesTotal   = $regularInvoices->sum(fn($inv) => $inv->total);
    $advanceInvoices = $contract->invoices->where('is_advance', true);
    $advanceCount    = $advanceInvoices->count();
    $advanceTotal    = $advanceInvoices->sum('advance_amount');
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
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($hasCoChanges ? $contract->revised_total : $contract->total, 2, ',', ' ') }} {{ $contract->currency }}</div>
        <div style="font-size:12px;margin-top:.5rem;display:flex;flex-direction:column;gap:.2rem">
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <span style="color:#6b7280">{{ __('Original amount') }}</span>
                <span>{{ number_format($contract->total, 2, ',', ' ') }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('contracts.change-orders.index', $contract) }}" style="color:#6b7280;text-decoration:none">{{ __('Change orders') }}</a>
                <span style="font-weight:600;color:{{ $cosChanges > 0 ? '#1d4ed8' : ($cosChanges < 0 ? '#dc2626' : '#9ca3af') }}">
                    {{ $cosChanges != 0 ? ($cosChanges > 0 ? '+' : '') . number_format($cosChanges, 2, ',', ' ') : '—' }}
                </span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('contracts.amendments.index', $contract) }}" style="color:#6b7280;text-decoration:none">{{ __('Amendments') }}</a>
                <span style="font-weight:600;color:{{ $amdChanges > 0 ? '#1d4ed8' : ($amdChanges < 0 ? '#dc2626' : '#9ca3af') }}">
                    {{ $amdChanges != 0 ? ($amdChanges > 0 ? '+' : '') . number_format($amdChanges, 2, ',', ' ') : '—' }}
                </span>
            </div>
        </div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Invoiced') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($contract->invoiced, 2, ',', ' ') }} {{ $contract->currency }}</div>
        @php $pct = $contract->revised_total > 0 ? round($contract->invoiced / $contract->revised_total * 100) : 0; @endphp
        <div style="margin-top:.5rem;height:4px;background:#e5e7eb;border-radius:2px">
            <div style="height:4px;background:#2563eb;border-radius:2px;width:{{ min($pct,100) }}%"></div>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:.3rem">{{ $pct }} %</div>
        <div style="font-size:12px;margin-top:.4rem;display:flex;flex-direction:column;gap:.2rem">
            <span style="display:flex;gap:.75rem;flex-wrap:wrap">
                <a href="{{ route('invoices.index', ['contract_id' => $contract->id]) }}" style="color:#6b7280;text-decoration:none">
                    {{ __('Invoices') }}: <strong>{{ $invoicesCount }}</strong>
                </a>
                <a href="{{ route('invoices.index', ['contract_id' => $contract->id, 'advance' => '1']) }}" style="color:#92400e;text-decoration:none">
                    {{ __('Down payments') }}: <strong>{{ $advanceCount }}</strong>
                </a>
            </span>
            @if($contract->retention_short || $contract->retention_long)
            @php $retReleased = $contract->retentionReleases->sum('amount'); @endphp
            <a href="{{ route('contracts.retention', $contract) }}" style="color:#6b7280;text-decoration:none">
                {{ __('Released') }}: <strong style="color:#16a34a">{{ number_format($retReleased, 2, ',', ' ') }}</strong>
                / {{ __('Held') }}: <strong style="color:#dc2626">{{ number_format($contract->retention_held, 2, ',', ' ') }}</strong>
            </a>
            @endif
        </div>
    </div>
    @if($hasFuture)
    <div class="card card-body" style="border:2px solid #dbeafe">
        <div style="font-size:11px;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Expected final') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($expectedFinal, 2, ',', ' ') }} {{ $contract->currency }}</div>
        <div style="font-size:12px;margin-top:.5rem;display:flex;flex-direction:column;gap:.2rem">
            <div style="display:flex;justify-content:space-between;gap:.5rem">
                <a href="{{ route('contracts.change-requests.index', $contract) }}" style="color:#6b7280;text-decoration:none">{{ __('Change requests') }}</a>
                <strong style="color:{{ $crTotal > 0 ? '#dc2626' : '#16a34a' }}">{{ $crTotal != 0 ? ($crTotal >= 0 ? '+' : '') . number_format($crTotal, 2, ',', ' ') : '—' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;gap:.5rem">
                <a href="{{ route('contracts.anticipateds.index', $contract) }}" style="color:#6b7280;text-decoration:none">{{ __('Anticipated') }}</a>
                <strong style="color:{{ $caTotal > 0 ? '#dc2626' : '#16a34a' }}">{{ $caTotal != 0 ? ($caTotal >= 0 ? '+' : '') . number_format($caTotal, 2, ',', ' ') : '—' }}</strong>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Contract Items --}}
<h2 style="font-size:1rem;font-weight:600;margin-bottom:.75rem">{{ __('Contract items') }}</h2>

@if($contract->items->isEmpty())
<div class="card" style="margin-bottom:1.5rem">
    <div class="empty" style="padding:1.5rem">
        <strong>{{ __('No items') }}</strong>
        @if($canEdit)<p><a href="{{ route('contracts.content', $contract) }}">{{ __('Go to edit mode') }}</a></p>@endif
    </div>
</div>
@else

@php
    $subtotalFn = function($cat) use (&$subtotalFn, $crPerItem, $caPerItem): array {
        $sub = ['amount'=>0,'co'=>0,'amd'=>0,'effective'=>0,'invoiced'=>0,'remaining'=>0,'cr'=>0,'ca'=>0,'expected'=>0];
        foreach ($cat->items as $si) {
            $inv = $si->invoiceItems->sum('amount');
            $co  = $si->changeOrderItems->filter(fn($c) => $c->changeOrder?->amendment_id === null)->sum('amount');
            $amd = $si->amendmentItems->sum('amount') + $si->changeOrderItems->filter(fn($c) => $c->changeOrder?->amendment_id !== null)->sum('amount');
            $eff = $si->amount + $co + $amd;
            $cr  = $crPerItem[$si->id] ?? 0;
            $ca  = $caPerItem[$si->id] ?? 0;
            $sub['amount']    += $si->amount;
            $sub['co']        += $co;
            $sub['amd']       += $amd;
            $sub['effective'] += $eff;
            $sub['invoiced']  += $inv;
            $sub['remaining'] += $eff - $inv;
            $sub['cr']        += $cr;
            $sub['ca']        += $ca;
            $sub['expected']  += $eff + $cr + $ca;
        }
        foreach ($cat->children as $child) {
            $childSub = $subtotalFn($child);
            foreach ($sub as $k => $v) { $sub[$k] += $childSub[$k]; }
        }
        return $sub;
    };
    $totalCoOnly     = $contract->standaloneChangeOrders->sum(fn($co) => $co->items->sum('amount'));
    $totalAmdOnly    = $contract->amendments->sum('total');
    $totalInvoiced   = $contract->invoiced;
    $effectiveTotal  = $hasCoChanges ? $contract->revised_total : $contract->total;
    $totalRemaining  = $effectiveTotal - $totalInvoiced;
@endphp

<div style="overflow-x:auto;margin-bottom:1.5rem">
<table class="bgt" style="border-collapse:collapse;font-size:12px;font-variant-numeric:tabular-nums;width:100%">
    <thead>
        <tr>
            <th style="min-width:80px;text-align:left">{{ __('Code') }}</th>
            <th style="min-width:220px;text-align:left;white-space:normal">{{ __('Description') }}</th>
            <th style="min-width:120px">{{ __('Amount') }}</th>
            @if($hasCos)<th style="min-width:110px">{{ __('CO changes') }}</th>@endif
            @if($hasAmendments)<th style="min-width:110px">{{ __('Amendments') }}</th>@endif
            @if($hasCoChanges)<th style="min-width:130px">{{ __('Effective amount') }}</th>@endif
            <th style="min-width:110px">{{ __('Invoiced') }}</th>
            <th style="min-width:110px">{{ __('Remaining') }}</th>
            @if($hasFuture)
            <th style="min-width:100px">{{ __('CR') }}</th>
            <th style="min-width:100px">{{ __('CA') }}</th>
            <th style="min-width:130px">{{ __('Expected total') }}</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($contract->categories->whereNull('parent_id') as $category)
            @include('contracts._cat_show',[
                'category'      => $category,
                'depth'         => 0,
                'ancestors'     => '',
                'subtotalFn'    => $subtotalFn,
                'hasCos'        => $hasCos,
                'hasAmendments' => $hasAmendments,
                'hasCoChanges'  => $hasCoChanges,
                'hasFuture'     => $hasFuture,
                'crPerItem'     => $crPerItem,
                'caPerItem'     => $caPerItem,
            ])
        @endforeach

        @foreach($contract->items->whereNull('contract_category_id') as $item)
        @php
            $invoiced  = $item->invoiceItems->sum('amount');
            $coChange  = $item->changeOrderItems->filter(fn($coi) => $coi->changeOrder?->amendment_id === null)->sum('amount');
            $amdChange = $item->amendmentItems->sum('amount')
                       + $item->changeOrderItems->filter(fn($coi) => $coi->changeOrder?->amendment_id !== null)->sum('amount');
            $effective = $item->amount + $coChange + $amdChange;
            $remaining = $effective - $invoiced;
            $pct       = $effective > 0 ? min(100, round($invoiced / $effective * 100)) : 0;
            $crVal     = $crPerItem[$item->id] ?? null;
            $caVal     = $caPerItem[$item->id] ?? null;
            $expected  = $effective + ($crVal ?? 0) + ($caVal ?? 0);
        @endphp
        <tr class="bgt-item" data-ancestors="">
            <td><code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code></td>
            <td style="text-align:left;white-space:normal">
                {{ $item->description }}
                <div style="margin-top:.25rem;height:3px;background:#e5e7eb;border-radius:2px">
                    <div style="height:3px;border-radius:2px;width:{{ $pct }}%;background:{{ $pct>=100?'#22c55e':'#3b82f6' }}"></div>
                </div>
            </td>
            <td>{{ number_format($item->amount,2,',',' ') }}</td>
            @if($hasCos)
            <td style="color:{{ $coChange>0?'#1d4ed8':($coChange<0?'#dc2626':'#9ca3af') }}">
                @if($coChange!=0)<a href="{{ route('contract-items.show',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right;font-weight:600">{{ number_format($coChange,2,',',' ') }}</a>@else—@endif
            </td>
            @endif
            @if($hasAmendments)
            <td style="color:{{ $amdChange>0?'#1d4ed8':($amdChange<0?'#dc2626':'#9ca3af') }}">
                @if($amdChange!=0)<a href="{{ route('contract-items.show',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right;font-weight:600">{{ number_format($amdChange,2,',',' ') }}</a>@else—@endif
            </td>
            @endif
            @if($hasCoChanges)<td style="font-weight:600">{{ number_format($effective,2,',',' ') }}</td>@endif
            <td style="color:#6b7280">{{ number_format($invoiced,2,',',' ') }}</td>
            <td style="font-weight:600;color:{{ $remaining>0?'#1d4ed8':($remaining<0?'#dc2626':'#6b7280') }}">{{ number_format($remaining,2,',',' ') }}</td>
            @if($hasFuture)
            <td style="color:{{ $crVal!==null?($crVal>0?'#dc2626':($crVal<0?'#16a34a':'#6b7280')):'#d1d5db' }}">
                @if($crVal!==null)<a href="{{ route('contract-items.show',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right;font-weight:600">{{ number_format($crVal,2,',',' ') }}</a>@else—@endif
            </td>
            <td style="color:{{ $caVal!==null?($caVal>0?'#dc2626':($caVal<0?'#16a34a':'#6b7280')):'#d1d5db' }}">
                @if($caVal!==null)<a href="{{ route('contract-items.show',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right;font-weight:600">{{ number_format($caVal,2,',',' ') }}</a>@else—@endif
            </td>
            <td style="font-weight:600;color:#1d4ed8">{{ number_format($expected,2,',',' ') }}</td>
            @endif
        </tr>
        @endforeach

        <tr class="bgt-total">
            <td colspan="2">{{ __('Total') }}</td>
            <td>{{ number_format($contract->total,2,',',' ') }}</td>
            @if($hasCos)
            <td style="color:{{ $totalCoOnly>=0?'#1d4ed8':'#dc2626' }}">{{ $totalCoOnly!=0?number_format($totalCoOnly,2,',',' '):'—' }}</td>
            @endif
            @if($hasAmendments)
            <td style="color:{{ $totalAmdOnly>=0?'#1d4ed8':'#dc2626' }}">{{ $totalAmdOnly!=0?number_format($totalAmdOnly,2,',',' '):'—' }}</td>
            @endif
            @if($hasCoChanges)<td>{{ number_format($contract->revised_total,2,',',' ') }}</td>@endif
            <td style="color:#6b7280">{{ number_format($totalInvoiced,2,',',' ') }}</td>
            <td style="color:{{ $totalRemaining>0?'#1d4ed8':($totalRemaining<0?'#dc2626':'#6b7280') }}">{{ number_format($totalRemaining,2,',',' ') }}</td>
            @if($hasFuture)
            <td style="color:{{ $crTotal>0?'#dc2626':($crTotal<0?'#16a34a':'#9ca3af') }}">{{ $crTotal!=0?number_format($crTotal,2,',',' '):'—' }}</td>
            <td style="color:{{ $caTotal>0?'#dc2626':($caTotal<0?'#16a34a':'#9ca3af') }}">{{ $caTotal!=0?number_format($caTotal,2,',',' '):'—' }}</td>
            <td style="color:#1d4ed8">{{ number_format($expectedFinal,2,',',' ') }}</td>
            @endif
        </tr>
    </tbody>
</table>
</div>
@endif

<script>
var bgtOpen={};
function bgtToggle(id){
    bgtOpen[id]=!bgtOpen[id];
    var row=document.getElementById('bgt-row-'+id);
    if(row){var c=row.querySelector('.bgt-caret');if(c)c.style.transform=bgtOpen[id]?'':'rotate(90deg)';}
    document.querySelectorAll('tr[data-ancestors]').forEach(function(r){
        var anc=(r.dataset.ancestors||'').split(',').filter(Boolean);
        if(!anc.length)return;
        r.style.display=anc.every(function(a){return !bgtOpen[a];})?'':'none';
    });
}
</script>

@endsection
