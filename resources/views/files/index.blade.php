@extends('layouts.app')
@section('title', __('Files'))

@section('content')
<div class="page-header">
    <h1 style="font-size:1.1rem">{{ __('Files') }}</h1>
</div>

<div class="card">
    <div class="card-body" style="padding-bottom:0">
        <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('File name...') }}" style="width:200px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Entity type') }}</label>
                <select name="type" style="width:180px" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    <option value="contract"            @selected(request('type')==='contract')>{{ __('Contract') }}</option>
                    <option value="amendment"           @selected(request('type')==='amendment')>{{ __('Amendment') }}</option>
                    <option value="change_order"        @selected(request('type')==='change_order')>{{ __('Change order') }}</option>
                    <option value="invoice"             @selected(request('type')==='invoice')>{{ __('Invoice') }}</option>
                    <option value="retention_release"   @selected(request('type')==='retention_release')>{{ __('Retention release') }}</option>
                    <option value="retention_guarantee" @selected(request('type')==='retention_guarantee')>{{ __('Retention guarantee') }}</option>
                </select>
            </div>
            @if($allTags->isNotEmpty())
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('Tag') }}</label>
                <select name="tag" style="width:150px" onchange="this.form.submit()">
                    <option value="">{{ __('All tags') }}</option>
                    @foreach($allTags as $t)
                        <option value="{{ $t }}" @selected(request('tag')===$t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('From') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" style="width:140px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem">{{ __('To') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" style="width:140px">
            </div>
            <button type="submit" class="btn btn-secondary">{{ __('Filter') }}</button>
            @if(request()->hasAny(['search','type','tag','date_from','date_to']))
                <a href="{{ route('files.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    @if($files->isEmpty())
        <div class="empty"><strong>{{ __('No files') }}</strong></div>
    @else
        <table style="font-size:12px">
            <thead>
                <tr>
                    <th>{{ __('File') }}</th>
                    <th style="width:120px">{{ __('Type') }}</th>
                    <th>{{ __('Entity') }}</th>
                    <th>{{ __('Tags') }}</th>
                    <th style="width:120px">{{ __('Uploaded by') }}</th>
                    <th style="text-align:right;width:80px">{{ __('Size') }}</th>
                    <th style="text-align:right;width:100px">{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($files as $file)
                @php
                    $typeKey  = class_basename($file->fileable_type);
                    $typeLabel = match($typeKey) {
                        'Contract'              => __('Contract'),
                        'Amendment'             => __('Amendment'),
                        'ChangeOrder'           => __('Change order'),
                        'Invoice'               => __('Invoice'),
                        'RetentionRelease'      => __('Retention release'),
                        'RetentionBankGuarantee'=> __('Retention guarantee'),
                        default                 => $typeKey,
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
                        'Contract'  => null,
                        default     => $file->fileable?->contract?->code.' '.$file->fileable?->contract?->name,
                    };
                    $entityRoute = match($typeKey) {
                        'Contract'   => route('contracts.show', $file->fileable_id),
                        'Amendment'  => route('amendments.show', $file->fileable_id),
                        'ChangeOrder'=> route('change-orders.show', $file->fileable_id),
                        'Invoice'    => route('invoices.show', $file->fileable_id),
                        default      => null,
                    };
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('files.show', $file) }}" target="_blank" style="font-weight:500">{{ $file->original_name }}</a>
                    </td>
                    <td>
                        <span class="badge badge-gray" style="font-size:10px">{{ $typeLabel }}</span>
                    </td>
                    <td>
                        @if($entityRoute)
                            <a href="{{ $entityRoute }}" style="font-weight:500">{{ $entityName }}</a>
                        @else
                            <span>{{ $entityName }}</span>
                        @endif
                        @if($contractName)
                            <div style="font-size:11px;color:#6b7280">{{ $contractName }}</div>
                        @endif
                    </td>
                    <td>
                        @foreach($file->tags as $tag)
                            <span style="display:inline-block;font-size:10px;padding:.1rem .35rem;border-radius:99px;background:#e0e7ff;color:#3730a3;margin-right:.15rem">{{ $tag->name }}</span>
                        @endforeach
                    </td>
                    <td style="color:#6b7280">{{ $file->uploader?->full_name ?? '—' }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $file->formatted_size }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $file->created_at->format('d.m.Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:.75rem 1rem;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:12px;color:#6b7280">{{ $files->total() }} {{ __('files') }}</span>
            {{ $files->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
