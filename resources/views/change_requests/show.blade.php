@extends('layouts.app')
@section('title', $changeRequest->code . ' — ' . $changeRequest->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $changeRequest->contract) }}"><span>{{ $changeRequest->contract->name }}</span></a>
    <span>{{ $changeRequest->code }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $changeRequest->name }} <code style="font-size:.8em;color:#6b7280">{{ $changeRequest->code }}</code></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem;display:flex;align-items:center;gap:.5rem">
            {{ __('Change request') }} · {{ $changeRequest->date?->format('d.m.Y') }}
            @if($changeRequest->note) · {{ $changeRequest->note }}@endif
            <span class="badge {{ $changeRequest->status_badge_class }}">{{ $changeRequest->status_label }}</span>
        </div>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            @if(!in_array($changeRequest->status, ['converted', 'rejected']))
                <form method="POST" action="{{ route('change-requests.convert', $changeRequest) }}"
                      onsubmit="return confirm('{{ __('Convert this change request to a change order?') }}')">
                    @csrf
                    <button class="btn btn-secondary">{{ __('Convert to CO') }}</button>
                </form>
            @else
                @if($changeRequest->convertedChangeOrder)
                    <a href="{{ route('change-orders.show', $changeRequest->convertedChangeOrder) }}" class="btn btn-secondary">
                        {{ __('→ CO') }}: {{ $changeRequest->convertedChangeOrder->code }}
                    </a>
                @endif
            @endif
            <a href="{{ route('change-requests.content', $changeRequest) }}" class="btn btn-primary">{{ __('Edit items') }}</a>
            <a href="{{ route('change-requests.edit', $changeRequest) }}" class="btn btn-secondary">{{ __('Settings') }}</a>
            <form method="POST" action="{{ route('change-requests.destroy', $changeRequest) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">{{ __('Delete') }}</button>
            </form>
        @else
            @if($changeRequest->status === 'converted' && $changeRequest->convertedChangeOrder)
                <a href="{{ route('change-orders.show', $changeRequest->convertedChangeOrder) }}" class="btn btn-secondary">
                    {{ __('→ CO') }}: {{ $changeRequest->convertedChangeOrder->code }}
                </a>
            @endif
        @endif
    </div>
</div>

