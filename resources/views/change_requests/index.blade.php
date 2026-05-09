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

<form method="GET" style="display:flex;gap:.5rem;margin-bottom:1rem">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search code / name...') }}" style="width:280px">
    <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
    @if(request('search'))<a href="{{ route('contracts.change-requests.index', $contract) }}" class="btn btn-secondary">{{ __('Clear') }}</a>@endif
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
                    <th style="width:100px">{{ __('Date') }}</th>
                    <th style="text-align:right;width:60px">{{ __('Items') }}</th>
                    <th style="text-align:right;width:130px">{{ __('Supplier') }}</th>
                    <th style="text-align:right;width:130px">{{ __('PM') }}</th>
                    <th style="text-align:right;width:130px;background:#eff6ff">{{ __('Report') }}</th>
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
                    <td>{{ $cr->date?->format('d.m.Y') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $cr->items->count() }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($cr->total_supplier, 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($cr->total_pm, 2, ',', ' ') }}</td>
                    <td style="text-align:right;font-weight:600;color:#2563eb;background:#eff6ff">{{ number_format($cr->total_report, 2, ',', ' ') }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('change-requests.show', $cr) }}" class="btn btn-secondary btn-sm">{{ __('Detail') }}</a>
                    </td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb;font-weight:600">
                    <td colspan="4" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($changeRequests->sum('total_supplier'), 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($changeRequests->sum('total_pm'), 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#2563eb;background:#eff6ff">{{ number_format($changeRequests->sum('total_report'), 2, ',', ' ') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <div style="padding:.75rem 1rem">{{ $changeRequests->links() }}</div>
    @endif
</div>
@endsection
