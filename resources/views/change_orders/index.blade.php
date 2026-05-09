@extends('layouts.app')
@section('title', __('Change orders') . ' — ' . $contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <span>{{ __('Change orders') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Change orders') }}: {{ $contract->name }}</h1>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contracts.change-orders.create', $contract) }}" class="btn btn-primary">+ {{ __('New change order') }}</a>
        @endif
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
    </div>
</div>

<form method="GET" style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search code / name...') }}" style="width:260px">
    <select name="amendment_id">
        <option value="">{{ __('All') }}</option>
        <option value="0" @selected(request('amendment_id') === '0')>{{ __('Standalone (no amendment)') }}</option>
        @foreach($amendments as $a)
            <option value="{{ $a->id }}" @selected(request('amendment_id') == $a->id)>{{ $a->code }} — {{ $a->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
    @if(request()->hasAny(['search','amendment_id']))<a href="{{ route('contracts.change-orders.index', $contract) }}" class="btn btn-secondary">{{ __('Clear') }}</a>@endif
</form>

<div class="card">
    @if($changeOrders->isEmpty())
        <div class="empty"><strong>{{ __('No change orders') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:80px">{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th style="width:100px">{{ __('Date') }}</th>
                    <th>{{ __('Amendment') }}</th>
                    <th style="text-align:right;width:60px">{{ __('Items') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Value') }}</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($changeOrders as $co)
                @php $total = $co->items->sum('amount'); @endphp
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $co->code }}</code></td>
                    <td>
                        <a href="{{ route('change-orders.show', $co) }}">{{ $co->name }}</a>
                        @if($co->note)<div style="font-size:12px;color:#6b7280">{{ $co->note }}</div>@endif
                    </td>
                    <td>{{ $co->date?->format('d.m.Y') }}</td>
                    <td style="font-size:13px;color:#6b7280">
                        @if($co->amendment)
                            <a href="{{ route('amendments.show', $co->amendment) }}" style="color:#6b7280">{{ $co->amendment->code }}</a>
                        @else
                            <span style="color:#d1d5db">{{ __('standalone') }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;color:#6b7280">{{ $co->items->count() }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $total >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $total >= 0 ? '+' : '' }}{{ number_format($total, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('change-orders.show', $co) }}" class="btn btn-secondary btn-sm">{{ __('Detail') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:.75rem 1rem">{{ $changeOrders->links() }}</div>
    @endif
</div>
@endsection
