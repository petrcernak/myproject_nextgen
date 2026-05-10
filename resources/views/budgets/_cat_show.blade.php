@php
    $indent = $depth * 1.25;
    $merged = collect();
    foreach ($category->items as $item) {
        $merged->push(['type' => 'item', 'key' => strtolower($item->code ?? $item->description ?? ''), 'obj' => $item]);
    }
    foreach ($category->children as $child) {
        $merged->push(['type' => 'category', 'key' => strtolower($child->code ?? $child->name ?? ''), 'obj' => $child]);
    }
    $merged = $merged->sortBy('key');
    $catSub = $subtotalFn($category);
    // column widths matching show.blade.php header
    $cw = [100, 90, 90, 110, 90, 90, 110, 90, 80, 90, 90, 100, 90, 80];
    // indices that use sign+color formatting
    $signIdx = [1, 2, 7, 8, 13];
    $catVals = [$catSub['amount'],$catSub['adj'],$catSub['trans'],$catSub['actual'],
                $catSub['contracts'],$catSub['changes'],$catSub['currComm'],
                $catSub['invoiced'],$catSub['fxImpact'],$catSub['vtp'],
                $catSub['anticipated'],$catSub['futureComm'],$catSub['cost'],$catSub['delta']];
@endphp

<details open class="cat-card" style="margin-bottom:.5rem;margin-left:{{ $indent }}rem;background:#fff;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.07);min-width:max-content">
    <summary style="list-style:none;display:flex;align-items:center;padding:.65rem 1rem;background:#f8fafc;border-bottom:1px solid #e5e7eb;cursor:pointer;user-select:none;gap:.5rem">
        <div style="display:flex;align-items:center;gap:.75rem;flex:1;min-width:300px">
            <span class="cat-caret" style="font-size:10px;color:#6b7280;transition:transform .15s;display:inline-block">▶</span>
            @if($category->code)
                <code style="color:#6b7280;font-size:12px">{{ $category->code }}</code>
            @endif
            <strong>{{ $category->name }}</strong>
        </div>
        <div style="display:flex;gap:0;font-size:12px;font-weight:600;flex-shrink:0">
            @foreach($cw as $i => $width)
                @php $v = $catVals[$i]; @endphp
                @if(in_array($i, $signIdx))
                    <span style="min-width:{{ $width }}px;text-align:right;padding-right:6px;color:{{ $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af') }}">
                        {{ $v != 0 ? ($v > 0 ? '+' : '').number_format(round($v),0,',',' ') : '—' }}
                    </span>
                @else
                    <span style="min-width:{{ $width }}px;text-align:right;padding-right:6px;color:{{ $v < 0 ? '#dc2626' : '#374151' }}">
                        {{ $v != 0 ? number_format(round($v),0,',',' ') : '—' }}
                    </span>
                @endif
            @endforeach
        </div>
    </summary>

    <div style="padding:.25rem .5rem">
        @foreach($merged as $entry)
            @if($entry['type'] === 'item')
                @php
                    $item   = $entry['obj'];
                    $d      = $itemCostData($item);
                    $itemVals = [$d['amount'],$d['adj'],$d['trans'],$d['actual'],
                                 $d['contracts'],$d['changes'],$d['currComm'],
                                 $d['invoiced'],$d['fxImpact'],$d['vtp'],
                                 $d['anticipated'],$d['futureComm'],$d['cost'],$d['delta']];
                @endphp
                <div style="display:flex;align-items:center;padding:.3rem .4rem;border-bottom:1px solid #f3f4f6;font-size:12px;min-width:max-content;gap:0">
                    <div style="display:flex;align-items:center;gap:.5rem;flex:1;min-width:300px">
                        <code style="color:#6b7280;font-size:11px;width:80px;flex-shrink:0">{{ $item->code ?? '—' }}</code>
                        <span>{{ $item->description }}</span>
                    </div>
                    <div style="display:flex;gap:0;flex-shrink:0">
                        @foreach($cw as $i => $width)
                            @php $v = $itemVals[$i]; @endphp
                            @if(in_array($i, $signIdx))
                                <span style="min-width:{{ $width }}px;text-align:right;padding-right:6px;font-weight:{{ $v != 0 ? '600' : '400' }};color:{{ $v > 0 ? '#1d4ed8' : ($v < 0 ? '#dc2626' : '#9ca3af') }}">
                                    {{ $v != 0 ? ($v > 0 ? '+' : '').number_format(round($v),0,',',' ') : '—' }}
                                </span>
                            @else
                                <span style="min-width:{{ $width }}px;text-align:right;padding-right:6px;color:{{ $v < 0 ? '#dc2626' : '#374151' }};font-weight:{{ $i === 3 ? '600' : '400' }}">
                                    {{ $v != 0 ? number_format(round($v),0,',',' ') : '—' }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                @include('budgets._cat_show', [
                    'category'    => $entry['obj'],
                    'budget'      => $budget,
                    'depth'       => $depth + 1,
                    'subtotalFn'  => $subtotalFn,
                    'itemCostData'=> $itemCostData,
                    'costData'    => $costData,
                ])
            @endif
        @endforeach
    </div>
</details>
