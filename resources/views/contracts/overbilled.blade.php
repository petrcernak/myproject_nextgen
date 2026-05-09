@extends('layouts.app')
@section('title', __('Overbilled contracts'))

@section('content')
<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Overbilled contracts') }}</h1>
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
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
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Currency') }}</label>
                <select name="currency" style="width:110px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($currencies as $c)
                        <option value="{{ $c }}" @selected(request('currency') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('From') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" style="width:140px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('To') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" style="width:140px">
            </div>
            <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
            @if(request()->hasAny(['company_id','currency','date_from','date_to']))
                <a href="{{ route('contracts.overbilled') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @if($contracts->isEmpty())
        <div class="empty"><strong>{{ __('No overbilled contracts') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Company') }}</th>
                    <th style="width:60px">{{ __('Curr.') }}</th>
                    <th style="text-align:right;width:130px">{{ __('Contract value') }}</th>
                    <th style="text-align:right;width:120px">{{ __('Invoiced') }}</th>
                    <th style="text-align:right;width:55px">{{ __('%') }}</th>
                    <th style="text-align:right;width:130px">{{ __('Excess') }}</th>
                    <th style="width:110px">{{ __('Type') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                @php
                    $totalOver = $contract->stat_invoiced > $contract->stat_revised_total;
                    $itemOver  = $contract->stat_overbilled_items->isNotEmpty();
                @endphp
                <tr>
                    <td><a href="{{ route('contracts.show', $contract) }}"><code style="font-size:11px">{{ $contract->code }}</code></a></td>
                    <td>
                        <a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a>
                        @if($itemOver && !$totalOver)
                            <div style="font-size:11px;color:#dc2626;margin-top:.15rem">
                                {{ $contract->stat_overbilled_items->count() }} {{ __('item(s) overbilled') }}:
                                {{ $contract->stat_overbilled_items->map(fn($i) => $i->code ?: $i->description)->implode(', ') }}
                            </div>
                        @endif
                    </td>
                    <td style="color:#6b7280;font-size:12px">{{ $contract->company?->name ?? '—' }}</td>
                    <td style="font-size:12px;color:#6b7280">{{ $contract->currency }}</td>
                    <td style="text-align:right">{{ number_format($contract->stat_revised_total, 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#dc2626;font-weight:600">{{ number_format($contract->stat_invoiced, 2, ',', ' ') }}</td>
                    <td style="text-align:right">
                        <span style="font-size:11px;font-weight:600;color:#dc2626">{{ $contract->stat_pct }} %</span>
                    </td>
                    <td style="text-align:right;font-weight:600;color:#dc2626">
                        {{ $totalOver ? number_format($contract->stat_diff, 2, ',', ' ') : '—' }}
                    </td>
                    <td style="font-size:11px">
                        @if($totalOver)
                            <span class="badge badge-red">{{ __('Total') }}</span>
                        @endif
                        @if($itemOver)
                            <span class="badge badge-yellow">{{ __('Item') }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:.75rem 1rem;font-size:12px;color:#6b7280">{{ $contracts->count() }} {{ __('contracts') }}</div>
    @endif
</div>
@endsection
