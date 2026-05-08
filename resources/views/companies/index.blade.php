@extends('layouts.app')
@section('title', __('Companies'))

@section('content')
<div class="page-header">
    <h1>{{ __('Companies') }}</h1>
    <a href="{{ route('companies.create') }}" class="btn btn-primary">+ {{ __('New company') }}</a>
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;margin-bottom:1rem">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search...') }}" style="max-width:280px">
            <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
            @if(request('search')) <a href="{{ route('companies.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a> @endif
        </form>
    </div>

    @if($companies->isEmpty())
        <div class="empty"><strong>{{ __('No companies') }}</strong></div>
    @else
        <table>
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Reg. No.') }}</th><th>{{ __('Tax ID') }}</th><th>{{ __('Email') }}</th><th>{{ __('Phone') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($companies as $company)
                <tr>
                    <td>{{ $company->name }}</td>
                    <td>{{ $company->regno ?? '—' }}</td>
                    <td>{{ $company->taxregno ?? '—' }}</td>
                    <td>{{ $company->email ?? '—' }}</td>
                    <td>{{ $company->phone ?? '—' }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('companies.edit', $company) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:1rem">{{ $companies->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
