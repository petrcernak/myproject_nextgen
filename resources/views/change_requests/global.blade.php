@extends('layouts.app')
@section('title', __('Change requests'))

@section('content')
<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Change requests') }}</h1>
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
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Status') }}</label>
                <select name="status" style="width:140px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="open"      @selected(request('status')==='open')>{{ __('Open') }}</option>
                    <option value="on_hold"   @selected(request('status')==='on_hold')>{{ __('On hold') }}</option>
                    <option value="closed"    @selected(request('status')==='closed')>{{ __('Closed') }}</option>
                    <option value="rejected"  @selected(request('status')==='rejected')>{{ __('Rejected') }}</option>
                    <option value="converted" @selected(request('status')==='converted')>{{ __('Converted') }}</option>
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
            @if(request()->hasAny(['contract_id','company_id','currency','status','date_from','date_to']))
                <a href="{{ route('change-requests.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @if($changeRequests->isEmpty())
        <div class="empty"><strong>{{ __('No change requests') }}</strong></div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:120px">{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Contract') }}</th>
                    <th style="width:60px">{{ __('Currency') }}</th>
                    <th style="width:90px">{{ __('Status') }}</th>
                    <th style="width:100px">{{ __('Date') }}</th>
                    <th style="text-align:right;width:60px">{{ __('Items') }}</th>
                    <th style="text-align:right;width:130px;background:#fef9c3">{{ __('Assumed') }}</th>
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
                        @if($cr->contract->company)
                            <div style="font-size:11px;color:#6b7280">{{ $cr->contract->company->name }}</div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('contracts.show', $cr->contract) }}" style="color:#6b7280;font-size:12px">
                            {{ $cr->contract->code }} {{ $cr->contract->name }}
                        </a>
                    </td>
                    <td style="font-size:12px;color:#6b7280">{{ $cr->contract->currency }}</td>
                    <td>
                        <span class="badge {{ $cr->status_badge_class }}">{{ $cr->status_label }}</span>
                        @if($cr->status === 'converted' && $cr->convertedChangeOrder)
                            <a href="{{ route('change-orders.show', $cr->convertedChangeOrder) }}" style="font-size:11px;color:#6b7280;display:block">→ {{ $cr->convertedChangeOrder->code }}</a>
                        @endif
                    </td>
                    <td>{{ $cr->date?->format('d.m.Y') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $cr->items->count() }}</td>
                    <td style="text-align:right;font-weight:600;background:#fef9c3;color:{{ $cr->countsInReport() ? '#854d0e' : '#9ca3af' }}">
                        {{ number_format($cr->total_report, 2, ',', ' ') }}
                    </td>
                    <td style="text-align:right;font-weight:600;background:#eff6ff;color:{{ $cr->countsInReport() ? '#2563eb' : '#9ca3af' }}">
                        {{ $cr->countsInReport() ? number_format($cr->total_report, 2, ',', ' ') : '—' }}
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('change-requests.show', $cr) }}" class="btn btn-secondary btn-sm">{{ __('Detail') }}</a>
                    </td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb;font-weight:600">
                    <td colspan="7" style="text-align:right;color:#6b7280;font-weight:400;font-size:12px">{{ __('Total') }}</td>
                    <td style="text-align:right;color:#854d0e;background:#fef9c3">{{ number_format($changeRequests->sum('total_report'), 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#2563eb;background:#eff6ff">{{ number_format($changeRequests->sum('total_effective_report'), 2, ',', ' ') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <div style="padding:.75rem 1rem">{{ $changeRequests->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
