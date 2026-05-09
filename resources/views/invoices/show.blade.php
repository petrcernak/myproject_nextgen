@extends('layouts.app')
@section('title', __('Invoice').' '.$invoice->no)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
    <a href="{{ route('projects.show', $invoice->contract->project) }}"><span>{{ $invoice->contract->project->name }}</span></a>
    <a href="{{ route('contracts.show', $invoice->contract) }}"><span>{{ $invoice->contract->name }}</span></a>
    <span>{{ $invoice->no }}</span>
</div>

<div class="page-header">
    <h1>
        {{ __('Invoice') }} {{ $invoice->no }}
        @if($invoice->is_advance)
            <span class="badge badge-yellow" style="font-size:13px;vertical-align:middle;margin-left:.5rem">{{ __('Advance') }}</span>
        @endif
    </h1>
    <div style="display:flex;gap:.5rem">
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger">{{ __('Delete') }}</button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem">
    <div class="card card-body">
        <table style="font-size:13px">
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Contract') }}</td><td style="border:none"><a href="{{ route('contracts.show', $invoice->contract) }}">{{ $invoice->contract->name }}</a></td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Sender') }}</td><td style="border:none">{{ $invoice->sender?->name ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Recipient') }}</td><td style="border:none">{{ $invoice->recipient?->name ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Issue date') }}</td><td style="border:none">{{ $invoice->issued?->format('d.m.Y') ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Tax date') }}</td><td style="border:none">{{ $invoice->taxdate?->format('d.m.Y') ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Due date') }}</td><td style="border:none">{{ $invoice->due?->format('d.m.Y') ?? '—' }}</td></tr>
            <tr><td style="color:#6b7280;border:none;padding:.3rem .5rem .3rem 0">{{ __('Paid') }}</td><td style="border:none">{{ $invoice->paid?->format('d.m.Y') ?? '—' }}</td></tr>
        </table>
    </div>
    <div class="card card-body">
        @php
            $cls = match($invoice->status) { 2 => 'badge-green', 4 => 'badge-red', 3 => 'badge-yellow', default => 'badge-gray' };
            $hasBreakdown = !$invoice->is_advance && (
                $invoice->retention_short_amount || $invoice->retention_long_amount || $invoice->deducted_amount
            );
        @endphp
        <div style="margin-bottom:.75rem"><span class="badge {{ $cls }}" style="font-size:13px">{{ $invoice->status_label }}</span></div>
        @if($hasBreakdown)
            <div style="font-size:13px;display:flex;flex-direction:column;gap:.25rem;margin-bottom:.75rem">
                <div style="display:flex;justify-content:space-between;color:#6b7280">
                    <span>{{ __('Items total') }}</span>
                    <span>{{ number_format($invoice->items_total, 2, ',', ' ') }}</span>
                </div>
                @if($invoice->retention_short_amount)
                <div style="display:flex;justify-content:space-between;color:#dc2626">
                    <span>{{ __('Short-term retention') }} ({{ $invoice->contract->retention_short }}%)</span>
                    <span>−{{ number_format($invoice->retention_short_amount, 2, ',', ' ') }}</span>
                </div>
                @endif
                @if($invoice->retention_long_amount)
                <div style="display:flex;justify-content:space-between;color:#dc2626">
                    <span>{{ __('Long-term retention') }} ({{ $invoice->contract->retention_long }}%)</span>
                    <span>−{{ number_format($invoice->retention_long_amount, 2, ',', ' ') }}</span>
                </div>
                @endif
                @if($invoice->deducted_amount)
                <div style="display:flex;justify-content:space-between;color:#dc2626">
                    <span>{{ __('Advance deductions') }}</span>
                    <span>−{{ number_format($invoice->deducted_amount, 2, ',', ' ') }}</span>
                </div>
                @endif
                <div style="border-top:1px solid #e5e7eb;margin-top:.1rem;padding-top:.3rem"></div>
            </div>
            <div style="font-size:13px;color:#6b7280">{{ __('Net payable') }}</div>
        @else
            <div style="font-size:13px;color:#6b7280">{{ __('Total amount') }}</div>
        @endif
        <div style="font-size:2rem;font-weight:700">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->contract->currency }}</div>
        @if($invoice->is_advance)
            @php $amortized = $invoice->advance_amount - $invoice->remaining_advance; @endphp
            <div style="margin-top:.75rem;font-size:13px;color:#6b7280">
                {{ __('Amortized') }}: <strong>{{ number_format($amortized, 2, ',', ' ') }}</strong>
                &nbsp;|&nbsp;
                {{ __('Remaining advance') }}: <strong style="color:{{ $invoice->remaining_advance > 0 ? '#1d4ed8' : '#6b7280' }}">{{ number_format($invoice->remaining_advance, 2, ',', ' ') }}</strong>
            </div>
        @endif
        @if($invoice->description)
            <div style="margin-top:1rem;font-size:13px;color:#374151">{{ $invoice->description }}</div>
        @endif
    </div>
