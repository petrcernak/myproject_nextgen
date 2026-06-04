@php
    $catId   = 'cc'.$category->id;
    $anc     = isset($ancestors) ? $ancestors : '';
    $ancList = $anc !== '' ? $anc.','.$catId : $catId;

    $sub    = $subtotalFn($category);
    $indent = $depth * 1.25;

    $merged = collect();
    foreach ($contract->items->where('contract_category_id', $category->id) as $item) {
        $merged->push(['type'=>'item','key'=>strtolower($item->code ?? $item->description ?? ''),'obj'=>$item]);
    }
    foreach ($category->children as $child) {
        $merged->push(['type'=>'category','key'=>strtolower($child->code ?? $child->name ?? ''),'obj'=>$child]);
    }
    $merged = $merged->sortBy('key');
@endphp

<tr id="bgt-row-{{ $catId }}" class="bgt-cat" data-ancestors="{{ $anc }}" onclick="bgtToggle('{{ $catId }}')">
    <td colspan="2" style="padding-left:{{ .5+$indent }}rem">
        <span class="bgt-caret" style="font-size:9px;color:#6b7280;display:inline-block;transition:transform .12s;transform:rotate(90deg);margin-right:.4rem">▶</span>
        @if($category->code)<code style="color:#6b7280;font-size:11px;margin-right:.25rem">{{ $category->code }}</code>@endif
        <strong>{{ $category->name }}</strong>
    </td>
    <td style="font-weight:600">{{ number_format($sub['amount'],2,',',' ') }}</td>
    @if($hasCos)
    <td style="font-weight:600;color:{{ $sub['co']>0?'#1d4ed8':($sub['co']<0?'#dc2626':'#9ca3af') }}">
        {{ $sub['co']!=0?number_format($sub['co'],2,',',' '):'—' }}
    </td>
    @endif
    @if($hasAmendments)
    <td style="font-weight:600;color:{{ $sub['amd']>0?'#1d4ed8':($sub['amd']<0?'#dc2626':'#9ca3af') }}">
        {{ $sub['amd']!=0?number_format($sub['amd'],2,',',' '):'—' }}
    </td>
    @endif
    @if($hasCoChanges)
    <td style="font-weight:600">{{ number_format($sub['effective'],2,',',' ') }}</td>
    @endif
    <td style="color:#6b7280;font-weight:600">{{ number_format($sub['invoiced'],2,',',' ') }}</td>
    <td style="font-weight:600;color:{{ $sub['remaining']>0?'#1d4ed8':($sub['remaining']<0?'#dc2626':'#6b7280') }}">
        {{ number_format($sub['remaining'],2,',',' ') }}
    </td>
    @if($hasFuture)
    <td style="font-weight:600;color:{{ $sub['cr']>0?'#dc2626':($sub['cr']<0?'#16a34a':'#9ca3af') }}">
        {{ $sub['cr']!=0?number_format($sub['cr'],2,',',' '):'—' }}
    </td>
    <td style="font-weight:600;color:{{ $sub['ca']>0?'#dc2626':($sub['ca']<0?'#16a34a':'#9ca3af') }}">
        {{ $sub['ca']!=0?number_format($sub['ca'],2,',',' '):'—' }}
    </td>
    <td style="font-weight:600;color:#1d4ed8">{{ number_format($sub['expected'],2,',',' ') }}</td>
    @endif
</tr>

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
        <tr class="bgt-item" data-ancestors="{{ $ancList }}">
            <td style="padding-left:{{ 2+$indent }}rem"><code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code></td>
            <td style="text-align:left;white-space:normal">
                {{ $item->description }}
                <div style="margin-top:.25rem;height:3px;background:#e5e7eb;border-radius:2px">
                    <div style="height:3px;border-radius:2px;width:{{ $pct }}%;background:{{ $pct>=100?'#22c55e':'#3b82f6' }}"></div>
                </div>
            </td>
            <td>{{ number_format($item->amount,2,',',' ') }}</td>
            @if($hasCos)
            <td style="color:{{ $coChange>0?'#1d4ed8':($coChange<0?'#dc2626':'#9ca3af') }}">
                @if($coChange!=0)<a href="{{ route('contract-items.show',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right;font-weight:600">{{ number_format($coChange,2,',',' ') }}</a>@else—@endif
            </td>
            @endif
            @if($hasAmendments)
            <td style="color:{{ $amdChange>0?'#1d4ed8':($amdChange<0?'#dc2626':'#9ca3af') }}">
                @if($amdChange!=0)<a href="{{ route('contract-items.show',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right;font-weight:600">{{ number_format($amdChange,2,',',' ') }}</a>@else—@endif
            </td>
            @endif
            @if($hasCoChanges)
            <td style="font-weight:600">{{ number_format($effective,2,',',' ') }}</td>
            @endif
            <td style="color:#6b7280">{{ number_format($invoiced,2,',',' ') }}</td>
            <td style="font-weight:600;color:{{ $remaining>0?'#1d4ed8':($remaining<0?'#dc2626':'#6b7280') }}">{{ number_format($remaining,2,',',' ') }}</td>
            @if($hasFuture)
            <td style="color:{{ $crVal!==null?($crVal>0?'#dc2626':($crVal<0?'#16a34a':'#6b7280')):'#d1d5db' }}">
                @if($crVal!==null)<a href="{{ route('contract-items.show',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right;font-weight:600">{{ number_format($crVal,2,',',' ') }}</a>@else—@endif
            </td>
            <td style="color:{{ $caVal!==null?($caVal>0?'#dc2626':($caVal<0?'#16a34a':'#6b7280')):'#d1d5db' }}">
                @if($caVal!==null)<a href="{{ route('contract-items.show',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right;font-weight:600">{{ number_format($caVal,2,',',' ') }}</a>@else—@endif
            </td>
            <td style="font-weight:600;color:#1d4ed8">{{ number_format($expected,2,',',' ') }}</td>
            @endif
        </tr>
    @else
        @include('contracts._cat_show',[
            'category'      => $entry['obj'],
            'depth'         => $depth+1,
            'ancestors'     => $ancList,
            'subtotalFn'    => $subtotalFn,
            'hasCos'        => $hasCos,
            'hasAmendments' => $hasAmendments,
            'hasCoChanges'  => $hasCoChanges,
            'hasFuture'     => $hasFuture,
            'crPerItem'     => $crPerItem,
            'caPerItem'     => $caPerItem,
        ])
    @endif
@endforeach
