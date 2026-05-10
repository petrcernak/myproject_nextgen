@extends('layouts.app')
@section('title', __('Contract Anticipated').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <span>{{ __('Contract Anticipated') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Contract Anticipated') }} <span style="font-size:.7em;font-weight:400;color:#6b7280">{{ $budget->name }}</span></h1>
    <div style="font-size:12px;color:#6b7280">
        {{ __('Read-only — data is pulled from the latest Anticipated entry on each linked contract.') }}
    </div>
</div>

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
    $total = 0.0;
@endphp

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
                <th style="text-align:right;width:140px">{{ __('Actual Budget') }}</th>
                <th style="text-align:right;width:140px">{{ __('Contract Ant.') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($flatItems as $row)
            @php
                $item  = $row['item'];
                $d     = $costData->get($item->id, []);
                $antCa = (float) ($d['anticipated_ca'] ?? 0);
                $total += $antCa;
            @endphp
            <tr>
                <td style="color:#6b7280;font-size:12px">{{ $row['path'] }}</td>
                <td><code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code></td>
                <td>{{ $item->description }}</td>
                <td style="text-align:right;color:#6b7280">{{ $fmt((float)$item->amount) }}</td>
                <td style="text-align:right;font-weight:{{ $antCa != 0 ? '600' : '400' }}">
                    {{ $antCa != 0 ? $fmt($antCa) : '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:700">
                <td colspan="4" style="text-align:right">{{ __('Total') }}</td>
                <td style="text-align:right">{{ $fmt($total) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>

<a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
@endsection
