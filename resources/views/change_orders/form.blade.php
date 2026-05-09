@extends('layouts.app')
@section('title', isset($changeOrder) ? __('Edit change order') : __('New change order'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    @isset($changeOrder)
        <a href="{{ route('change-orders.show', $changeOrder) }}"><span>{{ $changeOrder->code }}</span></a>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New change order') }}</span>
    @endisset
</div>

<div class="page-header">
    <h1>{{ isset($changeOrder) ? __('Edit change order') : __('New change order') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ isset($changeOrder) ? route('change-orders.update', $changeOrder) : route('contracts.change-orders.store', $contract) }}">
        @csrf
        @isset($changeOrder) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group" style="max-width:140px">
                <label>{{ __('Code') }} *</label>
                <input type="text" name="code" value="{{ old('code', $changeOrder->code ?? $nextCode ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', isset($changeOrder) ? $changeOrder->date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                @error('date')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $changeOrder->name ?? '') }}" required>
        </div>

        <div class="form-group">
            <label>{{ __('Amendment') }}</label>
            <select name="amendment_id">
                <option value="">— {{ __('Standalone (no amendment)') }} —</option>
                @foreach($amendments as $id => $aname)
                    <option value="{{ $id }}" @selected(old('amendment_id', $changeOrder->amendment_id ?? $selectedAmendmentId ?? '') == $id)>{{ $aname }}</option>
                @endforeach
            </select>
            <span style="font-size:12px;color:#6b7280">{{ __('Leave empty for standalone change order') }}</span>
        </div>

        <div class="form-group">
            <label>{{ __('Note') }}</label>
            <textarea name="note" rows="3">{{ old('note', $changeOrder->note ?? '') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @isset($changeOrder)
                <a href="{{ route('change-orders.show', $changeOrder) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @else
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @endisset
        </div>
    </form>
</div>
@endsection
