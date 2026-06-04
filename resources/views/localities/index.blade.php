@extends('layouts.app')
@section('title', __('Localities'))

@section('content')
<div class="breadcrumb">
    <span>{{ __('Localities') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Localities') }}</h1>
    <a href="{{ route('localities.create') }}" class="btn btn-primary">+ {{ __('New locality') }}</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="card">
    @if($localities->isEmpty())
        <div class="empty">
            <strong>{{ __('No localities') }}</strong>
            <p>{{ __('Create localities to organise your projects geographically.') }}</p>
        </div>
    @else
    <table>
        <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th style="text-align:center;width:90px">{{ __('Projects') }}</th>
                <th style="width:100px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($localities as $locality)
            <tr>
                <td><strong>{{ $locality->name }}</strong></td>
                <td style="text-align:center">{{ $locality->projects_count }}</td>
                <td style="text-align:right">
                    <div style="display:flex;gap:.3rem;justify-content:flex-end">
                        <a href="{{ route('localities.edit', $locality) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                        @if($locality->projects_count === 0)
                        <form method="POST" action="{{ route('localities.destroy', $locality) }}"
                            onsubmit="return confirm('{{ __('Really delete?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">✕</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
