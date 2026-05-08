@extends('layouts.app')
@section('title', __('Invoice').' '.$invoice->no)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    <a href="{{ route('projects.show', $invoice->contract->project) }}"><span>{{ $invoice->contract->project->name }}</span></a>
    <a href="{{ route('contracts.show', $invoice->contract) }}"><span>{{ $invoice->contract->name }}</span></a>
    <span>{{ $invoice->no }}</span>
</div>

<div class="page-header">
    <h1>{{ __('Invoice') }} {{ $invoice->no }}</h1>
    <div style="display:flex;gap:.5rem">
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger">{{ __('Delete') }}</button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <table style="font-size:13px">
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Contract') }}</td><td style="border:none"><a href="{{ route('contracts.show', $invoice->contract) }}">{{ $invoice->contract->name }}</a></td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Sender') }}</td><td style="border:none">{{ $invoice->sender?->name ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Recipient') }}</td><td style="border:none">{{ $invoice->recipient?->name ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Issue date') }}</td><td style="border:none">{{ $invoice->issued?->format('d.m.Y') ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Tax date') }}</td><td style="border:none">{{ $invoice->taxdate?->format('d.m.Y') ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Due date') }}</td><td style="border:none">{{ $invoice->due?->format('d.m.Y') ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Paid') }}</td><td style="border:none">{{ $invoice->paid?->format('d.m.Y') ?? '—' }}</td></tr>
        </table>
    </div>
    <div class="card card-body">
        @php $cls = match($invoice->status) { 2 => 'badge-green', 4 => 'badge-red', 3 => 'badge-yellow', default => 'badge-gray' }; @endphp
        <div style="margin-bottom:.75rem"><span class="badge {{ $cls }}" style="font-size:13px">{{ $invoice->status_label }}</span></div>
        <div style="font-size:13px;color:#6b7280">{{ __('Total amount') }}</div>
        <div style="font-size:2rem;font-weight:700">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->contract->currency }}</div>
        @if($invoice->description)
            <div style="margin-top:1rem;font-size:13px;color:#374151">{{ $invoice->description }}</div>
        @endif
    </div>
</div>

<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Invoice items') }}</h2>
</div>
<div class="card">
    @if($invoice->items->isEmpty())
        <div class="empty" style="padding:1.5rem"><strong>{{ __('No items') }}</strong></div>
    @else
        <table>
            <thead>
                <tr><th>{{ __('Description') }}</th><th style="text-align:right">{{ __('Amount') }} ({{ $invoice->contract->currency }})</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align:right">{{ number_format($item->amount, 2, ',', ' ') }}</td>
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('invoice-items.destroy', $item) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-secondary btn-sm">✕</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb">
                    <td style="font-weight:600">{{ __('Total') }}</td>
                    <td style="text-align:right;font-weight:600">{{ number_format($invoice->total, 2, ',', ' ') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif

    <div style="padding:1rem;border-top:1px solid #f3f4f6">
        <form method="POST" action="{{ route('invoices.items.store', $invoice) }}" style="display:flex;gap:.5rem;align-items:flex-end">
            @csrf
            <div style="flex:1">
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Item description') }}</label>
                <input type="text" name="description" placeholder="{{ __('Item name...') }}" required>
            </div>
            <div style="width:160px">
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Amount') }} ({{ $invoice->contract->currency }})</label>
                <input type="number" name="amount" step="0.01" placeholder="0.00" required>
            </div>
            <button type="submit" class="btn btn-primary" style="white-space:nowrap">{{ __('+ Add') }}</button>
        </form>
    </div>
</div>
@endsection
