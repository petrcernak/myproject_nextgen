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

@if($budget->note)
    <div class="card card-body" style="margin-bottom:1rem;font-size:13px;color:#374151">{{ $budget->note }}</div>
@endif

{{-- tiles are rendered after $grand is computed below --}}

@php
    $colFmt = fn($v) => number_format(round($v), 0, ',', ' ');
    $colSign = fn($v) => ($v > 0 ? '+' : '').number_format(round($v), 0, ',', ' ');
    $colColor = fn($v) => $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af');

    $itemCostData = function($item) use ($costData): array {
        $d          = $costData->get($item->id, []);
        $amount     = (float) $item->amount;
        $adj        = (float) $item->adjustment;
        $trans      = (float) $item->transfer;
        $actual     = $amount + $adj + $trans;
        $contracts  = (float) ($d['contracts'] ?? 0);
        $changesCo  = (float) ($d['changes_co'] ?? 0);
        $changesAmd = (float) ($d['changes_amd'] ?? 0);
        $changes    = $changesCo + $changesAmd;
        $currComm   = $contracts + $changes;
        $invoiced   = (float) ($d['invoiced'] ?? 0);
        $fxImpact   = (float) ($d['fx_impact'] ?? 0);
        $antCa      = (float) ($d['anticipated_ca'] ?? 0);
        $antCr      = (float) ($d['anticipated_cr'] ?? 0);
        $antManual  = (float) $item->anticipated_manual;
        $anticipated = $antCa + $antCr + $antManual;
        $vtp        = $item->value_to_place_manual !== null
            ? (float) $item->value_to_place_manual
            : max(0.0, $actual - $currComm - $anticipated - $fxImpact);
        $futureComm = $vtp + $anticipated;
        $cost       = $currComm + $futureComm + $fxImpact;
        $delta      = $cost - $actual;

        return compact('amount','adj','trans','actual','contracts','changesCo','changesAmd',
                       'changes','currComm','invoiced','fxImpact','antCa','antCr','antManual',
                       'anticipated','vtp','futureComm','cost','delta');
    };

    $subtotalFn = function($cat) use (&$subtotalFn, $itemCostData): array {
        $keys = ['amount','adj','trans','actual','contracts','changesCo','changesAmd','changes',
                 'currComm','invoiced','fxImpact','antCa','antCr','antManual',
                 'anticipated','vtp','futureComm','cost','delta'];
        $t = array_fill_keys($keys, 0.0);
        foreach ($cat->items as $item) {
            foreach ($itemCostData($item) as $k => $v) { if (isset($t[$k])) $t[$k] += $v; }
        }
        foreach ($cat->children as $child) {
            foreach ($subtotalFn($child) as $k => $v) { if (isset($t[$k])) $t[$k] += $v; }
        }
        return $t;
    };

    $rootCategories = $budget->categories->whereNull('parent_id');
    $keys = ['amount','adj','trans','actual','contracts','changesCo','changesAmd','changes',
             'currComm','invoiced','fxImpact','antCa','antCr','antManual',
             'anticipated','vtp','futureComm','cost','delta'];
    $grand = array_fill_keys($keys, 0.0);
    foreach ($rootCategories as $rc) {
        foreach ($subtotalFn($rc) as $k => $v) { if (isset($grand[$k])) $grand[$k] += $v; }
    }
    $fmt = fn($v) => number_format(round($v), 0, ',', ' ');
    $sgn = fn($v) => ($v != 0 ? ($v > 0 ? '+' : '').number_format(round($v), 0, ',', ' ') : '—');
    $cc  = fn($v) => $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af');
@endphp

