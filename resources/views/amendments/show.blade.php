@extends('layouts.app')
@section('title', $amendment->code . ' — ' . $amendment->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $amendment->contract) }}"><span>{{ $amendment->contract->name }}</span></a>
    <span>{{ $amendment->code }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $amendment->name }} <code style="font-size:.8em;color:#6b7280">{{ $amendment->code }}</code></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ __('Amendment') }} · {{ $amendment->date?->format('d.m.Y') }}
        </div>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('amendments.content', $amendment) }}" class="btn btn-primary">{{ __('Edit items') }}</a>
            <a href="{{ route('amendments.edit', $amendment) }}" class="btn btn-secondary">{{ __('Settings') }}</a>
            <form method="POST" action="{{ route('amendments.destroy', $amendment) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">{{ __('Delete') }}</button>
            </form>
        @endif
    </div>
</div>

{{-- Summary --}}
@php
    $directTotal = $amendment->items->sum('amount');
    $coTotal     = $amendment->changeOrders->sum('total');
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Direct item changes') }}</div>
        <div style="font-size:1.4rem;font-weight:700;color:{{ $directTotal >= 0 ? 'inherit' : '#dc2626' }}">
            {{ $directTotal >= 0 ? '+' : '' }}{{ number_format($directTotal, 2, ',', ' ') }} {{ $amendment->contract->currency }}
        </div>
        <div style="font-size:12px;color:#6b7280;margin-top:.3rem">{{ $amendment->items->count() }} {{ __('items') }}</div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Assigned COs') }}</div>
        <div style="font-size:1.4rem;font-weight:700;color:{{ $coTotal >= 0 ? 'inherit' : '#dc2626' }}">
            {{ $coTotal >= 0 ? '+' : '' }}{{ number_format($coTotal, 2, ',', ' ') }} {{ $amendment->contract->currency }}
        </div>
        <div style="font-size:12px;color:#6b7280;margin-top:.3rem">{{ $amendment->changeOrders->count() }} {{ __('change orders') }}</div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Amendment value') }}</div>
        <div style="font-size:1.4rem;font-weight:700;color:{{ $amendment->total >= 0 ? '#1d4ed8' : '#dc2626' }}">
            {{ $amendment->total >= 0 ? '+' : '' }}{{ number_format($amendment->total, 2, ',', ' ') }} {{ $amendment->contract->currency }}
        </div>
        @if($amendment->note)
            <div style="font-size:12px;color:#6b7280;margin-top:.3rem">{{ Str::limit($amendment->note, 60) }}</div>
        @endif
    </div>
</div>

{{-- Direct item changes --}}
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Direct item changes') }}</h2>
    @if($canEdit)
        <a href="{{ route('amendments.content', $amendment) }}" class="btn btn-secondary btn-sm">{{ __('Edit items') }}</a>
    @endif
</div>
<div class="card" style="margin-bottom:1.5rem">
    @if($amendment->items->isEmpty())
        <div class="empty">
            <strong>{{ __('No items') }}</strong>
            @if($canEdit)<p><a href="{{ route('amendments.content', $amendment) }}">{{ __('Go to edit mode') }}</a></p>@endif
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
                @foreach($amendment->items as $item)
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
                    <td style="text-align:right;color:{{ $directTotal >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $directTotal >= 0 ? '+' : '' }}{{ number_format($directTotal, 2, ',', ' ') }}
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif
</div>

{{-- Assigned Change Orders --}}
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Assigned change orders') }}</h2>
</div>
<div class="card">
    @if($amendment->changeOrders->isEmpty())
        <div class="empty">
            <strong>{{ __('No change orders') }}</strong>
            <p style="font-size:13px;color:#6b7280">{{ __('Assign change orders by editing them and selecting this amendment.') }}</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th style="text-align:right">{{ __('Items') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Value') }} ({{ $amendment->contract->currency }})</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($amendment->changeOrders as $co)
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $co->code }}</code></td>
                    <td><a href="{{ route('change-orders.show', $co) }}">{{ $co->name }}</a></td>
                    <td>{{ $co->date?->format('d.m.Y') }}</td>
                    <td style="text-align:right">{{ $co->items->count() }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $co->total >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $co->total >= 0 ? '+' : '' }}{{ number_format($co->total, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('change-orders.show', $co) }}" class="btn btn-secondary btn-sm">{{ __('Detail') }}</a>
                    </td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb;font-weight:600">
                    <td colspan="4" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                    <td style="text-align:right;color:{{ $coTotal >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $coTotal >= 0 ? '+' : '' }}{{ number_format($coTotal, 2, ',', ' ') }}
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif
</div>
@endsection
