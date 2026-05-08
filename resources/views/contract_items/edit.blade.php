@extends('layouts.app')
@section('title', __('Edit contract item'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    <a href="{{ route('projects.show', $contract->project) }}"><span>{{ $contract->project->name }}</span></a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <span>{{ __('Edit') }}</span>
</div>

<div class="page-header">
    <h1>{{ __('Edit contract item') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ route('contract-items.update', $item) }}">
        @csrf @method('PUT')

        <div class="form-row">
            <div class="form-group" style="max-width:130px">
                <label>{{ __('Code') }}</label>
                <input type="text" name="code" value="{{ old('code', $item->code) }}" maxlength="50">
            </div>
            <div class="form-group" style="flex:1">
                <label>{{ __('Description') }} *</label>
                <input type="text" name="description" value="{{ old('description', $item->description) }}" required>
            </div>
        </div>

        <div class="form-group" style="max-width:220px">
            <label>{{ __('Amount') }} ({{ $contract->currency }}) *</label>
            <input type="number" name="amount" step="0.01" value="{{ old('amount', $item->amount) }}" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
