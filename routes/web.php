<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('contracts.index'));
    Route::post('project/switch/{project}', function (\App\Models\Project $project) {
        session(['current_project_id' => $project->id]);
        return redirect()->back()->with('success', __('Switched to project: :name', ['name' => $project->name]));
    })->name('project.switch');

    Route::post('locale/{locale}', function (string $locale) {
        abort_unless(in_array($locale, ['en', 'cs']), 422);
        session(['locale' => $locale]);
        if (Auth::check()) {
            /** @var \App\Models\User $u */
            $u = Auth::user();
            $u->update(['locale' => $locale]);
        }
        return back();
    })->name('locale.switch');

    Route::post('group/switch/{group}', function (\App\Models\Group $group) {
        /** @var \App\Models\User $u */
        $u = Auth::user();
        abort_unless($u?->isSuperAdmin(), 403);
        session(['current_group_id' => $group->id, 'current_project_id' => null]);
        return redirect()->route('contracts.index')->with('success', __('Switched to group: :name', ['name' => $group->name]));
    })->name('group.switch');

    Route::resource('projects', ProjectController::class);
    Route::resource('projects.contracts', ContractController::class)->shallow();
    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('contracts/{contract}/content', [ContractController::class, 'editContent'])->name('contracts.content');
    Route::post('contracts/{contract}/items', [\App\Http\Controllers\ContractItemController::class, 'store'])->name('contracts.items.store');
    Route::get('contract-items/{item}/edit', [\App\Http\Controllers\ContractItemController::class, 'edit'])->name('contract-items.edit');
    Route::put('contract-items/{item}', [\App\Http\Controllers\ContractItemController::class, 'update'])->name('contract-items.update');
    Route::delete('contract-items/{item}', [\App\Http\Controllers\ContractItemController::class, 'destroy'])->name('contract-items.destroy');

    Route::resource('contracts.invoices', InvoiceController::class)->shallow();
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices/{invoice}/items', [\App\Http\Controllers\InvoiceItemController::class, 'store'])->name('invoices.items.store');
    Route::delete('invoice-items/{item}', [\App\Http\Controllers\InvoiceItemController::class, 'destroy'])->name('invoice-items.destroy');

    Route::resource('companies', CompanyController::class);
    Route::resource('groups', GroupController::class);
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show']);
    Route::get('users/{user}/rights', [\App\Http\Controllers\UserController::class, 'editRights'])->name('users.rights');
    Route::post('users/{user}/rights', [\App\Http\Controllers\UserController::class, 'updateRights'])->name('users.rights.update');

    // Budgets
    Route::get('budgets', [\App\Http\Controllers\BudgetController::class, 'index'])->name('budgets.index');
    Route::get('projects/{project}/budgets/create', [\App\Http\Controllers\BudgetController::class, 'create'])->name('projects.budgets.create');
    Route::post('projects/{project}/budgets', [\App\Http\Controllers\BudgetController::class, 'store'])->name('projects.budgets.store');
    Route::get('budgets/{budget}', [\App\Http\Controllers\BudgetController::class, 'show'])->name('budgets.show');
    Route::get('budgets/{budget}/content', [\App\Http\Controllers\BudgetController::class, 'editContent'])->name('budgets.content');
    Route::get('budgets/{budget}/edit', [\App\Http\Controllers\BudgetController::class, 'edit'])->name('budgets.edit');
    Route::put('budgets/{budget}', [\App\Http\Controllers\BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('budgets/{budget}', [\App\Http\Controllers\BudgetController::class, 'destroy'])->name('budgets.destroy');
    Route::post('budgets/{budget}/categories', [\App\Http\Controllers\BudgetController::class, 'storeCategory'])->name('budgets.categories.store');
    Route::delete('budget-categories/{category}', [\App\Http\Controllers\BudgetController::class, 'destroyCategory'])->name('budget-categories.destroy');
    Route::post('budget-categories/{category}/items', [\App\Http\Controllers\BudgetController::class, 'storeItem'])->name('budget-categories.items.store');
    Route::get('budget-items/{item}/edit', [\App\Http\Controllers\BudgetController::class, 'editItem'])->name('budget-items.edit');
    Route::put('budget-items/{item}', [\App\Http\Controllers\BudgetController::class, 'updateItem'])->name('budget-items.update');
    Route::delete('budget-items/{item}', [\App\Http\Controllers\BudgetController::class, 'destroyItem'])->name('budget-items.destroy');
});
