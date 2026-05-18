@extends('layouts.app')
@section('title', __('Invoiced').' — '.$item->description)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <span>{{ $item->code ? $item->code.' · ' : '' }}{{ $item->description }}</span>
</div>
<div class="page-header">
    <div>
        <h1>
            @if($item->code)<code style="font-size:.75em;color:#6b7280">{{ $item->code }}</code> @endif
            {{ $item->description }}
        </h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ $item->category->name }} · {{ $budget->name }} ({{ $budget->currency }})
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('budget-items.invoiced', $item) }}" style="display:flex;gap:.75rem;align-items:flex-end;margin-bottom:1rem;flex-wrap:wrap">
    <div class="form-group" style="margin-bottom:0;min-width:220px">
        <label style="margin-bottom:.25rem">{{ __('Contract') }}</label>
        <select name="contract_id" onchange="this.form.submit()" style="width:100%">
            <option value="">— {{ __('all contracts') }} —</option>
            @foreach($contracts as $c)
                <option value="{{ $c->id }}" @selected(request('contract_id') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" style="margin-bottom:0;min-width:180px">
        <label style="margin-bottom:.25rem">{{ __('Invoice no.') }}</label>
        <input type="text" name="invoice_no" value="{{ request('invoice_no') }}" placeholder="{{ __('partial match…') }}" style="width:100%">
    </div>
    <div style="display:flex;gap:.5rem">
        <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
        @if(request('contract_id') || request('invoice_no'))
            <a href="{{ route('budget-items.invoiced', $item) }}" class="btn btn-secondary">{{ __('Clear') }}</a>
        @endif
    </div>
</form>

@php
    $fmt   = fn($v) => number_format($v, 2, ',', ' ');
    $fmtI  = fn($v) => number_format($v, 0, ',', ' ');
    $sgn   = fn($v) => ($v > 0 ? '+' : '').number_format($v, 2, ',', ' ');
    $cc    = fn($v) => $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af');

    $statusColor = fn($s) => match($s) {
        2 => '#166534', // paid - green
        3 => '#92400e', // due soon - orange
        4 => '#991b1b', // overdue - red
        default => '#6b7280',
    };
    $statusBg = fn($s) => match($s) {
        2 => '#dcfce7',
        3 => '#fef9c3',
        4 => '#fee2e2',
        default => '#f3f4f6',
    };

    $totContractCcy = 0.0;
    $totBudgetContr = 0.0;
    $totBudgetInv   = 0.0;
    $totFxImpact    = 0.0;
@endphp

@if($invoiceItems->isEmpty())
    <div class="card card-body" style="color:#9ca3af;font-size:13px">{{ __('No invoices found for this budget item.') }}</div>
@else
<div style="overflow-x:auto">
<table style="font-size:12px;white-space:nowrap">
    <thead>
        <tr>
            <th>{{ __('Contract') }}</th>
            <th>{{ __('Invoice no.') }}</th>
            <th style="text-align:right">{{ __('Amount') }}<br><span style="font-weight:400;text-transform:none;letter-spacing:0">(contr. ccy)</span></th>
            <th style="text-align:right">{{ __('FX rate') }}<br><span style="font-weight:400;text-transform:none;letter-spacing:0">(contract)</span></th>
            <th style="text-align:right">{{ __('Amount') }}<br><span style="font-weight:400;text-transform:none;letter-spacing:0">({{ $budget->currency }} @ contr.)</span></th>
            <th style="text-align:right">{{ __('FX rate') }}<br><span style="font-weight:400;text-transform:none;letter-spacing:0">(invoice)</span></th>
            <th style="text-align:right">{{ __('Amount') }}<br><span style="font-weight:400;text-transform:none;letter-spacing:0">({{ $budget->currency }} @ inv.)</span></th>
            <th style="text-align:right">{{ __('FX Impact') }}</th>
            <th style="text-align:right">{{ __('Issued') }}</th>
            <th style="text-align:right">{{ __('Tax date') }}</th>
            <th style="text-align:right">{{ __('Due') }}</th>
            <th style="text-align:right">{{ __('Paid') }}</th>
            <th>{{ __('Status') }}</th>
        </tr>
    </thead>
    <tbody>
    @foreach($invoiceItems as $ii)
    @php
        $inv          = $ii->invoice;
        $contract     = $ii->contractItem->contract;
        $contractRate = (float) ($contract->fx_rate ?? 0);
        $invoiceRate  = (float) ($inv->fx_rate ?? 0) ?: $contractRate;
        $amount       = (float) $ii->amount;

        $amtBudgetContr = $contractRate > 0 ? $amount / $contractRate : $amount;
        $amtBudgetInv   = $invoiceRate  > 0 ? $amount / $invoiceRate  : $amount;
        $fxImpact       = ($contractRate > 0 && $invoiceRate > 0) ? $amtBudgetInv - $amtBudgetContr : 0.0;

        $totContractCcy += $amount;
        $totBudgetContr += $amtBudgetContr;
        $totBudgetInv   += $amtBudgetInv;
        $totFxImpact    += $fxImpact;
    @endphp
    <tr>
        <td>
            <a href="{{ route('contracts.show', $contract) }}" style="font-size:12px">{{ $contract->name }}</a>
            <span style="font-size:10px;color:#9ca3af;margin-left:.25rem">{{ $contract->currency }}</span>
        </td>
        <td>
            <a href="{{ route('invoices.show', $inv) }}">{{ $inv->no }}</a>
        </td>
        <td style="text-align:right;font-weight:600">{{ $fmtI(round($amount)) }}</td>
        <td style="text-align:right;color:#6b7280">
            {{ $contractRate > 0 ? number_format($contractRate, 4, ',', ' ') : '—' }}
        </td>
        <td style="text-align:right;font-weight:600">
            {{ $contractRate > 0 ? $fmtI(round($amtBudgetContr)) : $fmtI(round($amount)) }}
        </td>
        <td style="text-align:right;color:#6b7280">
            {{ ($inv->fx_rate && $inv->fx_rate != $contractRate) ? number_format((float)$inv->fx_rate, 4, ',', ' ') : '—' }}
        </td>
        <td style="text-align:right;font-weight:600">
            {{ $invoiceRate > 0 ? $fmtI(round($amtBudgetInv)) : $fmtI(round($amount)) }}
        </td>
        <td style="text-align:right;font-weight:{{ abs($fxImpact) > 0.5 ? '600' : '400' }};color:{{ $cc($fxImpact) }}">
            {{ abs($fxImpact) > 0.5 ? $sgn($fxImpact) : '—' }}
        </td>
        <td style="text-align:right;color:#6b7280">{{ $inv->issued?->format('d.m.Y') ?? '—' }}</td>
        <td style="text-align:right;color:#6b7280">{{ $inv->taxdate?->format('d.m.Y') ?? '—' }}</td>
        <td style="text-align:right;color:{{ $inv->due && $inv->due->isPast() && $inv->status != 2 ? '#dc2626' : '#6b7280' }}">
            {{ $inv->due?->format('d.m.Y') ?? '—' }}
        </td>
        <td style="text-align:right;color:{{ $inv->paid ? '#166534' : '#9ca3af' }}">
            {{ $inv->paid?->format('d.m.Y') ?? '—' }}
        </td>
        <td>
            <span style="display:inline-block;padding:.15rem .45rem;border-radius:99px;font-size:11px;font-weight:600;background:{{ $statusBg($inv->status) }};color:{{ $statusColor($inv->status) }}">
                {{ $inv->status_label }}
            </span>
        </td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr style="font-weight:700;background:#f9fafb">
            <td colspan="2" style="text-align:right;font-size:12px;color:#6b7280">{{ __('Total') }}</td>
            <td style="text-align:right">{{ $fmtI(round($totContractCcy)) }}</td>
            <td></td>
            <td style="text-align:right">{{ $fmtI(round($totBudgetContr)) }}</td>
            <td></td>
            <td style="text-align:right">{{ $fmtI(round($totBudgetInv)) }}</td>
            <td style="text-align:right;color:{{ $cc($totFxImpact) }}">
                {{ abs($totFxImpact) > 0.5 ? $sgn($totFxImpact) : '—' }}
            </td>
            <td colspan="5"></td>
        </tr>
    </tfoot>
</table>
</div>
@endif

<div style="margin-top:1rem">
    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
</div>
@endsection