</div>

@if($invoice->is_advance)
    {{-- Advance invoice: show amortization history --}}
    @php $deductionsReceived = $invoice->advanceDeductionsReceived()->with('invoice')->get(); @endphp
    @if($deductionsReceived->isNotEmpty())
    <div class="page-header" style="margin-bottom:.75rem">
        <h2 style="font-size:1rem">{{ __('Amortizations') }}</h2>
    </div>
    <div class="card" style="margin-bottom:1.5rem">
        <table>
            <thead>
                <tr>
                    <th>{{ __('Invoice') }}</th>
                    <th>{{ __('Issue date') }}</th>
                    <th style="text-align:right">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deductionsReceived as $ded)
                <tr>
                    <td><a href="{{ route('invoices.show', $ded->invoice) }}">{{ $ded->invoice->no }}</a></td>
                    <td>{{ $ded->invoice->issued?->format('d.m.Y') ?? '—' }}</td>
                    <td style="text-align:right">{{ number_format($ded->amount, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
                <tr style="background:#f9fafb">
                    <td colspan="2" style="font-weight:600">{{ __('Total amortized') }}</td>
                    <td style="text-align:right;font-weight:600">{{ number_format($deductionsReceived->sum('amount'), 2, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
@else
    {{-- Regular invoice: contract items billing overview --}}
    @if($contractItems->isNotEmpty())
    <div class="page-header" style="margin-bottom:.75rem">
        <h2 style="font-size:1rem">{{ __('Contract items billing') }}</h2>
    </div>
    <div class="card" style="margin-bottom:1.5rem">
        <table>
            <thead>
                <tr>
                    <th style="width:90px">{{ __('Code') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="text-align:right;width:140px">{{ __('Contract amount') }}</th>
                    <th style="text-align:right;width:140px">{{ __('Invoiced') }}</th>
                    <th style="text-align:right;width:140px">{{ __('Remaining') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contractItems as $ci)
                @php $pct = $ci->amount > 0 ? min(100, round($ci->invoiced_amount / $ci->amount * 100)) : 0; @endphp
                <tr>
                    <td><code style="color:#6b7280;font-size:12px">{{ $ci->code ?? '—' }}</code></td>
                    <td>
                        {{ $ci->description }}
                        <div style="margin-top:.3rem;height:4px;background:#e5e7eb;border-radius:2px;width:100%">
                            <div style="height:4px;border-radius:2px;background:{{ $pct >= 100 ? '#22c55e' : ($pct > 0 ? '#3b82f6' : '#e5e7eb') }};width:{{ $pct }}%"></div>
                        </div>
                    </td>
                    <td style="text-align:right">{{ number_format($ci->amount, 2, ',', ' ') }}</td>
                    <td style="text-align:right;color:#6b7280">{{ number_format($ci->invoiced_amount, 2, ',', ' ') }}</td>
                    <td style="text-align:right;font-weight:{{ $ci->remaining_amount > 0 ? '600' : '400' }};color:{{ $ci->remaining_amount > 0 ? '#1d4ed8' : ($ci->remaining_amount < 0 ? '#dc2626' : '#6b7280') }}">
                        {{ number_format($ci->remaining_amount, 2, ',', ' ') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Invoice items --}}
    <div class="page-header" style="margin-bottom:.75rem">
        <h2 style="font-size:1rem">{{ __('Invoice items') }}</h2>
    </div>
    <div class="card" style="margin-bottom:1.5rem">
        @if($invoice->items->isEmpty())
            <div class="empty" style="padding:1.5rem"><strong>{{ __('No items') }}</strong></div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width:90px">{{ __('Contract item') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th style="text-align:right">{{ __('Amount') }} ({{ $invoice->contract->currency }})</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr>
                        <td><code style="color:#6b7280;font-size:12px">{{ $item->contractItem?->code ?? '—' }}</code></td>
                        <td>{{ $item->description }}</td>
                        <td style="text-align:right">{{ number_format($item->amount, 2, ',', ' ') }}</td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('invoice-items.destroy', $item) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-secondary btn-sm">✕</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if($invoice->deductions->isNotEmpty())
                    @foreach($invoice->deductions as $ded)
                    <tr style="background:#fef9c3">
                        <td></td>
                        <td style="font-size:13px;color:#92400e">{{ __('Advance deduction') }}: <a href="{{ route('invoices.show', $ded->advanceInvoice) }}">{{ $ded->advanceInvoice->no }}</a></td>
                        <td style="text-align:right;color:#92400e">−{{ number_format($ded->amount, 2, ',', ' ') }}</td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('invoice-deductions.destroy', $ded) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-secondary btn-sm">✕</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                    <tr style="background:#f9fafb">
                        <td colspan="2" style="font-weight:600">{{ __('Total') }}</td>
                        <td style="text-align:right;font-weight:600">{{ number_format($invoice->total, 2, ',', ' ') }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        @endif

        <div style="padding:1rem;border-top:1px solid #f3f4f6">
            <form method="POST" action="{{ route('invoices.items.store', $invoice) }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr auto auto;gap:.5rem;align-items:flex-end">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Contract item') }}</label>
                        <select name="contract_item_id" id="ciSelect" onchange="fillFromContractItem(this)">
                            <option value="">{{ __('— free item —') }}</option>
                            @foreach($contractItems as $ci)
                            <option value="{{ $ci->id }}"
                                    data-description="{{ $ci->description }}"
                                    data-remaining="{{ number_format($ci->remaining_amount, 2, '.') }}"
                                    style="{{ $ci->remaining_amount <= 0 ? 'color:#9ca3af' : '' }}">
                                {{ $ci->code ? $ci->code.' — ' : '' }}{{ Str::limit($ci->description, 35) }}
                                &nbsp;({{ __('rem.') }}: {{ number_format($ci->remaining_amount, 2, ',', ' ') }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Description') }} *</label>
                        <input type="text" name="description" id="itemDescription" placeholder="{{ __('Item name...') }}" required>
                    </div>
                    <div style="width:160px">
                        <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Amount') }} ({{ $invoice->contract->currency }})</label>
                        <input type="number" name="amount" id="itemAmount" step="0.01" placeholder="0.00" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="white-space:nowrap">{{ __('+ Add') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Advance deduction section --}}
    @if($advanceInvoices->isNotEmpty())
    <div class="page-header" style="margin-bottom:.75rem">
        <h2 style="font-size:1rem">{{ __('Advance deduction') }}</h2>
    </div>
    <div class="card card-body" style="margin-bottom:1.5rem">
        <form method="POST" action="{{ route('invoices.deductions.store', $invoice) }}">
            @csrf
            <div style="display:flex;gap:.5rem;align-items:flex-end">
                <div style="flex:1">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Advance invoice') }}</label>
                    <select name="advance_invoice_id">
                        @foreach($advanceInvoices as $ai)
                        <option value="{{ $ai->id }}">
                            {{ $ai->no }} — {{ __('Remaining') }}: {{ number_format($ai->remaining_advance, 2, ',', ' ') }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div style="width:180px">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Amount') }}</label>
                    <input type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
                </div>
                <button type="submit" class="btn btn-primary" style="white-space:nowrap">{{ __('Deduct') }}</button>
            </div>
        </form>
    </div>
    @endif
@endif

{{-- Files --}}
@php
    $tagFilter  = request('tag');
    $shownFiles = $tagFilter
        ? $invoice->files->filter(fn($f) => $f->tags->pluck('name')->contains($tagFilter))
        : $invoice->files;
@endphp
<div class="page-header" style="margin-bottom:.75rem;margin-top:.5rem" id="files">
    <h2 style="font-size:1rem">{{ __('Files') }}</h2>
</div>
<div class="card" style="margin-bottom:1.5rem">
    @if($invoice->files->flatMap->tags->unique('id')->isNotEmpty())
    <div style="padding:.75rem 1rem;border-bottom:1px solid #f3f4f6;display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
        <span style="font-size:12px;color:#6b7280">{{ __('Filter') }}:</span>
        <a href="{{ route('invoices.show', $invoice) }}"
           style="font-size:12px;padding:.2rem .5rem;border-radius:99px;background:{{ !$tagFilter ? '#1d4ed8' : '#e5e7eb' }};color:{{ !$tagFilter ? '#fff' : '#374151' }};text-decoration:none">{{ __('All') }}</a>
        @foreach($invoice->files->flatMap->tags->unique('id')->sortBy('name') as $tag)
        <a href="{{ route('invoices.show', $invoice) }}?tag={{ urlencode($tag->name) }}"
           style="font-size:12px;padding:.2rem .5rem;border-radius:99px;background:{{ $tagFilter === $tag->name ? '#1d4ed8' : '#e5e7eb' }};color:{{ $tagFilter === $tag->name ? '#fff' : '#374151' }};text-decoration:none">{{ $tag->name }}</a>
        @endforeach
    </div>
    @endif
    @if($shownFiles->isEmpty())
        <div class="empty" style="padding:1.25rem"><strong>{{ __('No files') }}</strong></div>
    @else
        <table style="font-size:13px">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Tags') }}</th>
                    <th style="width:120px;color:#6b7280;font-weight:400">{{ __('Uploaded by') }}</th>
                    <th style="width:80px;color:#6b7280;font-weight:400;text-align:right">{{ __('Size') }}</th>
                    <th style="width:100px;color:#6b7280;font-weight:400;text-align:right">{{ __('Date') }}</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($shownFiles as $file)
                <tr>
                    <td><a href="{{ route('files.show', $file) }}" target="_blank" style="font-weight:500">{{ $file->original_name }}</a></td>
                    <td>@foreach($file->tags as $tag)<span style="display:inline-block;font-size:11px;padding:.1rem .4rem;border-radius:99px;background:#e0e7ff;color:#3730a3;margin-right:.2rem">{{ $tag->name }}</span>@endforeach</td>
                    <td style="font-size:12px;color:#6b7280">{{ $file->uploader?->full_name ?? '—' }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $file->formatted_size }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $file->created_at->format('d.m.Y') }}</td>
                    <td style="text-align:right">
                        @if($canEdit)
                        <form method="POST" action="{{ route('files.destroy', $file) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-secondary btn-sm">✕</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    @if($canEdit)
    <div style="padding:1rem;border-top:1px solid #f3f4f6">
        <form method="POST" action="{{ route('invoices.files.store', $invoice) }}" enctype="multipart/form-data">
            @csrf
            <div style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('File') }} *</label>
                    <input type="file" name="file" required>
                </div>
                <div style="flex:1;min-width:200px">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">
                        {{ __('Tags') }}
                        @if($existingTags->isNotEmpty())<span style="font-weight:400;color:#9ca3af">— {{ $existingTags->implode(', ') }}</span>@endif
                    </label>
                    <input type="text" name="tags" placeholder="{{ __('tag1, tag2...') }}" list="tag-suggestions" style="width:100%">
                    <datalist id="tag-suggestions">
                        @foreach($existingTags as $t)<option value="{{ $t }}">@endforeach
                    </datalist>
                </div>
                <button type="submit" class="btn btn-primary" style="white-space:nowrap">{{ __('Upload') }}</button>
            </div>
        </form>
    </div>
    @endif
</div>

<script>
function fillFromContractItem(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt.value) return;
    document.getElementById('itemDescription').value = opt.dataset.description;
    document.getElementById('itemAmount').value = opt.dataset.remaining;
}
</script>
@endsection
