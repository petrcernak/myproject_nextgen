@extends('layouts.app')
@section('title', __('Anticipated').' — '.$item->description)

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

    $typeLabels = [
        'anticipated'          => __('Anticipated'),
        'contract anticipated' => __('Contract Anticipated'),
        'change request'       => __('Change Request'),
    ];
    $typeBadge = [
        'anticipated'          => ['#dbeafe', '#1d4ed8'],
        'contract anticipated' => ['#dcfce7', '#166534'],
        'change request'       => ['#fef9c3', '#92400e'],
    ];
@endphp

{{-- Filters --}}
<form method="GET" action="{{ route('budget-items.anticipated', $item) }}" id="antFilterForm"
    style="display:flex;gap:.75rem;align-items:flex-end;margin-bottom:1rem;flex-wrap:wrap">
    <div class="form-group" style="margin-bottom:0;min-width:200px">
        <label style="margin-bottom:.25rem">{{ __('Type') }}</label>
        <select name="type" id="antTypeSelect" onchange="this.form.submit()" style="width:100%">
            <option value="">— {{ __('all types') }} —</option>
            @foreach($typeLabels as $val => $label)
                @if(in_array($val, $availableTypes))
                    <option value="{{ $val }}" @selected($typeFilter === $val)>{{ $label }}</option>
                @endif
            @endforeach
        </select>
    </div>
    <div class="form-group" style="margin-bottom:0;min-width:220px">
        <label style="margin-bottom:.25rem">{{ __('Description') }}</label>
        <input type="text" name="search" id="antSearchInput" value="{{ $textFilter }}"
            placeholder="{{ __('partial match…') }}" style="width:100%" autocomplete="off">
    </div>
    <div style="display:flex;gap:.5rem">
        <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
        @if($typeFilter || $textFilter)
            <a href="{{ route('budget-items.anticipated', $item) }}" class="btn btn-secondary">{{ __('Clear') }}</a>
        @endif
    </div>
</form>

