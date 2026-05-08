<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceItemController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:500'],
            'amount'      => ['required', 'numeric'],
        ]);

        $data['invoice_id'] = $invoice->id;
        $data['sort'] = $invoice->items()->max('sort') + 1;

        InvoiceItem::create($data);
        $invoice->recalculateStatus();

        return back()->with('success', 'Položka byla přidána.');
    }

    public function destroy(InvoiceItem $item): RedirectResponse
    {
        $invoice = $item->invoice;
        $item->delete();
        $invoice->recalculateStatus();

        return redirect()->route('invoices.show', $invoice)->with('success', 'Položka byla smazána.');
    }
}
