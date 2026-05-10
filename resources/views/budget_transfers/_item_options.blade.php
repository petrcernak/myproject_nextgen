{{-- Recursive partial: renders <optgroup>/<option> elements for a category tree --}}
@foreach($category->items->sortBy(fn($i) => strtolower($i->code ?? $i->description ?? '')) as $item)
    <option value="{{ $item->id }}" {{ $selected == $item->id ? 'selected' : '' }}>
        {{ $item->code ? $item->code.' — ' : '' }}{{ $item->description }}
        ({{ $category->name }})
    </option>
@endforeach
@foreach($category->children->sortBy(fn($c) => strtolower($c->code ?? $c->name ?? '')) as $child)
    @include('budget_transfers._item_options', ['category' => $child, 'selected' => $selected])
@endforeach
