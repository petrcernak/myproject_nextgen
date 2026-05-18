@extends('layouts.app')
@section('title', __('Value to Place').' — '.$item->description)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <span>{{ $item->code ? $item->code.' · ' : '' }}{{ $item->description }}</span>
</div>
<div class="page-header">
    <div>
        <h1>
            @if($item->code)<code style="font-size:.75em;color:#6b7280">{{ $item->code }}</code> @endif
            {{ $item->description }}
        </h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ $item->category->name }} · {{ $budget->name }} ({{ $budget->currency }})
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem">{{ session('success') }}</div>
@endif

@php
    $fmt = fn($v) => number_format(round($v), 0, ',', ' ');
    $sgn = fn($v) => ($v > 0 ? '+' : '').number_format(round($v), 0, ',', ' ');
    $cc  = fn($v) => $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af');
@endphp

<div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:flex-start;margin-bottom:1.5rem">

    {{-- Computation breakdown --}}
    <div class="card" style="flex:0 0 auto;min-width:380px">
        <div style="padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:13px">
            {{ __('Computation') }}
        </div>
        <table style="font-size:12px">
            <tbody>
                <tr>
                    <td style="color:#6b7280">{{ __('Actual Budget') }}</td>
                    <td style="text-align:right;font-weight:600;width:130px">{{ $fmt($actual) }}</td>
                </tr>
                <tr>
                    <td style="color:#6b7280">− {{ __('Current Commitments') }}</td>
                    <td style="text-align:right;font-weight:600">{{ $fmt($currComm) }}</td>
                </tr>
                <tr>
                    <td style="color:#6b7280">− {{ __('Anticipated') }}</td>
                    <td style="text-align:right;font-weight:600">{{ $fmt($anticipated) }}</td>
                </tr>
                <tr>
                    <td style="color:#6b7280">− {{ __('FX Impact') }}</td>
                    <td style="text-align:right;font-weight:{{ abs($fxImpact) > 0.5 ? '600' : '400' }};color:{{ $cc(-$fxImpact) }}">
                        {{ abs($fxImpact) > 0.5 ? $sgn(-$fxImpact) : '—' }}
                    </td>
                </tr>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="color:#6b7280">= {{ __('Residual (auto)') }}</td>
                    <td style="text-align:right;font-weight:600">{{ $fmt($vtpAutoResidual) }}</td>
                </tr>
                <tr style="border-top:1px solid #e5e7eb">
                    <td style="color:#6b7280">{{ __('Manual entries') }}</td>
                    <td style="text-align:right;font-weight:600">{{ $fmt($vtpManualSum) }}</td>
                </tr>
                @if($vtpAutoEnabled)
                <tr>
                    <td style="color:#6b7280">{{ __('Auto residual') }}</td>
                    <td style="text-align:right;font-weight:600">{{ $fmt($autoResidual) }}</td>
                </tr>
                @endif
                <tr style="border-top:2px solid #e5e7eb;font-weight:700">
                    <td style="padding-top:.4rem">{{ __('Total VtP') }}</td>
                    <td style="text-align:right;font-size:14px;padding-top:.4rem">{{ $fmt($vtpTotal) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Auto toggle --}}
    <div class="card" style="flex:0 0 auto;min-width:300px">
        <div style="padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:13px">
            {{ __('Auto-calculation') }}
        </div>
        <div style="padding:.75rem 1rem">
            <div style="font-size:12px;color:#6b7280;margin-bottom:.75rem">
                {{ __('When enabled, the system adds max(0, Actual − CurrComm − Anticipated − FX − manual entries) to the total.') }}
            </div>
            @if($canEdit)
            <form method="POST" action="{{ route('budget-items.vtp-auto.toggle', $item) }}">
                @csrf
                <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="vtp_auto" value="1"
                        {{ $vtpAutoEnabled ? 'checked' : '' }}
                        onchange="this.form.submit()">
                    {{ __('Use auto-calculation') }}
                </label>
            </form>
            @else
                <span style="font-size:13px;color:{{ $vtpAutoEnabled ? '#166534' : '#6b7280' }}">
                    {{ $vtpAutoEnabled ? __('Auto-calculation ON') : __('Auto-calculation OFF') }}
                </span>
            @endif
        </div>
    </div>
</div>

