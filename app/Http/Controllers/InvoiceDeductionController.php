<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceAdvanceDeduction;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceDeductionController extends Controller
{
    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless(
            Project::where('id', $invoice->contract->project_id)->where('id_group', $this->currentGroupId())->exists(),
            403
        );
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        $data = $request->validate([
            'advance_invoice_id' => ['required', 'exists:invoices,id'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
        ]);

        $advanceInvoice = Invoice::findOrFail($data['advance_invoice_id']);
        abort_unless($advanceInvoice->is_advance && $advanceInvoice->contract_id === $invoice->contract_id, 422);

        $remaining = $advanceInvoice->remaining_advance;
        abort_unless($data['amount'] <= $remaining + 0.001, 422);

        $data['invoice_id'] = $invoice->id;
        InvoiceAdvanceDeduction::create($data);
        $invoice->recalculateStatus();

        return back()->with('success', __('Deduction added.'));
    }

    public function destroy(InvoiceAdvanceDeduction $deduction): RedirectResponse
    {
        $invoice = $deduction->invoice;
        $this->authorizeInvoice($invoice);
        $deduction->delete();
        $invoice->recalculateStatus();

        return back()->with('success', __('Deduction removed.'));
    }
}
