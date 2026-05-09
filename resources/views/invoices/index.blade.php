@extends('layouts.app')
@section('title', ($isAdvanceList ? __('Down payments') : __('Invoices')) . ($selectedContract ? ' — ' . $selectedContract->name : ''))

@section('content')
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
    </h1>
    <div style="display:flex;gap:.5rem">
        @if($isAdvanceList && $selectedContract)
            <a href="{{ route('contracts.invoices.create', $selectedContract) }}?advance=1" class="btn btn-primary">+ {{ __('New down payment') }}</a>
        @elseif(!$isAdvanceList && $selectedContract)
            <a href="{{ route('contracts.invoices.create', $selectedContract) }}" class="btn btn-primary">+ {{ __('New invoice') }}</a>
        @endif
        @if($selectedContract)
            <a href="{{ route('contracts.show', $selectedContract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Number / description...') }}" style="width:180px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Contract') }}</label>
                <select name="contract_id" style="width:200px" onchange="this.form.submit()">
                    <option value="">{{ __('All contracts') }}</option>
                    @foreach($contracts as $c)
                        <option value="{{ $c->id }}" @selected(request('contract_id') == $c->id)>
                            {{ $c->code ? $c->code.' — ' : '' }}{{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Company') }}</label>
                <select name="company_id" style="width:180px" onchange="this.form.submit()">
                    <option value="">{{ __('All companies') }}</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected(request('company_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Status') }}</label>
                <select name="status" style="width:160px" onchange="this.form.submit()">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="1" @selected(request('status')=='1')>{{ __('Awaiting payment') }}</option>
                    <option value="3" @selected(request('status')=='3')>{{ __('Due soon') }}</option>
                    <option value="4" @selected(request('status')=='4')>{{ __('Overdue') }}</option>
                    <option value="2" @selected(request('status')=='2')>{{ __('Paid') }}</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('From') }}</label>
                <input type="date" name="from" value="{{ request('from') }}" style="width:140px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('To') }}</label>
                <input type="date" name="to" value="{{ request('to') }}" style="width:140px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Files') }}</label>
                <select name="file_filter" style="width:150px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="0" @selected(request('file_filter')==='0')>{{ __('No files') }}</option>
                    <option value="1" @selected(request('file_filter')==='1')>{{ __('Has files') }}</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
            @if(request()->hasAny(['search','contract_id','company_id','status','from','to','file_filter']))
                <a href="{{ route('invoices.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @if($invoices->isEmpty())
        <div class="empty"><strong>{{ __('No invoices') }}</strong><p>{{ __('Invoices are created through the contract detail.') }}</p></div>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('Number') }}</th>
                    <th>{{ __('Contract') }}</th>
                    <th>{{ __('Issued') }}</th>
                    <th>{{ __('Due') }}</th>
                    <th>{{ __('Paid') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th style="width:60px;text-align:center"></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->no }}</a></td>
                    <td><a href="{{ route('contracts.show', $invoice->contract) }}">{{ $invoice->contract->name }}</a></td>
                    <td>{{ $invoice->issued?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $invoice->due?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $invoice->paid?->format('d.m.Y') ?? '—' }}</td>
                    <td>
                        @php $cls = match($invoice->status) { 2 => 'badge-green', 4 => 'badge-red', 3 => 'badge-yellow', default => 'badge-gray' }; @endphp
                        <span class="badge {{ $cls }}">{{ $invoice->status_label }}</span>
                    </td>
                    <td style="text-align:center">
                        @if($invoice->files_count)
                            <a href="{{ route('invoices.show', $invoice) }}#files"
                               style="display:inline-flex;align-items:center;gap:.25rem;font-size:12px;color:#6b7280;text-decoration:none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                {{ $invoice->files_count }}
                            </a>
                        @endif
                    </td>
                    <td style="text-align:right"><a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:1rem">{{ $invoices->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
