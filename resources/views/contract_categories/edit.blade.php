@extends('layouts.app')
@section('title', __('Edit category'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <a href="{{ route('contracts.content', $contract) }}"><span>{{ __('Edit items') }}</span></a>
    <span>{{ __('Edit category') }}</span>
</div>

<div class="page-header">
    <h1>{{ __('Edit category') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ route('contract-categories.update', $category) }}">
        @csrf @method('PUT')

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Code') }}</label>
                <input type="text" name="code" value="{{ old('code', $category->code) }}" placeholder="A1">
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Category name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('contracts.content', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
