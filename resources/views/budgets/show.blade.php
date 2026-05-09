@extends('layouts.app')
@section('title', $budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <span>{{ $budget->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $budget->name }} <code style="font-size:.8em;color:#6b7280">{{ $budget->code }}</code></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ $budget->currency }}{{ $budget->date ? ' · '.$budget->date->format('d.m.Y') : '' }}
        </div>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('budgets.content', $budget) }}" class="btn btn-primary">{{ __('Edit content') }}</a>
            <a href="{{ route('budgets.edit', $budget) }}" class="btn btn-secondary">{{ __('Settings') }}</a>
            <form method="POST" action="{{ route('budgets.destroy', $budget) }}" onsubmit="return confirm('{{ __('Really delete the entire budget?') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">{{ __('Delete') }}</button>
            </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Total value') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ number_format($budget->total, 2, ',', ' ') }}</div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Category count') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $budget->categories->count() }}</div>
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Total item count') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $budget->categories->sum(fn($c) => $c->items->count()) }}</div>
    </div>
</div>

@if($budget->note)
    <div class="card card-body" style="margin-bottom:1.5rem;font-size:13px;color:#374151">{{ $budget->note }}</div>
@endif

@forelse($budget->categories as $category)
<div class="card" style="margin-bottom:1rem">
    <div style="padding:.65rem 1rem;background:#f8fafc;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between">
        <div>
            @if($category->code)
                <code style="color:#6b7280;margin-right:.5rem;font-size:12px">{{ $category->code }}</code>
            @endif
            <strong>{{ $category->name }}</strong>
        </div>
        <strong style="font-size:14px">{{ number_format($category->total, 2, ',', ' ') }}</strong>
    </div>

    @if($category->items->isNotEmpty())
    <table>
        <thead>
            <tr>
                <th style="width:110px">{{ __('Code') }}</th>
                <th>{{ __('Description') }}</th>
                <th style="text-align:right">{{ __('Amount') }} ({{ $budget->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($category->items as $item)
            <tr>
                <td><code style="color:#6b7280;font-size:12px">{{ $item->code ?? '—' }}</code></td>
                <td>{{ $item->description }}</td>
                <td style="text-align:right">{{ number_format($item->amount, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
            <tr style="background:#f9fafb;font-weight:600">
                <td colspan="2" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Category subtotal') }}</td>
                <td style="text-align:right">{{ number_format($category->total, 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>
    @else
        <div style="padding:.75rem 1rem;font-size:13px;color:#9ca3af;font-style:italic">{{ __('No items') }}</div>
    @endif
</div>
@empty
    <div class="card"><div class="empty"><strong>{{ __('No categories') }}</strong>
        @if($canEdit)<p><a href="{{ route('budgets.content', $budget) }}">{{ __('Go to edit mode') }}</a> {{ __('and add categories.') }}</p>@endif
    </div></div>
@endforelse

@if($budget->categories->isNotEmpty())
<div class="card card-body" style="display:flex;justify-content:space-between;align-items:center;font-size:15px;font-weight:600">
    <span>{{ __('Total') }}</span>
    <span>{{ number_format($budget->total, 2, ',', ' ') }}</span>
</div>
@endif
@endsection
