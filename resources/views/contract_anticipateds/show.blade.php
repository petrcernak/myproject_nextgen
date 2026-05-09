@extends('layouts.app')
@section('title', $contractAnticipated->code . ' — ' . $contractAnticipated->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contractAnticipated->contract) }}"><span>{{ $contractAnticipated->contract->name }}</span></a>
    <span>{{ $contractAnticipated->code }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $contractAnticipated->name }} <code style="font-size:.8em;color:#6b7280">{{ $contractAnticipated->code }}</code></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ __('Contract anticipated') }} · {{ $contractAnticipated->date?->format('d.m.Y') }}
            @if($contractAnticipated->note) · {{ $contractAnticipated->note }}@endif
        </div>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contract-anticipateds.content', $contractAnticipated) }}" class="btn btn-primary">{{ __('Edit items') }}</a>
            <a href="{{ route('contract-anticipateds.edit', $contractAnticipated) }}" class="btn btn-secondary">{{ __('Settings') }}</a>
            <form method="POST" action="{{ route('contract-anticipateds.destroy', $contractAnticipated) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">{{ __('Delete') }}</button>
            </form>
        @endif
    </div>
</div>

@php
    $contractTotal    = $contractAnticipated->contract->revised_total;
    $anticipatedDelta = $contractAnticipated->total;   // sum of all CA item amounts (deltas)
    $coveredItems     = $contractAnticipated->items->pluck('contract_item_id');
    $totalItems       = $contractAnticipated->contract->items->count();
    $coveredCount     = $coveredItems->count();
    $hasItems         = $coveredCount > 0;
    $expectedTotal    = $contractTotal + $anticipatedDelta;
@endphp

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Contract value') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ number_format($contractTotal, 2, ',', ' ') }} {{ $contractAnticipated->contract->currency }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:.3rem">{{ $totalItems }} {{ __('items') }}</div>
    </div>
    <div class="card card-body" style="border:2px solid {{ !$hasItems ? '#e5e7eb' : ($anticipatedDelta > 0 ? '#dc2626' : ($anticipatedDelta < 0 ? '#22c55e' : '#e5e7eb')) }}">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem;color:{{ !$hasItems ? '#9ca3af' : ($anticipatedDelta > 0 ? '#dc2626' : ($anticipatedDelta < 0 ? '#16a34a' : '#6b7280')) }}">
            {{ __('Anticipated change') }}
            @if($hasItems && $coveredCount < $totalItems)
                <span style="font-size:10px;font-weight:400"> ({{ $coveredCount }}/{{ $totalItems }})</span>
            @endif
        </div>
        @if(!$hasItems)
            <div style="font-size:1.4rem;font-weight:700;color:#9ca3af">—</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:.3rem">{{ __('No items entered yet') }}</div>
        @else
            <div style="font-size:1.4rem;font-weight:700;color:{{ $anticipatedDelta > 0 ? '#dc2626' : ($anticipatedDelta < 0 ? '#16a34a' : '#6b7280') }}">
                {{ $anticipatedDelta >= 0 ? '+' : '' }}{{ number_format($anticipatedDelta, 2, ',', ' ') }} {{ $contractAnticipated->contract->currency }}
            </div>
            <div style="font-size:12px;margin-top:.3rem;color:{{ $anticipatedDelta > 0 ? '#dc2626' : ($anticipatedDelta < 0 ? '#16a34a' : '#6b7280') }}">
                {{ $anticipatedDelta > 0 ? __('Cost overrun anticipated') : ($anticipatedDelta < 0 ? __('Saving anticipated') : __('On budget')) }}
            </div>
        @endif
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Expected total') }}</div>
        @if(!$hasItems)
            <div style="font-size:1.4rem;font-weight:700;color:#9ca3af">—</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:.3rem">{{ __('No items entered yet') }}</div>
        @else
            <div style="font-size:1.4rem;font-weight:700">{{ number_format($expectedTotal, 2, ',', ' ') }} {{ $contractAnticipated->contract->currency }}</div>
            @if($coveredCount < $totalItems)
                <div style="font-size:12px;color:#9ca3af;margin-top:.3rem">{{ __('covered items only') }}</div>
            @else
                <div style="font-size:12px;color:#6b7280;margin-top:.3rem">&nbsp;</div>
            @endif
        @endif
    </div>
</div>

{{-- Items table --}}
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Anticipated costs per item') }}</h2>
    @if($canEdit)
        <a href="{{ route('contract-anticipateds.content', $contractAnticipated) }}" class="btn btn-secondary btn-sm">{{ __('Edit items') }}</a>
    @endif
</div>
<div class="card">
    @if($contractAnticipated->items->isEmpty())
        <div class="empty">
            <strong>{{ __('No items') }}</strong>
            @if($canEdit)<p><a href="{{ route('contract-anticipateds.content', $contractAnticipated) }}">{{ __('Go to edit mode') }}</a></p>@endif
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:80px">{{ __('Code') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Effective amount') }}</th>
                    <th style="text-align:right;width:160px">{{ __('Anticipated change') }}</th>
                    <th style="text-align:right;width:160px">{{ __('Expected total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contractAnticipated->items as $aItem)
                @php
                    $ci              = $aItem->contractItem;
                    $delta           = $aItem->amount;
                    $effectiveAmount = $ci?->effective_amount ?? 0;
                    $expected        = $effectiveAmount + $delta;
                @endphp
                <tr>
                    <td><code style="font-size:12px;color:#6b7280">{{ $ci?->code ?? '—' }}</code></td>
                    <td>
                        {{ $ci?->description ?? '—' }}
                        @if($aItem->description)
                            <div style="font-size:12px;color:#6b7280">{{ $aItem->description }}</div>
                        @endif
                    </td>
                    <td style="text-align:right">{{ number_format($effectiveAmount, 2, ',', ' ') }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $delta > 0 ? '#dc2626' : ($delta < 0 ? '#16a34a' : '#6b7280') }}">
                        {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:right;font-weight:600">{{ number_format($expected, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb;font-weight:600">
                    <td colspan="2" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">
                        {{ __('Total') }}
                        @if($coveredCount < $totalItems)
                            <span style="font-weight:400;color:#9ca3af"> ({{ $coveredCount }}/{{ $totalItems }})</span>
                        @endif
                    </td>
                    <td style="text-align:right">{{ $hasItems ? number_format($contractAnticipated->contract->items->whereIn('id', $coveredItems->all())->sum('effective_amount'), 2, ',', ' ') : '—' }}</td>
                    <td style="text-align:right;color:{{ $anticipatedDelta > 0 ? '#dc2626' : ($anticipatedDelta < 0 ? '#16a34a' : '#6b7280') }}">
                        {{ $hasItems ? ($anticipatedDelta >= 0 ? '+' : '') . number_format($anticipatedDelta, 2, ',', ' ') : '—' }}
                    </td>
                    <td style="text-align:right">{{ $hasItems ? number_format($expectedTotal, 2, ',', ' ') : '—' }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</div>
@endsection
