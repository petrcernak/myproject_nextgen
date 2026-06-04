@extends('layouts.app')
@section('title', isset($locality) ? __('Edit locality') : __('New locality'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('localities.index') }}">{{ __('Localities') }}</a>
    <span>{{ isset($locality) ? __('Edit') : __('New locality') }}</span>
</div>
<div class="page-header">
    <h1>{{ isset($locality) ? __('Edit locality') : __('New locality') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ isset($locality) ? route('localities.update', $locality) : route('localities.store') }}">
        @csrf
        @isset($locality) @method('PUT') @endisset

        <div class="form-group">
            <label>{{ __('Name') }} *</label>
            <input type="text" name="name" value="{{ old('name', $locality->name ?? '') }}" required>
            @error('name')<span class="form-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('localities.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
