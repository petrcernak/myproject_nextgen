@extends('layouts.app')
@section('title', $item->code ? $item->code.' — '.$item->description : $item->description)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $item->contract) }}"><span>{{ $item->contract->name }}</span></a>
    <span>{{ $item->code ? $item->code.' — '.Str::limit($item->description, 40) : Str::limit($item->description, 50) }}</span>
</div>

<div class="page-header">
    <div>
        <h1 style="font-size:1.1rem">
            @if($item->code)<code style="color:#6b7280;font-size:.85em">{{ $item->code }}</code> @endif
            {{ $item->description }}
        </h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">{{ $item->contract->name }} · {{ $item->contract->currency }}</div>
    </div>
    <a href="{{ route('contracts.show', $item->contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>

{{-- Summary cards --}}
@php
    $coStandalone = $item->changeOrderItems->filter(fn($coi) => $coi->changeOrder?->amendment_id === null)->sum('amount');
    $amdChange    = $item->amendmentItems->sum('amount')
                  + $item->changeOrderItems->filter(fn($coi) => $coi->changeOrder?->amendment_id !== null)->sum('amount');
    $effective    = $item->amount + $coStandalone + $amdChange;
    $invoiced     = $item->invoiceItems->sum('amount');
    $crLatest     = $item->changeRequestItems->sum(fn($cri) => $cri->revisions->first()?->amount_report ?? 0);
    $caTotal      = $item->anticipatedItems->sum('amount');
