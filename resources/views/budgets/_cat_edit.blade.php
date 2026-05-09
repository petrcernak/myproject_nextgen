@php
    $indent      = $depth * 1.5;
    $borderColor = $depth > 0 ? '#60a5fa' : '#2563eb';
    $bgHeader    = $depth > 0 ? '#f0f8ff' : '#f0f6ff';
    $canDelete   = $category->items->isEmpty() && $category->children->isEmpty();

    $lastChild = $category->children->sortBy('sort')->last();
    if ($lastChild && $lastChild->code && preg_match('/^(.*?)(\d+)$/', $lastChild->code, $m)) {
        $nextChildCode = $m[1] . str_pad((int)$m[2] + 1, strlen($m[2]), '0', STR_PAD_LEFT);
    } elseif (!$lastChild && $category->code) {
        $nextChildCode = rtrim($category->code, '.') . '.01';
    } else {
        $nextChildCode = '';
    }

    $merged = collect();
    foreach ($category->items as $item) {
        $merged->push(['type' => 'item', 'key' => strtolower($item->code ?? $item->description ?? ''), 'obj' => $item]);
    }
    foreach ($category->children as $child) {
        $merged->push(['type' => 'category', 'key' => strtolower($child->code ?? $child->name ?? ''), 'obj' => $child]);
    }
    $merged = $merged->sortBy('key');
@endphp

<details open class="cat-card" style="margin-bottom:.75rem;margin-left:{{ $indent }}rem;border-left:3px solid {{ $borderColor }};background:#fff;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.07)">
    <summary style="list-style:none;display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;background:{{ $bgHeader }};cursor:pointer;user-select:none">
        <div style="display:flex;align-items:center;gap:.75rem">
            <span class="cat-caret" style="font-size:10px;color:{{ $borderColor }};transition:transform .15s;display:inline-block">▶</span>
            @if($category->code)
                <code style="color:{{ $borderColor }};font-size:12px;font-weight:700">{{ $category->code }}</code>
            @endif
            <strong>{{ $category->name }}</strong>
            <span style="font-size:13px;color:#6b7280">{{ number_format($category->total, 2, ',', ' ') }}</span>
        </div>
        <div style="display:flex;gap:.3rem;align-items:center" onclick="event.stopPropagation()">
            <button type="button"
                    onclick="document.getElementById('subcat-form-{{ $category->id }}').classList.toggle('hidden-form')"
                    class="btn btn-secondary btn-sm">+ {{ __('Subcategory') }}</button>
            <a href="{{ route('budget-categories.edit', $category) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
            @if($canDelete)
            <form method="POST" action="{{ route('budget-categories.destroy', $category) }}" onsubmit="return confirm('{{ __('Delete category?') }}')">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
            </form>
            @else
                <button class="btn btn-sm" disabled title="{{ __('Remove all items and subcategories first') }}"
                        style="background:#e5e7eb;color:#9ca3af;cursor:not-allowed;border:1px solid #d1d5db">{{ __('Delete') }}</button>
            @endif
        </div>
    </summary>

    {{-- Add subcategory form --}}
    <div id="subcat-form-{{ $category->id }}" class="hidden-form" style="padding:.75rem 1rem;background:#eff6ff;border-bottom:1px solid #dbeafe">
        <div style="font-size:11px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem">{{ __('Add subcategory') }}</div>
        <form method="POST" action="{{ route('budgets.categories.store', $budget) }}" style="display:flex;gap:.5rem;align-items:flex-end">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $category->id }}">
            <div style="width:130px">
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Code') }}</label>
                <input type="text" name="code" value="{{ $nextChildCode }}" placeholder="01.01">
            </div>
            <div style="flex:1">
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Subcategory name *') }}</label>
                <input type="text" name="name" placeholder="{{ __('Subcategory name...') }}" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('+ Add') }}</button>
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="document.getElementById('subcat-form-{{ $category->id }}').classList.add('hidden-form')">{{ __('Cancel') }}</button>
        </form>
    </div>

    {{-- Merged sorted items + subcategories --}}
    <div style="padding:.25rem .5rem">
        @foreach($merged as $entry)
            @if($entry['type'] === 'item')
                @php $item = $entry['obj']; @endphp
                <div style="display:flex;align-items:center;gap:.5rem;padding:.3rem .4rem;border-bottom:1px solid #f3f4f6">
                    <code style="color:#6b7280;font-size:11px;width:100px;flex-shrink:0">{{ $item->code ?? '—' }}</code>
                    <span style="flex:1;font-size:12px">{{ $item->description }}</span>
                    <span style="font-size:12px;font-weight:600;white-space:nowrap;min-width:120px;text-align:right">{{ number_format($item->amount, 2, ',', ' ') }}</span>
                    <a href="{{ route('budget-items.edit', $item) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('budget-items.destroy', $item) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">✕</button>
                    </form>
                </div>
            @else
                @include('budgets._cat_edit', ['category' => $entry['obj'], 'budget' => $budget, 'depth' => $depth + 1])
            @endif
        @endforeach
    </div>

    {{-- Add item form --}}
    <div style="padding:.65rem 1rem;background:#fafafa;border-top:1px solid #f3f4f6">
        <form method="POST" action="{{ route('budget-categories.items.store', $category) }}" style="display:flex;gap:.5rem;align-items:flex-end">
            @csrf
            <div style="width:110px">
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Code') }}</label>
                <input type="text" name="code" value="{{ str_pad($category->items->count() + 1, 2, '0', STR_PAD_LEFT) }}" maxlength="50">
            </div>
            <div style="flex:1">
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Description') }} *</label>
                <input type="text" name="description" placeholder="{{ __('Item description...') }}" required>
            </div>
            <div style="width:150px">
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Amount') }} *</label>
                <input type="number" name="amount" step="0.01" placeholder="0.00" required>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="white-space:nowrap">{{ __('+ Add item') }}</button>
        </form>
    </div>
</details>
