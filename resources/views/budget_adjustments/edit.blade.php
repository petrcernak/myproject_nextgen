@extends('layouts.app')
@section('title', __('Edit adjustment').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <a href="{{ route('budgets.adjustments.index', $budget) }}">{{ __('Adjustments') }}</a>
    <span>{{ __('Edit adjustment') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Edit adjustment') }}</h1>
</div>

<form method="POST" action="{{ route('budget-adjustments.update', $adjustment) }}">
    @csrf @method('PUT')

    <div class="card card-body" style="max-width:700px;margin-bottom:1rem">
        <div class="form-row">
            <div class="form-group" style="max-width:180px">
                <label>{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', $adjustment->date->format('Y-m-d')) }}" required>
            </div>
            <div class="form-group" style="flex:1">
                <label>{{ __('Description') }} *</label>
                <input type="text" name="description" value="{{ old('description', $adjustment->description) }}" required>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem">
        <div style="padding:.65rem 1rem;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:600;color:#374151">
            {{ __('Item adjustments — leave blank or 0 to remove.') }}
        </div>
        <table style="font-size:12px">
            <thead>
                <tr>
                    <th>{{ __('Item') }}</th>
                    <th style="text-align:right;width:140px">{{ __('Amount') }} ({{ $budget->currency }})</th>
                    <th style="width:160px">{{ __('Adjustment') }} ({{ $budget->currency }})</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $existingAmounts = $adjustment->items->pluck('amount', 'budget_item_id')->map(fn($v) => $v != 0 ? $v : null)->toArray();
                @endphp
                @foreach($budget->categories->whereNull('parent_id') as $category)
                    @include('budget_adjustments._item_tree', ['category'=>$category,'depth'=>0,'existingAmounts'=>$existingAmounts])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        <a href="{{ route('budgets.adjustments.index', $budget) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
