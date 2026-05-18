@extends('layouts.app')
@section('title', __('Transfers').' — '.$item->description)

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
            {{ $item->category->name }} · {{ $budget->name }} ({{ $budget->currency }}) · {{ __('Transfers') }}
        </div>
    </div>
    <a href="{{ route('budgets.transfers.index', $budget) }}" class="btn btn-secondary">{{ __('All transfers') }}</a>
</div>

@php
    $fmt  = fn($v) => number_format(round(abs($v)), 0, ',', ' ');
    $sgn  = fn($v) => ($v > 0 ? '+' : '').number_format(round($v), 0, ',', ' ');
    $cc   = fn($v) => $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af');
    $transfersIn  = $rows->where('dir', 'in');
    $transfersOut = $rows->where('dir', 'out');
    $totalIn  = $transfersIn->sum('amount');
    $totalOut = $transfersOut->sum('amount');
@endphp

{{-- Transfers IN --}}
<h2 style="font-size:.9rem;font-weight:600;margin-bottom:.5rem;color:#166534">{{ __('Transfers IN') }} <span style="font-size:.8em;font-weight:400;color:#6b7280">({{ __('to this item') }})</span></h2>
<div class="card" style="margin-bottom:1.5rem">
    @if($transfersIn->isEmpty())
        <div style="padding:.75rem 1rem;font-size:13px;color:#9ca3af">{{ __('No incoming transfers.') }}</div>
    @else
    <table style="font-size:12px">
        <thead>
            <tr>
                <th style="width:110px">{{ __('Date') }}</th>
                <th>{{ __('Description') }}</th>
                <th>{{ __('From item') }}</th>
                <th style="text-align:right;width:150px">{{ __('Amount') }} ({{ $budget->currency }})</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transfersIn->sortByDesc(fn($r) => $r['transfer']->date) as $row)
        @php $tr = $row['transfer']; @endphp
        <tr>
            <td style="color:#6b7280">{{ $tr->date->format('d.m.Y') }}</td>
            <td><a href="{{ route('budget-transfers.show', $tr) }}" style="color:inherit">{{ $tr->description }}</a></td>
            <td style="color:#6b7280;font-size:11px">
                <code style="font-size:10px;color:#9ca3af">{{ $tr->fromItem->code ?? '' }}</code>
                {{ $tr->fromItem->description }}
                <span style="color:#d1d5db">· {{ $tr->fromItem->category?->name }}</span>
            </td>
            <td style="text-align:right;font-weight:600;color:#1d4ed8">+{{ $fmt($tr->amount) }}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:700;background:#f9fafb">
                <td colspan="3" style="text-align:right;font-size:12px;color:#6b7280">{{ __('Total IN') }}</td>
                <td style="text-align:right;color:{{ $cc($totalIn) }}">{{ $totalIn != 0 ? $sgn($totalIn) : '—' }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>

{{-- Transfers OUT --}}
<h2 style="font-size:.9rem;font-weight:600;margin-bottom:.5rem;color:#991b1b">{{ __('Transfers OUT') }} <span style="font-size:.8em;font-weight:400;color:#6b7280">({{ __('from this item') }})</span></h2>
<div class="card" style="margin-bottom:1.5rem">
    @if($transfersOut->isEmpty())
        <div style="padding:.75rem 1rem;font-size:13px;color:#9ca3af">{{ __('No outgoing transfers.') }}</div>
    @else
    <table style="font-size:12px">
        <thead>
            <tr>
                <th style="width:110px">{{ __('Date') }}</th>
                <th>{{ __('Description') }}</th>
                <th>{{ __('To item') }}</th>
                <th style="text-align:right;width:150px">{{ __('Amount') }} ({{ $budget->currency }})</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transfersOut->sortByDesc(fn($r) => $r['transfer']->date) as $row)
        @php $tr = $row['transfer']; @endphp
        <tr>
            <td style="color:#6b7280">{{ $tr->date->format('d.m.Y') }}</td>
            <td><a href="{{ route('budget-transfers.show', $tr) }}" style="color:inherit">{{ $tr->description }}</a></td>
            <td style="color:#6b7280;font-size:11px">
                <code style="font-size:10px;color:#9ca3af">{{ $tr->toItem->code ?? '' }}</code>
                {{ $tr->toItem->description }}
                <span style="color:#d1d5db">· {{ $tr->toItem->category?->name }}</span>
            </td>
            <td style="text-align:right;font-weight:600;color:#dc2626">−{{ $fmt($tr->amount) }}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:700;background:#f9fafb">
                <td colspan="3" style="text-align:right;font-size:12px;color:#6b7280">{{ __('Total OUT') }}</td>
                <td style="text-align:right;color:{{ $cc($totalOut) }}">{{ $totalOut != 0 ? $sgn($totalOut) : '—' }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>

<div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem">
    <span style="font-size:13px;font-weight:600;color:#374151">{{ __('Net') }}:</span>
    <span style="font-size:16px;font-weight:700;color:{{ $net > 0 ? '#1d4ed8' : ($net < 0 ? '#dc2626' : '#9ca3af') }}">
        {{ $net != 0 ? ($net > 0 ? '+' : '').number_format(round($net), 0, ',', ' ').' '.$budget->currency : '—' }}
    </span>
</div>

<a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
@endsection
