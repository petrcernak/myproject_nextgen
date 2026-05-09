<?php

use App\Http\Controllers\AmendmentController;
use App\Http\Controllers\AmendmentItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangeOrderController;
use App\Http\Controllers\ChangeOrderItemController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\ChangeRequestItemController;
use App\Http\Controllers\ChangeRequestItemRevisionController;
use App\Http\Controllers\ContractAnticipatedController;
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

    // Amendments
    Route::get('contracts/{contract}/amendments', [AmendmentController::class, 'indexForContract'])->name('contracts.amendments.index');
    Route::get('contracts/{contract}/amendments/create', [AmendmentController::class, 'create'])->name('contracts.amendments.create');
    Route::post('contracts/{contract}/amendments', [AmendmentController::class, 'store'])->name('contracts.amendments.store');
    Route::get('amendments/{amendment}', [AmendmentController::class, 'show'])->name('amendments.show');
    Route::get('amendments/{amendment}/content', [AmendmentController::class, 'editContent'])->name('amendments.content');
    Route::get('amendments/{amendment}/edit', [AmendmentController::class, 'edit'])->name('amendments.edit');
    Route::put('amendments/{amendment}', [AmendmentController::class, 'update'])->name('amendments.update');
    Route::delete('amendments/{amendment}', [AmendmentController::class, 'destroy'])->name('amendments.destroy');

    // Amendment Items
    Route::post('amendments/{amendment}/items', [AmendmentItemController::class, 'store'])->name('amendments.items.store');
    Route::get('amendment-items/{item}/edit', [AmendmentItemController::class, 'edit'])->name('amendment-items.edit');
    Route::put('amendment-items/{item}', [AmendmentItemController::class, 'update'])->name('amendment-items.update');
    Route::delete('amendment-items/{item}', [AmendmentItemController::class, 'destroy'])->name('amendment-items.destroy');

    // Change Orders
    Route::get('contracts/{contract}/change-orders', [ChangeOrderController::class, 'indexForContract'])->name('contracts.change-orders.index');
    Route::get('contracts/{contract}/change-orders/create', [ChangeOrderController::class, 'create'])->name('contracts.change-orders.create');
    Route::post('contracts/{contract}/change-orders', [ChangeOrderController::class, 'store'])->name('contracts.change-orders.store');
    Route::get('change-orders/{changeOrder}', [ChangeOrderController::class, 'show'])->name('change-orders.show');
    Route::get('change-orders/{changeOrder}/content', [ChangeOrderController::class, 'editContent'])->name('change-orders.content');
    Route::get('change-orders/{changeOrder}/edit', [ChangeOrderController::class, 'edit'])->name('change-orders.edit');
    Route::put('change-orders/{changeOrder}', [ChangeOrderController::class, 'update'])->name('change-orders.update');
    Route::delete('change-orders/{changeOrder}', [ChangeOrderController::class, 'destroy'])->name('change-orders.destroy');

    // Change Order Items
    Route::post('change-orders/{changeOrder}/items', [ChangeOrderItemController::class, 'store'])->name('change-orders.items.store');
    Route::get('co-items/{item}/edit', [ChangeOrderItemController::class, 'edit'])->name('co-items.edit');
    Route::put('co-items/{item}', [ChangeOrderItemController::class, 'update'])->name('co-items.update');
    Route::delete('co-items/{item}', [ChangeOrderItemController::class, 'destroy'])->name('co-items.destroy');

    // Contract Anticipated
    Route::get('contracts/{contract}/anticipateds', [ContractAnticipatedController::class, 'indexForContract'])->name('contracts.anticipateds.index');
    Route::get('contracts/{contract}/anticipateds/create', [ContractAnticipatedController::class, 'create'])->name('contracts.anticipateds.create');
    Route::post('contracts/{contract}/anticipateds', [ContractAnticipatedController::class, 'store'])->name('contracts.anticipateds.store');
    Route::get('contract-anticipateds/{contractAnticipated}', [ContractAnticipatedController::class, 'show'])->name('contract-anticipateds.show');
    Route::get('contract-anticipateds/{contractAnticipated}/content', [ContractAnticipatedController::class, 'editContent'])->name('contract-anticipateds.content');
    Route::get('contract-anticipateds/{contractAnticipated}/edit', [ContractAnticipatedController::class, 'edit'])->name('contract-anticipateds.edit');
    Route::put('contract-anticipateds/{contractAnticipated}', [ContractAnticipatedController::class, 'update'])->name('contract-anticipateds.update');
    Route::delete('contract-anticipateds/{contractAnticipated}', [ContractAnticipatedController::class, 'destroy'])->name('contract-anticipateds.destroy');
    Route::post('contract-anticipateds/{contractAnticipated}/items', [ContractAnticipatedController::class, 'storeItem'])->name('contracts.anticipateds.items.store');
    Route::get('ca-items/{item}/edit', [ContractAnticipatedController::class, 'editItem'])->name('ca-items.edit');
    Route::put('ca-items/{item}', [ContractAnticipatedController::class, 'updateItem'])->name('ca-items.update');
    Route::delete('ca-items/{item}', [ContractAnticipatedController::class, 'destroyItem'])->name('ca-items.destroy');

    // Change Requests
    Route::get('contracts/{contract}/change-requests', [ChangeRequestController::class, 'indexForContract'])->name('contracts.change-requests.index');
    Route::get('contracts/{contract}/change-requests/create', [ChangeRequestController::class, 'create'])->name('contracts.change-requests.create');
    Route::post('contracts/{contract}/change-requests', [ChangeRequestController::class, 'store'])->name('contracts.change-requests.store');
    Route::get('change-requests/{changeRequest}', [ChangeRequestController::class, 'show'])->name('change-requests.show');
    Route::get('change-requests/{changeRequest}/content', [ChangeRequestController::class, 'editContent'])->name('change-requests.content');
    Route::get('change-requests/{changeRequest}/edit', [ChangeRequestController::class, 'edit'])->name('change-requests.edit');
    Route::put('change-requests/{changeRequest}', [ChangeRequestController::class, 'update'])->name('change-requests.update');
    Route::delete('change-requests/{changeRequest}', [ChangeRequestController::class, 'destroy'])->name('change-requests.destroy');

    // Change Request Items
    Route::post('change-requests/{changeRequest}/items', [ChangeRequestItemController::class, 'store'])->name('change-requests.items.store');
    Route::delete('cr-items/{item}', [ChangeRequestItemController::class, 'destroy'])->name('cr-items.destroy');

    // Change Request Item Revisions
    Route::post('cr-items/{item}/revisions', [ChangeRequestItemRevisionController::class, 'store'])->name('cr-item-revisions.store');
    Route::get('cr-revisions/{revision}/edit', [ChangeRequestItemRevisionController::class, 'edit'])->name('cr-revisions.edit');
    Route::put('cr-revisions/{revision}', [ChangeRequestItemRevisionController::class, 'update'])->name('cr-revisions.update');
    Route::delete('cr-revisions/{revision}', [ChangeRequestItemRevisionController::class, 'destroy'])->name('cr-revisions.destroy');

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
