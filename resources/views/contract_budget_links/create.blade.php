@extends('layouts.app')
@section('title', __('Link to budget').' — '.$contract->name)

@section('content')
<div class="breadcrumb">
    <a href="{{ route('contracts.index') }}">{{ __('Contracts') }}</a>
    <a href="{{ route('contracts.show', $contract) }}"><span>{{ $contract->name }}</span></a>
    <span>{{ __('Link to budget') }}</span>
</div>
<div class="page-header">
    <h1>{{ __('Link to budget') }}</h1>
</div>

<div class="card card-body" style="max-width:500px">
    @if($budgets->isEmpty())
        <p style="color:#6b7280;font-size:13px">{{ __('All project budgets are already linked, or no budgets exist for this project.') }}</p>
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Back') }}</a>
    @else
    <form method="POST" action="{{ route('contracts.budget-links.store', $contract) }}">
        @csrf
        @php
            $budgetCurrencies = $budgets->pluck('currency', 'id');
            $contractCurrency = $contract->currency;
        @endphp
        <div class="form-group">
            <label>{{ __('Budget') }} *</label>
            <select name="budget_id" id="bl-budget-select" required style="width:100%">
                <option value="">— {{ __('select') }} —</option>
                @foreach($budgets as $budget)
                    <option value="{{ $budget->id }}"
                            data-currency="{{ $budget->currency }}"
                            @selected(old('budget_id') == $budget->id)>
                        {{ $budget->name }} ({{ $budget->currency }})
                        @if($budget->code) · {{ $budget->code }} @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="max-width:260px">
            <label>
                {{ __('FX Rate') }}
                <span id="bl-fx-label" style="font-size:11px;color:#9ca3af">
                    ({{ $contractCurrency }} / {{ __('budget currency') }})
                </span>
            </label>
            <input type="number" name="fx_rate" id="bl-fx-rate" step="0.000001" min="0"
                   value="{{ old('fx_rate') }}" placeholder="{{ __('leave blank if same currency') }}"
                   style="text-align:right">
            <div id="bl-fx-hint" style="font-size:11px;color:#9ca3af;margin-top:.25rem">
                {{ __('e.g. 25.40 = 25.40 CZK per 1 EUR') }}
            </div>
        </div>
        <script>
        (function () {
            const sel    = document.getElementById('bl-budget-select');
            const fxInput = document.getElementById('bl-fx-rate');
            const fxLabel = document.getElementById('bl-fx-label');
            const fxHint  = document.getElementById('bl-fx-hint');
            const contractCurrency = '{{ $contractCurrency }}';

            function update() {
                const opt = sel.options[sel.selectedIndex];
                const budgetCurrency = opt ? opt.dataset.currency : '';
                if (!budgetCurrency) return;
                fxLabel.textContent = '(' + contractCurrency + ' / ' + budgetCurrency + ')';
                if (contractCurrency === budgetCurrency) {
                    fxInput.value = '1.000000';
                    fxHint.textContent = '{{ __("Same currency — rate set to 1.000000") }}';
                    fxInput.style.color = '#9ca3af';
                } else {
                    if (fxInput.value === '1.000000') fxInput.value = '';
                    fxHint.textContent = '{{ __("e.g. 25.40 = 25.40") }} ' + contractCurrency + ' {{ __("per 1") }} ' + budgetCurrency;
                    fxInput.style.color = '';
                }
            }

            sel.addEventListener('change', update);
            if (sel.value) update();
        })();
        </script>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ __('Create link') }}</button>
            <a href="{{ route('contracts.show', $contract) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
    @endif
</div>
@endsection
