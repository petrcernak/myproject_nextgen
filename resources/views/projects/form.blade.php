@extends('layouts.app')
@section('title', isset($project) ? __('Edit project') : __('New project'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    @isset($project)
        <a href="{{ route('projects.show', $project) }}"><span>{{ $project->name }}</span></a>
        <span>{{ __('Edit') }}</span>
    @else
        <span>{{ __('New project') }}</span>
    @endisset
</div>

<div class="page-header">
    <h1>{{ isset($project) ? __('Edit project') : __('New project') }}</h1>
</div>

<div class="card card-body" style="max-width:700px">
    <form method="POST" action="{{ isset($project) ? route('projects.update', $project) : route('projects.store') }}">
        @csrf
        @isset($project) @method('PUT') @endisset

        <div class="form-row">
            <div class="form-group">
                <label for="code">{{ __('Code') }} *</label>
                <input id="code" type="text" name="code" value="{{ old('code', $project->code ?? '') }}" required>
            </div>
            <div class="form-group">
                <label for="status">{{ __('Status') }} *</label>
                <select id="status" name="status">
                    <option value="active" @selected(old('status', $project->status ?? 'active') === 'active')>{{ __('Active') }}</option>
                    <option value="finished" @selected(old('status', $project->status ?? '') === 'finished')>{{ __('Finished') }}</option>
                    <option value="cancelled" @selected(old('status', $project->status ?? '') === 'cancelled')>{{ __('Cancelled') }}</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="name">{{ __('Name') }} *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $project->name ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="id_company">{{ __('Company') }} *</label>
            <select id="id_company" name="id_company" required>
                <option value="">{{ __('— select company —') }}</option>
                @foreach($companies as $id => $name)
                    <option value="{{ $id }}" @selected(old('id_company', $project->id_company ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('id_company')<span class="form-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="locality_id">{{ __('Locality') }} *</label>
            <select id="locality_id" name="locality_id" required>
                <option value="" disabled @selected(old('locality_id', $project->locality_id ?? '') === '')>{{ __('— select locality —') }}</option>
                @foreach($localities as $loc)
                    <option value="{{ $loc->id }}" @selected(old('locality_id', $project->locality_id ?? '') == $loc->id)>{{ $loc->name }}</option>
                @endforeach
            </select>
            @error('locality_id')<span class="form-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="note">{{ __('Note') }}</label>
            <textarea id="note" name="note" rows="3">{{ old('note', $project->note ?? '') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            @isset($project)
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @else
                <a href="{{ route('projects.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
            @endisset
        </div>
    </form>
</div>
@endsection
