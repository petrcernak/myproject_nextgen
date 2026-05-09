@extends('layouts.app')
@section('title', $changeOrder->code . ' — ' . $changeOrder->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $changeOrder->contract) }}"><span>{{ $changeOrder->contract->name }}</span></a>
    @if($changeOrder->amendment)
        <a href="{{ route('amendments.show', $changeOrder->amendment) }}"><span>{{ $changeOrder->amendment->code }}</span></a>
    @endif
    <span>{{ $changeOrder->code }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $changeOrder->name }} <code style="font-size:.8em;color:#6b7280">{{ $changeOrder->code }}</code></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ __('Change order') }}
            @if($changeOrder->amendment)
                · <a href="{{ route('amendments.show', $changeOrder->amendment) }}" style="color:#6b7280">{{ $changeOrder->amendment->code }} {{ $changeOrder->amendment->name }}</a>
            @else
                · <span style="color:#92400e">{{ __('Standalone') }}</span>
            @endif
            · {{ $changeOrder->date?->format('d.m.Y') }}
        </div>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('change-orders.content', $changeOrder) }}" class="btn btn-primary">{{ __('Edit items') }}</a>
            <a href="{{ route('change-orders.edit', $changeOrder) }}" class="btn btn-secondary">{{ __('Settings') }}</a>
            <form method="POST" action="{{ route('change-orders.destroy', $changeOrder) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">{{ __('Delete') }}</button>
            </form>
        @endif
    </div>
</div>

{{-- Summary --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;max-width:600px">
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Change order value') }}</div>
        <div style="font-size:1.6rem;font-weight:700;color:{{ $changeOrder->total >= 0 ? 'inherit' : '#dc2626' }}">
            {{ $changeOrder->total >= 0 ? '+' : '' }}{{ number_format($changeOrder->total, 2, ',', ' ') }} {{ $changeOrder->contract->currency }}
        </div>
        <div style="font-size:12px;color:#6b7280;margin-top:.4rem">{{ $changeOrder->items->count() }} {{ __('items') }}</div>
    </div>
    @if($changeOrder->note)
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Note') }}</div>
        <div style="font-size:13px">{{ $changeOrder->note }}</div>
    </div>
    @endif
</div>

{{-- Items --}}
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Change order items') }}</h2>
    @if($canEdit)
        <a href="{{ route('change-orders.content', $changeOrder) }}" class="btn btn-secondary btn-sm">{{ __('Edit items') }}</a>
    @endif
</div>
<div class="card">
    @if($changeOrder->items->isEmpty())
        <div class="empty">
            <strong>{{ __('No items') }}</strong>
            @if($canEdit)<p><a href="{{ route('change-orders.content', $changeOrder) }}">{{ __('Go to edit mode') }}</a></p>@endif
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:80px">{{ __('Code') }}</th>
                    <th>{{ __('Contract item') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Original amount') }}</th>
                    <th style="text-align:right;width:130px">{{ __('Change') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Effective amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($changeOrder->items as $item)
                @php
                    $orig = $item->contractItem?->amount ?? 0;
                    $eff  = $orig + $item->amount;
                @endphp
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $item->contractItem?->code ?? '—' }}</code></td>
                    <td>
                        {{ $item->contractItem?->description ?? '—' }}
                        @if($item->description)
                            <div style="font-size:12px;color:#6b7280">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($orig, 2, ',', ' ') }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $item->amount >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $item->amount >= 0 ? '+' : '' }}{{ number_format($item->amount, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:right;font-weight:600">{{ number_format($eff, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb;font-weight:600">
                    <td colspan="3" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total change') }}</td>
                    <td style="text-align:right;color:{{ $changeOrder->total >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $changeOrder->total >= 0 ? '+' : '' }}{{ number_format($changeOrder->total, 2, ',', ' ') }}
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif
</div>
@endsection
