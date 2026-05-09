@extends('layouts.app')
@section('title', __('Contract anticipated') . ' — ' . $contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <span>{{ __('Contract anticipated') }}</span>
</div>

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Contract anticipated') }}: {{ $contract->name }}</h1>
    <div style="display:flex;gap:.5rem">
        @if($canEdit)
            <a href="{{ route('contracts.anticipateds.create', $contract) }}" class="btn btn-primary">+ {{ __('New anticipated') }}</a>
        @endif
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
    </div>
</div>

<form method="GET" style="display:flex;gap:.5rem;margin-bottom:1rem">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search code / name...') }}" style="width:280px">
    <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
    @if(request('search'))<a href="{{ route('contracts.anticipateds.index', $contract) }}" class="btn btn-secondary">{{ __('Clear') }}</a>@endif
</form>

<div class="card">
    @if($anticipateds->isEmpty())
        <div class="empty"><strong>{{ __('No anticipated') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:160px">{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th style="width:100px">{{ __('Date') }}</th>
                    <th style="text-align:right;width:120px">{{ __('Items covered') }}</th>
                    <th style="text-align:right;width:150px">{{ __('Anticipated change') }}</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($anticipateds as $ca)
                @php $delta = $ca->items->sum('amount'); @endphp
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $ca->code }}</code></td>
                    <td>
                        <a href="{{ route('contract-anticipateds.show', $ca) }}">{{ $ca->name }}</a>
                        @if($ca->note)<div style="font-size:12px;color:#6b7280">{{ $ca->note }}</div>@endif
                    </td>
                    <td>{{ $ca->date?->format('d.m.Y') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $ca->items->count() }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $delta > 0 ? '#dc2626' : ($delta < 0 ? '#16a34a' : '#6b7280') }}">
                        {{ $delta != 0 ? ($delta >= 0 ? '+' : '') . number_format($delta, 2, ',', ' ') : '—' }}
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('contract-anticipateds.show', $ca) }}" class="btn btn-secondary btn-sm">{{ __('Detail') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:.75rem 1rem">{{ $anticipateds->links() }}</div>
    @endif
</div>
@endsection
