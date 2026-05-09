@extends('layouts.app')
@section('title', isset($contractAnticipated) ? __('Edit anticipated') : __('New anticipated'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    @isset($contractAnticipated)
        <a href="{{ route('contract-anticipateds.show', $contractAnticipated) }}"><span>{{ $contractAnticipated->code }}</span></a>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New anticipated') }}</span>
    @endisset
</div>

<div class="page-header">
    <h1>{{ isset($contractAnticipated) ? __('Edit anticipated') : __('New anticipated') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ isset($contractAnticipated) ? route('contract-anticipateds.update', $contractAnticipated) : route('contracts.anticipateds.store', $contract) }}">
        @csrf
        @isset($contractAnticipated) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Code') }} *</label>
                <input type="text" name="code" value="{{ old('code', $contractAnticipated->code ?? $nextCode ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', isset($contractAnticipated) ? $contractAnticipated->date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                @error('date')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $contractAnticipated->name ?? '') }}" required>
        </div>

        <div class="form-group">
            <label>{{ __('Note') }}</label>
            <textarea name="note" rows="3">{{ old('note', $contractAnticipated->note ?? '') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @isset($contractAnticipated)
                <a href="{{ route('contract-anticipateds.show', $contractAnticipated) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @else
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @endisset
        </div>
    </form>
</div>
@endsection