{{-- Summary --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Supplier total') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ number_format($changeRequest->total_supplier, 2, ',', ' ') }} {{ $changeRequest->contract->currency }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:.3rem">{{ __('latest revision per item') }}</div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('PM total') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ number_format($changeRequest->total_pm, 2, ',', ' ') }} {{ $changeRequest->contract->currency }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:.3rem">{{ __('latest revision per item') }}</div>
    </div>
    <div class="card card-body" style="border:2px solid #ca8a04">
        <div style="font-size:11px;color:#ca8a04;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Assumed') }}</div>
        <div style="font-size:1.4rem;font-weight:700;color:#ca8a04">{{ number_format($changeRequest->total_report, 2, ',', ' ') }} {{ $changeRequest->contract->currency }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:.3rem">
            @if($changeRequest->countsInReport())
                <span style="color:#2563eb;font-weight:600">{{ __('Counts in report') }}</span>
            @else
                <span style="color:#9ca3af">{{ __('Not counted') }} ({{ $changeRequest->status_label }})</span>
            @endif
        </div>
    </div>
</div>

{{-- Items with full revision history --}}
@if($changeRequest->items->isEmpty())
<div class="card card-body" style="color:#6b7280">
    <strong>{{ __('No items') }}</strong>
    @if($canEdit)<p><a href="{{ route('change-requests.content', $changeRequest) }}">{{ __('Go to edit mode') }}</a></p>@endif
</div>
@else

@foreach($changeRequest->items as $item)
@php $latestRev = $item->latestRevision; @endphp
<div class="card" style="margin-bottom:1.25rem">
    {{-- Item header --}}
    <div style="padding:.6rem 1rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;background:#f9fafb">
        <div>
            @if($item->contractItem?->code)
                <code style="color:#6b7280;font-size:12px;margin-right:.5rem">{{ $item->contractItem->code }}</code>
            @endif
            <strong>{{ $item->contractItem?->description ?? '—' }}</strong>
            @if($item->description)
                <span style="font-size:12px;color:#6b7280;margin-left:.5rem">— {{ $item->description }}</span>
            @endif
        </div>
        <div style="font-size:12px;color:#6b7280">
            {{ $item->revisions->count() }} {{ __('revisions') }}
        </div>
    </div>

    @if($item->revisions->isEmpty())
        <div style="padding:.75rem 1rem;color:#9ca3af;font-size:13px">{{ __('No revisions yet') }}</div>
    @else
    <table style="font-size:13px">
        <thead>
            <tr style="background:#f9fafb">
                <th style="width:110px">{{ __('Date') }}</th>
                <th style="text-align:right;width:150px">{{ __('Supplier') }}</th>
                <th style="text-align:right;width:150px">{{ __('PM') }}</th>
                <th style="text-align:right;width:150px;background:#fef9c3">{{ __('Assumed') }}</th>
                <th>{{ __('Note') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->revisions as $rev)
            @php $isLatest = $latestRev && $rev->id === $latestRev->id; @endphp
            <tr @if($isLatest) style="font-weight:600;background:#f0f9ff" @endif>
                <td>
                    {{ $rev->date?->format('d.m.Y') }}
                    @if($isLatest)
                        <span style="font-size:10px;background:#2563eb;color:#fff;border-radius:3px;padding:1px 5px;margin-left:.3rem">{{ __('latest') }}</span>
                    @endif
                </td>
                <td style="text-align:right;color:{{ $isLatest ? 'inherit' : '#6b7280' }}">
                    {{ number_format($rev->amount_supplier, 2, ',', ' ') }}
                </td>
                <td style="text-align:right;color:{{ $isLatest ? 'inherit' : '#6b7280' }}">
                    {{ number_format($rev->amount_pm, 2, ',', ' ') }}
                </td>
                <td style="text-align:right;background:#fef9c3;color:{{ $isLatest ? '#2563eb' : '#6b7280' }}">
                    {{ number_format($rev->amount_report, 2, ',', ' ') }}
                </td>
                <td style="color:#6b7280">{{ $rev->note }}</td>
            </tr>
            @endforeach
        </tbody>
        @if($item->revisions->count() > 1)
        {{-- Difference between oldest and latest --}}
        @php
            $oldest = $item->revisions->last();
            $diffS  = $latestRev->amount_supplier - $oldest->amount_supplier;
            $diffPm = $latestRev->amount_pm       - $oldest->amount_pm;
            $diffR  = $latestRev->amount_report   - $oldest->amount_report;
        @endphp
        <tfoot>
            <tr style="font-size:11px;color:#6b7280;border-top:1px dashed #e5e7eb">
                <td style="padding:.25rem .75rem">{{ __('Change vs. first') }}</td>
                <td style="text-align:right;color:{{ $diffS >= 0 ? '#1d4ed8' : '#dc2626' }}">
                    {{ $diffS >= 0 ? '+' : '' }}{{ number_format($diffS, 2, ',', ' ') }}
                </td>
                <td style="text-align:right;color:{{ $diffPm >= 0 ? '#1d4ed8' : '#dc2626' }}">
                    {{ $diffPm >= 0 ? '+' : '' }}{{ number_format($diffPm, 2, ',', ' ') }}
                </td>
                <td style="text-align:right;background:#fef9c3;color:{{ $diffR >= 0 ? '#1d4ed8' : '#dc2626' }}">
                    {{ $diffR >= 0 ? '+' : '' }}{{ number_format($diffR, 2, ',', ' ') }}
                </td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
    @endif
</div>
@endforeach

@endif
@endsection
