@extends('layouts.app')
@section('title', __('Edit change request') . ' — ' . $changeRequest->code)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $changeRequest->contract) }}"><span>{{ $changeRequest->contract->name }}</span></a>
    <a href="{{ route('change-requests.show', $changeRequest) }}"><span>{{ $changeRequest->code }}</span></a>
    <span>{{ __('Edit') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Edit items') }}: {{ $changeRequest->code }} {{ $changeRequest->name }}</h1>
    <a href="{{ route('change-requests.show', $changeRequest) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>

{{-- Add new contract item to CR --}}
<div class="card card-body" style="margin-bottom:1.5rem;max-width:700px">
    <form method="POST" action="{{ route('change-requests.items.store', $changeRequest) }}">
        @csrf
        <div style="font-weight:600;font-size:14px;margin-bottom:.75rem">{{ __('Add contract item to CR') }}</div>
        <div class="form-row">
            <div class="form-group" style="flex:3">
                <label>{{ __('Contract item') }} *</label>
                <select name="contract_item_id" required>
                    <option value="">— {{ __('select item') }} —</option>
                    @foreach($changeRequest->contract->items as $ci)
                        @php $alreadyAdded = $changeRequest->items->contains('contract_item_id', $ci->id); @endphp
                        <option value="{{ $ci->id }}" @selected(old('contract_item_id') == $ci->id) @disabled($alreadyAdded)>
                            @if($ci->code)[{{ $ci->code }}] @endif{{ $ci->description }}{{ $alreadyAdded ? ' ✓' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:2">
                <label>{{ __('Description') }}</label>
                <input type="text" name="description" value="{{ old('description') }}" placeholder="{{ __('Optional') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('+ Add item') }}</button>
    </form>
</div>

{{-- Items with revision history --}}
@foreach($changeRequest->items as $item)
<div class="card" style="margin-bottom:1.5rem">
    {{-- Item header --}}
    <div style="padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
        <div>
            <strong>
                @if($item->contractItem?->code)<code style="color:#6b7280;font-size:12px;margin-right:.5rem">{{ $item->contractItem->code }}</code>@endif
                {{ $item->contractItem?->description ?? '—' }}
            </strong>
            @if($item->description)<span style="font-size:12px;color:#6b7280;margin-left:.5rem">— {{ $item->description }}</span>@endif
        </div>
        <form method="POST" action="{{ route('cr-items.destroy', $item) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
        </form>
    </div>

    {{-- Revision history --}}
    @if($item->revisions->isNotEmpty())
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="width:100px">{{ __('Date') }}</th>
                <th style="text-align:right;width:140px">{{ __('Supplier') }}</th>
                <th style="text-align:right;width:140px">{{ __('PM') }}</th>
                <th style="text-align:right;width:140px;background:#eff6ff">{{ __('Report') }}</th>
                <th>{{ __('Note') }}</th>
                <th style="width:60px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->revisions as $rev)
            @if(isset($editRevision) && $editRevision->id === $rev->id)
            <tr style="background:#fefce8">
                <td colspan="6" style="padding:.5rem .75rem">
                    <form method="POST" action="{{ route('cr-revisions.update', $rev) }}" style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                        @csrf @method('PUT')
                        <div class="form-group" style="margin-bottom:0;width:130px">
                            <label style="font-size:11px">{{ __('Date') }}</label>
                            <input type="date" name="date" value="{{ old('date', $rev->date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;width:130px">
                            <label style="font-size:11px">{{ __('Supplier') }}</label>
                            <input type="number" name="amount_supplier" value="{{ old('amount_supplier', $rev->amount_supplier) }}" step="0.01" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;width:130px">
                            <label style="font-size:11px">{{ __('PM') }}</label>
                            <input type="number" name="amount_pm" value="{{ old('amount_pm', $rev->amount_pm) }}" step="0.01" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;width:130px">
                            <label style="font-size:11px">{{ __('Report') }}</label>
                            <input type="number" name="amount_report" value="{{ old('amount_report', $rev->amount_report) }}" step="0.01" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;flex:1;min-width:150px">
                            <label style="font-size:11px">{{ __('Note') }}</label>
                            <input type="text" name="note" value="{{ old('note', $rev->note) }}">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
                        <a href="{{ route('change-requests.content', $changeRequest) }}" class="btn btn-secondary btn-sm">{{ __('Cancel') }}</a>
                    </form>
                </td>
            </tr>
            @else
            <tr @if($loop->first) style="font-weight:600;background:#f0f9ff" @endif>
                <td>
                    {{ $rev->date?->format('d.m.Y') }}
                    @if($loop->first)<span style="font-size:10px;color:#2563eb;margin-left:.3rem">{{ __('latest') }}</span>@endif
                </td>
                <td style="text-align:right">{{ number_format($rev->amount_supplier, 2, ',', ' ') }}</td>
                <td style="text-align:right">{{ number_format($rev->amount_pm, 2, ',', ' ') }}</td>
                <td style="text-align:right;background:#eff6ff;color:#2563eb">{{ number_format($rev->amount_report, 2, ',', ' ') }}</td>
                <td style="color:#6b7280;font-size:12px">{{ $rev->note }}</td>
                <td style="text-align:right">
                    <a href="{{ route('cr-revisions.edit', $rev) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('cr-revisions.destroy', $rev) }}" style="display:inline" onsubmit="return confirm('{{ __('Really delete?') }}')">
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

    {{-- Add revision form --}}
    <div style="padding:.75rem 1rem;border-top:1px solid #e5e7eb;background:#fafafa">
        <form method="POST" action="{{ route('cr-item-revisions.store', $item) }}" style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div style="font-size:12px;font-weight:600;width:100%;margin-bottom:.25rem;color:#374151">{{ __('Add revision') }}</div>
            <div class="form-group" style="margin-bottom:0;width:130px">
                <label style="font-size:11px">{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="form-group" style="margin-bottom:0;width:130px">
                <label style="font-size:11px">{{ __('Supplier') }} *</label>
                <input type="number" name="amount_supplier" value="{{ old('amount_supplier') }}" step="0.01" required placeholder="0.00">
            </div>
            <div class="form-group" style="margin-bottom:0;width:130px">
                <label style="font-size:11px">{{ __('PM') }} *</label>
                <input type="number" name="amount_pm" value="{{ old('amount_pm') }}" step="0.01" required placeholder="0.00">
            </div>
            <div class="form-group" style="margin-bottom:0;width:130px">
                <label style="font-size:11px">{{ __('Report') }} *</label>
                <input type="number" name="amount_report" value="{{ old('amount_report') }}" step="0.01" required placeholder="0.00">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:150px">
                <label style="font-size:11px">{{ __('Note') }}</label>
                <input type="text" name="note" value="{{ old('note') }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">+ {{ __('Add') }}</button>
        </form>
    </div>
</div>
@endforeach

@if($changeRequest->items->isEmpty())
<div class="card card-body" style="color:#6b7280;font-size:13px">
    {{ __('Add contract items above to start entering change request amounts.') }}
</div>
@endif
@endsection