{{-- Budget tiles --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem">

    {{-- Tile 1: Actual Budget --}}
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Actual Budget') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $fmt($grand['actual']) }}</div>
        <div style="font-size:12px;margin-top:.5rem;display:flex;flex-direction:column;gap:.2rem">
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <span style="color:#6b7280">{{ __('Original budget') }}</span>
                <span>{{ $fmt($grand['amount']) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.adjustments.index', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Adjustments') }}</a>
                <span style="font-weight:600;color:{{ $cc($grand['adj']) }}">{{ $grand['adj'] != 0 ? $sgn($grand['adj']) : '—' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.transfers.index', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Transfers') }}</a>
                <span style="font-weight:600;color:{{ $cc($grand['trans']) }}">{{ $grand['trans'] != 0 ? $sgn($grand['trans']) : '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Tile 2: Current Commitments --}}
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Curr. Commitments') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $fmt($grand['currComm']) }}</div>
        <div style="font-size:12px;margin-top:.5rem;display:flex;flex-direction:column;gap:.2rem">
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.contracts', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Contracts') }}</a>
                <span>{{ $fmt($grand['contracts']) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.amendments', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Amendments') }}</a>
                <span style="font-weight:600;color:{{ $cc($grand['changesAmd']) }}">{{ $grand['changesAmd'] != 0 ? $sgn($grand['changesAmd']) : '—' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.change-orders', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Change orders') }}</a>
                <span style="font-weight:600;color:{{ $cc($grand['changesCo']) }}">{{ $grand['changesCo'] != 0 ? $sgn($grand['changesCo']) : '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Tile 3: Invoiced + FX Impact --}}
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Invoiced') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $fmt($grand['invoiced']) }}</div>
        <div style="font-size:12px;margin-top:.5rem;display:flex;flex-direction:column;gap:.2rem">
            <div>
                <span style="color:#6b7280">{{ __('Invoices') }}: <strong>{{ $invoiceCount }}</strong></span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem;margin-top:.35rem;padding-top:.35rem;border-top:1px solid #f3f4f6">
                <span style="color:#6b7280;text-transform:uppercase;font-size:10px;letter-spacing:.04em;align-self:center">{{ __('FX Impact') }}</span>
                <span style="font-size:1rem;font-weight:700;color:{{ $cc($grand['fxImpact']) }}">{{ $grand['fxImpact'] != 0 ? $sgn($grand['fxImpact']) : '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Tile 4: Future Commitments --}}
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Future Comm.') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $fmt($grand['futureComm']) }}</div>
        <div style="font-size:12px;margin-top:.5rem;display:flex;flex-direction:column;gap:.2rem">
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.value-to-place', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Value to Place') }}</a>
                <span>{{ $fmt($grand['vtp']) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.anticipated', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Anticipated') }}</a>
                <span>{{ $fmt($grand['anticipated']) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.contract-anticipated', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Contract Ant.') }}</a>
                <span>{{ $fmt($grand['antCa']) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <a href="{{ route('budgets.change-requests', $budget) }}" style="color:#6b7280;text-decoration:none">{{ __('Change Requests') }}</a>
                <span style="font-weight:600;color:{{ $cc($grand['antCr']) }}">{{ $grand['antCr'] != 0 ? $sgn($grand['antCr']) : '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Tile 5: Projected Final Cost + Δ --}}
    <div class="card card-body" style="border:2px solid #dbeafe">
        <div style="font-size:11px;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Projected Final') }}</div>
        <div style="font-size:1.6rem;font-weight:700">{{ $fmt($grand['cost']) }}</div>
        <div style="margin-top:.75rem;padding-top:.5rem;border-top:1px solid #dbeafe">
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">{{ __('Delta') }}</div>
            <div style="font-size:1.4rem;font-weight:700;color:{{ $cc($grand['delta']) }}">
                {{ $grand['delta'] != 0 ? $sgn($grand['delta']) : '—' }}
            </div>
        </div>
    </div>

</div>

@php
$cols = [
    ['Amount',            100],
    ['Adjustment',         90],
    ['Transfer',           90],
    ['Actual Budget',     110],
    ['Contracts',          90],
    ['Changes',            90],
    ['Curr. Comm.',       110],
    ['Invoiced',           90],
    ['FX Impact',          80],
    ['Value to Place',     90],
    ['Anticipated',        90],
    ['Future Comm.',      100],
    ['Cost',               90],
    ['△',                  80],
];
@endphp

@if($rootCategories->isNotEmpty())
{{-- Single scroll container — header + rows scroll together --}}
<div style="overflow-x:auto;margin-bottom:1.5rem" id="budget-table-scroll">
    {{-- Sticky column header --}}
    <div style="display:flex;align-items:center;position:sticky;top:0;z-index:20;background:#f1f5f9;border-bottom:2px solid #cbd5e1;padding:.35rem 1rem .35rem 1rem;min-width:max-content;gap:0">
        <div style="flex:1;min-width:300px;font-size:10px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em">
            {{ __('Item') }}
        </div>
        @foreach($cols as [$label, $w])
        <span style="min-width:{{ $w }}px;text-align:right;padding-right:6px;font-size:10px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap">
            {{ __($label) }}
        </span>
        @endforeach
    </div>

    {{-- Category rows --}}
    @foreach($rootCategories as $category)
        @include('budgets._cat_show', [
            'category'    => $category,
            'budget'      => $budget,
            'depth'       => 0,
            'subtotalFn'  => $subtotalFn,
            'itemCostData'=> $itemCostData,
            'costData'    => $costData,
        ])
    @endforeach

    {{-- Grand total --}}
    <div class="card card-body" style="display:flex;align-items:center;font-size:13px;font-weight:700;min-width:max-content;margin-top:.25rem;gap:0">
        <span style="flex:1;min-width:300px">{{ __('Total') }}</span>
        @php $gv = [$grand['amount'],$grand['adj'],$grand['trans'],$grand['actual'],$grand['contracts'],$grand['changes'],$grand['currComm'],$grand['invoiced'],$grand['fxImpact'],$grand['vtp'],$grand['anticipated'],$grand['futureComm'],$grand['cost'],$grand['delta']]; @endphp
        @foreach($cols as $i => [$label, $w])
        @php $v = $gv[$i]; @endphp
        <span style="min-width:{{ $w }}px;text-align:right;padding-right:6px;color:{{ in_array($label,['Adjustment','Transfer','FX Impact','△']) ? ($colColor($v)) : ($v < 0 ? '#dc2626' : 'inherit') }}">
            {{ $v != 0 ? $colFmt($v) : '—' }}
        </span>
        @endforeach
    </div>
</div>
@else
    <div class="card" style="margin-bottom:1.5rem"><div class="empty"><strong>{{ __('No categories') }}</strong>
        @if($canEdit)<p><a href="{{ route('budgets.content', $budget) }}">{{ __('Go to edit mode') }}</a> {{ __('and add categories.') }}</p>@endif
    </div></div>
@endif


@endsection
