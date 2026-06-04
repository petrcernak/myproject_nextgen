@extends('layouts.app')
@section('title', __('Change password'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('settings') }}">{{ __('Settings') }}</a>
    <span>{{ __('Change password') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Change password') }}</h1>
</div>

@if(session('success'))
    <div class="alert alert-success" style="max-width:480px">{{ session('success') }}</div>
@endif

<div class="card card-body" style="max-width:480px">
    <form method="POST" action="{{ route('settings.password') }}">
        @csrf
        <div class="form-group">
            <label>{{ __('Current password') }} *</label>
            <input type="password" name="current_password" autocomplete="current-password" required>
            @error('current_password')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label>{{ __('New password') }} * <span style="font-size:11px;font-weight:400;color:#6b7280">({{ __('min. 8 characters') }})</span></label>
            <input type="password" name="password" autocomplete="new-password" required minlength="8">
            @error('password')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label>{{ __('Confirm new password') }} *</label>
            <input type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Change password') }}</button>
            <a href="{{ route('settings') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
