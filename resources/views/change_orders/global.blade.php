@extends('layouts.app')
@section('title', __('Change orders'))

@section('content')
<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Change orders') }}</h1>
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Contract') }}</label>
                <select name="contract_id" style="width:220px" onchange="this.form.submit()">
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
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Currency') }}</label>
                <select name="currency" style="width:100px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($currencies as $cur)
                        <option value="{{ $cur }}" @selected(request('currency') === $cur)>{{ $cur }}</option>
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
            @if(request()->hasAny(['contract_id','company_id','currency','date_from','date_to']))
                <a href="{{ route('change-orders.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @if($changeOrders->isEmpty())
        <div class="empty"><strong>{{ __('No change orders') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:100px">{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Contract') }}</th>
                    <th style="width:120px">{{ __('Amendment') }}</th>
                    <th style="width:60px">{{ __('Currency') }}</th>
                    <th style="width:100px">{{ __('Date') }}</th>
                    <th style="text-align:right;width:130px">{{ __('Total') }}</th>
                    <th style="width:40px;text-align:center"></th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($changeOrders as $co)
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $co->code }}</code></td>
                    <td>
                        <a href="{{ route('change-orders.show', $co) }}">{{ $co->name }}</a>
                        @if($co->contract->company)
                            <div style="font-size:11px;color:#6b7280">{{ $co->contract->company->name }}</div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('contracts.show', $co->contract) }}" style="color:#6b7280;font-size:12px">
                            {{ $co->contract->code }} {{ $co->contract->name }}
                        </a>
                    </td>
                    <td style="font-size:12px;color:#6b7280">
                        @if($co->amendment)
                            <a href="{{ route('amendments.show', $co->amendment) }}" style="color:#6b7280">{{ $co->amendment->code }}</a>
                        @else
                            <span style="color:#92400e">{{ __('Standalone') }}</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#6b7280">{{ $co->contract->currency }}</td>
                    <td>{{ $co->date?->format('d.m.Y') }}</td>
                    <td style="text-align:right;font-weight:600;color:{{ $co->total >= 0 ? '#1d4ed8' : '#dc2626' }}">
                        {{ number_format($co->total, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:center">
                        @if($co->files_count)
                            <a href="{{ route('change-orders.show', $co) }}#files" style="font-size:11px;color:#6b7280;text-decoration:none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
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
        <div style="padding:.75rem 1rem">{{ $changeOrders->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
