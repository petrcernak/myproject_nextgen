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
@endphp

@if($canEdit)
<form method="POST" action="{{ route('budgets.anticipated.save', $budget) }}">
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
                <th style="text-align:right;width:160px">{{ __('Actual Budget') }}</th>
                <th style="text-align:right;width:160px">{{ __('Anticipated (manual)') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($flatItems as $row)
            @php $item = $row['item']; @endphp
            <tr>
                <td style="color:#6b7280;font-size:12px">{{ $row['path'] }}</td>
                <td><code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code></td>
                <td>{{ $item->description }}</td>
                <td style="text-align:right;color:#374151">{{ number_format(round($item->amount), 0, ',', ' ') }}</td>
                <td style="text-align:right">
                    @if($canEdit)
                        <input type="number" name="items[{{ $item->id }}]"
                               value="{{ $item->anticipated_manual != 0 ? $item->anticipated_manual : '' }}"
                               placeholder="0"
                               step="1" style="text-align:right;width:140px">
                    @else
                        {{ $item->anticipated_manual != 0 ? number_format(round($item->anticipated_manual), 0, ',', ' ') : '—' }}
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
