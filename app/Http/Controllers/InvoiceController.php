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
            ->withCount('files')
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
            ->when($request->filled('advance'), fn ($q) => $q->where('is_advance', $request->advance === '1'))
            ->when($request->file_filter === '0', fn ($q) => $q->doesntHave('files'))
            ->when($request->file_filter === '1', fn ($q) => $q->has('files'))
            ->orderByDesc('issued')
            ->paginate(50)
            ->withQueryString();

        $selectedContract = $request->contract_id
            ? $contracts->firstWhere('id', $request->contract_id)
            : null;
        $isAdvanceList = $request->filled('advance') && $request->advance === '1';

        return view('invoices.index', compact('invoices', 'contracts', 'companies', 'selectedContract', 'isAdvanceList'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);
        $invoice->load(['contract.project', 'sender', 'recipient', 'items.contractItem', 'deductions.advanceInvoice', 'files.tags']);
        $contractItems = $invoice->contract->items()
            ->with('invoiceItems')
            ->orderBy('sort')
            ->get()
            ->each(fn ($item) => $item->append(['invoiced_amount', 'remaining_amount']));
        $advanceInvoices = $invoice->is_advance ? collect() : $invoice->contract->invoices()
            ->where('is_advance', true)
            ->get();
        $canEdit      = $this->currentUser()->canWrite($invoice->contract->project);
        $existingTags = \App\Models\FileTag::where('id_group', $this->currentGroupId())->orderBy('name')->pluck('name');
        return view('invoices.show', compact('invoice', 'contractItems', 'advanceInvoices', 'canEdit', 'existingTags'));
    }

    public function create(Request $request, Contract $contract): View
    {
        abort_unless(
            Project::where('id', $contract->project_id)->where('id_group', $this->currentGroupId())->exists(),
            403
        );
        $isAdvance = $request->boolean('advance');
        return view('invoices.form', compact('contract', 'isAdvance'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        abort_unless(
            Project::where('id', $contract->project_id)->where('id_group', $this->currentGroupId())->exists(),
            403
        );
        $data = $request->validate([
            'no'             => ['required', 'string', 'max:100'],
            'description'    => ['nullable', 'string'],
            'issued'         => ['nullable', 'date'],
            'taxdate'        => ['required', 'date'],
            'due'            => ['nullable', 'date'],
            'paid'           => ['nullable', 'date'],
            'note'           => ['nullable', 'string'],
            'is_advance'     => ['nullable', 'boolean'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['is_advance'] = $request->input('is_advance') === '1';

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
        $contract  = $invoice->contract;
        $isAdvance = $invoice->is_advance;
        return view('invoices.form', compact('invoice', 'contract', 'isAdvance'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);
        $data = $request->validate([
            'no'             => ['required', 'string', 'max:100'],
            'description'    => ['nullable', 'string'],
            'issued'         => ['nullable', 'date'],
            'taxdate'        => ['required', 'date'],
            'due'            => ['nullable', 'date'],
            'paid'           => ['nullable', 'date'],
            'note'           => ['nullable', 'string'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
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
