<?php

namespace App\Http\Controllers;

use App\Models\ContractItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceItemController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'contract_item_id' => ['nullable', 'exists:contract_items,id'],
            'description'      => ['required', 'string', 'max:500'],
            'amount'           => ['required', 'numeric'],
        ]);

        // verify contract_item belongs to this invoice's contract
        if ($data['contract_item_id']) {
            $ci = ContractItem::find($data['contract_item_id']);
            abort_unless($ci && $ci->contract_id === $invoice->contract_id, 422);
        }

        $data['invoice_id'] = $invoice->id;
        $data['sort'] = $invoice->items()->max('sort') + 1;

        InvoiceItem::create($data);
        $invoice->recalculateStatus();

        return back()->with('success', __('Item added.'));
    }

    public function destroy(InvoiceItem $item): RedirectResponse
    {
        $invoice = $item->invoice;
        $item->delete();
        $invoice->recalculateStatus();

        return redirect()->route('invoices.show', $invoice)->with('success', __('Item deleted.'));
    }
}
