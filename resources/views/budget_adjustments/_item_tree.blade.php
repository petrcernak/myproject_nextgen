{{-- Recursive partial: renders category + items with adjustment inputs --}}
@php
    $catIndent = $depth * 1.2;
    $merged = collect();
    foreach ($category->items as $item) {
        $merged->push(['type'=>'item','key'=>strtolower($item->code ?? $item->description ?? ''),'obj'=>$item]);
    }
    foreach ($category->children as $child) {
        $merged->push(['type'=>'category','key'=>strtolower($child->code ?? $child->name ?? ''),'obj'=>$child]);
    }
    $merged = $merged->sortBy('key');
@endphp

<tr style="background:#f0f6ff">
    <td colspan="3" style="font-weight:700;font-size:11px;color:#2563eb;padding-left:{{ 8 + $catIndent * 16 }}px">
        {{ $category->code ? $category->code.' — ' : '' }}{{ $category->name }}
    </td>
</tr>
@foreach($merged as $entry)
    @if($entry['type'] === 'item')
        @php
            $item = $entry['obj'];
            $existing = $existingAmounts[$item->id] ?? null;
        @endphp
        <tr>
            <td style="padding-left:{{ 8 + ($catIndent + 1) * 16 }}px;font-size:12px">
                <code style="color:#6b7280;font-size:11px">{{ $item->code ?? '—' }}</code>
                {{ $item->description }}
            </td>
            <td style="text-align:right;font-size:12px;color:#6b7280;white-space:nowrap">
                {{ number_format($item->amount, 2, ',', ' ') }}
            </td>
            <td style="width:160px">
                <input type="number" name="items[{{ $item->id }}]" step="0.01"
                       value="{{ $existing }}"
                       placeholder="0.00"
                       style="width:100%;text-align:right">
            </td>
        </tr>
    @else
        @include('budget_adjustments._item_tree', [
            'category'       => $entry['obj'],
            'depth'          => $depth + 1,
            'existingAmounts'=> $existingAmounts,
        ])
    @endif
@endforeach
