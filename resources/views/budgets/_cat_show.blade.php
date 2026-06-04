@php
    $catId   = 'c'.$category->id;
    $anc     = isset($ancestors) ? $ancestors : '';
    $ancList = $anc !== '' ? $anc.','.$catId : $catId;

    $merged = collect();
    foreach ($category->items as $item) {
        $merged->push(['type'=>'item','key'=>strtolower($item->code ?? $item->description ?? ''),'obj'=>$item]);
    }
    foreach ($category->children as $child) {
        $merged->push(['type'=>'category','key'=>strtolower($child->code ?? $child->name ?? ''),'obj'=>$child]);
    }
    $merged = $merged->sortBy('key');

    $catSub  = $subtotalFn($category);
    $signIdx = [1,2,7,8];
    $catVals = [$catSub['amount'],$catSub['adj'],$catSub['trans'],$catSub['actual'],
                $catSub['contracts'],$catSub['changes'],$catSub['currComm'],
                $catSub['invoiced'],$catSub['fxImpact'],$catSub['vtp'],
                $catSub['anticipated'],$catSub['futureComm'],$catSub['cost'],$catSub['delta']];
    $indent  = $depth * 1.25;
@endphp

<tr id="bgt-row-{{ $catId }}" class="bgt-cat" data-ancestors="{{ $anc }}" onclick="bgtToggle('{{ $catId }}')">
    <td style="padding-left:{{ .5+$indent }}rem">
        <span class="bgt-caret" style="font-size:9px;color:#6b7280;display:inline-block;transition:transform .12s;transform:rotate(90deg);margin-right:.4rem">▶</span>
        @if($category->code)<code style="color:#6b7280;font-size:11px;margin-right:.25rem">{{ $category->code }}</code>@endif
        <strong>{{ $category->name }}</strong>
    </td>
    @foreach($catVals as $i => $v)
        @if($i === 13)
            <td style="font-weight:600;color:{{ $v > 0 ? '#dc2626' : ($v < 0 ? '#1d4ed8' : '#374151') }}">
                {{ $v != 0 ? ($v > 0 ? '+' : '').number_format(round($v),0,',',' ') : '—' }}
            </td>
        @elseif(in_array($i,$signIdx))
            <td style="font-weight:600;color:{{ $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af') }}">
                {{ $v != 0 ? ($v > 0 ? '+' : '').number_format(round($v),0,',',' ') : '—' }}
            </td>
        @else
            <td style="font-weight:600;color:{{ $v < 0 ? '#dc2626' : '#374151' }}">
                {{ $v != 0 ? number_format(round($v),0,',',' ') : '—' }}
            </td>
        @endif
    @endforeach
</tr>

@foreach($merged as $entry)
    @if($entry['type'] === 'item')
        @php
            $item     = $entry['obj'];
            $d        = $itemCostData($item);
            $itemVals = [$d['amount'],$d['adj'],$d['trans'],$d['actual'],
                         $d['contracts'],$d['changes'],$d['currComm'],
                         $d['invoiced'],$d['fxImpact'],$d['vtp'],
                         $d['anticipated'],$d['futureComm'],$d['cost'],$d['delta']];
        @endphp
        <tr class="bgt-item" data-ancestors="{{ $ancList }}">
            <td style="padding-left:{{ 2+$indent }}rem">
                <code style="color:#6b7280;font-size:11px;display:inline-block;min-width:38px">{{ $item->code ?? '—' }}</code>
                {{ $item->description }}
            </td>
            @foreach($itemVals as $i => $v)
                @if($i === 1)
                    <td style="font-weight:{{ $v!=0?'600':'400' }};color:{{ $v>0?'#1d4ed8':($v<0?'#dc2626':'#9ca3af') }}">
                        @if($v!=0)<a href="{{ route('budget-items.adjustments',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right">{{ ($v>0?'+':'').number_format(round($v),0,',',' ') }}</a>@else—@endif
                    </td>
                @elseif($i === 2)
                    <td style="font-weight:{{ $v!=0?'600':'400' }};color:{{ $v>0?'#1d4ed8':($v<0?'#dc2626':'#9ca3af') }}">
                        @if($v!=0)<a href="{{ route('budget-items.transfers',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right">{{ ($v>0?'+':'').number_format(round($v),0,',',' ') }}</a>@else—@endif
                    </td>
                @elseif(in_array($i,[4,5,6]))
                    <td style="color:{{ $v<0?'#dc2626':'#374151' }};font-weight:{{ $i===6?'600':'400' }}">
                        @if($v!=0)<a href="{{ route('budget-items.commitments',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right">{{ number_format(round($v),0,',',' ') }}</a>@else—@endif
                    </td>
                @elseif($i === 7)
                    <td>
                        @if($v!=0)<a href="{{ route('budget-items.invoiced',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right">{{ number_format(round($v),0,',',' ') }}</a>@else—@endif
                    </td>
                @elseif($i === 9)
                    <td><a href="{{ route('budget-items.value-to-place',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right">{{ $v!=0?number_format(round($v),0,',',' '):'—' }}</a></td>
                @elseif($i === 10)
                    <td><a href="{{ route('budget-items.anticipated',$item) }}" style="color:inherit;text-decoration:none;display:block;text-align:right">{{ $v!=0?number_format(round($v),0,',',' '):'—' }}</a></td>
                @elseif($i === 13)
                    <td style="font-weight:{{ $v!=0?'600':'400' }};color:{{ $v>0?'#dc2626':($v<0?'#1d4ed8':'#374151') }}">
                        {{ $v!=0?($v>0?'+':'').number_format(round($v),0,',',' '):'—' }}
                    </td>
                @elseif(in_array($i,$signIdx))
                    <td style="font-weight:{{ $v!=0?'600':'400' }};color:{{ $v>0?'#1d4ed8':($v<0?'#dc2626':'#9ca3af') }}">
                        {{ $v!=0?($v>0?'+':'').number_format(round($v),0,',',' '):'—' }}
                    </td>
                @else
                    <td style="color:{{ $v<0?'#dc2626':'#374151' }};font-weight:{{ $i===3?'600':'400' }}">
                        {{ $v!=0?number_format(round($v),0,',',' '):'—' }}
                    </td>
                @endif
            @endforeach
        </tr>
    @else
        @include('budgets._cat_show',[
            'category'    => $entry['obj'],
            'budget'      => $budget,
            'depth'       => $depth+1,
            'ancestors'   => $ancList,
            'subtotalFn'  => $subtotalFn,
            'itemCostData'=> $itemCostData,
            'costData'    => $costData,
        ])
    @endif
@endforeach
