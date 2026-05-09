@extends('layouts.app')
@section('title', isset($changeRequest) ? __('Edit change request') : __('New change request'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    @isset($changeRequest)
        <a href="{{ route('change-requests.show', $changeRequest) }}"><span>{{ $changeRequest->code }}</span></a>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New change request') }}</span>
    @endisset
</div>

<div class="page-header">
    <h1>{{ isset($changeRequest) ? __('Edit change request') : __('New change request') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ isset($changeRequest) ? route('change-requests.update', $changeRequest) : route('contracts.change-requests.store', $contract) }}">
        @csrf
        @isset($changeRequest) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Code') }} *</label>
                <input type="text" name="code" value="{{ old('code', $changeRequest->code ?? $nextCode ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', isset($changeRequest) ? $changeRequest->date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                @error('date')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $changeRequest->name ?? '') }}" required>
        </div>

        <div class="form-group">
            <label>{{ __('Status') }}</label>
            <select name="status">
                <option value="open"      @selected(old('status', $changeRequest->status ?? 'open') === 'open')>{{ __('Open') }}</option>
                <option value="on_hold"   @selected(old('status', $changeRequest->status ?? '') === 'on_hold')>{{ __('On hold') }}</option>
                <option value="closed"    @selected(old('status', $changeRequest->status ?? '') === 'closed')>{{ __('Closed') }}</option>
                <option value="rejected"  @selected(old('status', $changeRequest->status ?? '') === 'rejected')>{{ __('Rejected') }}</option>
                <option value="converted" @selected(old('status', $changeRequest->status ?? '') === 'converted')>{{ __('Converted') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label>{{ __('Note') }}</label>
            <textarea name="note" rows="3">{{ old('note', $changeRequest->note ?? '') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @isset($changeRequest)
                <a href="{{ route('change-requests.show', $changeRequest) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @else
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @endisset
        </div>
    </form>
</div>
@endsection
