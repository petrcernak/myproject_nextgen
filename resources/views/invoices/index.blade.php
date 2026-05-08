@extends('layouts.app')
@section('title', __('Invoices'))

@section('content')
<div class="page-header">
    <h1>{{ __('Invoices') }} — {{ $currentProject->name }}</h1>
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Number / description...') }}" style="width:200px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Status') }}</label>
                <select name="status" style="width:170px">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="1" @selected(request('status')=='1')>{{ __('Awaiting payment') }}</option>
                    <option value="3" @selected(request('status')=='3')>{{ __('Due soon') }}</option>
                    <option value="4" @selected(request('status')=='4')>{{ __('Overdue') }}</option>
                    <option value="2" @selected(request('status')=='2')>{{ __('Paid') }}</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('From') }}</label>
                <input type="date" name="from" value="{{ request('from') }}" style="width:145px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('To') }}</label>
                <input type="date" name="to" value="{{ request('to') }}" style="width:145px">
            </div>
            <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
            @if(request()->hasAny(['search','status','from','to']))
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
                    <th>{{ __('Contract / Project') }}</th>
                    <th>{{ __('Issued') }}</th>
                    <th>{{ __('Due') }}</th>
                    <th>{{ __('Paid') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->no }}</a></td>
                    <td>
                        <a href="{{ route('contracts.show', $invoice->contract) }}">{{ $invoice->contract->name }}</a>
                        <div style="font-size:11px;color:#6b7280">{{ $invoice->contract->project->name }}</div>
                    </td>
                    <td>{{ $invoice->issued?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $invoice->due?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $invoice->paid?->format('d.m.Y') ?? '—' }}</td>
                    <td>
                        @php $cls = match($invoice->status) { 2 => 'badge-green', 4 => 'badge-red', 3 => 'badge-yellow', default => 'badge-gray' }; @endphp
                        <span class="badge {{ $cls }}">{{ $invoice->status_label }}</span>
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
