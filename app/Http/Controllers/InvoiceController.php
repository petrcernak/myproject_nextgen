<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless(
            Project::where('id', $invoice->contract->project_id)->where('id_group', $this->currentGroupId())->exists(),
            403
        );
    }

    public function index(Request $request): View|RedirectResponse
    {
        $projectId = session('current_project_id');

        if (!$projectId) {
            return redirect()->route('projects.index')
                ->with('error', __('Please select a project first.'));
        }

        $contracts = Contract::where('project_id', $projectId)->orderBy('name')->get(['id', 'name', 'code']);
        $contractIds = $contracts->pluck('id');

        $companies = Company::where('id_group', $this->currentGroupId())
            ->orderBy('name')->pluck('name', 'id');

        $invoices = Invoice::with(['contract', 'sender', 'recipient'])
            ->whereIn('contract_id', $contractIds)
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('no', 'ilike', "%{$request->search}%")
                   ->orWhere('description', 'ilike', "%{$request->search}%");
            }))
            ->when($request->contract_id, fn ($q) => $q->where('contract_id', $request->contract_id))
            ->when($request->company_id,  fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('company_id', $request->company_id)))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from, fn ($q) => $q->where('issued', '>=', $request->from))
            ->when($request->to,   fn ($q) => $q->where('issued', '<=', $request->to))
            ->orderByDesc('issued')
            ->paginate(50)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'contracts', 'companies'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);
        $invoice->load(['contract.project', 'sender', 'recipient', 'items.contractItem']);
        $contractItems = $invoice->contract->items()
            ->with('invoiceItems')
            ->orderBy('sort')
            ->get()
            ->each(fn ($item) => $item->append(['invoiced_amount', 'remaining_amount']));
        return view('invoices.show', compact('invoice', 'contractItems'));
    }

    public function create(Contract $contract): View
    {
        abort_unless(
            Project::where('id', $contract->project_id)->where('id_group', $this->currentGroupId())->exists(),
            403
        );
        return view('invoices.form', compact('contract'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        abort_unless(
            Project::where('id', $contract->project_id)->where('id_group', $this->currentGroupId())->exists(),
            403
        );
        $data = $request->validate([
            'no'          => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'issued'      => ['nullable', 'date'],
            'taxdate'     => ['nullable', 'date'],
            'due'         => ['nullable', 'date'],
            'paid'        => ['nullable', 'date'],
            'note'        => ['nullable', 'string'],
        ]);

        $projectCompanyId = $contract->project->id_company;

        $data['contract_id'] = $contract->id;
        $data['sendby_id']   = $contract->direction === 1 ? $projectCompanyId : $contract->company_id;
        $data['sendto_id']   = $contract->direction === 1 ? $contract->company_id : $projectCompanyId;

        $invoice = Invoice::create($data);
        $invoice->recalculateStatus();

        return redirect()->route('invoices.show', $invoice)->with('success', __('Invoice created.'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);
        $contract = $invoice->contract;
        return view('invoices.form', compact('invoice', 'contract'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);
        $data = $request->validate([
            'no'          => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'issued'      => ['nullable', 'date'],
            'taxdate'     => ['nullable', 'date'],
            'due'         => ['nullable', 'date'],
            'paid'        => ['nullable', 'date'],
            'note'        => ['nullable', 'string'],
        ]);

        $invoice->update($data);
        $invoice->recalculateStatus();

        return redirect()->route('invoices.show', $invoice)->with('success', __('Invoice saved.'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);
        $contract = $invoice->contract;
        $invoice->items()->delete();
        $invoice->delete();

        return redirect()->route('contracts.show', $contract)->with('success', __('Invoice deleted.'));
    }
}