@endphp

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Original amount') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ number_format($item->amount, 2, ',', ' ') }}</div>
        @if($coStandalone != 0 || $amdChange != 0)
        <div style="font-size:12px;margin-top:.4rem;display:flex;flex-direction:column;gap:.15rem">
            @if($coStandalone != 0)<div style="color:#6b7280">CO: <span style="font-weight:600;color:{{ $coStandalone > 0 ? '#1d4ed8' : '#dc2626' }}">{{ $coStandalone > 0 ? '+' : '' }}{{ number_format($coStandalone, 2, ',', ' ') }}</span></div>@endif
            @if($amdChange != 0)<div style="color:#6b7280">{{ __('Amd') }}: <span style="font-weight:600;color:{{ $amdChange > 0 ? '#1d4ed8' : '#dc2626' }}">{{ $amdChange > 0 ? '+' : '' }}{{ number_format($amdChange, 2, ',', ' ') }}</span></div>@endif
        </div>
        @endif
    </div>
    <div class="card card-body">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">{{ __('Effective amount') }}</div>
        <div style="font-size:1.4rem;font-weight:700">{{ number_format($effective, 2, ',', ' ') }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:.3rem">
            {{ __('Invoiced') }}: {{ number_format($invoiced, 2, ',', ' ') }}
            &nbsp;·&nbsp;
            <span style="color:{{ ($effective-$invoiced) > 0 ? '#1d4ed8' : '#dc2626' }}">{{ __('Rem.') }}: {{ number_format($effective - $invoiced, 2, ',', ' ') }}</span>
        </div>
    </div>
    @if($crLatest != 0 || $item->changeRequestItems->isNotEmpty())
    <div class="card card-body" style="background:#fef2f2">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">CR</div>
        <div style="font-size:1.4rem;font-weight:700;color:{{ $crLatest > 0 ? '#dc2626' : ($crLatest < 0 ? '#16a34a' : '#374151') }}">
            {{ $crLatest > 0 ? '+' : '' }}{{ number_format($crLatest, 2, ',', ' ') }}
        </div>
    </div>
    @endif
    @if($caTotal != 0 || $item->anticipatedItems->isNotEmpty())
    <div class="card card-body" style="background:#fef2f2">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">CA</div>
        <div style="font-size:1.4rem;font-weight:700;color:{{ $caTotal > 0 ? '#dc2626' : ($caTotal < 0 ? '#16a34a' : '#374151') }}">
            {{ $caTotal > 0 ? '+' : '' }}{{ number_format($caTotal, 2, ',', ' ') }}
        </div>
        <div style="font-size:12px;color:#6b7280;margin-top:.3rem">
            {{ __('Expected total') }}: <strong>{{ number_format($effective + $caTotal, 2, ',', ' ') }}</strong>
        </div>
    </div>
    @endif
</div>

{{-- Change Orders --}}
@if($item->changeOrderItems->isNotEmpty())
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Change orders') }}</h2>
</div>
<div class="card" style="margin-bottom:1.5rem">
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="width:120px">{{ __('Code') }}</th>
                <th>{{ __('Name') }}</th>
                <th style="width:120px">{{ __('Amendment') }}</th>
                <th style="width:100px">{{ __('Date') }}</th>
                <th style="text-align:right;width:140px">{{ __('Change amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->changeOrderItems->sortBy('sort') as $coi)
            @php $co = $coi->changeOrder; @endphp
            <tr>
                <td><code style="color:#6b7280;font-size:11px">{{ $co->code }}</code></td>
                <td><a href="{{ route('change-orders.show', $co) }}">{{ $co->name }}</a>
                    @if($coi->description)<div style="font-size:11px;color:#6b7280">{{ $coi->description }}</div>@endif
                </td>
                <td style="font-size:12px;color:#6b7280">
                    @if($co->amendment)
                        <a href="{{ route('amendments.show', $co->amendment) }}">{{ $co->amendment->code }}</a>
                    @else
                        <span style="color:#9ca3af">{{ __('standalone') }}</span>
                    @endif
                </td>
                <td style="color:#6b7280">{{ $co->date?->format('d.m.Y') ?? '—' }}</td>
                <td style="text-align:right;font-weight:600;color:{{ $coi->amount > 0 ? '#1d4ed8' : ($coi->amount < 0 ? '#dc2626' : '#6b7280') }}">
                    {{ $coi->amount > 0 ? '+' : '' }}{{ number_format($coi->amount, 2, ',', ' ') }}
                </td>
            </tr>
            @endforeach
            <tr style="background:#f9fafb;font-weight:600">
                <td colspan="4" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                <td style="text-align:right;color:{{ $item->changeOrderItems->sum('amount') >= 0 ? '#1d4ed8' : '#dc2626' }}">
                    {{ $item->changeOrderItems->sum('amount') >= 0 ? '+' : '' }}{{ number_format($item->changeOrderItems->sum('amount'), 2, ',', ' ') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- Amendments --}}
@if($item->amendmentItems->isNotEmpty())
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Amendments') }}</h2>
</div>
<div class="card" style="margin-bottom:1.5rem">
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="width:120px">{{ __('Code') }}</th>
                <th>{{ __('Name') }}</th>
                <th style="width:100px">{{ __('Date') }}</th>
                <th style="text-align:right;width:140px">{{ __('Change amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->amendmentItems as $ai)
            <tr>
                <td><code style="color:#6b7280;font-size:11px">{{ $ai->amendment->code }}</code></td>
                <td><a href="{{ route('amendments.show', $ai->amendment) }}">{{ $ai->amendment->name }}</a>
                    @if($ai->description)<div style="font-size:11px;color:#6b7280">{{ $ai->description }}</div>@endif
                </td>
                <td style="color:#6b7280">{{ $ai->amendment->date?->format('d.m.Y') ?? '—' }}</td>
                <td style="text-align:right;font-weight:600;color:{{ $ai->amount > 0 ? '#1d4ed8' : ($ai->amount < 0 ? '#dc2626' : '#6b7280') }}">
                    {{ $ai->amount > 0 ? '+' : '' }}{{ number_format($ai->amount, 2, ',', ' ') }}
                </td>
            </tr>
            @endforeach
            <tr style="background:#f9fafb;font-weight:600">
                <td colspan="3" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                <td style="text-align:right;color:{{ $item->amendmentItems->sum('amount') >= 0 ? '#1d4ed8' : '#dc2626' }}">
                    {{ $item->amendmentItems->sum('amount') >= 0 ? '+' : '' }}{{ number_format($item->amendmentItems->sum('amount'), 2, ',', ' ') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- Change Requests --}}
@if($item->changeRequestItems->isNotEmpty())
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Change requests') }}</h2>
</div>
<div class="card" style="margin-bottom:1.5rem">
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="width:120px">{{ __('Code') }}</th>
                <th>{{ __('Name') }}</th>
                <th style="width:100px">{{ __('Date') }}</th>
                <th style="text-align:right;width:120px">{{ __('Supplier') }}</th>
                <th style="text-align:right;width:120px">{{ __('PM') }}</th>
                <th style="text-align:right;width:120px;background:#dbeafe">{{ __('Report') }}</th>
                <th style="width:60px;text-align:right">{{ __('Rev.') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->changeRequestItems as $cri)
            @php $rev = $cri->revisions->first(); @endphp
            <tr>
                <td><code style="color:#6b7280;font-size:11px">{{ $cri->changeRequest->code }}</code></td>
                <td><a href="{{ route('change-requests.show', $cri->changeRequest) }}">{{ $cri->changeRequest->name }}</a>
                    @if($cri->description)<div style="font-size:11px;color:#6b7280">{{ $cri->description }}</div>@endif
                </td>
                <td style="color:#6b7280">{{ $cri->changeRequest->date?->format('d.m.Y') ?? '—' }}</td>
                <td style="text-align:right;color:#6b7280">{{ $rev ? number_format($rev->amount_supplier, 2, ',', ' ') : '—' }}</td>
                <td style="text-align:right;color:#6b7280">{{ $rev ? number_format($rev->amount_pm, 2, ',', ' ') : '—' }}</td>
                <td style="text-align:right;font-weight:600;background:#eff6ff">{{ $rev ? number_format($rev->amount_report, 2, ',', ' ') : '—' }}</td>
                <td style="text-align:right;color:#6b7280">{{ $cri->revisions->count() }}</td>
            </tr>
            @endforeach
            <tr style="background:#f9fafb;font-weight:600">
                <td colspan="5" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                <td style="text-align:right;background:#eff6ff">{{ number_format($crLatest, 2, ',', ' ') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- Anticipated --}}
@if($item->anticipatedItems->isNotEmpty())
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Contract anticipated') }}</h2>
</div>
<div class="card" style="margin-bottom:1.5rem">
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="width:120px">{{ __('Code') }}</th>
                <th>{{ __('Name') }}</th>
                <th style="width:100px">{{ __('Date') }}</th>
                <th style="text-align:right;width:140px">{{ __('Anticipated change') }}</th>
                <th style="text-align:right;width:140px">{{ __('Expected total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->anticipatedItems as $ai)
            <tr>
                <td><code style="color:#6b7280;font-size:11px">{{ $ai->anticipated->code }}</code></td>
                <td><a href="{{ route('contract-anticipateds.show', $ai->anticipated) }}">{{ $ai->anticipated->name }}</a>
                    @if($ai->description)<div style="font-size:11px;color:#6b7280">{{ $ai->description }}</div>@endif
                </td>
                <td style="color:#6b7280">{{ $ai->anticipated->date?->format('d.m.Y') ?? '—' }}</td>
                <td style="text-align:right;font-weight:600;color:{{ $ai->amount > 0 ? '#dc2626' : ($ai->amount < 0 ? '#16a34a' : '#6b7280') }}">
                    {{ $ai->amount > 0 ? '+' : '' }}{{ number_format($ai->amount, 2, ',', ' ') }}
                </td>
                <td style="text-align:right;color:#1d4ed8;font-weight:600">
                    {{ number_format($effective + $ai->amount, 2, ',', ' ') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
