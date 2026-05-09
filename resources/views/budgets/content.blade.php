@extends('layouts.app')
@section('title', __('Edit content').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}"><span>{{ $budget->name }}</span></a>
    <span>{{ __('Edit content') }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $budget->name }} <code style="font-size:.8em;color:#6b7280">{{ $budget->code }}</code></h1>
        <div style="font-size:12px;color:#f59e0b;margin-top:.2rem;font-weight:600">✎ {{ __('Edit mode') }}</div>
    </div>
    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>

<div class="card" style="margin-bottom:1.25rem;padding:1rem;border:2px dashed #e5e7eb">
    <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.6rem">{{ __('Add category') }}</div>
    <form method="POST" action="{{ route('budgets.categories.store', $budget) }}" style="display:flex;gap:.5rem;align-items:flex-end">
        @csrf
        @php
            $lastCode = $budget->categories->sortBy('sort')->last()?->code;
            $nextCode = '';
            if ($lastCode && preg_match('/^(\D*)(\d+)$/', $lastCode, $m)) {
                $nextCode = $m[1] . str_pad((int)$m[2] + 1, strlen($m[2]), '0', STR_PAD_LEFT);
            } elseif (!$lastCode) {
                $nextCode = '01';
            }
        @endphp
        <div style="width:130px">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Code') }}</label>
            <input type="text" name="code" value="{{ $nextCode }}" placeholder="A1">
        </div>
        <div style="flex:1">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Category name *') }}</label>
            <input type="text" name="name" placeholder="{{ __('Category name...') }}" required>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('+ Add category') }}</button>
    </form>
</div>

@php $rootCategories = $budget->categories->whereNull('parent_id'); @endphp
@forelse($rootCategories as $category)
    @include('budgets._cat_edit', ['category' => $category, 'budget' => $budget, 'depth' => 0])
@empty
    <div class="card"><div class="empty"><strong>{{ __('No categories') }}</strong><p>{{ __('Add the first category using the form above.') }}</p></div></div>
@endforelse

<div style="margin-top:1rem">
    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>
@endsection
