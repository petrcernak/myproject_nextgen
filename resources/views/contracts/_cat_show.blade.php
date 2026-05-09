@php
    $sub = $subtotalFn($category);
    $indent = $depth * 20;
    $catLabel = ($category->code ? $category->code . ' — ' : '') . $category->name;
    $parentCatId = $parentCatId ?? 0;

    $merged = collect();
    foreach ($contract->items->where('contract_category_id', $category->id) as $item) {
        $merged->push(['type' => 'item', 'key' => strtolower($item->code ?? $item->description ?? ''), 'obj' => $item]);
    }
    foreach ($category->children as $child) {
        $merged->push(['type' => 'category', 'key' => strtolower($child->code ?? $child->name ?? ''), 'obj' => $child]);
    }
    $merged = $merged->sortBy('key');
@endphp

{{-- Category header row --}}
<tr data-cat="{{ $category->id }}" data-parent="{{ $parentCatId }}" data-open="1"
    style="background:#f0f6ff;font-weight:700;font-size:11px">
    <td colspan="2" style="color:#2563eb;padding-left:{{ 4 + $indent }}px">
        <button type="button" onclick="toggleCat({{ $category->id }})"
                style="background:none;border:none;cursor:pointer;padding:0 4px 0 0;color:#2563eb;font-size:10px">
            <span class="cat-caret" style="display:inline-block;transition:transform .15s;transform:rotate(90deg)">▶</span>
        </button>
        {{ $catLabel }}
    </td>
    <td style="text-align:right;white-space:nowrap">{{ number_format($sub['amount'], 2, ',', ' ') }}</td>
    @if($hasCos)
    <td style="text-align:right;white-space:nowrap;color:{{ $sub['co'] > 0 ? '#1d4ed8' : ($sub['co'] < 0 ? '#dc2626' : '#9ca3af') }}">
        {{ $sub['co'] != 0 ? number_format($sub['co'], 2, ',', ' ') : '—' }}
    </td>
    @endif
    @if($hasAmendments)
    <td style="text-align:right;white-space:nowrap;color:{{ $sub['amd'] > 0 ? '#1d4ed8' : ($sub['amd'] < 0 ? '#dc2626' : '#9ca3af') }}">
        {{ $sub['amd'] != 0 ? number_format($sub['amd'], 2, ',', ' ') : '—' }}
    </td>
    @endif
    @if($hasCoChanges)
    <td style="text-align:right;white-space:nowrap">{{ number_format($sub['effective'], 2, ',', ' ') }}</td>
    @endif
    <td style="text-align:right;white-space:nowrap;color:#6b7280">{{ number_format($sub['invoiced'], 2, ',', ' ') }}</td>
    <td style="text-align:right;white-space:nowrap;color:{{ $sub['remaining'] > 0 ? '#1d4ed8' : ($sub['remaining'] < 0 ? '#dc2626' : '#6b7280') }}">
        {{ number_format($sub['remaining'], 2, ',', ' ') }}
    </td>
    @if($hasFuture)
    <td style="text-align:right;white-space:nowrap;background:#fef2f2;color:{{ $sub['cr'] > 0 ? '#dc2626' : ($sub['cr'] < 0 ? '#16a34a' : '#9ca3af') }}">
        {{ $sub['cr'] != 0 ? number_format($sub['cr'], 2, ',', ' ') : '—' }}
    </td>
    <td style="text-align:right;white-space:nowrap;background:#fef2f2;color:{{ $sub['ca'] > 0 ? '#dc2626' : ($sub['ca'] < 0 ? '#16a34a' : '#9ca3af') }}">
        {{ $sub['ca'] != 0 ? number_format($sub['ca'], 2, ',', ' ') : '—' }}
    </td>
    <td style="text-align:right;background:#dbeafe;color:#1d4ed8">{{ number_format($sub['expected'], 2, ',', ' ') }}</td>
    @endif
</tr>

