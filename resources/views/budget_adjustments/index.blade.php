@extends('layouts.app')
@section('title', __('Adjustments').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <span>{{ __('Adjustments') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Adjustments') }} <span style="font-size:.7em;font-weight:400;color:#6b7280">{{ $budget->name }}</span></h1>
    @if($canEdit)
        <a href="{{ route('budgets.adjustments.create', $budget) }}" class="btn btn-primary">{{ __('+ New adjustment') }}</a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($budget->adjustments->isEmpty())
    <div class="card card-body" style="color:#9ca3af;font-size:13px">{{ __('No adjustments yet.') }}</div>
@else
<div class="card" style="margin-bottom:1.5rem">
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="white-space:nowrap">{{ __('Date') }}</th>
                <th>{{ __('Description') }}</th>
                <th style="text-align:right;width:180px">{{ __('Total') }} ({{ $budget->currency }})</th>
                <th style="width:80px"></th>
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
                    <a href="{{ route('budget-adjustments.edit', $adj) }}" style="font-size:12px;color:#6b7280">{{ __('Edit') }}</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        @php $total = $budget->adjustments->sum('total'); @endphp
        <tfoot>
            <tr style="font-weight:700;background:#f9fafb">
                <td colspan="2" style="text-align:right;color:#6b7280;font-size:12px">{{ __('Total') }}</td>
                <td style="text-align:right;color:{{ $total > 0 ? '#1d4ed8' : ($total < 0 ? '#dc2626' : '#9ca3af') }}">
                    {{ ($total > 0 ? '+' : '') . number_format($total, 2, ',', ' ') }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

<a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back to budget') }}</a>
@endsection
