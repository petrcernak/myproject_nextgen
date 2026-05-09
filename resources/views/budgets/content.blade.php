@extends('layouts.app')
@section('title', __('Edit content').' — '.$budget->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('budgets.index') }}">{{ __('Budgets') }}</a>
    <a href="{{ route('budgets.show', $budget) }}"><span>{{ $budget->name }}</span></a>
    <span>{{ __('Edit content') }}</span>
</div>

<div class="page-header">
    <div>
        <h1>{{ $budget->name }} <code style="font-size:.8em;color:#6b7280">{{ $budget->code }}</code></h1>
        <div style="font-size:12px;color:#f59e0b;margin-top:.2rem;font-weight:600">✎ {{ __('Edit mode') }}</div>
    </div>
    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>

<div class="card" style="margin-bottom:1.25rem;padding:1rem;border:2px dashed #e5e7eb">
    <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.6rem">{{ __('Add category') }}</div>
    <form method="POST" action="{{ route('budgets.categories.store', $budget) }}" style="display:flex;gap:.5rem;align-items:flex-end">
        @csrf
        <div style="width:130px">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Code') }}</label>
            <input type="text" name="code" placeholder="A1">
        </div>
        <div style="flex:1">
            <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:.25rem">{{ __('Category name *') }}</label>
            <input type="text" name="name" placeholder="{{ __('Category name...') }}" required>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('+ Add category') }}</button>
    </form>
</div>

@forelse($budget->categories as $category)
<div class="card" style="margin-bottom:1rem;border-left:3px solid #2563eb">

    <div style="padding:.65rem 1rem;background:#f0f6ff;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:.75rem">
            @if($category->code)
                <code style="color:#2563eb;font-size:12px;font-weight:700">{{ $category->code }}</code>
            @endif
            <strong>{{ $category->name }}</strong>
            <span style="font-size:13px;color:#6b7280">{{ number_format($category->total, 2, ',', ' ') }}</span>
        </div>
        <form method="POST" action="{{ route('budget-categories.destroy', $category) }}" onsubmit="return confirm('{{ __('Delete category including all items?') }}')">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-sm">{{ __('Delete category') }}</button>
        </form>
    </div>

    @if($category->items->isNotEmpty())
    <table>
        <thead>
            <tr>
                <th style="width:110px">{{ __('Code') }}</th>
                <th>{{ __('Description') }}</th>
                <th style="text-align:right;width:160px">{{ __('Amount') }} ({{ $budget->currency }})</th>
                <th style="width:120px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($category->items as $item)
            <tr>
                <td><code style="color:#6b7280;font-size:12px">{{ $item->code ?? '—' }}</code></td>
                <td>{{ $item->description }}</td>
                <td style="text-align:right">{{ number_format($item->amount, 2, ',', ' ') }}</td>
                <td>
                    <div style="display:flex;gap:.3rem;justify-content:flex-end">
                        <a href="{{ route('budget-items.edit', $item) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('budget-items.destroy', $item) }}" onsubmit="return confirm('{{ __('Really delete?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">✕</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            <tr style="background:#f9fafb">
                <td colspan="2" style="text-align:right;color:#6b7280;font-size:12px">{{ __('Subtotal') }}</td>
                <td style="text-align:right;font-weight:600">{{ number_format($category->total, 2, ',', ' ') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div style="padding:.75rem 1rem;background:#fafafa;border-top:1px solid #f3f4f6">
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
            <button type="submit" class="btn btn-secondary btn-sm" style="white-space:nowrap">{{ __('+ Add') }}</button>
        </form>
    </div>

</div>
@empty
    <div class="card"><div class="empty"><strong>{{ __('No categories') }}</strong><p>{{ __('Add the first category using the form above.') }}</p></div></div>
@endforelse

<div style="margin-top:1rem">
    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-secondary">{{ __('← Back to overview') }}</a>
</div>
@endsection
