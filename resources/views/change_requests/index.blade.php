@extends('layouts.app')
@section('title', __('Change requests') . ' — ' . $contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <span>{{ __('Change requests') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Change requests') }}: {{ $contract->name }}</h1>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contracts.change-requests.create', $contract) }}" class="btn btn-primary">+ {{ __('New change request') }}</a>
        @endif
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
    </div>
</div>

<form method="GET" style="display:flex;gap:.5rem;margin-bottom:1rem;align-items:flex-end;flex-wrap:wrap">
    <div>
        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Search') }}</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Code / name...') }}" style="width:240px">
    </div>
    <div>
        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Status') }}</label>
        <select name="status" style="width:150px" onchange="this.form.submit()">
            <option value="">{{ __('All') }}</option>
            <option value="open"      @selected(request('status')==='open')>{{ __('Open') }}</option>
            <option value="on_hold"   @selected(request('status')==='on_hold')>{{ __('On hold') }}</option>
            <option value="closed"    @selected(request('status')==='closed')>{{ __('Closed') }}</option>
            <option value="rejected"  @selected(request('status')==='rejected')>{{ __('Rejected') }}</option>
            <option value="converted" @selected(request('status')==='converted')>{{ __('Converted') }}</option>
        </select>
    </div>
    <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
    @if(request()->hasAny(['search','status']))<a href="{{ route('contracts.change-requests.index', $contract) }}" class="btn btn-secondary">{{ __('Clear') }}</a>@endif
</form>

<div class="card">
    @if($changeRequests->isEmpty())
        <div class="empty"><strong>{{ __('No change requests') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:130px">{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th style="width:90px">{{ __('Status') }}</th>
                    <th style="width:100px">{{ __('Date') }}</th>
                    <th style="text-align:right;width:60px">{{ __('Items') }}</th>
                    <th style="text-align:right;width:120px">{{ __('Supplier') }}</th>
                    <th style="text-align:right;width:120px">{{ __('PM') }}</th>
                    <th style="text-align:right;width:120px;background:#fef9c3">{{ __('Assumed') }}</th>
                    <th style="text-align:right;width:120px;background:#eff6ff">{{ __('Report') }}</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($changeRequests as $cr)
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $cr->code }}</code></td>
                    <td>
                        <a href="{{ route('change-requests.show', $cr) }}">{{ $cr->name }}</a>
                        @if($cr->note)<div style="font-size:12px;color:#6b7280">{{ $cr->note }}</div>@endif
                    </td>
                    <td>
                        <span class="badge {{ $cr->status_badge_class }}">{{ $cr->status_label }}</span>
                        @if($cr->status === 'converted' && $cr->convertedChangeOrder)
                            <a href="{{ route('change-orders.show', $cr->convertedChangeOrder) }}"
                               style="font-size:11px;color:#6b7280;margin-left:.3rem">→ {{ $cr->convertedChangeOrder->code }}</a>
                        @endif
                    </td>
                    <td>{{ $cr->date?->format('d.m.Y') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $cr->items->count() }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($cr->total_supplier, 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($cr->total_pm, 2, ',', ' ') }}</td>
                    <td style="text-align:right;font-weight:600;background:#fef9c3;color:{{ $cr->countsInReport() ? '#854d0e' : '#9ca3af' }}">{{ number_format($cr->total_report, 2, ',', ' ') }}</td>
                    <td style="text-align:right;font-weight:600;background:#eff6ff;color:{{ $cr->countsInReport() ? '#2563eb' : '#9ca3af' }}">
                        {{ $cr->countsInReport() ? number_format($cr->total_report, 2, ',', ' ') : '—' }}
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('change-requests.show', $cr) }}" class="btn btn-secondary btn-sm">{{ __('Detail') }}</a>
                    </td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb;font-weight:600">
                    <td colspan="5" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($changeRequests->sum('total_supplier'), 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($changeRequests->sum('total_pm'), 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#854d0e;background:#fef9c3">{{ number_format($changeRequests->sum('total_report'), 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#2563eb;background:#eff6ff">{{ number_format($changeRequests->sum('total_effective_report'), 2, ',', ' ') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <div style="padding:.75rem 1rem">{{ $changeRequests->links() }}</div>
    @endif
</div>
@endsection
