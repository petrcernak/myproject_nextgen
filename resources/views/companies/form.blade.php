@extends('layouts.app')
@section('title', isset($company) ? __('Edit company') : __('New company'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('companies.index') }}">{{ __('Companies') }}</a>
    @isset($company)
        <a href="{{ route('companies.show', $company) }}">{{ $company->name }}</a>
    @endisset
    <span>{{ isset($company) ? __('Edit') : __('New company') }}</span>
</div>

<div class="page-header">
    <h1>{{ isset($company) ? __('Edit company') : __('New company') }}</h1>
    @isset($company)
        <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger">{{ __('Delete') }}</button>
        </form>
    @endisset
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ isset($company) ? route('companies.update', $company) : route('companies.store') }}">
        @csrf
        @isset($company) @method('PUT') @endisset

        <div class="form-group">
            <label>{{ __('Name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $company->name ?? '') }}" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Reg. No.') }}</label>
                <input type="text" name="regno" value="{{ old('regno', $company->regno ?? '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('Tax ID') }}</label>
                <input type="text" name="taxregno" value="{{ old('taxregno', $company->taxregno ?? '') }}">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $company->email ?? '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('Phone') }}</label>
                <input type="text" name="phone" value="{{ old('phone', $company->phone ?? '') }}">
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('Website') }}</label>
            <input type="text" name="url" value="{{ old('url', $company->url ?? '') }}">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ isset($company) ? route('companies.show', $company) : route('companies.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
