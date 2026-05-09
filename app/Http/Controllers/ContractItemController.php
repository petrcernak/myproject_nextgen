<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractItemController extends Controller
{
    public function show(ContractItem $item): View
    {
        abort_unless(
            Project::where('id', $item->contract->project_id)
                ->where('id_group', $this->currentGroupId())
                ->exists(),
            403
        );

        $item->load([
            'contract',
            'changeOrderItems.changeOrder.amendment',
            'amendmentItems.amendment',
            'changeRequestItems.changeRequest',
            'changeRequestItems.revisions',
            'anticipatedItems.anticipated',
            'invoiceItems.invoice',
        ]);

        return view('contract_items.show', compact('item'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $data = $request->validate([
            'code'                 => ['nullable', 'string', 'max:50'],
            'description'          => ['required', 'string', 'max:500'],
            'amount'               => ['required', 'numeric'],
            'contract_category_id' => ['required', 'integer', 'exists:contract_categories,id'],
        ]);

        $data['contract_id'] = $contract->id;
        $data['sort'] = $contract->items()->max('sort') + 1;

        ContractItem::create($data);

        return back()->with('success', __('Item added.'));
    }

    public function edit(ContractItem $item): View
    {
        $contract = $item->contract->load('categories');
        return view('contract_items.edit', ['item' => $item, 'contract' => $contract]);
    }

    public function update(Request $request, ContractItem $item): RedirectResponse
    {
        $data = $request->validate([
            'code'                 => ['nullable', 'string', 'max:50'],
            'description'          => ['required', 'string', 'max:500'],
            'amount'               => ['required', 'numeric'],
            'contract_category_id' => ['required', 'integer', 'exists:contract_categories,id'],
        ]);

        $item->update($data);

        return redirect()->route('contracts.content', $item->contract)->with('success', __('Item saved.'));
    }

    public function destroy(ContractItem $item): RedirectResponse
    {
        $contract = $item->contract;
        $item->delete();

        return redirect()->route('contracts.show', $contract)->with('success', __('Item deleted.'));
    }
}
