@extends('layouts.app')
@section('title', isset($user) ? __('Edit user') : __('New user'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('users.index') }}">{{ __('Users') }}</a>
    <span>{{ isset($user) ? __('Edit user') : __('New user') }}</span>
</div>

<div class="page-header">
    <h1>{{ isset($user) ? __('Edit user') : __('New user') }}</h1>
</div>

<div class="card card-body" style="max-width:600px">
    <form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">
        @csrf
        @isset($user) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Firstname') }} *</label>
                <input type="text" name="firstname" value="{{ old('firstname', $user->firstname ?? '') }}" required>
                @error('firstname')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('Surname') }} *</label>
                <input type="text" name="surname" value="{{ old('surname', $user->surname ?? '') }}" required>
                @error('surname')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Username') }} *</label>
                <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}" required autocomplete="off">
                @error('username')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}">
                @error('email')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group" style="max-width:280px">
            <label>{{ __('Password') }}{{ isset($user) ? '' : ' *' }}</label>
            <input type="password" name="password" autocomplete="new-password" {{ isset($user) ? '' : 'required' }}>
            @isset($user)
                <span style="font-size:11px;color:#6b7280">{{ __('Leave blank to keep current password') }}</span>
            @endisset
            @error('password')<span class="form-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>{{ __('Level') }} *</label>
                <select name="level" required>
                    <option value="1" @selected(old('level', $user->level ?? 1) == 1)>{{ __('Member') }}</option>
                    <option value="5" @selected(old('level', $user->level ?? 1) == 5)>{{ __('Project creator') }}</option>
                    <option value="7" @selected(old('level', $user->level ?? 1) == 7)>{{ __('Group admin') }}</option>
                </select>
                @error('level')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:.25rem">
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                    <input type="checkbox" name="active" value="1" {{ old('active', $user->active ?? true) ? 'checked' : '' }}>
                    {{ __('Active') }}
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
