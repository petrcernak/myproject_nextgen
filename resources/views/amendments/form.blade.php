@extends('layouts.app')
@section('title', isset($amendment) ? __('Edit amendment') : __('New amendment'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    @isset($amendment)
        <a href="{{ route('amendments.show', $amendment) }}"><span>{{ $amendment->code }}</span></a>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New amendment') }}</span>
    @endisset
</div>

<div class="page-header">
    <h1>{{ isset($amendment) ? __('Edit amendment') : __('New amendment') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ isset($amendment) ? route('amendments.update', $amendment) : route('contracts.amendments.store', $contract) }}">
        @csrf
        @isset($amendment) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group" style="max-width:120px">
                <label>{{ __('Code') }} *</label>
                <input type="text" name="code" value="{{ old('code', $amendment->code ?? $nextCode ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', isset($amendment) ? $amendment->date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                @error('date')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $amendment->name ?? '') }}" required>
        </div>

        <div class="form-group">
            <label>{{ __('Note') }}</label>
            <textarea name="note" rows="3">{{ old('note', $amendment->note ?? '') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @isset($amendment)
                <a href="{{ route('amendments.show', $amendment) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @else
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @endisset
        </div>
    </form>
</div>
@endsection
