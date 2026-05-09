@php
    $indent   = $depth * 1.25;
    $merged = collect();
    foreach ($category->items as $item) {
        $merged->push(['type' => 'item', 'key' => strtolower($item->code ?? $item->description ?? ''), 'obj' => $item]);
    }
    foreach ($category->children as $child) {
        $merged->push(['type' => 'category', 'key' => strtolower($child->code ?? $child->name ?? ''), 'obj' => $child]);
    }
    $merged = $merged->sortBy('key');
    $catSub = $subtotalFn($category);
@endphp

<details open class="cat-card" style="margin-bottom:.75rem;margin-left:{{ $indent }}rem;background:#fff;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.07)">
    <summary style="list-style:none;display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;background:#f8fafc;border-bottom:1px solid #e5e7eb;cursor:pointer;user-select:none">
        <div style="display:flex;align-items:center;gap:.75rem">
            <span class="cat-caret" style="font-size:10px;color:#6b7280;transition:transform .15s;display:inline-block">▶</span>
            @if($category->code)
                <code style="color:#6b7280;font-size:12px">{{ $category->code }}</code>
            @endif
            <strong>{{ $category->name }}</strong>
        </div>
        <div style="display:flex;gap:2rem;font-size:12px;font-weight:600">
            <span style="color:#6b7280;min-width:100px;text-align:right">{{ number_format($catSub['amount'], 2, ',', ' ') }}</span>
            <span style="color:{{ $catSub['adjustment'] > 0 ? '#1d4ed8' : ($catSub['adjustment'] < 0 ? '#dc2626' : '#9ca3af') }};min-width:100px;text-align:right">
                {{ $catSub['adjustment'] != 0 ? ($catSub['adjustment'] > 0 ? '+' : '').number_format($catSub['adjustment'], 2, ',', ' ') : '—' }}
            </span>
            <span style="color:{{ $catSub['transfer'] > 0 ? '#1d4ed8' : ($catSub['transfer'] < 0 ? '#dc2626' : '#9ca3af') }};min-width:100px;text-align:right">
                {{ $catSub['transfer'] != 0 ? ($catSub['transfer'] > 0 ? '+' : '').number_format($catSub['transfer'], 2, ',', ' ') : '—' }}
            </span>
            <span style="min-width:110px;text-align:right">{{ number_format($catSub['actual'], 2, ',', ' ') }}</span>
        </div>
    </summary>

    <div style="padding:.25rem .5rem">
        @foreach($merged as $entry)
            @if($entry['type'] === 'item')
                @php
                    $item       = $entry['obj'];
                    $adj        = $item->adjustment;
                    $transfer   = (float) $item->transfer;
                    $actual     = $item->actual_budget;
                @endphp
                <div style="display:flex;align-items:center;gap:.5rem;padding:.3rem .4rem;border-bottom:1px solid #f3f4f6;font-size:12px">
                    <code style="color:#6b7280;font-size:11px;width:100px;flex-shrink:0">{{ $item->code ?? '—' }}</code>
                    <span style="flex:1">{{ $item->description }}</span>
                    <span style="min-width:100px;text-align:right;color:#6b7280">{{ number_format($item->amount, 2, ',', ' ') }}</span>
                    <span style="min-width:100px;text-align:right;font-weight:{{ $adj != 0 ? '600' : '400' }};color:{{ $adj > 0 ? '#1d4ed8' : ($adj < 0 ? '#dc2626' : '#9ca3af') }}">
                        {{ $adj != 0 ? ($adj > 0 ? '+' : '').number_format($adj, 2, ',', ' ') : '—' }}
                    </span>
                    <span style="min-width:100px;text-align:right;font-weight:{{ $transfer != 0 ? '600' : '400' }};color:{{ $transfer > 0 ? '#1d4ed8' : ($transfer < 0 ? '#dc2626' : '#9ca3af') }}">
                        {{ $transfer != 0 ? ($transfer > 0 ? '+' : '').number_format($transfer, 2, ',', ' ') : '—' }}
                    </span>
                    <span style="min-width:110px;text-align:right;font-weight:600">{{ number_format($actual, 2, ',', ' ') }}</span>
                </div>
            @else
                @include('budgets._cat_show', [
                    'category'   => $entry['obj'],
                    'budget'     => $budget,
                    'depth'      => $depth + 1,
                    'subtotalFn' => $subtotalFn,
                ])
            @endif
        @endforeach
    </div>
</details>
