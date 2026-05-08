<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractItemController extends Controller
{
    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $data = $request->validate([
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'amount'      => ['required', 'numeric'],
        ]);

        $data['contract_id'] = $contract->id;
        $data['sort'] = $contract->items()->max('sort') + 1;

        ContractItem::create($data);

        return back()->with('success', __('Item added.'));
    }

    public function edit(ContractItem $item): View
    {
        return view('contract_items.edit', ['item' => $item, 'contract' => $item->contract]);
    }

    public function update(Request $request, ContractItem $item): RedirectResponse
    {
        $data = $request->validate([
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'amount'      => ['required', 'numeric'],
        ]);

        $item->update($data);

        return redirect()->route('contracts.show', $item->contract)->with('success', __('Item saved.'));
    }

    public function destroy(ContractItem $item): RedirectResponse
    {
        $contract = $item->contract;
        $item->delete();

        return redirect()->route('contracts.show', $contract)->with('success', __('Item deleted.'));
    }
}
