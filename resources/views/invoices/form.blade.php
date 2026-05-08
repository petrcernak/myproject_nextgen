@extends('layouts.app')
@section('title', isset($invoice) ? __('Edit invoice') : __('New invoice'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    <a href="{{ route('projects.show', $contract->project) }}"><span>{{ $contract->project->name }}</span></a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    @isset($invoice)
        <a href="{{ route('invoices.show', $invoice) }}"><span>{{ $invoice->no }}</span></a>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New invoice') }}</span>
    @endisset
</div>

<div class="page-header">
    <h1>{{ isset($invoice) ? __('Edit invoice') : __('New invoice') }}</h1>
</div>

<div class="card card-body" style="max-width:700px">
    <form method="POST" action="{{ isset($invoice) ? route('invoices.update', $invoice) : route('contracts.invoices.store', $contract) }}">
        @csrf
        @isset($invoice) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Invoice number *') }}</label>
                <input type="text" name="no" value="{{ old('no', $invoice->no ?? '') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Description') }}</label>
            <textarea name="description" rows="3">{{ old('description', $invoice->description ?? '') }}</textarea>
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label>{{ __('Issue date') }}</label>
                <input type="date" name="issued" value="{{ old('issued', isset($invoice) ? $invoice->issued?->format('Y-m-d') : '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('Tax date') }}</label>
                <input type="date" name="taxdate" value="{{ old('taxdate', isset($invoice) ? $invoice->taxdate?->format('Y-m-d') : '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('Due date') }}</label>
                <input type="date" name="due" value="{{ old('due', isset($invoice) ? $invoice->due?->format('Y-m-d') : '') }}">
            </div>
        </div>

        <div class="form-group" style="max-width:220px">
            <label>{{ __('Payment date') }}</label>
            <input type="date" name="paid" value="{{ old('paid', isset($invoice) ? $invoice->paid?->format('Y-m-d') : '') }}">
        </div>

        <div class="form-group">
            <label>{{ __('Note') }}</label>
            <textarea name="note" rows="2">{{ old('note', $invoice->note ?? '') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @isset($invoice)
                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @else
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @endisset
        </div>
    </form>
</div>
@endsection
