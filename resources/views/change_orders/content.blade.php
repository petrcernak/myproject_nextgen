@extends('layouts.app')
@section('title', __('Edit change order') . ' — ' . $changeOrder->code)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $changeOrder->contract) }}"><span>{{ $changeOrder->contract->name }}</span></a>
    <a href="{{ route('change-orders.show', $changeOrder) }}"><span>{{ $changeOrder->code }}</span></a>
    <span>{{ __('Edit') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Edit items') }}: {{ $changeOrder->code }} {{ $changeOrder->name }}</h1>
    <a href="{{ route('change-orders.show', $changeOrder) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>

{{-- Add new item form --}}
<div class="card card-body" style="margin-bottom:1.5rem;max-width:750px">
    <form method="POST" action="{{ route('change-orders.items.store', $changeOrder) }}">
        @csrf
        <div style="font-weight:600;font-size:14px;margin-bottom:.75rem">{{ __('Add item') }}</div>
        <div class="form-group">
            <label>{{ __('Contract item') }} *</label>
            <select name="contract_item_id" required>
                <option value="">— {{ __('select item') }} —</option>
                @foreach($changeOrder->contract->items as $ci)
                    <option value="{{ $ci->id }}" @selected(old('contract_item_id') == $ci->id)>
                        @if($ci->code)[{{ $ci->code }}] @endif{{ $ci->description }} ({{ number_format($ci->amount, 2, ',', ' ') }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Change amount') }} *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" required placeholder="+10 000 or −5 000">
            </div>
            <div class="form-group">
                <label>{{ __('Description') }}</label>
                <input type="text" name="description" value="{{ old('description') }}" placeholder="{{ __('Optional note on this change') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('+ Add item') }}</button>
    </form>
</div>

{{-- Existing items --}}
<div class="card">
    @if($changeOrder->items->isEmpty())
        <div class="empty"><strong>{{ __('No items') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:80px">{{ __('Code') }}</th>
                    <th>{{ __('Contract item') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Original') }}</th>
                    <th style="text-align:right;width:130px">{{ __('Change') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($changeOrder->items as $item)
                @if(isset($editItem) && $editItem->id === $item->id)
                <tr style="background:#eff6ff">
                    <td colspan="3">
                        <form method="POST" action="{{ route('co-items.update', $item) }}" style="display:flex;gap:.75rem;align-items:flex-end;padding:.25rem 0">
                            @csrf @method('PUT')
                            <div class="form-group" style="flex:2;margin-bottom:0">
                                <label style="font-size:12px">{{ __('Change amount') }}</label>
                                <input type="number" name="amount" value="{{ old('amount', $item->amount) }}" step="0.01" required>
                            </div>
                            <div class="form-group" style="flex:3;margin-bottom:0">
                                <label style="font-size:12px">{{ __('Description') }}</label>
                                <input type="text" name="description" value="{{ old('description', $item->description) }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
                            <a href="{{ route('change-orders.content', $changeOrder) }}" class="btn btn-secondary btn-sm">{{ __('Cancel') }}</a>
                        </form>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                @else
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $item->contractItem?->code ?? '—' }}</code></td>
                    <td>
                        {{ $item->contractItem?->description ?? '—' }}
                        @if($item->description)<div style="font-size:12px;color:#6b7280">{{ $item->description }}</div>@endif
                    </td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($item->contractItem?->amount ?? 0, 2, ',', ' ') }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $item->amount >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $item->amount >= 0 ? '+' : '' }}{{ number_format($item->amount, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('co-items.edit', $item) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('co-items.destroy', $item) }}" style="display:inline" onsubmit="return confirm('{{ __('Really delete?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
