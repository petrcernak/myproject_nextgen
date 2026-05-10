@extends('layouts.app')
@section('title', __('Transfers').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <span>{{ __('Transfers') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Transfers') }} <span style="font-size:.7em;font-weight:400;color:#6b7280">{{ $budget->name }}</span></h1>
    @if($canEdit)
        <a href="{{ route('budgets.transfers.create', $budget) }}" class="btn btn-primary">{{ __('+ New transfer') }}</a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($budget->transfers->isEmpty())
    <div class="card card-body" style="color:#9ca3af;font-size:13px">{{ __('No transfers yet.') }}</div>
@else
<div class="card" style="margin-bottom:1.5rem">
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="white-space:nowrap">{{ __('Date') }}</th>
                <th>{{ __('Description') }}</th>
                <th>{{ __('From') }}</th>
                <th>{{ __('To') }}</th>
                <th style="text-align:right;width:160px">{{ __('Amount') }} ({{ $budget->currency }})</th>
                <th style="width:60px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($budget->transfers->sortByDesc('date') as $tr)
            <tr>
                <td style="white-space:nowrap;color:#6b7280">{{ $tr->date->format('d.m.Y') }}</td>
                <td><a href="{{ route('budget-transfers.show', $tr) }}">{{ $tr->description }}</a></td>
                <td style="font-size:12px">
                    <code style="color:#6b7280;font-size:11px">{{ $tr->fromItem->code ?? '' }}</code>
                    {{ $tr->fromItem->description }}
                    <span style="color:#9ca3af">· {{ $tr->fromItem->category?->name }}</span>
                </td>
                <td style="font-size:12px">
                    <code style="color:#6b7280;font-size:11px">{{ $tr->toItem->code ?? '' }}</code>
                    {{ $tr->toItem->description }}
                    <span style="color:#9ca3af">· {{ $tr->toItem->category?->name }}</span>
                </td>
                <td style="text-align:right;font-weight:600">{{ number_format($tr->amount, 2, ',', ' ') }}</td>
                <td style="text-align:right">
                    @if($canEdit)
                    <a href="{{ route('budget-transfers.edit', $tr) }}" style="font-size:12px;color:#6b7280">{{ __('Edit') }}</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back to budget') }}</a>
@endsection
