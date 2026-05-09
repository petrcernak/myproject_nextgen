@extends('layouts.app')
@section('title', __('Amendments') . ' — ' . $contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <span>{{ __('Amendments') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Amendments') }}: {{ $contract->name }}</h1>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contracts.amendments.create', $contract) }}" class="btn btn-primary">+ {{ __('New amendment') }}</a>
        @endif
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
    </div>
</div>

<form method="GET" style="display:flex;gap:.5rem;margin-bottom:1rem">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search code / name...') }}" style="width:280px">
    <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
    @if(request('search'))<a href="{{ route('contracts.amendments.index', $contract) }}" class="btn btn-secondary">{{ __('Clear') }}</a>@endif
</form>

<div class="card">
    @if($amendments->isEmpty())
        <div class="empty"><strong>{{ __('No amendments') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:80px">{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th style="width:100px">{{ __('Date') }}</th>
                    <th style="text-align:right;width:60px">{{ __('COs') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Value') }}</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($amendments as $amendment)
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $amendment->code }}</code></td>
                    <td>
                        <a href="{{ route('amendments.show', $amendment) }}">{{ $amendment->name }}</a>
                        @if($amendment->note)<div style="font-size:12px;color:#6b7280">{{ $amendment->note }}</div>@endif
                    </td>
                    <td>{{ $amendment->date?->format('d.m.Y') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $amendment->changeOrders->count() }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $amendment->total >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ $amendment->total >= 0 ? '+' : '' }}{{ number_format($amendment->total, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('amendments.show', $amendment) }}" class="btn btn-secondary btn-sm">{{ __('Detail') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:.75rem 1rem">{{ $amendments->links() }}</div>
    @endif
</div>
@endsection
