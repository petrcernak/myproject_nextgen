@extends('layouts.app')
@section('title', $company->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('companies.index') }}">{{ __('Companies') }}</a>
    <span>{{ $company->name }}</span>
</div>

<div class="page-header">
    <h1>{{ $company->name }}</h1>
    @if($canEdit)
        <a href="{{ route('companies.edit', $company) }}" class="btn btn-primary">{{ __('Edit') }}</a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success" style="max-width:600px;margin-bottom:1rem">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;max-width:700px">
    <div class="card card-body">
        <table style="font-size:13px;width:100%">
            <tr>
                <td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0;width:120px">{{ __('Reg. No.') }}</td>
                <td style="border:none;font-weight:500">{{ $company->regno ?? '—' }}</td>
            </tr>
            <tr>
                <td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Tax ID') }}</td>
                <td style="border:none;font-weight:500">{{ $company->taxregno ?? '—' }}</td>
            </tr>
        </table>
    </div>
    <div class="card card-body">
        <table style="font-size:13px;width:100%">
            <tr>
                <td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0;width:80px">{{ __('Email') }}</td>
                <td style="border:none">
                    @if($company->email)<a href="mailto:{{ $company->email }}">{{ $company->email }}</a>@else—@endif
                </td>
            </tr>
            <tr>
                <td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Phone') }}</td>
                <td style="border:none">{{ $company->phone ?? '—' }}</td>
            </tr>
            <tr>
                <td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Website') }}</td>
                <td style="border:none">
                    @if($company->url)<a href="{{ $company->url }}" target="_blank" rel="noopener">{{ $company->url }}</a>@else—@endif
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- Current project contracts --}}
@php
    $otherCount = $otherContractsByProject->sum(fn($g) => $g->count());
    $otherProjectCount = $otherContractsByProject->count();
@endphp

<div style="display:flex;align-items:baseline;gap:1rem;margin-bottom:.75rem">
    <h2 style="font-size:1rem;font-weight:600">
        {{ __('Contracts') }}
        @if($currentProjectId)
            — {{ \App\Models\Project::find($currentProjectId)?->name }}
        @endif
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.4rem">({{ $currentContracts->count() }})</span>
    </h2>

</div>

@if($currentContracts->isEmpty())
    <div class="card" style="margin-bottom:1.5rem"><div class="empty"><strong>{{ __('No contracts for this project') }}</strong></div></div>
@else
<div style="overflow-x:auto;margin-bottom:1.5rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:90px">{{ __('Code') }}</th>
            <th style="text-align:left;min-width:220px">{{ __('Name') }}</th>
            <th style="text-align:left;min-width:100px">{{ __('Direction') }}</th>
            <th style="min-width:80px">{{ __('Currency') }}</th>
            <th style="min-width:130px">{{ __('Value') }}</th>
            <th style="min-width:100px">{{ __('Date') }}</th>
            <th style="min-width:70px">{{ __('Invoices') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($currentContracts as $contract)
        <tr>
            <td><code style="font-size:12px;color:#6b7280">{{ $contract->code }}</code></td>
            <td style="text-align:left"><a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a></td>
            <td style="text-align:left">{{ $contract->direction === 1 ? __('Income') : __('Expense') }}</td>
            <td style="text-align:right;font-size:12px;color:#6b7280">{{ $contract->currency }}</td>
            <td style="text-align:right;font-variant-numeric:tabular-nums">{{ number_format((float)($contract->items_sum_amount ?? 0), 2, ',', ' ') }}</td>
            <td style="text-align:right;color:#6b7280">{{ $contract->date?->format('d.m.Y') ?? '—' }}</td>
            <td style="text-align:right">{{ $contract->invoices_count }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@endif

{{-- Other projects contracts --}}
@if($otherCount > 0)
<div style="margin-bottom:1.5rem">
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:.75rem;color:#6b7280">{{ __('Contracts on other projects') }}</h2>

    @foreach($otherContractsByProject as $projectId => $contracts)
    @php $project = $contracts->first()->project; @endphp
    <div style="margin-bottom:1.25rem">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.4rem">
            <span style="font-size:13px;font-weight:600;color:#374151">{{ $project->name }}</span>
            <span style="font-size:11px;color:#6b7280">({{ $contracts->count() }})</span>
        </div>
        <div style="overflow-x:auto">
        <table class="ltbl" style="font-size:13px">
            <thead>
                <tr>
                    <th style="text-align:left;min-width:90px">{{ __('Code') }}</th>
                    <th style="text-align:left;min-width:220px">{{ __('Name') }}</th>
                    <th style="text-align:left;min-width:100px">{{ __('Direction') }}</th>
                    <th style="min-width:80px">{{ __('Currency') }}</th>
                    <th style="min-width:130px">{{ __('Value') }}</th>
                    <th style="min-width:100px">{{ __('Date') }}</th>
                    <th style="min-width:70px">{{ __('Invoices') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                <tr>
                    <td><code style="font-size:12px;color:#6b7280">{{ $contract->code }}</code></td>
                    <td style="text-align:left"><a href="{{ route('contracts.open', $contract) }}">{{ $contract->name }}</a></td>
                    <td style="text-align:left">{{ $contract->direction === 1 ? __('Income') : __('Expense') }}</td>
                    <td style="text-align:right;font-size:12px;color:#6b7280">{{ $contract->currency }}</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums">{{ number_format((float)($contract->items_sum_amount ?? 0), 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $contract->date?->format('d.m.Y') ?? '—' }}</td>
                    <td style="text-align:right">{{ $contract->invoices_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
