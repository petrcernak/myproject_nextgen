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
    <select name="file_filter" style="width:150px" onchange="this.form.submit()">
        <option value="">{{ __('All') }}</option>
        <option value="0" @selected(request('file_filter')==='0')>{{ __('No files') }}</option>
        <option value="1" @selected(request('file_filter')==='1')>{{ __('Has files') }}</option>
    </select>
    <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
    @if(request()->hasAny(['search','amendment_id','file_filter']))<a href="{{ route('contracts.change-orders.index', $contract) }}" class="btn btn-secondary">{{ __('Clear') }}</a>@endif
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
                    <th style="width:60px;text-align:center"></th>
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
                    <td style="text-align:center">
                        @if($co->files_count)
                            <a href="{{ route('change-orders.show', $co) }}#files"
                               style="display:inline-flex;align-items:center;gap:.25rem;font-size:12px;color:#6b7280;text-decoration:none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                {{ $co->files_count }}
                            </a>
                        @endif
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
