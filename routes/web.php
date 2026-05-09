<?php

use App\Http\Controllers\AmendmentController;
use App\Http\Controllers\ContractCategoryController;
use App\Http\Controllers\RetentionReleaseController;
use App\Http\Controllers\RetentionBankGuaranteeController;
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
    Route::get('contracts-underbilled', [ContractController::class, 'underbilled'])->name('contracts.underbilled');
    Route::get('contracts-overbilled',  [ContractController::class, 'overbilled'])->name('contracts.overbilled');
    Route::get('contracts/{contract}/files', [ContractController::class, 'showFiles'])->name('contracts.files');
    Route::get('contracts/{contract}/retention', [ContractController::class, 'showRetention'])->name('contracts.retention');
    Route::get('contracts/{contract}/content', [ContractController::class, 'editContent'])->name('contracts.content');
    Route::post('contracts/{contract}/categories',          [ContractCategoryController::class, 'store'])->name('contracts.categories.store');
    Route::get('contract-categories/{category}/edit',       [ContractCategoryController::class, 'edit'])->name('contract-categories.edit');
    Route::put('contract-categories/{category}',            [ContractCategoryController::class, 'update'])->name('contract-categories.update');
    Route::delete('contract-categories/{category}',         [ContractCategoryController::class, 'destroy'])->name('contract-categories.destroy');
    Route::post('contracts/{contract}/items', [\App\Http\Controllers\ContractItemController::class, 'store'])->name('contracts.items.store');
    Route::get('contract-items/{item}', [\App\Http\Controllers\ContractItemController::class, 'show'])->name('contract-items.show');
    Route::get('contract-items/{item}/edit', [\App\Http\Controllers\ContractItemController::class, 'edit'])->name('contract-items.edit');
    Route::put('contract-items/{item}', [\App\Http\Controllers\ContractItemController::class, 'update'])->name('contract-items.update');
    Route::delete('contract-items/{item}', [\App\Http\Controllers\ContractItemController::class, 'destroy'])->name('contract-items.destroy');

    Route::get('amendments', [AmendmentController::class, 'index'])->name('amendments.index');
    Route::get('change-orders', [ChangeOrderController::class, 'index'])->name('change-orders.index');
    Route::get('change-requests', [ChangeRequestController::class, 'index'])->name('change-requests.index');

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

    Route::post('change-requests/{changeRequest}/convert', [ChangeRequestController::class, 'convert'])->name('change-requests.convert');

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
    Route::post('invoices/{invoice}/deductions', [\App\Http\Controllers\InvoiceDeductionController::class, 'store'])->name('invoices.deductions.store');
    Route::delete('invoice-deductions/{deduction}', [\App\Http\Controllers\InvoiceDeductionController::class, 'destroy'])->name('invoice-deductions.destroy');

    // Retention releases
    Route::post('contracts/{contract}/retention-releases',           [RetentionReleaseController::class, 'store'])->name('contracts.retention-releases.store');
    Route::delete('retention-releases/{retentionRelease}',           [RetentionReleaseController::class, 'destroy'])->name('retention-releases.destroy');
    Route::post('retention-releases/{retentionRelease}/files',       [\App\Http\Controllers\FileController::class, 'storeForRetentionRelease'])->name('retention-releases.files.store');

    // Retention bank guarantees
    Route::post('contracts/{contract}/retention-bank-guarantees',    [RetentionBankGuaranteeController::class, 'store'])->name('contracts.retention-bank-guarantees.store');
    Route::delete('retention-bank-guarantees/{retentionBankGuarantee}', [RetentionBankGuaranteeController::class, 'destroy'])->name('retention-bank-guarantees.destroy');
    Route::post('retention-bank-guarantees/{retentionBankGuarantee}/files', [\App\Http\Controllers\FileController::class, 'storeForRetentionBankGuarantee'])->name('retention-bank-guarantees.files.store');

    Route::post('contracts/{contract}/files',        [\App\Http\Controllers\FileController::class, 'storeForContract'])->name('contracts.files.store');
    Route::post('amendments/{amendment}/files',      [\App\Http\Controllers\FileController::class, 'storeForAmendment'])->name('amendments.files.store');
    Route::post('change-orders/{changeOrder}/files', [\App\Http\Controllers\FileController::class, 'storeForChangeOrder'])->name('change-orders.files.store');
    Route::post('invoices/{invoice}/files',          [\App\Http\Controllers\FileController::class, 'storeForInvoice'])->name('invoices.files.store');
    Route::get('files',              [\App\Http\Controllers\FileController::class, 'indexGlobal'])->name('files.index');
    Route::get('files/{file}/view',  [\App\Http\Controllers\FileController::class, 'show'])->name('files.show');
    Route::delete('files/{file}',    [\App\Http\Controllers\FileController::class, 'destroy'])->name('files.destroy');

    Route::resource('companies', CompanyController::class);
    Route::resource('groups', GroupController::class);
    Route::get('activity-log', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show']);
    Route::get('users/{user}/rights', [\App\Http\Controllers\UserController::class, 'editRights'])->name('users.rights');
    Route::post('users/{user}/rights', [\App\Http\Controllers\UserController::class, 'updateRights'])->name('users.rights.update');

    // Budgets
    Route::get('budgets/{budget}/adjustments/create', [\App\Http\Controllers\BudgetAdjustmentController::class, 'create'])->name('budgets.adjustments.create');
    Route::post('budgets/{budget}/adjustments',       [\App\Http\Controllers\BudgetAdjustmentController::class, 'store'])->name('budgets.adjustments.store');
    Route::get('budget-adjustments/{adjustment}',     [\App\Http\Controllers\BudgetAdjustmentController::class, 'show'])->name('budget-adjustments.show');
    Route::get('budget-adjustments/{adjustment}/edit',[\App\Http\Controllers\BudgetAdjustmentController::class, 'edit'])->name('budget-adjustments.edit');
    Route::put('budget-adjustments/{adjustment}',     [\App\Http\Controllers\BudgetAdjustmentController::class, 'update'])->name('budget-adjustments.update');
    Route::delete('budget-adjustments/{adjustment}',  [\App\Http\Controllers\BudgetAdjustmentController::class, 'destroy'])->name('budget-adjustments.destroy');
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
    Route::get('budget-categories/{category}/edit', [\App\Http\Controllers\BudgetController::class, 'editCategory'])->name('budget-categories.edit');
    Route::put('budget-categories/{category}', [\App\Http\Controllers\BudgetController::class, 'updateCategory'])->name('budget-categories.update');
    Route::get('budget-items/{item}/edit', [\App\Http\Controllers\BudgetController::class, 'editItem'])->name('budget-items.edit');
    Route::put('budget-items/{item}', [\App\Http\Controllers\BudgetController::class, 'updateItem'])->name('budget-items.update');
    Route::delete('budget-items/{item}', [\App\Http\Controllers\BudgetController::class, 'destroyItem'])->name('budget-items.destroy');
});
