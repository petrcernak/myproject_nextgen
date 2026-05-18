@extends('layouts.app')
@section('title', __('Commitments').' — '.$item->description)

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
            {{ $item->category->name }} · {{ $budget->name }}
        </div>
    </div>
</div>

@php
    $fmt   = fn($v) => number_format(round($v), 0, ',', ' ');
    $sgn   = fn($v) => ($v > 0 ? '+' : '').number_format(round($v), 0, ',', ' ');
    $cc    = fn($v) => $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af');

    $grandContracts = 0.0;
    $grandCo        = 0.0;
    $grandAmd       = 0.0;
@endphp

@if($contractItems->isEmpty())
    <div class="card card-body" style="color:#9ca3af;font-size:13px">
        {{ __('No contracts linked to this budget item.') }}
    </div>
@else
<div class="card" style="margin-bottom:1.5rem">
    <table style="font-size:13px">
        <thead>
            <tr>
                <th>{{ __('Contract') }}</th>
                <th>{{ __('Item') }}</th>
                <th style="text-align:right;width:140px">{{ __('Contracts') }}</th>
                <th style="text-align:right;width:140px">{{ __('Change Orders') }}</th>
                <th style="text-align:right;width:140px">{{ __('Amendments') }}</th>
                <th style="text-align:right;width:140px">{{ __('Curr. Comm.') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($contractItems as $ci)
        @php
            $contracts = (float) $ci->amount;
            $coTotal   = (float) $ci->changeOrderItems->sum('amount');
            $amdTotal  = (float) $ci->amendmentItems->sum('amount');
            $currComm  = $contracts + $coTotal + $amdTotal;
            $grandContracts += $contracts;
            $grandCo        += $coTotal;
            $grandAmd       += $amdTotal;
        @endphp
        <tr>
            <td>
                <a href="{{ route('contracts.show', $ci->contract) }}">{{ $ci->contract->name }}</a>
                <span style="font-size:11px;color:#9ca3af;margin-left:.35rem">{{ $ci->contract->currency }}</span>
            </td>
            <td style="font-size:12px">
                @if($ci->code)<code style="color:#6b7280;font-size:11px">{{ $ci->code }}</code> @endif
                {{ $ci->description }}
            </td>
            <td style="text-align:right;font-weight:600">{{ $fmt($contracts) }}</td>
            <td style="text-align:right;font-weight:{{ $coTotal != 0 ? '600' : '400' }};color:{{ $cc($coTotal) }}">
                {{ $coTotal != 0 ? $sgn($coTotal) : '—' }}
            </td>
            <td style="text-align:right;font-weight:{{ $amdTotal != 0 ? '600' : '400' }};color:{{ $cc($amdTotal) }}">
                {{ $amdTotal != 0 ? $sgn($amdTotal) : '—' }}
            </td>
            <td style="text-align:right;font-weight:700">{{ $fmt($currComm) }}</td>
        </tr>

        {{-- Change order detail rows --}}
        @foreach($ci->changeOrderItems as $co)
        <tr style="background:#fafafa">
            <td style="padding-left:1.5rem;color:#6b7280;font-size:12px">
                <a href="{{ route('change-orders.show', $co->changeOrder) }}" style="color:#6b7280">
                    {{ $co->changeOrder->name ?? $co->changeOrder->code ?? __('Change order') }}
                </a>
            </td>
            <td style="font-size:11px;color:#9ca3af">{{ __('Change order') }}</td>
            <td></td>
            <td style="text-align:right;font-size:12px;color:{{ $cc((float)$co->amount) }}">
                {{ $sgn((float)$co->amount) }}
            </td>
            <td></td>
            <td></td>
        </tr>
        @endforeach

        {{-- Amendment detail rows --}}
        @foreach($ci->amendmentItems as $ai)
        <tr style="background:#fafafa">
            <td style="padding-left:1.5rem;color:#6b7280;font-size:12px">
                <a href="{{ route('amendments.show', $ai->amendment) }}" style="color:#6b7280">
                    {{ $ai->amendment->name ?? $ai->amendment->code ?? __('Amendment') }}
                </a>
            </td>
            <td style="font-size:11px;color:#9ca3af">{{ __('Amendment') }}</td>
            <td></td>
            <td></td>
            <td style="text-align:right;font-size:12px;color:{{ $cc((float)$ai->amount) }}">
                {{ $sgn((float)$ai->amount) }}
            </td>
            <td></td>
        </tr>
        @endforeach

        @endforeach
        </tbody>
        <tfoot>
            @php $grandTotal = $grandContracts + $grandCo + $grandAmd; @endphp
            <tr style="font-weight:700;background:#f9fafb">
                <td colspan="2" style="text-align:right;color:#6b7280;font-size:12px">{{ __('Total') }}</td>
                <td style="text-align:right">{{ $fmt($grandContracts) }}</td>
                <td style="text-align:right;color:{{ $cc($grandCo) }}">{{ $grandCo != 0 ? $sgn($grandCo) : '—' }}</td>
                <td style="text-align:right;color:{{ $cc($grandAmd) }}">{{ $grandAmd != 0 ? $sgn($grandAmd) : '—' }}</td>
                <td style="text-align:right;font-size:14px">{{ $fmt($grandTotal) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

<a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
@endsection