{{-- Entries table --}}
<div class="card" style="margin-bottom:1.5rem">
    @if($rows->isEmpty())
        <div style="padding:.75rem 1rem;font-size:13px;color:#9ca3af">
            {{ ($typeFilter || $textFilter) ? __('No entries match the filter.') : __('No anticipated entries for this item.') }}
        </div>
    @else
    <div style="overflow-x:auto">
    <table style="font-size:12px;white-space:nowrap">
        <thead>
            <tr>
                <th style="width:110px">{{ __('Date') }}</th>
                <th style="width:160px">{{ __('Type') }}</th>
                <th>{{ __('Description') }}</th>
                <th style="text-align:right;width:130px">{{ __('Orig. amount') }}</th>
                <th style="text-align:right;width:80px">{{ __('FX rate') }}</th>
                <th style="text-align:right;width:140px">{{ __('Amount') }} ({{ $budget->currency }})</th>
                @if($canEdit)<th style="width:120px"></th>@endif
            </tr>
        </thead>
        <tbody>
        @php $grandTotal = 0.0; @endphp
        @foreach($rows as $row)
        @php
            $bg     = $typeBadge[$row['type']] ?? ['#f3f4f6','#6b7280'];
            $grandTotal += $row['amount_budget'];
            $isManual = $row['type'] === 'anticipated';
        @endphp
        <tr>
            <td style="color:#6b7280">{{ $row['date']?->format('d.m.Y') ?? '—' }}</td>
            <td>
                <span style="display:inline-block;padding:.15rem .45rem;border-radius:99px;font-size:11px;font-weight:600;background:{{ $bg[0] }};color:{{ $bg[1] }}">
                    {{ $typeLabels[$row['type']] ?? $row['type'] }}
                </span>
            </td>
            <td>{{ $row['description'] ?? '—' }}</td>
            <td style="text-align:right;color:#6b7280">
                @if($row['amount_orig'] !== null)
                    {{ $fmt($row['amount_orig']) }}
                    @if($row['currency'])<span style="font-size:10px;color:#9ca3af;margin-left:.2rem">{{ $row['currency'] }}</span>@endif
                @else
                    —
                @endif
            </td>
            <td style="text-align:right;color:#6b7280">
                {{ $row['fx_rate'] ? number_format($row['fx_rate'], 4, ',', ' ') : '—' }}
            </td>
            <td style="text-align:right;font-weight:600">{{ $fmt($row['amount_budget']) }}</td>
            @if($canEdit)
            <td style="text-align:right">
                @if($isManual)
                    <button type="button" onclick="toggleAntEdit({{ $row['id'] }})"
                        style="font-size:11px;background:none;border:none;cursor:pointer;color:#6b7280;text-decoration:underline">
                        {{ __('Edit') }}
                    </button>
                    <form method="POST" action="{{ route('budget-anticipated-entries.destroy', $row['id']) }}"
                        style="display:inline" onsubmit="return confirm('{{ __('Delete this entry?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size:11px;background:none;border:none;cursor:pointer;color:#dc2626;text-decoration:underline">
                            {{ __('Delete') }}
                        </button>
                    </form>
                @else
                    <span style="font-size:11px;color:#9ca3af">{{ __('read-only') }}</span>
                @endif
            </td>
            @endif
        </tr>
        @if($canEdit && $isManual)
        <tr id="ant-edit-{{ $row['id'] }}" style="display:none;background:#f8fafc">
            <td colspan="{{ $canEdit ? 7 : 6 }}" style="padding:.5rem .75rem">
                <form method="POST" action="{{ route('budget-anticipated-entries.update', $row['id']) }}"
                    style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                    @csrf @method('PUT')
                    <div class="form-group" style="margin-bottom:0">
                        <label style="font-size:11px;margin-bottom:.2rem">{{ __('Date') }}</label>
                        <input type="date" name="date" value="{{ $row['date']?->format('Y-m-d') }}" required style="width:140px;font-size:12px">
                    </div>
                    <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px">
                        <label style="font-size:11px;margin-bottom:.2rem">{{ __('Description') }}</label>
                        <input type="text" name="description" value="{{ $row['entry']->description }}" style="width:100%;font-size:12px">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label style="font-size:11px;margin-bottom:.2rem">{{ __('Amount') }} ({{ $budget->currency }})</label>
                        <input type="number" name="amount" value="{{ round($row['entry']->amount) }}" step="1" required style="width:130px;font-size:12px">
                    </div>
                    <button type="submit" class="btn btn-primary" style="font-size:12px;padding:.3rem .7rem">{{ __('Save') }}</button>
                    <button type="button" onclick="toggleAntEdit({{ $row['id'] }})"
                        class="btn btn-secondary" style="font-size:12px;padding:.3rem .7rem">{{ __('Cancel') }}</button>
                </form>
            </td>
        </tr>
        @endif
        @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:700;background:#f9fafb">
                <td colspan="5" style="text-align:right;font-size:12px;color:#6b7280">{{ __('Total') }} ({{ $budget->currency }})</td>
                <td style="text-align:right">{{ $fmt($grandTotal) }}</td>
                @if($canEdit)<td></td>@endif
            </tr>
        </tfoot>
    </table>
    </div>
    @endif
</div>

@if($canEdit)
{{-- Add manual entry --}}
<div class="card" style="max-width:700px;margin-bottom:1.5rem">
    <div style="padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:13px">
        {{ __('Add anticipated entry') }}
        <span style="font-size:11px;font-weight:400;color:#6b7280;margin-left:.5rem">({{ $budget->currency }})</span>
    </div>
    <div style="padding:.75rem 1rem">
        <form method="POST" action="{{ route('budget-items.anticipated.store', $item) }}"
            style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div class="form-group" style="margin-bottom:0">
                <label style="font-size:11px;margin-bottom:.2rem">{{ __('Date') }}</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required style="width:140px;font-size:12px">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px">
                <label style="font-size:11px;margin-bottom:.2rem">{{ __('Description / reason') }}</label>
                <input type="text" name="description" style="width:100%;font-size:12px" placeholder="{{ __('e.g. Design works Q3') }}">
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
function toggleAntEdit(id) {
    var row = document.getElementById('ant-edit-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

(function () {
    var input = document.getElementById('antSearchInput');
    if (!input) return;
    var timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            document.getElementById('antFilterForm').submit();
        }, 400);
    });
})();
</script>
@endsection
