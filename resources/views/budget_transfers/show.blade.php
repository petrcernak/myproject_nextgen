@extends('layouts.app')
@section('title', __('Transfer').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <a href="{{ route('budgets.transfers.index', $budget) }}">{{ __('Transfers') }}</a>
    <span>{{ __('Transfer') }}</span>
</div>
<div class="page-header">
    <div>
        <h1>{{ $transfer->description }}</h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">{{ $transfer->date->format('d.m.Y') }}</div>
    </div>
    <div style="display:flex;gap:.5rem">
        <a href="{{ route('budget-transfers.edit', $transfer) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
        <form method="POST" action="{{ route('budget-transfers.destroy', $transfer) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger">{{ __('Delete') }}</button>
        </form>
    </div>
</div>

<div class="card card-body" style="max-width:700px">
    <table style="font-size:13px;width:100%">
        <tbody>
            <tr>
                <td style="color:#6b7280;width:120px;padding:.4rem 0">{{ __('From') }}</td>
                <td style="font-weight:600">
                    <code style="color:#6b7280;font-size:11px">{{ $transfer->fromItem->code ?? '—' }}</code>
                    {{ $transfer->fromItem->description }}
                    <span style="font-size:11px;color:#9ca3af">· {{ $transfer->fromItem->category?->name }}</span>
                </td>
            </tr>
            <tr>
                <td style="color:#6b7280;padding:.4rem 0">{{ __('To') }}</td>
                <td style="font-weight:600">
                    <code style="color:#6b7280;font-size:11px">{{ $transfer->toItem->code ?? '—' }}</code>
                    {{ $transfer->toItem->description }}
                    <span style="font-size:11px;color:#9ca3af">· {{ $transfer->toItem->category?->name }}</span>
                </td>
            </tr>
            <tr>
                <td style="color:#6b7280;padding:.4rem 0">{{ __('Amount') }}</td>
                <td style="font-weight:700;font-size:15px">{{ number_format($transfer->amount, 2, ',', ' ') }} {{ $budget->currency }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280">
        {{ __('Net effect') }}:
        <strong style="color:#dc2626">
            −{{ number_format($transfer->amount, 2, ',', ' ') }}
        </strong>
        {{ $transfer->fromItem->description }}
        &nbsp;/&nbsp;
        <strong style="color:#1d4ed8">
            +{{ number_format($transfer->amount, 2, ',', ' ') }}
        </strong>
        {{ $transfer->toItem->description }}
        &nbsp;·&nbsp;
        {{ __('Net sum') }}: <strong>0,00</strong>
    </div>
</div>
@endsection
