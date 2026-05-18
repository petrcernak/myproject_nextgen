@extends('layouts.app')
@section('title', __('Contracts'))

@section('content')
<div class="page-header">
    <h1>{{ __('Contracts') }} — {{ $currentProject->name }}</h1>
    @if($currentProject && $canEdit)
        <a href="{{ route('projects.contracts.create', $currentProject) }}" class="btn btn-primary">+ {{ __('New contract') }}</a>
    @endif
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search code / name...') }}" style="width:220px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Direction') }}</label>
                <select name="direction" style="width:150px" onchange="this.form.submit()">
                    <option value="">{{ __('All directions') }}</option>
                    <option value="1" @selected(request('direction')=='1')>{{ __('Income') }}</option>
                    <option value="-1" @selected(request('direction')=='-1')>{{ __('Expense') }}</option>
                </select>
            </div>
            @if($companies->count() > 1)
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Company') }}</label>
                <select name="company_id" style="width:180px" onchange="this.form.submit()">
                    <option value="">{{ __('All companies') }}</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected(request('company_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Currency') }}</label>
                <select name="currency" style="width:110px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($currencies as $c)
                        <option value="{{ $c }}" @selected(request('currency') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Files') }}</label>
                <select name="file_filter" style="width:150px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="0" @selected(request('file_filter')==='0')>{{ __('No files') }}</option>
                    <option value="1" @selected(request('file_filter')==='1')>{{ __('Has files') }}</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
            @if(request()->hasAny(['search','direction','currency','company_id','file_filter']))
                <a href="{{ route('contracts.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @if($contracts->isEmpty())
        <div class="empty"><strong>{{ __('No contracts') }}</strong><p>{{ __('Create the first contract using the button above.') }}</p></div>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Company') }}</th>
                    <th style="width:70px">{{ __('Currency') }}</th>
                    <th>{{ __('Direction') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Invoices') }}</th>
                    <th style="width:60px;text-align:center"></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                <tr>
                    <td><code>{{ $contract->code }}</code></td>
                    <td><a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a></td>
                    <td>{{ $contract->company?->name ?? '—' }}</td>
                    <td style="font-size:12px;color:#6b7280">{{ $contract->currency }}</td>
                    <td>{{ $contract->direction === 1 ? __('Income') : __('Expense') }}</td>
                    <td>{{ $contract->date?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $contract->invoices()->count() }}</td>
                    <td style="text-align:center">
                        @if($contract->files_count)
                            <a href="{{ route('contracts.show', $contract) }}#files"
                               style="display:inline-flex;align-items:center;gap:.25rem;font-size:12px;color:#6b7280;text-decoration:none"
                               title="{{ __('Files') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                {{ $contract->files_count }}
                            </a>
                        @endif
                    </td>
                    <td style="text-align:right">@if($canEdit)<a href="{{ route('contracts.edit', $contract) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:1rem">{{ $contracts->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
