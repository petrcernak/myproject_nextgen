@extends('layouts.app')
@section('title', __('Edit budget item'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    <a href="{{ route('projects.show', $budget->project) }}"><span>{{ $budget->project->name }}</span></a>
    <a href="{{ route('budgets.show', $budget) }}"><span>{{ $budget->name }}</span></a>
    <span>{{ __('Edit') }}</span>
</div>

<div class="page-header">
    <h1>{{ __('Edit budget item') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ route('budget-items.update', $item) }}">
        @csrf @method('PUT')

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Code') }}</label>
                <input type="text" name="code" value="{{ old('code', $item->code) }}" placeholder="A1.1">
            </div>
            <div class="form-group">
                <label>{{ __('Amount') }} *</label>
                <input type="number" name="amount" step="0.01" value="{{ old('amount', $item->amount) }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Description') }} *</label>
            <input type="text" name="description" value="{{ old('description', $item->description) }}" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
