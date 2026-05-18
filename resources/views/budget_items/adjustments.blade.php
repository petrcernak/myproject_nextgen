@extends('layouts.app')
@section('title', __('Adjustments').' — '.$item->description)

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
            {{ $item->category->name }} · {{ $budget->name }} ({{ $budget->currency }}) · {{ __('Adjustments') }}
        </div>
    </div>
    <a href="{{ route('budgets.adjustments.index', $budget) }}" class="btn btn-secondary">{{ __('All adjustments') }}</a>
</div>

<div class="card" style="margin-bottom:1.5rem">
    @if($adjItems->isEmpty())
        <div style="padding:.75rem 1rem;font-size:13px;color:#9ca3af">{{ __('No adjustments for this item.') }}</div>
    @else
    <table style="font-size:12px">
        <thead>
            <tr>
                <th style="width:110px">{{ __('Date') }}</th>
                <th>{{ __('Adjustment') }}</th>
                <th style="text-align:right;width:160px">{{ __('Amount') }} ({{ $budget->currency }})</th>
            </tr>
        </thead>
        <tbody>
        @foreach($adjItems as $ai)
        @php $adj = $ai->adjustment; @endphp
        <tr>
            <td style="color:#6b7280">{{ $adj->date?->format('d.m.Y') ?? '—' }}</td>
            <td><a href="{{ route('budget-adjustments.show', $adj) }}" style="color:inherit">{{ $adj->description }}</a></td>
            <td style="text-align:right;font-weight:600;color:{{ $ai->amount > 0 ? '#1d4ed8' : ($ai->amount < 0 ? '#dc2626' : '#9ca3af') }}">
                {{ $ai->amount != 0 ? ($ai->amount > 0 ? '+' : '').number_format(round($ai->amount), 0, ',', ' ') : '—' }}
            </td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:700;background:#f9fafb">
                <td colspan="2" style="text-align:right;font-size:12px;color:#6b7280">{{ __('Total') }}</td>
                <td style="text-align:right;color:{{ $total > 0 ? '#1d4ed8' : ($total < 0 ? '#dc2626' : '#9ca3af') }}">
                    {{ $total != 0 ? ($total > 0 ? '+' : '').number_format(round($total), 0, ',', ' ') : '—' }}
                </td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>

<a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
@endsection
