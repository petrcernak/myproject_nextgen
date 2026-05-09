@extends('layouts.app')
@section('title', $budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <span>{{ $budget->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $budget->name }} <code style="font-size:.8em;color:#6b7280">{{ $budget->code }}</code></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ $budget->currency }}{{ $budget->date ? ' · '.$budget->date->format('d.m.Y') : '' }}
        </div>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('budgets.adjustments.create', $budget) }}" class="btn btn-secondary">{{ __('+ New adjustment') }}</a>
            <a href="{{ route('budgets.content', $budget) }}" class="btn btn-primary">{{ __('Edit content') }}</a>
            <a href="{{ route('budgets.edit', $budget) }}" class="btn btn-secondary">{{ __('Settings') }}</a>
            <form method="POST" action="{{ route('budgets.destroy', $budget) }}" onsubmit="return confirm('{{ __('Really delete the entire budget?') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">{{ __('Delete') }}</button>
            </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Total value') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($budget->total, 2, ',', ' ') }}</div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Category count') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $budget->categories->count() }}</div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Total item count') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $budget->categories->sum(fn($c) => $c->items->count()) }}</div>
    </div>
</div>

@if($budget->note)
    <div class="card card-body" style="margin-bottom:1.5rem;font-size:13px;color:#374151">{{ $budget->note }}</div>
@endif

@php
    $subtotalFn = function($cat) use (&$subtotalFn): array {
        $amount     = 0.0;
        $adjustment = 0.0;
        $transfer   = 0.0;
        foreach ($cat->items as $item) {
            $amount     += (float) $item->amount;
            $adjustment += (float) $item->adjustment;
            $transfer   += (float) $item->transfer;
        }
        foreach ($cat->children as $child) {
            $sub         = $subtotalFn($child);
            $amount     += $sub['amount'];
            $adjustment += $sub['adjustment'];
            $transfer   += $sub['transfer'];
        }
        return [
            'amount'     => $amount,
            'adjustment' => $adjustment,
            'transfer'   => $transfer,
            'actual'     => $amount + $adjustment + $transfer,
        ];
    };
    $rootCategories = $budget->categories->whereNull('parent_id');

    $grandAmount     = 0.0;
    $grandAdjustment = 0.0;
    $grandTransfer   = 0.0;
    foreach ($rootCategories as $rc) {
        $sub              = $subtotalFn($rc);
        $grandAmount     += $sub['amount'];
        $grandAdjustment += $sub['adjustment'];
        $grandTransfer   += $sub['transfer'];
    }
    $grandActual = $grandAmount + $grandAdjustment + $grandTransfer;
@endphp

@if($rootCategories->isNotEmpty())
<div style="display:flex;justify-content:flex-end;gap:2rem;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;padding:.3rem 1rem .3rem 0;margin-bottom:.25rem">
    <span style="min-width:100px;text-align:right">{{ __('Amount') }}</span>
    <span style="min-width:100px;text-align:right">{{ __('Adjustment') }}</span>
    <span style="min-width:100px;text-align:right">{{ __('Transfer') }}</span>
    <span style="min-width:110px;text-align:right">{{ __('Actual Budget') }}</span>
</div>
@endif

@forelse($rootCategories as $category)
    @include('budgets._cat_show', [
        'category'   => $category,
        'budget'     => $budget,
        'depth'      => 0,
        'subtotalFn' => $subtotalFn,
    ])
@empty
    <div class="card"><div class="empty"><strong>{{ __('No categories') }}</strong>
        @if($canEdit)<p><a href="{{ route('budgets.content', $budget) }}">{{ __('Go to edit mode') }}</a> {{ __('and add categories.') }}</p>@endif
    </div></div>
@endforelse

@if($rootCategories->isNotEmpty())
<div class="card card-body" style="display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:700;margin-bottom:1.5rem">
    <span>{{ __('Total') }}</span>
    <div style="display:flex;gap:2rem;font-size:13px">
        <span style="color:#6b7280;min-width:100px;text-align:right">{{ number_format($grandAmount, 2, ',', ' ') }}</span>
        <span style="color:{{ $grandAdjustment > 0 ? '#1d4ed8' : ($grandAdjustment < 0 ? '#dc2626' : '#9ca3af') }};min-width:100px;text-align:right">
            {{ $grandAdjustment != 0 ? ($grandAdjustment > 0 ? '+' : '').number_format($grandAdjustment, 2, ',', ' ') : '—' }}
        </span>
        <span style="color:{{ $grandTransfer > 0 ? '#1d4ed8' : ($grandTransfer < 0 ? '#dc2626' : '#9ca3af') }};min-width:100px;text-align:right">
            {{ $grandTransfer != 0 ? ($grandTransfer > 0 ? '+' : '').number_format($grandTransfer, 2, ',', ' ') : '—' }}
        </span>
        <span style="min-width:110px;text-align:right">{{ number_format($grandActual, 2, ',', ' ') }}</span>
    </div>
</div>
@endif

{{-- Adjustments section --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
    <h2 style="font-size:15px;font-weight:700;margin:0">{{ __('Adjustments') }}</h2>
    @if($canEdit)
        <a href="{{ route('budgets.adjustments.create', $budget) }}" class="btn btn-secondary" style="font-size:12px">{{ __('+ New') }}</a>
    @endif
</div>

@if($budget->adjustments->isNotEmpty())
<div class="card" style="margin-bottom:1.5rem">
    <table style="font-size:12px">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Description') }}</th>
                <th style="text-align:right;width:160px">{{ __('Total') }} ({{ $budget->currency }})</th>
                <th style="width:100px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($budget->adjustments->sortByDesc('date') as $adj)
            <tr>
                <td style="white-space:nowrap;color:#6b7280">{{ $adj->date->format('d.m.Y') }}</td>
                <td><a href="{{ route('budget-adjustments.show', $adj) }}">{{ $adj->description }}</a></td>
                <td style="text-align:right;font-weight:600;color:{{ $adj->total > 0 ? '#1d4ed8' : ($adj->total < 0 ? '#dc2626' : '#9ca3af') }}">
                    {{ ($adj->total > 0 ? '+' : '') . number_format($adj->total, 2, ',', ' ') }}
                </td>
                <td style="text-align:right">
                    @if($canEdit)
                    <a href="{{ route('budget-adjustments.edit', $adj) }}" style="font-size:11px;color:#6b7280">{{ __('Edit') }}</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="card card-body" style="font-size:13px;color:#9ca3af;margin-bottom:1.5rem">{{ __('No adjustments yet.') }}</div>
@endif

@endsection
