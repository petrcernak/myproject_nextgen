@extends('layouts.app')
@section('title', __('Value to Place').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <span>{{ __('Value to Place') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Value to Place') }} <span style="font-size:.7em;font-weight:400;color:#6b7280">{{ $budget->name }}</span></h1>
    <div style="font-size:12px;color:#6b7280;max-width:480px;line-height:1.5">
        {{ __('Value to Place = max(0, Actual Budget − Curr. Commitments − Anticipated − FX Impact). Override with a manual value to fix it.') }}
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@php
    $flatItems = [];
    $walkCats = function ($cats, $path = '') use (&$walkCats, &$flatItems) {
        foreach ($cats as $cat) {
            $catPath = $path ? $path.' / '.$cat->name : $cat->name;
            foreach ($cat->items as $item) {
                $flatItems[] = ['item' => $item, 'path' => $catPath];
            }
            $walkCats($cat->children, $catPath);
        }
    };
    $walkCats($budget->categories->whereNull('parent_id'));

    $fmt = fn($v) => number_format(round($v), 0, ',', ' ');
@endphp

@if($canEdit)
<form method="POST" action="{{ route('budgets.value-to-place.save', $budget) }}">
    @csrf
@endif

<div class="card" style="margin-bottom:1.5rem">
    @if(empty($flatItems))
        <div class="empty"><strong>{{ __('No items') }}</strong></div>
    @else
    <table style="font-size:13px">
        <thead>
            <tr>
                <th>{{ __('Category') }}</th>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Description') }}</th>
                <th style="text-align:right;width:120px">{{ __('Actual Budget') }}</th>
                <th style="text-align:right;width:120px">{{ __('Curr. Comm.') }}</th>
                <th style="text-align:right;width:120px">{{ __('Anticipated') }}</th>
                <th style="text-align:right;width:120px">{{ __('Computed VtP') }}</th>
                <th style="text-align:right;width:160px">{{ __('Manual Override') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($flatItems as $row)
            @php
                $item  = $row['item'];
                $d     = $costData->get($item->id, []);
                $amount    = (float) $item->amount;
                $adj       = (float) $item->adjustment;
                $trans     = (float) $item->transfer;
                $actual    = $amount + $adj + $trans;
                $contracts = (float) ($d['contracts'] ?? 0);
                $changesCo = (float) ($d['changes_co'] ?? 0);
                $changesAmd= (float) ($d['changes_amd'] ?? 0);
                $currComm  = $contracts + $changesCo + $changesAmd;
                $fxImpact  = (float) ($d['fx_impact'] ?? 0);
                $antCa     = (float) ($d['anticipated_ca'] ?? 0);
                $antCr     = (float) ($d['anticipated_cr'] ?? 0);
                $antManual = (float) $item->anticipated_manual;
                $anticipated = $antCa + $antCr + $antManual;
                $computedVtp = max(0.0, $actual - $currComm - $anticipated - $fxImpact);
            @endphp
            <tr>
                <td style="color:#6b7280;font-size:12px">{{ $row['path'] }}</td>
                <td><code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code></td>
                <td>{{ $item->description }}</td>
                <td style="text-align:right">{{ $fmt($actual) }}</td>
                <td style="text-align:right">{{ $fmt($currComm) }}</td>
                <td style="text-align:right">{{ $fmt($anticipated) }}</td>
                <td style="text-align:right;color:#6b7280">{{ $fmt($computedVtp) }}</td>
                <td style="text-align:right">
                    @if($canEdit)
                        <input type="number" name="items[{{ $item->id }}]"
                               value="{{ $item->value_to_place_manual !== null ? $item->value_to_place_manual : '' }}"
                               placeholder="{{ __('auto') }}"
                               step="1" style="text-align:right;width:140px">
                    @else
                        {{ $item->value_to_place_manual !== null ? $fmt($item->value_to_place_manual) : '—' }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@if($canEdit)
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
    </div>
</form>
@else
    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
@endif
@endsection
