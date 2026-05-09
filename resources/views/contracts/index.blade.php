@extends('layouts.app')
@section('title', __('Contracts'))

@section('content')
<div class="page-header">
    <h1>{{ __('Contracts') }} — {{ $currentProject->name }}</h1>
    @if($currentProject)
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
            @if($currencies->count() > 1)
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Currency') }}</label>
                <select name="currency" style="width:110px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($currencies as $c)
                        <option value="{{ $c }}" @selected(request('currency') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
            @if(request()->hasAny(['search','direction','currency','company_id']))
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
                    <th>{{ __('Direction') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Invoices') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                <tr>
                    <td><code>{{ $contract->code }}</code></td>
                    <td><a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a></td>
                    <td>{{ $contract->company?->name ?? '—' }}</td>
                    <td>{{ $contract->direction === 1 ? __('Income') : __('Expense') }}</td>
                    <td>{{ $contract->date?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $contract->invoices()->count() }}</td>
                    <td style="text-align:right"><a href="{{ route('contracts.edit', $contract) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:1rem">{{ $contracts->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
