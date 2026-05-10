@extends('layouts.app')
@section('title', __('Edit transfer').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <a href="{{ route('budgets.transfers.index', $budget) }}">{{ __('Transfers') }}</a>
    <span>{{ __('Edit transfer') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Edit transfer') }}</h1>
</div>

<form method="POST" action="{{ route('budget-transfers.update', $transfer) }}">
    @csrf @method('PUT')

    <div class="card card-body" style="max-width:700px;margin-bottom:1rem">
        <div class="form-row">
            <div class="form-group" style="max-width:180px">
                <label>{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', $transfer->date->format('Y-m-d')) }}" required>
            </div>
            <div class="form-group" style="flex:1">
                <label>{{ __('Description / reason') }} *</label>
                <input type="text" name="description" value="{{ old('description', $transfer->description) }}" required>
            </div>
        </div>

        <div class="form-row" style="align-items:flex-end;gap:1rem">
            <div class="form-group" style="flex:1">
                <label>{{ __('From item') }} *</label>
                <select name="from_budget_item_id" required style="width:100%">
                    <option value="">— {{ __('select') }} —</option>
                    @foreach($budget->categories->whereNull('parent_id') as $cat)
                        <optgroup label="{{ $cat->code ? $cat->code.' — ' : '' }}{{ $cat->name }}">
                            @include('budget_transfers._item_options', [
                                'category' => $cat,
                                'selected' => old('from_budget_item_id', $transfer->from_budget_item_id),
                            ])
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div style="font-size:1.4rem;color:#6b7280;padding-bottom:.25rem">→</div>

            <div class="form-group" style="flex:1">
                <label>{{ __('To item') }} *</label>
                <select name="to_budget_item_id" required style="width:100%">
                    <option value="">— {{ __('select') }} —</option>
                    @foreach($budget->categories->whereNull('parent_id') as $cat)
                        <optgroup label="{{ $cat->code ? $cat->code.' — ' : '' }}{{ $cat->name }}">
                            @include('budget_transfers._item_options', [
                                'category' => $cat,
                                'selected' => old('to_budget_item_id', $transfer->to_budget_item_id),
                            ])
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="max-width:220px">
                <label>{{ __('Amount') }} ({{ $budget->currency }}) *</label>
                <input type="number" name="amount" step="0.01" min="0.01"
                       value="{{ old('amount', $transfer->amount) }}" required
                       style="text-align:right">
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="max-width:700px;margin-bottom:1rem">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        <a href="{{ route('budgets.transfers.index', $budget) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
