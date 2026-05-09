<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractCategoryController extends Controller
{
    private function authorizeContract(Contract $contract, bool $requireWrite = false): void
    {
        abort_unless(
            $contract->project->id_group == $this->currentGroupId(),
            403
        );
        if ($requireWrite) {
            abort_unless($this->currentUser()->canWrite($contract->project), 403);
        }
    }

    private function authorizeCategory(ContractCategory $category, bool $requireWrite = false): void
    {
        $this->authorizeContract($category->contract, $requireWrite);
    }

    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($contract, requireWrite: true);
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:contract_categories,id'],
        ]);

        $data['contract_id'] = $contract->id;
        $data['sort'] = $contract->categories()->max('sort') + 1;

        ContractCategory::create($data);

        return back()->with('success', __('Category added.'));
    }

    public function edit(ContractCategory $category): View
    {
        $this->authorizeCategory($category, requireWrite: true);
        return view('contract_categories.edit', [
            'category' => $category,
            'contract' => $category->contract,
        ]);
    }

    public function update(Request $request, ContractCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category, requireWrite: true);
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->update($data);

        return redirect()->route('contracts.content', $category->contract)->with('success', __('Category saved.'));
    }

    public function destroy(ContractCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category, requireWrite: true);
        $contract = $category->contract;
        abort_if(
            $category->items()->exists() || $category->children()->exists(),
            422,
            __('Cannot delete a category that contains items or subcategories.')
        );
        $category->delete();

        return redirect()->route('contracts.content', $contract)->with('success', __('Category deleted.'));
    }
}