{{-- Merged sorted items + child categories --}}
@foreach($merged as $entry)
    @if($entry['type'] === 'item')
        @php
            $item      = $entry['obj'];
            $invoiced  = $item->invoiceItems->sum('amount');
            $coChange  = $item->changeOrderItems->filter(fn($coi) => $coi->changeOrder?->amendment_id === null)->sum('amount');
            $amdChange = $item->amendmentItems->sum('amount')
                       + $item->changeOrderItems->filter(fn($coi) => $coi->changeOrder?->amendment_id !== null)->sum('amount');
            $effective = $item->amount + $coChange + $amdChange;
            $remaining = $effective - $invoiced;
            $pct       = $effective > 0 ? min(100, round($invoiced / $effective * 100)) : 0;
            $crVal     = $crPerItem[$item->id] ?? null;
            $caVal     = $caPerItem[$item->id] ?? null;
            $expected  = $effective + ($crVal ?? 0) + ($caVal ?? 0);
        @endphp
        <tr data-parent="{{ $category->id }}">
            <td style="padding-left:{{ 8 + $indent + 12 }}px"><code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code></td>
            <td>
                {{ $item->description }}
                <div style="margin-top:.3rem;height:3px;background:#e5e7eb;border-radius:2px">
                    <div style="height:3px;border-radius:2px;width:{{ $pct }}%;background:{{ $pct >= 100 ? '#22c55e' : '#3b82f6' }}"></div>
                </div>
            </td>
            <td style="text-align:right;white-space:nowrap">{{ number_format($item->amount, 2, ',', ' ') }}</td>
            @if($hasCos)
            <td style="text-align:right;white-space:nowrap;color:{{ $coChange > 0 ? '#1d4ed8' : ($coChange < 0 ? '#dc2626' : '#9ca3af') }}">
                @if($coChange != 0)<a href="{{ route('contract-items.show', $item) }}" style="color:inherit;font-weight:600">{{ number_format($coChange, 2, ',', ' ') }}</a>@else—@endif
            </td>
            @endif
            @if($hasAmendments)
            <td style="text-align:right;white-space:nowrap;color:{{ $amdChange > 0 ? '#1d4ed8' : ($amdChange < 0 ? '#dc2626' : '#9ca3af') }}">
                @if($amdChange != 0)<a href="{{ route('contract-items.show', $item) }}" style="color:inherit;font-weight:600">{{ number_format($amdChange, 2, ',', ' ') }}</a>@else—@endif
            </td>
            @endif
            @if($hasCoChanges)
            <td style="text-align:right;white-space:nowrap;font-weight:600">{{ number_format($effective, 2, ',', ' ') }}</td>
            @endif
            <td style="text-align:right;white-space:nowrap;color:#6b7280">{{ number_format($invoiced, 2, ',', ' ') }}</td>
            <td style="text-align:right;white-space:nowrap;font-weight:600;color:{{ $remaining > 0 ? '#1d4ed8' : ($remaining < 0 ? '#dc2626' : '#6b7280') }}">
                {{ number_format($remaining, 2, ',', ' ') }}
            </td>
            @if($hasFuture)
            <td style="text-align:right;white-space:nowrap;background:#fef2f2;color:{{ $crVal !== null ? ($crVal > 0 ? '#dc2626' : ($crVal < 0 ? '#16a34a' : '#6b7280')) : '#d1d5db' }}">
                @if($crVal !== null)<a href="{{ route('contract-items.show', $item) }}" style="color:inherit;font-weight:600">{{ number_format($crVal, 2, ',', ' ') }}</a>@else—@endif
            </td>
            <td style="text-align:right;white-space:nowrap;background:#fef2f2;color:{{ $caVal !== null ? ($caVal > 0 ? '#dc2626' : ($caVal < 0 ? '#16a34a' : '#6b7280')) : '#d1d5db' }}">
                @if($caVal !== null)<a href="{{ route('contract-items.show', $item) }}" style="color:inherit;font-weight:600">{{ number_format($caVal, 2, ',', ' ') }}</a>@else—@endif
            </td>
            <td style="text-align:right;font-weight:600;background:#dbeafe;color:#1d4ed8">{{ number_format($expected, 2, ',', ' ') }}</td>
            @endif
        </tr>
    @else
        @include('contracts._cat_show', [
            'category'      => $entry['obj'],
            'parentCatId'   => $category->id,
            'depth'         => $depth + 1,
            'subtotalFn'    => $subtotalFn,
            'hasCos'        => $hasCos,
            'hasAmendments' => $hasAmendments,
            'hasCoChanges'  => $hasCoChanges,
            'hasFuture'     => $hasFuture,
            'crPerItem'     => $crPerItem,
            'caPerItem'     => $caPerItem,
            'colCount'      => $colCount,
        ])
    @endif
@endforeach
