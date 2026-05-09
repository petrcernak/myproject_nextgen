@extends('layouts.app')
@section('title', $contract->name . ' — ' . __('Retention'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a>
    <span>{{ __('Retention') }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ __('Retention') }} <span style="font-weight:400;color:#6b7280;font-size:.8em">— {{ $contract->name }}</span></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">
            {{ $contract->code }} · {{ $contract->currency }}
        </div>
    </div>
</div>

@php
    $shortHeld      = $contract->retention_short_held;
    $shortReleased  = $contract->retention_short_released;
    $shortRemaining = $contract->retention_short_remaining;
    $longHeld       = $contract->retention_long_held;
    $longReleased   = $contract->retention_long_released;
    $longRemaining  = $contract->retention_long_remaining;
    $shortReleases  = $contract->retentionReleases->where('type', 'short');
    $longReleases   = $contract->retentionReleases->where('type', 'long');
    $bankGuarantees = $contract->retentionBankGuarantees;
@endphp

@if(!$contract->retention_short && !$contract->retention_long)
<div class="card card-body">
    <div style="color:#6b7280">{{ __('No retention rates configured on this contract.') }}</div>
</div>
@endif

{{-- Short-term retention --}}
@if($contract->retention_short)
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Short-term retention') }} <span style="font-weight:400;color:#6b7280">{{ $contract->retention_short }}%</span></h2>
</div>
<div class="card" style="margin-bottom:1.5rem">
    {{-- Summary bar --}}
    <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f3f4f6;display:flex;gap:2rem;flex-wrap:wrap">
        <div>
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">{{ __('Held') }}</div>
            <div style="font-size:1.4rem;font-weight:700;color:#dc2626">{{ number_format($shortHeld, 2, ',', ' ') }} {{ $contract->currency }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">{{ __('Released') }}</div>
            <div style="font-size:1.4rem;font-weight:700;color:#16a34a">{{ number_format($shortReleased, 2, ',', ' ') }} {{ $contract->currency }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">{{ __('Remaining') }}</div>
            <div style="font-size:1.4rem;font-weight:700">{{ number_format($shortRemaining, 2, ',', ' ') }} {{ $contract->currency }}</div>
        </div>
    </div>
    @if($shortHeld > 0)
    @php $shortPct = min(100, round($shortReleased / $shortHeld * 100)); @endphp
    <div style="padding:0 1.25rem .75rem;margin-top:.75rem">
        <div style="height:6px;background:#e5e7eb;border-radius:3px">
            <div style="height:6px;background:#16a34a;border-radius:3px;width:{{ $shortPct }}%"></div>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:.3rem">{{ $shortPct }} % {{ __('released') }}</div>
    </div>
    @endif

    {{-- Releases table --}}
    @if($shortReleases->isNotEmpty())
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="width:120px">{{ __('Date') }}</th>
                <th style="text-align:right;width:150px">{{ __('Amount') }}</th>
                <th>{{ __('Note') }}</th>
                <th style="width:200px">{{ __('Files') }}</th>
                @if($canEdit)<th style="width:60px"></th>@endif
            </tr>
        </thead>
        <tbody>
        @foreach($shortReleases as $rel)
        <tr>
            <td style="color:#6b7280">{{ $rel->release_date->format('d.m.Y') }}</td>
            <td style="text-align:right;font-weight:600;color:#16a34a">{{ number_format($rel->amount, 2, ',', ' ') }}</td>
            <td style="font-size:12px;color:#6b7280">{{ $rel->note ?? '—' }}</td>
            <td>
                @foreach($rel->files as $rf)
                <a href="{{ route('files.show', $rf) }}" target="_blank" style="font-size:12px;display:block">
                    📎 {{ $rf->original_name }}
                </a>
                @endforeach
                @if($canEdit)
                <form method="POST" action="{{ route('retention-releases.files.store', $rel) }}" enctype="multipart/form-data" style="margin-top:.35rem;display:flex;gap:.35rem;align-items:center">
                    @csrf
                    <input type="file" name="file" style="font-size:11px">
                    <button type="submit" class="btn btn-secondary btn-sm">{{ __('Upload') }}</button>
                </form>
                @endif
            </td>
            @if($canEdit)
            <td style="text-align:right">
                <form method="POST" action="{{ route('retention-releases.destroy', $rel) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">✕</button>
                </form>
            </td>
            @endif
        </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <div class="empty" style="padding:1rem 1.25rem"><span style="color:#6b7280;font-size:13px">{{ __('No releases recorded yet.') }}</span></div>
    @endif

    {{-- Add release form --}}
    @if($canEdit)
    <div style="padding:.75rem 1.25rem;border-top:1px solid #f3f4f6;background:#fafafa">
        <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:.5rem">{{ __('Record release') }}</div>
        <form method="POST" action="{{ route('contracts.retention-releases.store', $contract) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="short">
            <div style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Date') }} *</label>
                    <input type="date" name="release_date" required style="padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Amount') }} *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00" style="width:140px;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                </div>
                <div style="flex:1;min-width:200px">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Note') }}</label>
                    <input type="text" name="note" placeholder="{{ __('e.g. Defect removal confirmation') }}" style="width:100%;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('File') }}</label>
                    <input type="file" name="file" style="font-size:13px">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endif

{{-- Long-term retention --}}
@if($contract->retention_long)
<div class="page-header" style="margin-bottom:.75rem">
    <h2 style="font-size:1rem">{{ __('Long-term retention') }} <span style="font-weight:400;color:#6b7280">{{ $contract->retention_long }}%</span></h2>
</div>
<div class="card" style="margin-bottom:1.5rem">
    {{-- Summary bar --}}
    <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f3f4f6;display:flex;gap:2rem;flex-wrap:wrap">
        <div>
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">{{ __('Held') }}</div>
            <div style="font-size:1.4rem;font-weight:700;color:#dc2626">{{ number_format($longHeld, 2, ',', ' ') }} {{ $contract->currency }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">{{ __('Released') }}</div>
            <div style="font-size:1.4rem;font-weight:700;color:#16a34a">{{ number_format($longReleased, 2, ',', ' ') }} {{ $contract->currency }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">{{ __('Remaining') }}</div>
            <div style="font-size:1.4rem;font-weight:700">{{ number_format($longRemaining, 2, ',', ' ') }} {{ $contract->currency }}</div>
        </div>
    </div>
    @if($longHeld > 0)
    @php $longPct = min(100, round($longReleased / $longHeld * 100)); @endphp
    <div style="padding:0 1.25rem .75rem;margin-top:.75rem">
        <div style="height:6px;background:#e5e7eb;border-radius:3px">
            <div style="height:6px;background:#16a34a;border-radius:3px;width:{{ $longPct }}%"></div>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:.3rem">{{ $longPct }} % {{ __('released') }}</div>
    </div>
    @endif

    {{-- Releases table --}}
    @if($longReleases->isNotEmpty())
    <table style="font-size:13px">
        <thead>
            <tr>
                <th style="width:120px">{{ __('Date') }}</th>
                <th style="text-align:right;width:150px">{{ __('Amount') }}</th>
                <th>{{ __('Note') }}</th>
                <th style="width:200px">{{ __('Files') }}</th>
                @if($canEdit)<th style="width:60px"></th>@endif
            </tr>
        </thead>
        <tbody>
        @foreach($longReleases as $rel)
        <tr>
            <td style="color:#6b7280">{{ $rel->release_date->format('d.m.Y') }}</td>
            <td style="text-align:right;font-weight:600;color:#16a34a">{{ number_format($rel->amount, 2, ',', ' ') }}</td>
            <td style="font-size:12px;color:#6b7280">{{ $rel->note ?? '—' }}</td>
            <td>
                @foreach($rel->files as $rf)
                <a href="{{ route('files.show', $rf) }}" target="_blank" style="font-size:12px;display:block">
                    📎 {{ $rf->original_name }}
                </a>
                @endforeach
                @if($canEdit)
                <form method="POST" action="{{ route('retention-releases.files.store', $rel) }}" enctype="multipart/form-data" style="margin-top:.35rem;display:flex;gap:.35rem;align-items:center">
                    @csrf
                    <input type="file" name="file" style="font-size:11px">
                    <button type="submit" class="btn btn-secondary btn-sm">{{ __('Upload') }}</button>
                </form>
                @endif
            </td>
            @if($canEdit)
            <td style="text-align:right">
                <form method="POST" action="{{ route('retention-releases.destroy', $rel) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">✕</button>
                </form>
            </td>
            @endif
        </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <div class="empty" style="padding:1rem 1.25rem"><span style="color:#6b7280;font-size:13px">{{ __('No releases recorded yet.') }}</span></div>
    @endif

    {{-- Add release form --}}
    @if($canEdit)
    <div style="padding:.75rem 1.25rem;border-top:1px solid #f3f4f6;background:#fafafa">
        <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:.5rem">{{ __('Record release') }}</div>
        <form method="POST" action="{{ route('contracts.retention-releases.store', $contract) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="long">
            <div style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Date') }} *</label>
                    <input type="date" name="release_date" required style="padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Amount') }} *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00" style="width:140px;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                </div>
                <div style="flex:1;min-width:200px">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Note') }}</label>
                    <input type="text" name="note" style="width:100%;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('File') }}</label>
                    <input type="file" name="file" style="font-size:13px">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Bank guarantees --}}
    <div style="border-top:2px solid #f3f4f6">
        <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f3f4f6;background:#fafafa">
            <span style="font-size:13px;font-weight:600;color:#374151">{{ __('Bank guarantees') }}</span>
            <span style="font-size:12px;color:#6b7280;margin-left:.5rem">{{ __('replacing long-term retention') }}</span>
        </div>

        @if($bankGuarantees->isNotEmpty())
        <table style="font-size:13px">
            <thead>
                <tr>
                    <th style="text-align:right;width:150px">{{ __('Amount') }}</th>
                    <th style="width:120px">{{ __('Valid from') }}</th>
                    <th style="width:120px">{{ __('Valid until') }}</th>
                    <th>{{ __('Note') }}</th>
                    <th style="width:200px">{{ __('Files') }}</th>
                    @if($canEdit)<th style="width:60px"></th>@endif
                </tr>
            </thead>
            <tbody>
            @foreach($bankGuarantees as $bg)
            <tr>
                <td style="text-align:right;font-weight:600">{{ number_format($bg->amount, 2, ',', ' ') }}</td>
                <td style="color:#6b7280">{{ $bg->valid_from?->format('d.m.Y') ?? '—' }}</td>
                <td style="color:{{ $bg->valid_until && $bg->valid_until->isPast() ? '#dc2626' : '#6b7280' }}">
                    {{ $bg->valid_until?->format('d.m.Y') ?? '—' }}
                    @if($bg->valid_until && $bg->valid_until->isPast())
                        <span style="font-size:11px;color:#dc2626">{{ __('expired') }}</span>
                    @endif
                </td>
                <td style="font-size:12px;color:#6b7280">{{ $bg->note ?? '—' }}</td>
                <td>
                    @foreach($bg->files as $bgf)
                    <a href="{{ route('files.show', $bgf) }}" target="_blank" style="font-size:12px;display:block">
                        📎 {{ $bgf->original_name }}
                    </a>
                    @endforeach
                    @if($canEdit)
                    <form method="POST" action="{{ route('retention-bank-guarantees.files.store', $bg) }}" enctype="multipart/form-data" style="margin-top:.35rem;display:flex;gap:.35rem;align-items:center">
                        @csrf
                        <input type="file" name="file" style="font-size:11px">
                        <button type="submit" class="btn btn-secondary btn-sm">{{ __('Upload') }}</button>
                    </form>
                    @endif
                </td>
                @if($canEdit)
                <td style="text-align:right">
                    <form method="POST" action="{{ route('retention-bank-guarantees.destroy', $bg) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">✕</button>
                    </form>
                </td>
                @endif
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="empty" style="padding:1rem 1.25rem"><span style="color:#6b7280;font-size:13px">{{ __('No bank guarantees recorded.') }}</span></div>
        @endif

        @if($canEdit)
        <div style="padding:.75rem 1.25rem;border-top:1px solid #f3f4f6;background:#fafafa">
            <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:.5rem">{{ __('Add bank guarantee') }}</div>
            <form method="POST" action="{{ route('contracts.retention-bank-guarantees.store', $contract) }}">
                @csrf
                <div style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Amount') }} *</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00" style="width:140px;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Valid from') }}</label>
                        <input type="date" name="valid_from" style="padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Valid until') }}</label>
                        <input type="date" name="valid_until" style="padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                    </div>
                    <div style="flex:1;min-width:180px">
                        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Note') }}</label>
                        <input type="text" name="note" style="width:100%;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
@endif

@endsection
