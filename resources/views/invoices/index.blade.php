@extends('layouts.app')
@section('title', ($isAdvanceList ? __('Down payments') : __('Invoices')) . ($selectedContract ? ' — ' . $selectedContract->name : ''))

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

@if($selectedContract)
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $selectedContract) }}"><span>{{ $selectedContract->name }}</span></a>
    <span>{{ $isAdvanceList ? __('Down payments') : __('Invoices') }}</span>
</div>
@endif

<div class="page-header">
    <h1 style="font-size:1.1rem">
        {{ $isAdvanceList ? __('Down payments') : __('Invoices') }}
        @if($selectedContract): {{ $selectedContract->name }}@endif
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $invoices->total() }} / {{ $totalCount }}</span>
    </h1>
    <div style="display:flex;gap:.5rem">
        @if($isAdvanceList && $selectedContract)
            <a href="{{ route('contracts.invoices.create', $selectedContract) }}?advance=1" class="btn btn-primary">+ {{ __('New down payment') }}</a>
        @elseif(!$isAdvanceList && $selectedContract)
            <a href="{{ route('contracts.invoices.create', $selectedContract) }}" class="btn btn-primary">+ {{ __('New invoice') }}</a>
        @endif
        @if($selectedContract)
            <a href="{{ route('contracts.show', $selectedContract) }}" class="btn btn-secondary">{{ __('← Back') }}</a>
        @endif
    </div>
</div>

@if($invoices->isEmpty() && !request()->hasAny(['search','contract_id','company_id','status','from','to','file_filter']))
    <div class="card"><div class="empty"><strong>{{ __('No invoices') }}</strong><p>{{ __('Invoices are created through the contract detail.') }}</p></div></div>
@else

<form method="GET" id="lf">
@if(request()->filled('advance'))<input type="hidden" name="advance" value="{{ request('advance') }}">@endif
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:13px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:120px{{ request('search') ? ';background:#dbeafe' : '' }}">
                {{ __('Number') }}
                <input class="{{ $fa('search') }}" type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Number / description…') }}">
            </th>
            <th style="text-align:left;min-width:200px{{ request('contract_id') ? ';background:#dbeafe' : '' }}">
                {{ __('Contract') }}
                <select class="{{ $fa('contract_id') }}" name="contract_id" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($contracts as $c)
                        <option value="{{ $c->id }}" @selected(request('contract_id') == $c->id)>{{ $c->code ? $c->code.' — ' : '' }}{{ $c->name }}</option>
                    @endforeach
                </select>
            </th>
            <th style="text-align:left;min-width:160px{{ request('company_id') ? ';background:#dbeafe' : '' }}">
                {{ __('Company') }}
                <select class="{{ $fa('company_id') }}" name="company_id" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected(request('company_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </th>
            <th style="min-width:100px{{ request('from') ? ';background:#dbeafe' : '' }}">
                {{ __('Issued') }}
                <input class="{{ $fa('from') }}" type="date" name="from" value="{{ request('from') }}">
            </th>
            <th style="min-width:100px{{ request('to') ? ';background:#dbeafe' : '' }}">
                {{ __('To') }}
                <input class="{{ $fa('to') }}" type="date" name="to" value="{{ request('to') }}">
            </th>
            <th style="min-width:100px">{{ __('Due') }}</th>
            <th style="min-width:100px">{{ __('Paid') }}</th>
            <th style="min-width:120px{{ request('status') ? ';background:#dbeafe' : '' }}">
                {{ __('Status') }}
                <select class="{{ $fa('status') }}" name="status" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="1" @selected(request('status')==='1')>{{ __('Awaiting payment') }}</option>
                    <option value="3" @selected(request('status')==='3')>{{ __('Due soon') }}</option>
                    <option value="4" @selected(request('status')==='4')>{{ __('Overdue') }}</option>
                    <option value="2" @selected(request('status')==='2')>{{ __('Paid') }}</option>
                </select>
            </th>
            <th style="min-width:60px{{ request('file_filter') !== null && request('file_filter') !== '' ? ';background:#dbeafe' : '' }}">
                {{ __('Files') }}
                <select class="{{ $fa('file_filter') }}" name="file_filter" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="0" @selected(request('file_filter')==='0')>{{ __('No files') }}</option>
                    <option value="1" @selected(request('file_filter')==='1')>{{ __('Has files') }}</option>
                </select>
            </th>
            <th style="min-width:80px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search','contract_id','company_id','status','from','to','file_filter']))
                    <a href="{{ route('invoices.index') }}{{ request()->filled('advance') ? '?advance='.request('advance') : '' }}" class="btn btn-secondary btn-sm">×</a>
                @endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $invoice)
        <tr>
            <td style="text-align:left"><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->no }}</a></td>
            <td style="text-align:left"><a href="{{ route('contracts.show', $invoice->contract) }}">{{ $invoice->contract->name }}</a></td>
            <td style="text-align:left;color:#6b7280">{{ $invoice->contract->company?->name ?? '—' }}</td>
            <td style="text-align:right;color:#6b7280">{{ $invoice->issued?->format('d.m.Y') ?? '—' }}</td>
            <td style="text-align:right;color:#6b7280"></td>
            <td style="text-align:right;color:#6b7280">{{ $invoice->due?->format('d.m.Y') ?? '—' }}</td>
            <td style="text-align:right;color:#6b7280">{{ $invoice->paid?->format('d.m.Y') ?? '—' }}</td>
            <td>
                @php $cls = match($invoice->status) { 2 => 'badge-green', 4 => 'badge-red', 3 => 'badge-yellow', default => 'badge-gray' }; @endphp
                <span class="badge {{ $cls }}">{{ $invoice->status_label }}</span>
            </td>
            <td style="text-align:center">
                @if($invoice->files_count)
                    <a href="{{ route('invoices.show', $invoice) }}#files" style="display:inline-flex;align-items:center;gap:.25rem;font-size:12px;color:#6b7280;text-decoration:none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ $invoice->files_count }}
                    </a>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No results.') }}</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</form>
<div>{{ $invoices->withQueryString()->links() }}</div>
@endif
@endsection
