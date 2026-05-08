@extends('layouts.app')
@section('title', $contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <span>{{ $contract->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $contract->name }} <code style="font-size:.8em;color:#6b7280">{{ $contract->code }}</code></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ $contract->direction === 1 ? __('Income') : __('Expense') }} · {{ $contract->currency }}
            @if($contract->date) · {{ $contract->date->format('d.m.Y') }} @endif
        </div>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contracts.content', $contract) }}" class="btn btn-primary">{{ __('Edit items') }}</a>
            <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-secondary">{{ __('Settings') }}</a>
            @if($contract->isDeletable())
                <form method="POST" action="{{ route('contracts.destroy', $contract) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">{{ __('Delete') }}</button>
                </form>
            @endif
        @endif
    </div>
</div>

{{-- Summary --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <table style="font-size:13px">
            <tr><td style="color:#6b7280;border:none;padding:.25rem .5rem .25rem 0">{{ __('Company') }}</td><td style="border:none">{{ $contract->company?->name ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.25rem .5rem .25rem 0">{{ __('Maturity') }}</td><td style="border:none">{{ $contract->maturity }} {{ __('days') }}</td></tr>
            @if($contract->description)
            <tr><td style="color:#6b7280;border:none;padding:.25rem .5rem;vertical-align:top">{{ __('Description') }}</td><td style="border:none">{{ $contract->description }}</td></tr>
            @endif
        </table>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Contract value') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($contract->total, 2, ',', ' ') }} {{ $contract->currency }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:.4rem">{{ $contract->items->count() }} {{ __('items') }}</div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Invoiced') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($contract->invoiced, 2, ',', ' ') }} {{ $contract->currency }}</div>
        @php $pct = $contract->total > 0 ? round($contract->invoiced / $contract->total * 100) : 0; @endphp
        <div style="margin-top:.5rem;height:4px;background:#e5e7eb;border-radius:2px">
            <div style="height:4px;background:#2563eb;border-radius:2px;width:{{ min($pct,100) }}%"></div>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:.3rem">{{ $pct }} %</div>
    </div>
</div>

{{-- Items read-only --}}
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Contract items') }}</h2>
    @if($canEdit)
        <a href="{{ route('contracts.content', $contract) }}" class="btn btn-secondary btn-sm">{{ __('Edit items') }}</a>
    @endif
</div>
<div class="card" style="margin-bottom:1.5rem">
    @if($contract->items->isEmpty())
        <div class="empty" style="padding:1.5rem">
            <strong>{{ __('No items') }}</strong>
            @if($canEdit)<p><a href="{{ route('contracts.content', $contract) }}">{{ __('Go to edit mode') }}</a></p>@endif
        </div>
    @else
        <table>
            <thead>
                <tr><th style="width:90px">{{ __('Code') }}</th><th>{{ __('Description') }}</th><th style="text-align:right">{{ __('Amount') }} ({{ $contract->currency }})</th></tr>
            </thead>
            <tbody>
                @foreach($contract->items as $item)
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $item->code ?? '—' }}</code></td>
                    <td>{{ $item->description }}</td>
                    <td style="text-align:right">{{ number_format($item->amount, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb;font-weight:600">
                    <td colspan="2" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                    <td style="text-align:right">{{ number_format($contract->total, 2, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</div>

{{-- Invoices --}}
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Invoices') }}</h2>
    @if($canEdit)
        <a href="{{ route('contracts.invoices.create', $contract) }}" class="btn btn-primary btn-sm">+ {{ __('New invoice') }}</a>
    @endif
</div>
<div class="card">
    @if($contract->invoices->isEmpty())
        <div class="empty"><strong>{{ __('No invoices') }}</strong></div>
    @else
        <table>
            <thead>
                <tr><th>{{ __('Number') }}</th><th>{{ __('Description') }}</th><th>{{ __('Issued') }}</th><th>{{ __('Due') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Status') }}</th><th style="text-align:right">{{ __('Amount') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($contract->invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->no }}</a></td>
                    <td style="color:#6b7280;font-size:13px">{{ Str::limit($invoice->description, 45) }}</td>
                    <td>{{ $invoice->issued?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $invoice->due?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $invoice->paid?->format('d.m.Y') ?? '—' }}</td>
                    <td>
                        @php $cls = match($invoice->status) { 2 => 'badge-green', 4 => 'badge-red', 3 => 'badge-yellow', default => 'badge-gray' }; @endphp
                        <span class="badge {{ $cls }}">{{ $invoice->status_label }}</span>
                    </td>
                    <td style="text-align:right">{{ number_format($invoice->total, 2, ',', ' ') }}</td>
                    <td style="text-align:right">
                        @if($canEdit)
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                        @else
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary btn-sm">{{ __('Detail') }}</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
