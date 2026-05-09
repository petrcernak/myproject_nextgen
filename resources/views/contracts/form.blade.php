@extends('layouts.app')
@section('title', isset($contract) ? __('Edit contract') : __('New contract'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    <a href="{{ route('projects.show', $project) }}"><span>{{ $project->name }}</span></a>
    @isset($contract)
        <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New contract') }}</span>
    @endisset
</div>

<div class="page-header">
    <h1>{{ isset($contract) ? __('Edit contract') : __('New contract') }}</h1>
</div>

<div class="card card-body" style="max-width:700px">
    <form method="POST" action="{{ isset($contract) ? route('contracts.update', $contract) : route('projects.contracts.store', $project) }}">
        @csrf
        @isset($contract) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Code') }} *</label>
                <input type="text" name="code" value="{{ old('code', $contract->code ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Currency') }} *</label>
                <select name="currency">
                    @foreach(['CZK','EUR','USD','PLN'] as $c)
                        <option value="{{ $c }}" @selected(old('currency', ($contract ?? null)?->currency ?? $currentGroup?->currency ?? 'CZK') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $contract->name ?? '') }}" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Company') }} *</label>
                <select name="company_id" required>
                    <option value="">{{ __('— select company —') }}</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected(old('company_id', $contract->company_id ?? '') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('company_id')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('Direction') }} *</label>
                <select name="direction">
                    <option value="1" @selected(old('direction', $contract->direction ?? -1) == 1)>{{ __('Income (from client)') }}</option>
                    <option value="-1" @selected(old('direction', $contract->direction ?? -1) == -1)>{{ __('Expense (supplier)') }}</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Contract date') }} *</label>
                <input type="date" name="date" value="{{ old('date', isset($contract) ? $contract->date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                @error('date')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('Maturity (days)') }}</label>
                <input type="number" name="maturity" value="{{ old('maturity', $contract->maturity ?? 30) }}" min="0">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Retention short-term (%)') }}</label>
                <input type="number" name="retention_short" value="{{ old('retention_short', $contract->retention_short ?? '') }}" min="0" max="100" step="0.01" placeholder="0.00">
                @error('retention_short')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('Retention long-term (%)') }}</label>
                <input type="number" name="retention_long" value="{{ old('retention_long', $contract->retention_long ?? '') }}" min="0" max="100" step="0.01" placeholder="0.00">
                @error('retention_long')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Description') }}</label>
            <textarea name="description" rows="3">{{ old('description', $contract->description ?? '') }}</textarea>
        </div>

        <div class="form-group">
            <label>{{ __('Note') }}</label>
            <textarea name="note" rows="2">{{ old('note', $contract->note ?? '') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @isset($contract)
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @else
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @endisset
        </div>
    </form>
</div>
@endsection
