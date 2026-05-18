@extends('layouts.app')
@section('title', __('Anticipated').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}">{{ $budget->name }}</a>
    <span>{{ __('Anticipated') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Anticipated') }} <span style="font-size:.7em;font-weight:400;color:#6b7280">{{ $budget->name }}</span></h1>
</div>

@php
    $fmt = fn($v) => number_format(round($v), 0, ',', ' ');
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
                <th style="text-align:right;width:130px">{{ __('Anticipated (manual)') }}</th>
                <th style="text-align:right;width:130px">{{ __('Contract Ant.') }}</th>
                <th style="text-align:right;width:130px">{{ __('Change Requests') }}</th>
                <th style="text-align:right;width:140px;font-weight:700">{{ __('Total') }}</th>
                <th style="width:80px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($flatItems as $row)
            @php
                $item = $row['item'];
                $d    = $costData->get($item->id, []);
                $antM = (float) ($d['ant_manual_sum'] ?? 0);
                $antCa= (float) ($d['anticipated_ca'] ?? 0);
                $antCr= (float) ($d['anticipated_cr'] ?? 0);
                $total= $antM + $antCa + $antCr;
            @endphp
            <tr>
                <td style="color:#6b7280;font-size:12px">{{ $row['path'] }}</td>
                <td><code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code></td>
                <td>{{ $item->description }}</td>
                <td style="text-align:right">{{ $antM != 0 ? $fmt($antM) : '—' }}</td>
                <td style="text-align:right;color:#6b7280">{{ $antCa != 0 ? $fmt($antCa) : '—' }}</td>
                <td style="text-align:right;color:{{ $antCr > 0 ? '#1d4ed8' : ($antCr < 0 ? '#dc2626' : '#9ca3af') }}">
                    {{ $antCr != 0 ? (($antCr > 0 ? '+' : '').$fmt($antCr)) : '—' }}
                </td>
                <td style="text-align:right;font-weight:700">{{ $total != 0 ? $fmt($total) : '—' }}</td>
                <td style="text-align:right">
                    <a href="{{ route('budget-items.anticipated', $item) }}" style="font-size:11px;color:#6b7280;text-decoration:underline">{{ __('Detail') }}</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

<a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('Back') }}</a>
@endsection
