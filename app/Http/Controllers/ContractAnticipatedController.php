<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractAnticipated;
use App\Models\ContractAnticipatedItem;
use App\Models\ContractItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractAnticipatedController extends Controller
{
    private function authorizeForContract(Contract $contract, bool $write = false): void
    {
        abort_unless($contract->project && $contract->project->id_group == $this->currentGroupId(), 403);
        $user = $this->currentUser();
        if ($write) {
            abort_unless($user->canWrite($contract->project), 403);
        } else {
            abort_unless($user->canRead($contract->project), 403);
        }
    }

    private function authorizeCa(ContractAnticipated $ca, bool $write = false): void
    {
        $this->authorizeForContract($ca->contract, $write);
    }

    public function indexForContract(Contract $contract, Request $request): View
    {
        $this->authorizeForContract($contract);
        $anticipateds = $contract->anticipateds()
            ->with(['items'])
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('code', 'ilike', "%{$request->search}%")
                   ->orWhere('name', 'ilike', "%{$request->search}%");
            }))
            ->orderByDesc('date')
            ->paginate(50)->withQueryString();
        $canEdit = $this->currentUser()->canWrite($contract->project);
        return view('contract_anticipateds.index', compact('contract', 'anticipateds', 'canEdit'));
    }

    public function create(Contract $contract): View
    {
        $this->authorizeForContract($contract);
        abort_unless($this->currentUser()->canWrite($contract->project), 403);
        $count    = $contract->anticipateds()->count();
        $nextCode = $contract->code . '/CA' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
        return view('contract_anticipateds.form', compact('contract', 'nextCode'));
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeForContract($contract, write: true);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $data['contract_id'] = $contract->id;
        $ca = ContractAnticipated::create($data);
        return redirect()->route('contract-anticipateds.show', $ca)->with('success', __('Anticipated created.'));
    }

    public function show(ContractAnticipated $contractAnticipated): View
    {
        $this->authorizeCa($contractAnticipated);
        $contractAnticipated->load([
            'contract.items.changeOrderItems',
            'contract.items.amendmentItems',
            'items.contractItem',
        ]);
        $canEdit = $this->currentUser()->canWrite($contractAnticipated->contract->project);
        return view('contract_anticipateds.show', compact('contractAnticipated', 'canEdit'));
    }

    public function editContent(ContractAnticipated $contractAnticipated): View|RedirectResponse
    {
        $this->authorizeCa($contractAnticipated);
        if (!$this->currentUser()->canWrite($contractAnticipated->contract->project)) {
            return redirect()->route('contract-anticipateds.show', $contractAnticipated)
                ->with('error', __('You do not have permission to edit anticipated content.'));
        }
        $contractAnticipated->load(['contract.items', 'items.contractItem']);
        return view('contract_anticipateds.content', compact('contractAnticipated'));
    }

    public function edit(ContractAnticipated $contractAnticipated): View
    {
        $this->authorizeCa($contractAnticipated);
        abort_unless($this->currentUser()->canWrite($contractAnticipated->contract->project), 403);
        $contract = $contractAnticipated->contract;
        return view('contract_anticipateds.form', compact('contractAnticipated', 'contract'));
    }

    public function update(Request $request, ContractAnticipated $contractAnticipated): RedirectResponse
    {
        $this->authorizeCa($contractAnticipated, write: true);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);
        $contractAnticipated->update($data);
        return redirect()->route('contract-anticipateds.show', $contractAnticipated)->with('success', __('Anticipated saved.'));
    }

    public function destroy(ContractAnticipated $contractAnticipated): RedirectResponse
    {
        $this->authorizeCa($contractAnticipated, write: true);
        $contract = $contractAnticipated->contract;
        $contractAnticipated->delete();
        return redirect()->route('contracts.show', $contract)->with('success', __('Anticipated deleted.'));
    }

    public function storeItem(Request $request, ContractAnticipated $contractAnticipated): RedirectResponse
    {
        $this->authorizeCa($contractAnticipated, write: true);

        $data = $request->validate([
            'contract_item_id' => ['required', 'exists:contract_items,id'],
            'amount'           => ['required', 'numeric'],
            'description'      => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless(
            ContractItem::where('id', $data['contract_item_id'])
                ->where('contract_id', $contractAnticipated->contract_id)->exists(),
            422
        );

        $data['contract_anticipated_id'] = $contractAnticipated->id;
        $data['sort'] = ($contractAnticipated->items()->max('sort') ?? 0) + 1;

        ContractAnticipatedItem::create($data);

        return back()->with('success', __('Item added.'));
    }

    public function updateItem(Request $request, ContractAnticipatedItem $item): RedirectResponse
    {
        $this->authorizeCa($item->anticipated, write: true);

        $data = $request->validate([
            'amount'      => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $item->update($data);

        return redirect()->route('contract-anticipateds.content', $item->anticipated)->with('success', __('Item saved.'));
    }

    public function editItem(ContractAnticipatedItem $item): View
    {
        $this->authorizeCa($item->anticipated, write: true);
        $contractAnticipated = $item->anticipated;
        $contractAnticipated->load(['contract.items', 'items.contractItem']);
        return view('contract_anticipateds.content', compact('contractAnticipated'))->with('editItem', $item);
    }

    public function destroyItem(ContractAnticipatedItem $item): RedirectResponse
    {
        $this->authorizeCa($item->anticipated, write: true);
        $item->delete();
        return back()->with('success', __('Item deleted.'));
    }
}
