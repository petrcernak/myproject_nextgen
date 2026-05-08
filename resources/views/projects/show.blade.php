@extends('layouts.app')
@section('title', $project->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    <span>{{ $project->name }}</span>
</div>

<div class="page-header">
    <h1>{{ $project->name }} <code style="font-size:.8em;color:#6b7280">{{ $project->code }}</code></h1>
    <div style="display:flex;gap:.5rem">
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
        @if($project->isDeletable())
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">{{ __('Delete') }}</button>
            </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <table style="font-size:13px">
            <tr><td style="color:#6b7280;padding:.3rem .5rem .3rem 0;border:none">{{ __('Company') }}</td><td style="border:none">{{ $project->company?->name ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;padding:.3rem .5rem .3rem 0;border:none">{{ __('Status') }}</td><td style="border:none">{{ ucfirst($project->status) }}</td></tr>
            <tr><td style="color:#6b7280;padding:.3rem .5rem;border:none;vertical-align:top">{{ __('Note') }}</td><td style="border:none">{{ $project->note ?? '—' }}</td></tr>
        </table>
    </div>
</div>

<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Budgets') }}</h2>
    <a href="{{ route('projects.budgets.create', $project) }}" class="btn btn-primary btn-sm">+ {{ __('New budget') }}</a>
</div>
<div class="card" style="margin-bottom:1.5rem">
    @php $budgets = $project->budgets ?? $project->budgets()->get(); @endphp
    @if($budgets->isEmpty())
        <div class="empty"><strong>{{ __('No budgets') }}</strong></div>
    @else
        <table>
            <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Date') }}</th><th style="text-align:right">{{ __('Total') }}</th><th></th></tr></thead>
            <tbody>
                @foreach($budgets as $budget)
                <tr>
                    <td><code>{{ $budget->code }}</code></td>
                    <td><a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a></td>
                    <td>{{ $budget->date?->format('d.m.Y') ?? '—' }}</td>
                    <td style="text-align:right">{{ number_format($budget->total, 2, ',', ' ') }}</td>
                    <td style="text-align:right"><a href="{{ route('budgets.edit', $budget) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="page-header">
    <h2 style="font-size:1rem">{{ __('Contracts') }}</h2>
    <a href="{{ route('projects.contracts.create', $project) }}" class="btn btn-primary btn-sm">+ {{ __('New contract') }}</a>
</div>
<div class="card">
    @if($project->contracts->isEmpty())
        <div class="empty"><strong>{{ __('No contracts') }}</strong></div>
    @else
        <table>
            <thead>
                <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Company') }}</th><th>{{ __('Direction') }}</th><th>{{ __('Currency') }}</th><th>{{ __('Date') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($project->contracts as $contract)
                <tr>
                    <td><code>{{ $contract->code }}</code></td>
                    <td><a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a></td>
                    <td>{{ $contract->company?->name ?? '—' }}</td>
                    <td>{{ $contract->direction === 1 ? __('Income') : __('Expense') }}</td>
                    <td>{{ $contract->currency }}</td>
                    <td>{{ $contract->date?->format('d.m.Y') ?? '—' }}</td>
                    <td style="text-align:right"><a href="{{ route('contracts.edit', $contract) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
