@extends('layouts.app')
@section('title', __('Edit content').' — '.$contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a>
    <span>{{ __('Edit content') }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $contract->name }} <code style="font-size:.8em;color:#6b7280">{{ $contract->code }}</code></h1>
        <div style="font-size:12px;color:#f59e0b;margin-top:.2rem;font-weight:600">✎ {{ __('Edit mode') }}</div>
    </div>
    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>

{{-- Add category --}}
<div class="card" style="margin-bottom:1.25rem;padding:1rem;border:2px dashed #e5e7eb">
    <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.6rem">{{ __('Add category') }}</div>
    <form method="POST" action="{{ route('contracts.categories.store', $contract) }}" style="display:flex;gap:.5rem;align-items:flex-end">
        @csrf
        @php
            $lastCatCode = $contract->categories->sortBy('sort')->last()?->code;
            $nextCatCode = '';
            if ($lastCatCode && preg_match('/^(\D*)(\d+)$/', $lastCatCode, $m)) {
                $nextCatCode = $m[1] . str_pad((int)$m[2] + 1, strlen($m[2]), '0', STR_PAD_LEFT);
            } elseif (!$lastCatCode) {
                $nextCatCode = '01';
            }
        @endphp
        <div style="width:130px">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Code') }}</label>
            <input type="text" name="code" value="{{ $nextCatCode }}" placeholder="A1">
        </div>
        <div style="flex:1">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Category name *') }}</label>
            <input type="text" name="name" placeholder="{{ __('Category name...') }}" required>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('+ Add category') }}</button>
    </form>
</div>

{{-- Categories with their items --}}
@php $rootCategories = $contract->categories->whereNull('parent_id'); @endphp
@foreach($rootCategories as $category)
    @include('contracts._cat_edit', ['category' => $category, 'contract' => $contract, 'depth' => 0])
@endforeach

{{-- Uncategorized items (legacy only — no add form) --}}
@php $uncategorized = $contract->items->whereNull('contract_category_id'); @endphp
@if($uncategorized->isNotEmpty())
<div class="card" style="margin-bottom:1rem;border-left:3px solid #f59e0b">
    <div style="padding:.65rem 1rem;background:#fffbeb;border-bottom:1px solid #fde68a;display:flex;align-items:center;gap:.75rem">
        <strong style="color:#92400e">{{ __('Uncategorized') }}</strong>
        <span style="font-size:11px;color:#92400e">{{ __('Assign a category via Edit to move these items.') }}</span>
    </div>
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
            @foreach($uncategorized as $item)
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
                <td colspan="2" style="text-align:right;color:#6b7280;font-size:12px">{{ __('Subtotal') }}</td>
                <td style="text-align:right;font-weight:600">{{ number_format($uncategorized->sum('amount'), 2, ',', ' ') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>
@endif

@if($rootCategories->isEmpty())
<div class="card" style="margin-bottom:1rem">
    <div class="empty"><strong>{{ __('No categories yet') }}</strong><p>{{ __('Create a category above before adding items.') }}</p></div>
</div>
@endif

<div style="margin-top:1rem">
    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>
@endsection
