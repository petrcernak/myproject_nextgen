@extends('layouts.app')
@section('title', __('Adjustment').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}"><span>{{ $budget->name }}</span></a>
    <span>{{ __('Adjustment') }}</span>
</div>
<div class="page-header">
    <div>
        <h1>{{ $adjustment->description }}</h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">{{ $adjustment->date->format('d.m.Y') }}</div>
    </div>
    <div style="display:flex;gap:.5rem">
        <a href="{{ route('budget-adjustments.edit', $adjustment) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
        <form method="POST" action="{{ route('budget-adjustments.destroy', $adjustment) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger">{{ __('Delete') }}</button>
        </form>
    </div>
</div>

<div class="card">
    <table style="font-size:12px">
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th style="text-align:right;width:160px">{{ __('Adjustment') }} ({{ $budget->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($adjustment->items as $ai)
            <tr>
                <td>
                    <code style="color:#6b7280;font-size:11px">{{ $ai->budgetItem?->code ?? '—' }}</code>
                    {{ $ai->budgetItem?->description }}
                    <span style="font-size:11px;color:#9ca3af">· {{ $ai->budgetItem?->category?->name }}</span>
                </td>
                <td style="text-align:right;font-weight:600;color:{{ $ai->amount > 0 ? '#1d4ed8' : '#dc2626' }}">
                    {{ ($ai->amount > 0 ? '+' : '') . number_format($ai->amount, 2, ',', ' ') }}
                </td>
            </tr>
            @endforeach
            <tr style="background:#f9fafb;font-weight:600">
                <td style="text-align:right;color:#6b7280;font-size:11px">{{ __('Total') }}</td>
                <td style="text-align:right;color:{{ $adjustment->total > 0 ? '#1d4ed8' : '#dc2626' }}">
                    {{ ($adjustment->total > 0 ? '+' : '') . number_format($adjustment->total, 2, ',', ' ') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
