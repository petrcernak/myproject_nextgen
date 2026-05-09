<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    private function authorizeBudget(Budget $budget, bool $requireWrite = false): void
    {
        $project = $budget->project;
        abort_unless($project && $project->id_group == $this->currentGroupId(), 403);
        $user = $this->currentUser();
        if ($requireWrite) {
            abort_unless($user->canWrite($project), 403);
        } else {
            abort_unless($user->canRead($project), 403);
        }
    }

    private function authorizeCategory(BudgetCategory $category, bool $requireWrite = false): void
    {
        $this->authorizeBudget($category->budget, $requireWrite);
    }

    private function authorizeItem(BudgetItem $item, bool $requireWrite = false): void
    {
        $this->authorizeCategory($item->category, $requireWrite);
    }

    public function index(): View|RedirectResponse
    {
        $projectId = session('current_project_id');

        if (!$projectId) {
            return redirect()->route('projects.index')
                ->with('error', __('Please select a project first.'));
        }

        $budgets = Budget::where('project_id', $projectId)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('budgets.index', compact('budgets'));
    }

    public function show(Budget $budget): View
    {
        $this->authorizeBudget($budget);
        $budget->load(['project', 'categories.items']);
        $canEdit = $this->currentUser()->canWrite($budget->project);
        return view('budgets.show', compact('budget', 'canEdit'));
    }

    public function editContent(Budget $budget): View|RedirectResponse
    {
        $this->authorizeBudget($budget);
        if (!$this->currentUser()->canWrite($budget->project)) {
            return redirect()->route('budgets.show', $budget)
                ->with('error', __('You do not have permission to edit budget content.'));
        }
        $budget->load(['categories.items']);
        return view('budgets.content', compact('budget'));
    }

    public function create(Project $project): View
    {
        abort_unless($project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);
        return view('budgets.form', compact('project'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->id_group == $this->currentGroupId(), 403);
        abort_unless($this->currentUser()->canWrite($project), 403);
        $data = $request->validate([
            'code'     => ['required', 'string', 'max:50'],
            'name'     => ['required', 'string', 'max:255'],
            'date'     => ['required', 'date'],
            'currency' => ['required', 'string', 'max:10'],
            'note'     => ['nullable', 'string'],
        ]);

        $data['project_id'] = $project->id;
        $budget = Budget::create($data);

        return redirect()->route('budgets.show', $budget)->with('success', __('Budget created.'));
    }

    public function edit(Budget $budget): View
    {
        $this->authorizeBudget($budget);
        return view('budgets.form', ['project' => $budget->project, 'budget' => $budget]);
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $data = $request->validate([
            'code'     => ['required', 'string', 'max:50'],
            'name'     => ['required', 'string', 'max:255'],
            'date'     => ['required', 'date'],
            'currency' => ['required', 'string', 'max:10'],
            'note'     => ['nullable', 'string'],
        ]);

        $budget->update($data);

        return redirect()->route('budgets.show', $budget)->with('success', __('Budget saved.'));
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $project = $budget->project;
        $budget->delete();

        return redirect()->route('projects.show', $project)->with('success', __('Budget deleted.'));
    }

    // --- Categories ---

    public function storeCategory(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeBudget($budget, requireWrite: true);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $data['budget_id'] = $budget->id;
        $data['sort'] = $budget->categories()->max('sort') + 1;

        BudgetCategory::create($data);

        return back()->with('success', __('Category added.'));
    }

    public function destroyCategory(BudgetCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category, requireWrite: true);
        $budget = $category->budget;
        $category->delete();

        return redirect()->route('budgets.show', $budget)->with('success', __('Category deleted.'));
    }

    // --- Items ---

    public function storeItem(Request $request, BudgetCategory $category): RedirectResponse
    {
        $this->authorizeCategory($category, requireWrite: true);
        $data = $request->validate([
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'amount'      => ['required', 'numeric'],
        ]);

        $data['budget_category_id'] = $category->id;
        $data['sort'] = $category->items()->max('sort') + 1;

        BudgetItem::create($data);

        return back()->with('success', __('Item added.'));
    }

    public function editItem(BudgetItem $item): View
    {
        $this->authorizeItem($item);
        return view('budget_items.edit', ['item' => $item, 'budget' => $item->category->budget]);
    }

    public function updateItem(Request $request, BudgetItem $item): RedirectResponse
    {
        $this->authorizeItem($item, requireWrite: true);
        $data = $request->validate([
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'amount'      => ['required', 'numeric'],
        ]);

        $item->update($data);

        return redirect()->route('budgets.show', $item->category->budget)->with('success', __('Item saved.'));
    }

    public function destroyItem(BudgetItem $item): RedirectResponse
    {
        $this->authorizeItem($item, requireWrite: true);
        $budget = $item->category->budget;
        $item->delete();

        return redirect()->route('budgets.show', $budget)->with('success', __('Item deleted.'));
    }
}
