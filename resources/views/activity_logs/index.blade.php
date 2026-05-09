@extends('layouts.app')
@section('title', __('Activity log'))

@section('content')
<div class="page-header">
    <h1>{{ __('Activity log') }}</h1>
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Name / label...') }}" style="width:180px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('User') }}</label>
                <select name="user_id" style="width:180px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Action') }}</label>
                <select name="action" style="width:140px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="created"  @selected(request('action')=='created')>{{ __('Created') }}</option>
                    <option value="updated"  @selected(request('action')=='updated')>{{ __('Updated') }}</option>
                    <option value="deleted"  @selected(request('action')=='deleted')>{{ __('Deleted') }}</option>
                    <option value="uploaded" @selected(request('action')=='uploaded')>{{ __('Uploaded') }}</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Type') }}</label>
                <select name="subject" style="width:160px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach(['Contract','Invoice','Amendment','ChangeOrder','ChangeRequest','ContractAnticipated','Budget','File'] as $type)
                        <option value="{{ $type }}" @selected(request('subject')==$type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('From') }}</label>
                <input type="date" name="from" value="{{ request('from') }}" style="width:140px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('To') }}</label>
                <input type="date" name="to" value="{{ request('to') }}" style="width:140px">
            </div>
            <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
            @if(request()->hasAny(['search','user_id','action','subject','from','to']))
                <a href="{{ route('activity-log.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @if($logs->isEmpty())
        <div class="empty"><strong>{{ __('No records') }}</strong></div>
    @else
        <table style="font-size:13px">
            <thead>
                <tr>
                    <th style="width:140px">{{ __('Date') }}</th>
                    <th style="width:160px">{{ __('User') }}</th>
                    <th style="width:100px">{{ __('Action') }}</th>
                    <th style="width:140px">{{ __('Type') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Changes') }}</th>
                    <th style="width:100px;color:#6b7280;font-weight:400">IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php
                    $actionColor = match($log->action) {
                        'created'  => '#16a34a',
                        'deleted'  => '#dc2626',
                        'uploaded' => '#7c3aed',
                        default    => '#2563eb',
                    };
                @endphp
                <tr>
                    <td style="color:#6b7280;white-space:nowrap">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                    <td>{{ $log->user?->full_name ?? '—' }}<div style="font-size:11px;color:#9ca3af">{{ $log->user?->username }}</div></td>
                    <td><span style="font-size:11px;font-weight:600;color:{{ $actionColor }}">{{ strtoupper($log->action) }}</span></td>
                    <td style="color:#6b7280">{{ $log->subject_type }}</td>
                    <td style="font-weight:500">{{ $log->subject_label }}</td>
                    <td style="font-size:12px;color:#6b7280">
                        @if($log->changes)
                            @foreach($log->changes as $field => $change)
                                <div><span style="color:#374151">{{ $field }}</span>:
                                    <span style="color:#dc2626;text-decoration:line-through">{{ $change['from'] ?? '—' }}</span>
                                    → <span style="color:#16a34a">{{ $change['to'] ?? '—' }}</span>
                                </div>
                            @endforeach
                        @endif
                    </td>
                    <td style="color:#9ca3af;font-size:12px">{{ $log->ip }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:1rem">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
