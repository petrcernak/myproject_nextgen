@extends('layouts.app')
@section('title', isset($budget) ? __('Edit budget') : __('New budget'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    <a href="{{ route('projects.show', $project) }}"><span>{{ $project->name }}</span></a>
    @isset($budget)
        <a href="{{ route('budgets.show', $budget) }}"><span>{{ $budget->name }}</span></a>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New budget') }}</span>
    @endisset
</div>

<div class="page-header">
    <h1>{{ isset($budget) ? __('Edit budget') : __('New budget') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ isset($budget) ? route('budgets.update', $budget) : route('projects.budgets.store', $project) }}">
        @csrf
        @isset($budget) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Code') }} *</label>
                <input type="text" name="code" value="{{ old('code', $budget->code ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', isset($budget) ? $budget->date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                @error('date')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $budget->name ?? '') }}" required>
        </div>

        <div class="form-group">
            <label>{{ __('Note') }}</label>
            <textarea name="note" rows="3">{{ old('note', $budget->note ?? '') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @isset($budget)
                <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @else
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @endisset
        </div>
    </form>
</div>
@endsection
