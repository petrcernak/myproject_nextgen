@extends('layouts.app')
@section('title', isset($group) ? __('Edit group') : __('New group'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('groups.index') }}">{{ __('Groups') }}</a>
    @isset($group)
        <a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a>
    @endisset
    <span>{{ isset($group) ? __('Edit') : __('New group') }}</span>
</div>

<div class="page-header">
    <h1>{{ isset($group) ? __('Edit group') : __('New group') }}</h1>
</div>

<div class="card card-body" style="max-width:480px">
    <form method="POST" action="{{ isset($group) ? route('groups.update', $group) : route('groups.store') }}">
        @csrf
        @isset($group) @method('PUT') @endisset

        <div class="form-row" style="margin-bottom:1rem">
            <div class="form-group" style="margin-bottom:0">
                <label>{{ __('Code') }} *</label>
                <input type="text" name="code" value="{{ old('code', $group->code ?? '') }}" placeholder="ACME" required>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>{{ __('Name') }} *</label>
                <input type="text" name="name" value="{{ old('name', $group->name ?? '') }}" placeholder="Acme s.r.o." required>
            </div>
            <div class="form-group" style="margin-bottom:0;max-width:110px">
                <label>{{ __('Default currency') }} *</label>
                <select name="currency" required>
                    @foreach(['CZK','EUR','USD','PLN'] as $c)
                        <option value="{{ $c }}" @selected(old('currency', $group->currency ?? 'CZK') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ isset($group) ? __('Save') : __('Create group') }}</button>
            <a href="{{ isset($group) ? route('groups.show', $group) : route('groups.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