{{-- Filter + entries list --}}
<div class="card" style="margin-bottom:1.5rem">
    <div style="padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
        <span style="font-weight:600;font-size:13px">{{ __('Manual entries') }}</span>
        <form method="GET" action="{{ route('budget-items.value-to-place', $item) }}" style="display:flex;gap:.5rem;align-items:center;margin-left:auto">
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('Filter by description…') }}"
                style="font-size:12px;width:220px">
            <button type="submit" class="btn btn-secondary" style="font-size:12px;padding:.25rem .6rem">{{ __('Filter') }}</button>
            @if($search)
                <a href="{{ route('budget-items.value-to-place', $item) }}" class="btn btn-secondary" style="font-size:12px;padding:.25rem .6rem">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @if($vtpEntries->isEmpty())
        <div style="padding:.75rem 1rem;font-size:13px;color:#9ca3af">
            {{ $search ? __('No entries match the filter.') : __('No manual entries yet.') }}
        </div>
    @else
    <table style="font-size:12px">
        <thead>
            <tr>
                <th style="width:110px">{{ __('Date') }}</th>
                <th>{{ __('Description') }}</th>
                <th style="text-align:right;width:130px">{{ __('Amount') }}</th>
                @if($canEdit)<th style="width:120px"></th>@endif
            </tr>
        </thead>
        <tbody>
        @foreach($vtpEntries as $entry)
            <tr id="row-{{ $entry->id }}">
                <td style="color:#6b7280">{{ $entry->date->format('d.m.Y') }}</td>
                <td>{{ $entry->description ?? '—' }}</td>
                <td style="text-align:right;font-weight:600">{{ $fmt($entry->amount) }}</td>
                @if($canEdit)
                <td style="text-align:right">
                    <button type="button" onclick="toggleEdit({{ $entry->id }})"
                        style="font-size:11px;background:none;border:none;cursor:pointer;color:#6b7280;text-decoration:underline">
                        {{ __('Edit') }}
                    </button>
                    <form method="POST" action="{{ route('budget-item-vtps.destroy', $entry) }}"
                        style="display:inline"
                        onsubmit="return confirm('{{ __('Delete this entry?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size:11px;background:none;border:none;cursor:pointer;color:#dc2626;text-decoration:underline">
                            {{ __('Delete') }}
                        </button>
                    </form>
                </td>
                @endif
            </tr>
            @if($canEdit)
            <tr id="edit-{{ $entry->id }}" style="display:none;background:#f8fafc">
                <td colspan="4" style="padding:.5rem .75rem">
                    <form method="POST" action="{{ route('budget-item-vtps.update', $entry) }}"
                        style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                        @csrf @method('PUT')
                        <div class="form-group" style="margin-bottom:0">
                            <label style="font-size:11px;margin-bottom:.2rem">{{ __('Date') }}</label>
                            <input type="date" name="date" value="{{ $entry->date->format('Y-m-d') }}" required style="width:140px;font-size:12px">
                        </div>
                        <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px">
                            <label style="font-size:11px;margin-bottom:.2rem">{{ __('Description') }}</label>
                            <input type="text" name="description" value="{{ $entry->description }}" style="width:100%;font-size:12px">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label style="font-size:11px;margin-bottom:.2rem">{{ __('Amount') }}</label>
                            <input type="number" name="amount" value="{{ round($entry->amount) }}" step="1" required style="width:130px;font-size:12px">
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size:12px;padding:.3rem .7rem">{{ __('Save') }}</button>
                        <button type="button" onclick="toggleEdit({{ $entry->id }})"
                            class="btn btn-secondary" style="font-size:12px;padding:.3rem .7rem">{{ __('Cancel') }}</button>
                    </form>
                </td>
            </tr>
            @endif
        @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:700;background:#f9fafb">
                <td colspan="2" style="text-align:right;font-size:12px;color:#6b7280">{{ __('Total') }}</td>
                <td style="text-align:right">{{ $fmt($vtpEntries->sum('amount')) }}</td>
                @if($canEdit)<td></td>@endif
            </tr>
        </tfoot>
    </table>
    @endif
</div>

@if($canEdit)
{{-- Add new entry --}}
<div class="card" style="max-width:700px;margin-bottom:1.5rem">
    <div style="padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:13px">
        {{ __('Add entry') }}
    </div>
    <div style="padding:.75rem 1rem">
        <form method="POST" action="{{ route('budget-items.vtp.store', $item) }}"
            style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div class="form-group" style="margin-bottom:0">
                <label style="font-size:11px;margin-bottom:.2rem">{{ __('Date') }}</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required style="width:140px;font-size:12px">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px">
                <label style="font-size:11px;margin-bottom:.2rem">{{ __('Description / reason') }}</label>
                <input type="text" name="description" style="width:100%;font-size:12px" placeholder="{{ __('e.g. Foundation works phase 2') }}">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label style="font-size:11px;margin-bottom:.2rem">{{ __('Amount') }} ({{ $budget->currency }})</label>
                <input type="number" name="amount" step="1" required style="width:130px;font-size:12px" placeholder="0">
            </div>
            <button type="submit" class="btn btn-primary" style="font-size:12px;padding:.3rem .7rem">{{ __('Add') }}</button>
        </form>
    </div>
</div>
@endif

<div>
    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
</div>

<script>
function toggleEdit(id) {
    var row = document.getElementById('edit-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>
@endsection
