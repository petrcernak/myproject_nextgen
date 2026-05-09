@extends('layouts.app')
@section('title', $contract->name . ' — ' . __('Files'))

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a>
    <span>{{ __('Files') }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ __('Files') }} <span style="font-weight:400;color:#6b7280;font-size:.8em">— {{ $contract->name }}</span></h1>
        <div style="font-size:13px;color:#6b7280;margin-top:.2rem">{{ $contract->code }}</div>
    </div>
</div>

@php
    $tagFilter  = request('tag');
    $shownFiles = $tagFilter
        ? $contract->files->filter(fn($f) => $f->tags->pluck('name')->contains($tagFilter))
        : $contract->files;
@endphp

<div class="card" style="margin-bottom:1.5rem">
    {{-- Tag filter --}}
    @if($contract->files->flatMap->tags->unique('id')->isNotEmpty())
    <div style="padding:.75rem 1rem;border-bottom:1px solid #f3f4f6;display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
        <span style="font-size:12px;color:#6b7280">{{ __('Filter') }}:</span>
        <a href="{{ route('contracts.files', $contract) }}"
           style="font-size:12px;padding:.2rem .5rem;border-radius:99px;background:{{ !$tagFilter ? '#1d4ed8' : '#e5e7eb' }};color:{{ !$tagFilter ? '#fff' : '#374151' }};text-decoration:none">
            {{ __('All') }}
        </a>
        @foreach($contract->files->flatMap->tags->unique('id')->sortBy('name') as $tag)
        <a href="{{ route('contracts.files', $contract) }}?tag={{ urlencode($tag->name) }}"
           style="font-size:12px;padding:.2rem .5rem;border-radius:99px;background:{{ $tagFilter === $tag->name ? '#1d4ed8' : '#e5e7eb' }};color:{{ $tagFilter === $tag->name ? '#fff' : '#374151' }};text-decoration:none">
            {{ $tag->name }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- File list --}}
    @if($shownFiles->isEmpty())
        <div class="empty" style="padding:1.5rem"><strong>{{ __('No files') }}</strong></div>
    @else
        <table style="font-size:13px">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Tags') }}</th>
                    <th style="width:130px;color:#6b7280;font-weight:400">{{ __('Uploaded by') }}</th>
                    <th style="width:80px;color:#6b7280;font-weight:400;text-align:right">{{ __('Size') }}</th>
                    <th style="width:100px;color:#6b7280;font-weight:400;text-align:right">{{ __('Date') }}</th>
                    @if($canEdit)<th style="width:60px"></th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($shownFiles as $file)
                <tr>
                    <td><a href="{{ route('files.show', $file) }}" target="_blank" style="font-weight:500">{{ $file->original_name }}</a></td>
                    <td>
                        @foreach($file->tags as $tag)
                            <span style="display:inline-block;font-size:11px;padding:.1rem .4rem;border-radius:99px;background:#e0e7ff;color:#3730a3;margin-right:.2rem">{{ $tag->name }}</span>
                        @endforeach
                    </td>
                    <td style="font-size:12px;color:#6b7280">{{ $file->uploader?->full_name ?? '—' }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $file->formatted_size }}</td>
                    <td style="text-align:right;color:#6b7280">{{ $file->created_at->format('d.m.Y') }}</td>
                    @if($canEdit)
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('files.destroy', $file) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-secondary btn-sm">✕</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Upload form --}}
    @if($canEdit)
    <div style="padding:1rem;border-top:1px solid #f3f4f6">
        <form method="POST" action="{{ route('contracts.files.store', $contract) }}" enctype="multipart/form-data">
            @csrf
            <div style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('File') }} *</label>
                    <input type="file" name="file" required>
                </div>
                <div style="flex:1;min-width:200px">
                    <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">
                        {{ __('Tags') }}
                        @if($existingTags->isNotEmpty())
                            <span style="font-weight:400;color:#9ca3af">— {{ $existingTags->implode(', ') }}</span>
                        @endif
                    </label>
                    <input type="text" name="tags" placeholder="{{ __('tag1, tag2...') }}" list="tag-suggestions" style="width:100%">
                    <datalist id="tag-suggestions">
                        @foreach($existingTags as $t)
                            <option value="{{ $t }}">
                        @endforeach
                    </datalist>
                </div>
                <button type="submit" class="btn btn-primary" style="white-space:nowrap">{{ __('Upload') }}</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
