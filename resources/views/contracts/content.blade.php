@extends('layouts.app')
@section('title', __('Edit items').' — '.$contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a>
    <span>{{ __('Edit items') }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $contract->name }} <code style="font-size:.8em;color:#6b7280">{{ $contract->code }}</code></h1>
        <div style="font-size:12px;color:#f59e0b;margin-top:.2rem;font-weight:600">✎ {{ __('Edit mode') }}</div>
    </div>
    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>

<div class="card" style="margin-bottom:1.25rem;padding:1rem;border:2px dashed #e5e7eb">
    <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.6rem">{{ __('Add item') }}</div>
    <form method="POST" action="{{ route('contracts.items.store', $contract) }}" style="display:flex;gap:.5rem;align-items:flex-end">
        @csrf
        <div style="width:90px">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Code') }}</label>
            <input type="text" name="code" value="{{ str_pad($contract->items->count() + 1, 2, '0', STR_PAD_LEFT) }}" maxlength="50">
        </div>
        <div style="flex:1">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Description') }} *</label>
            <input type="text" name="description" placeholder="{{ __('Item description...') }}" required>
        </div>
        <div style="width:180px">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Amount') }} ({{ $contract->currency }}) *</label>
            <input type="number" name="amount" step="0.01" placeholder="0.00" required>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('+ Add item') }}</button>
    </form>
</div>

<div class="card" style="border-left:3px solid #2563eb">
    @if($contract->items->isEmpty())
        <div class="empty" style="padding:1.5rem">
            <strong>{{ __('No items') }}</strong>
            <p>{{ __('Add the first item using the form above.') }}</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:90px">{{ __('Code') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="text-align:right;width:180px">{{ __('Amount') }} ({{ $contract->currency }})</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($contract->items as $item)
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $item->code ?? '—' }}</code></td>
                    <td>{{ $item->description }}</td>
                    <td style="text-align:right">{{ number_format($item->amount, 2, ',', ' ') }}</td>
                    <td>
                        <div style="display:flex;gap:.3rem;justify-content:flex-end">
                            <a href="{{ route('contract-items.edit', $item) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('contract-items.destroy', $item) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb">
                    <td colspan="2" style="text-align:right;color:#6b7280;font-size:12px">{{ __('Total') }}</td>
                    <td style="text-align:right;font-weight:600">{{ number_format($contract->total, 2, ',', ' ') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif
</div>

<div style="margin-top:1rem">
    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>
@endsection
