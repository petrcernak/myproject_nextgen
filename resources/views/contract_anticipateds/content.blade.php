@extends('layouts.app')
@section('title', __('Edit anticipated') . ' — ' . $contractAnticipated->code)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contractAnticipated->contract) }}"><span>{{ $contractAnticipated->contract->name }}</span></a>
    <a href="{{ route('contract-anticipateds.show', $contractAnticipated) }}"><span>{{ $contractAnticipated->code }}</span></a>
    <span>{{ __('Edit') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Edit items') }}: {{ $contractAnticipated->code }} {{ $contractAnticipated->name }}</h1>
    <a href="{{ route('contract-anticipateds.show', $contractAnticipated) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>

@php $coveredIds = $contractAnticipated->items->pluck('contract_item_id'); @endphp

{{-- Add new item form --}}
<div class="card card-body" style="margin-bottom:1.5rem;max-width:750px">
    <form method="POST" action="{{ route('contracts.anticipateds.items.store', $contractAnticipated) }}">
        @csrf
        <div style="font-weight:600;font-size:14px;margin-bottom:.75rem">{{ __('Add item') }}</div>
        <div class="form-group">
            <label>{{ __('Contract item') }} *</label>
            <select name="contract_item_id" required>
                <option value="">— {{ __('select item') }} —</option>
                @foreach($contractAnticipated->contract->items as $ci)
                    @if(!$coveredIds->contains($ci->id))
                    <option value="{{ $ci->id }}" @selected(old('contract_item_id') == $ci->id)>
                        @if($ci->code)[{{ $ci->code }}] @endif{{ $ci->description }} ({{ number_format($ci->amount, 2, ',', ' ') }})
                    </option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Anticipated change') }} *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" required placeholder="+10 000 or −5 000">
            </div>
            <div class="form-group">
                <label>{{ __('Note') }}</label>
                <input type="text" name="description" value="{{ old('description') }}" placeholder="{{ __('Note') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('+ Add item') }}</button>
    </form>
</div>

{{-- Existing items --}}
<div class="card">
    @if($contractAnticipated->items->isEmpty())
        <div class="empty"><strong>{{ __('No items') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:80px">{{ __('Code') }}</th>
                    <th>{{ __('Contract item') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Contract amount') }}</th>
                    <th style="text-align:right;width:180px">{{ __('Anticipated change') }}</th>
                    <th style="text-align:right;width:160px">{{ __('Expected total') }}</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($contractAnticipated->items as $aItem)
                @php $ci = $aItem->contractItem; @endphp
                @if(isset($editItem) && $editItem->id === $aItem->id)
                <tr style="background:#eff6ff">
                    <td><code style="color:#6b7280;font-size:12px">{{ $ci?->code ?? '—' }}</code></td>
                    <td colspan="3">
                        <form method="POST" action="{{ route('ca-items.update', $aItem) }}" style="display:flex;gap:.75rem;align-items:flex-end;padding:.25rem 0">
                            @csrf @method('PUT')
                            <div class="form-group" style="flex:2;margin-bottom:0">
                                <label style="font-size:12px">{{ __('Anticipated change') }}</label>
                                <input type="number" name="amount" value="{{ old('amount', $aItem->amount) }}" step="0.01" required>
                            </div>
                            <div class="form-group" style="flex:3;margin-bottom:0">
                                <label style="font-size:12px">{{ __('Note') }}</label>
                                <input type="text" name="description" value="{{ old('description', $aItem->description) }}" placeholder="{{ __('Note') }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
                            <a href="{{ route('contract-anticipateds.content', $contractAnticipated) }}" class="btn btn-secondary btn-sm">{{ __('Cancel') }}</a>
                        </form>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                @else
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $ci?->code ?? '—' }}</code></td>
                    <td>
                        {{ $ci?->description ?? '—' }}
                        @if($aItem->description)<div style="font-size:12px;color:#6b7280">{{ $aItem->description }}</div>@endif
                    </td>
                    <td style="text-align:right;color:#6b7280">{{ $ci ? number_format($ci->amount, 2, ',', ' ') : '—' }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $aItem->amount > 0 ? '#dc2626' : ($aItem->amount < 0 ? '#16a34a' : '#6b7280') }}">
                        {{ $aItem->amount >= 0 ? '+' : '' }}{{ number_format($aItem->amount, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:right">
                        {{ $ci ? number_format($ci->amount + $aItem->amount, 2, ',', ' ') : '—' }}
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('ca-items.edit', $aItem) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('ca-items.destroy', $aItem) }}" style="display:inline" onsubmit="return confirm('{{ __('Really delete?') }}')">
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
