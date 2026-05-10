@extends('layouts.app')
@section('title', __('Budget link').' — '.$link->contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $link->contract) }}"><span>{{ $link->contract->name }}</span></a>
    <span>{{ __('Budget link') }}</span>
</div>

<div class="page-header">
    <div>
        <h1 style="display:flex;align-items:center;gap:.75rem">
            {{ $link->contract->name }}
            <span style="color:#9ca3af;font-size:1rem">↔</span>
            {{ $link->budget->name }}
            @if($link->budget->code)
                <code style="font-size:.75em;color:#6b7280">{{ $link->budget->code }}</code>
            @endif
        </h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.25rem;display:flex;align-items:center;gap:1rem">
            <span>{{ $link->contract->currency }} → {{ $link->budget->currency }}</span>
            @if($link->fx_rate)
                <span>FX: <strong>{{ number_format($link->fx_rate, 4, '.', '') }}</strong> {{ $link->contract->currency }}/{{ $link->budget->currency }}</span>
            @else
                <span style="color:#f59e0b">{{ __('FX rate not set') }}</span>
            @endif
            @php
                $linkedCount = $link->contract->items->filter(fn($ci) => $ci->budget_item_id && $budgetItems->contains('id', $ci->budget_item_id))->count();
                $totalCount = $link->contract->items->count();
            @endphp
            <span style="font-size:11px;background:{{ $linkedCount >= $totalCount && $totalCount > 0 ? '#d1fae5' : '#fef3c7' }};color:{{ $linkedCount >= $totalCount && $totalCount > 0 ? '#065f46' : '#92400e' }};padding:.2rem .5rem;border-radius:999px;font-weight:600">
                {{ $linkedCount }}/{{ $totalCount }} {{ __('items linked') }}
            </span>
        </div>
    </div>
    @if($canEdit)
    <div style="display:flex;gap:.5rem">
        <form method="POST" action="{{ route('contract-budget-links.destroy', $link) }}" onsubmit="return confirm('{{ __('Remove this budget link and clear all item assignments?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger" style="font-size:12px">{{ __('Remove link') }}</button>
        </form>
    </div>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('contract-budget-links.update', $link) }}">
    @csrf @method('PUT')

    {{-- FX Rate --}}
    <div class="card card-body" style="margin-bottom:1rem;max-width:500px">
        <div class="form-row" style="align-items:flex-end">
            <div class="form-group" style="flex:1">
                <label style="font-size:12px">
                    {{ __('FX Rate') }}
                    <span style="color:#9ca3af">({{ $link->contract->currency }} per 1 {{ $link->budget->currency }})</span>
                </label>
                <input type="number" name="fx_rate" step="0.000001" min="0"
                       value="{{ old('fx_rate', $link->fx_rate) }}"
                       placeholder="{{ __('blank = same currency') }}"
                       style="text-align:right;max-width:180px">
            </div>
            <div class="form-group" style="align-self:flex-end;padding-bottom:.05rem">
                <button type="submit" class="btn btn-primary" style="font-size:12px">{{ __('Save') }}</button>
            </div>
        </div>
    </div>

    {{-- Item assignment table --}}
    <div class="card" style="margin-bottom:1.5rem">
        <div style="padding:.6rem 1rem;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:600;color:#374151;display:flex;justify-content:space-between">
            <span>{{ __('Contract items') }}</span>
            <span>{{ __('↔ Budget item') }}</span>
        </div>
        <table style="font-size:12px">
            <thead>
                <tr>
                    <th style="width:90px">{{ __('Code') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="text-align:right;width:130px">{{ __('Amount') }} ({{ $link->contract->currency }})</th>
                    <th style="width:320px">{{ __('Budget item') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($link->contract->items->sortBy(fn($i) => strtolower($i->code ?? $i->description ?? '')) as $ci)
                @php $currentBi = $linkedMap->get($ci->id); @endphp
                <tr>
                    <td><code style="color:#6b7280;font-size:11px">{{ $ci->code ?? '—' }}</code></td>
                    <td>{{ $ci->description }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($ci->amount, 2, ',', ' ') }}</td>
                    <td>
                        <select name="items[{{ $ci->id }}]" style="width:100%;font-size:11px">
                            <option value="">— {{ __('not linked') }} —</option>
                            @foreach($budgetItems->sortBy(fn($bi) => strtolower($bi->code ?? $bi->description ?? '')) as $bi)
                                <option value="{{ $bi->id }}" @selected($currentBi == $bi->id)>
                                    {{ $bi->code ? $bi->code.' — ' : '' }}{{ $bi->description }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:1rem">{{ __('No contract items.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($link->contract->items->isNotEmpty())
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ __('Save assignments') }}</button>
        <a href="{{ route('contracts.show', $link->contract) }}" class="btn btn-secondary">{{ __('Back to contract') }}</a>
    </div>
    @endif
</form>
@endsection
