@extends('layouts.app')
@section('title', __('Budgets'))

@section('content')
<div class="page-header">
    <h1>{{ __('Budgets') }} — {{ $currentProject->name }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $budgets->total() }} / {{ $totalCount }}</span>
    </h1>
    @if($currentProject && $canEdit)
        <a href="{{ route('projects.budgets.create', $currentProject) }}" class="btn btn-primary">+ {{ __('New budget') }}</a>
    @endif
</div>

@if($budgets->isEmpty())
    <div class="card"><div class="empty"><strong>{{ __('No budgets') }}</strong><p>{{ __('Create the first budget using the button above.') }}</p></div></div>
@else
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:90px">{{ __('Code') }}</th>
            <th style="text-align:left;min-width:240px">{{ __('Name') }}</th>
            <th style="min-width:100px">{{ __('Date') }}</th>
            <th style="min-width:140px">{{ __('Total') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($budgets as $budget)
        <tr>
            <td><code style="color:#6b7280">{{ $budget->code }}</code></td>
            <td style="text-align:left"><a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a></td>
            <td style="text-align:right;color:#6b7280">{{ $budget->date?->format('d.m.Y') ?? '—' }}</td>
            <td style="text-align:right;font-variant-numeric:tabular-nums">{{ number_format($budget->total, 2, ',', ' ') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
<div>{{ $budgets->withQueryString()->links() }}</div>
@endif
@endsection
