@extends('layouts.app')
@section('title', __('Files'))

@section('content')
@php $fa = fn($k) => request($k) !== null && request($k) !== '' ? 'fi fi-active' : 'fi'; @endphp

<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Files') }}
        <span style="font-size:13px;font-weight:400;color:#6b7280;margin-left:.5rem">{{ $files->total() }} / {{ $totalCount }}</span>
    </h1>
</div>

@if($files->isEmpty() && !request()->hasAny(['search','type','tag','date_from','date_to']))
    <div class="card"><div class="empty"><strong>{{ __('No files') }}</strong></div></div>
@else

<form method="GET" id="lf">
<div style="overflow-x:auto;margin-bottom:1rem">
<table class="ltbl" style="font-size:12px">
    <thead>
        <tr>
            <th style="text-align:left;min-width:200px{{ request('search') ? ';background:#dbeafe' : '' }}">
                {{ __('File') }}
                <input class="{{ $fa('search') }}" type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('File name…') }}">
            </th>
            <th style="text-align:left;min-width:140px{{ request('type') ? ';background:#dbeafe' : '' }}">
                {{ __('Type') }}
                <select class="{{ $fa('type') }}" name="type" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="contract"            @selected(request('type')==='contract')>{{ __('Contract') }}</option>
                    <option value="amendment"           @selected(request('type')==='amendment')>{{ __('Amendment') }}</option>
                    <option value="change_order"        @selected(request('type')==='change_order')>{{ __('Change order') }}</option>
                    <option value="invoice"             @selected(request('type')==='invoice')>{{ __('Invoice') }}</option>
                    <option value="retention_release"   @selected(request('type')==='retention_release')>{{ __('Retention release') }}</option>
                    <option value="retention_guarantee" @selected(request('type')==='retention_guarantee')>{{ __('Retention guarantee') }}</option>
                </select>
            </th>
            <th style="text-align:left;min-width:180px">{{ __('Entity') }}</th>
            <th style="text-align:left;min-width:130px{{ request('tag') ? ';background:#dbeafe' : '' }}">
                {{ __('Tags') }}
                @if($allTags->isNotEmpty())
                <select class="{{ $fa('tag') }}" name="tag" onchange="document.getElementById('lf').submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($allTags as $t)
                        <option value="{{ $t }}" @selected(request('tag')===$t)>{{ $t }}</option>
                    @endforeach
                </select>
                @endif
            </th>
            <th style="min-width:120px">{{ __('Uploaded by') }}</th>
            <th style="min-width:80px">{{ __('Size') }}</th>
            <th style="min-width:100px{{ request('date_from') ? ';background:#dbeafe' : '' }}">
                {{ __('From') }}
                <input class="{{ $fa('date_from') }}" type="date" name="date_from" value="{{ request('date_from') }}">
            </th>
            <th style="min-width:100px{{ request('date_to') ? ';background:#dbeafe' : '' }}">
                {{ __('To') }}
                <input class="{{ $fa('date_to') }}" type="date" name="date_to" value="{{ request('date_to') }}">
            </th>
            <th style="min-width:70px;text-align:right">
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search','type','tag','date_from','date_to']))<a href="{{ route('files.index') }}" class="btn btn-secondary btn-sm">×</a>@endif
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($files as $file)
        @php
            $typeKey   = class_basename($file->fileable_type);
            $typeLabel = match($typeKey) {
                'Contract'               => __('Contract'),
                'Amendment'              => __('Amendment'),
                'ChangeOrder'            => __('Change order'),
                'Invoice'                => __('Invoice'),
                'RetentionRelease'       => __('Retention release'),
                'RetentionBankGuarantee' => __('Retention guarantee'),
                default                  => $typeKey,
            };
            $entityName = match($typeKey) {
                'Contract'               => ($file->fileable?->code ? $file->fileable->code.' — ' : '').$file->fileable?->name,
                'Amendment'              => $file->fileable?->code.' — '.$file->fileable?->name,
                'ChangeOrder'            => $file->fileable?->code.' — '.$file->fileable?->name,
                'Invoice'                => $file->fileable?->no,
                'RetentionRelease'       => __('Release').' '.$file->fileable?->release_date?->format('d.m.Y'),
                'RetentionBankGuarantee' => __('Guarantee').' '.$file->fileable?->valid_from?->format('d.m.Y'),
                default                  => '—',
            };
            $contractName = match($typeKey) {
                'Contract' => null,
                default    => $file->fileable?->contract?->code.' '.$file->fileable?->contract?->name,
            };
            $entityRoute = match($typeKey) {
                'Contract'    => route('contracts.show', $file->fileable_id),
                'Amendment'   => route('amendments.show', $file->fileable_id),
                'ChangeOrder' => route('change-orders.show', $file->fileable_id),
                'Invoice'     => route('invoices.show', $file->fileable_id),
                default       => null,
            };
        @endphp
        <tr>
            <td style="text-align:left">
                <a href="{{ route('files.show', $file) }}" target="_blank" style="font-weight:500">{{ $file->original_name }}</a>
            </td>
            <td style="text-align:left"><span class="badge badge-gray" style="font-size:10px">{{ $typeLabel }}</span></td>
            <td style="text-align:left">
                @if($entityRoute)<a href="{{ $entityRoute }}" style="font-weight:500">{{ $entityName }}</a>
                @else<span>{{ $entityName }}</span>@endif
                @if($contractName)<div style="font-size:11px;color:#6b7280">{{ $contractName }}</div>@endif
            </td>
            <td style="text-align:left">
                @foreach($file->tags as $tag)
                    <span style="display:inline-block;font-size:10px;padding:.1rem .35rem;border-radius:99px;background:#e0e7ff;color:#3730a3;margin-right:.15rem">{{ $tag->name }}</span>
                @endforeach
            </td>
            <td style="text-align:left;color:#6b7280">{{ $file->uploader?->full_name ?? '—' }}</td>
            <td style="text-align:right;color:#6b7280">{{ $file->formatted_size }}</td>
            <td style="text-align:right;color:#6b7280">{{ $file->created_at->format('d.m.Y') }}</td>
            <td></td>
            <td></td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:2rem 1rem">{{ __('No results.') }}</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</form>
<div>{{ $files->withQueryString()->links() }}</div>
@endif
@endsection
