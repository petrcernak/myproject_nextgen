@extends('layouts.app')
@section('title', __('Budgets'))

@section('content')
<div class="page-header">
    <h1>{{ __('Budgets') }} — {{ $currentProject->name }}</h1>
    @if($currentProject && $canEdit)
        <a href="{{ route('projects.budgets.create', $currentProject) }}" class="btn btn-primary">+ {{ __('New budget') }}</a>
    @endif
</div>

<div class="card">
    @if($budgets->isEmpty())
        <div class="empty"><strong>{{ __('No budgets') }}</strong><p>{{ __('Create the first budget using the button above.') }}</p></div>
    @else
        <table>
            <thead>
                <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Date') }}</th><th style="text-align:right">{{ __('Total') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($budgets as $budget)
                <tr>
                    <td><code>{{ $budget->code }}</code></td>
                    <td><a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a></td>
                    <td>{{ $budget->date?->format('d.m.Y') ?? '—' }}</td>
                    <td style="text-align:right">{{ number_format($budget->total, 2, ',', ' ') }}</td>
                    <td style="text-align:right">@if($canEdit)<a href="{{ route('budgets.edit', $budget) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:1rem">{{ $budgets->links() }}</div>
    @endif
</div>
@endsection
