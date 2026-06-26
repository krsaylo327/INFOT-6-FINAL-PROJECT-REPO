<?php

use App\Http\Controllers\Admin\UploaderReconciliationController;
use App\Http\Controllers\AgreementController;
use App\Http\Controllers\AgreementSubscriptionController;
use App\Http\Controllers\Settings\AgreementSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/

// Provide a named home route for auth tests
Route::inertia('/', 'Welcome')->name('home');

// Include additional settings routes (profile/security)
require __DIR__.'/settings.php';

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [AgreementController::class, 'dashboard'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | WORKFLOW DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/workflow-dashboard',
        [AgreementController::class, 'coordinatorWorkflowDashboard']
    );

    /*
    |--------------------------------------------------------------------------
    | AGREEMENTS
    |--------------------------------------------------------------------------
    */

    // AGREEMENTS LIST
    Route::get(
        '/agreements',
        [AgreementController::class, 'agreements']
    );

    // CREATE PAGE
    Route::get(
        '/agreements/create',
        [AgreementController::class, 'create']
    )->middleware('role:coordinator,admin');

    // STORE AGREEMENT
    Route::post(
        '/agreements',
        [AgreementController::class, 'store']
    )->middleware('role:coordinator,admin');

    // AGREEMENT DETAILS
    Route::get(
        '/agreements/{id}',
        [AgreementController::class, 'show']
    );

    // DOWNLOAD CURRENT DOCUMENT
    Route::get(
        '/agreements/{id}/download',
        [AgreementController::class, 'download']
    );

    // VIEW CURRENT DOCUMENT (inline)
    Route::get(
        '/agreements/{id}/view',
        [AgreementController::class, 'view']
    );

    // DOWNLOAD SPECIFIC VERSION
    Route::get(
        '/agreements/{id}/versions/{versionId}/download',
        [AgreementController::class, 'downloadVersion']
    );

    // VIEW SPECIFIC VERSION (inline)
    Route::get(
        '/agreements/{id}/versions/{versionId}/view',
        [AgreementController::class, 'viewVersion']
    );

    // Agreement subscription endpoints
    Route::post('/agreements/{id}/subscribe', [AgreementSubscriptionController::class, 'store']);
    Route::delete('/agreements/{id}/subscribe', [AgreementSubscriptionController::class, 'destroy']);

    // EDIT PAGE
    Route::get(
        '/agreements/{id}/edit',
        [AgreementController::class, 'edit']
    );

    // UPDATE AGREEMENT
    Route::put(
        '/agreements/{id}',
        [AgreementController::class, 'update']
    );

    /*
    |--------------------------------------------------------------------------
    | WORKFLOW ACTIONS
    |--------------------------------------------------------------------------
    */

    // FORWARD AGREEMENT
    Route::post(
        '/agreements/{id}/forward',
        [AgreementController::class, 'forwardWorkflow']
    )->middleware('role:coordinator,admin');

    // RETURN AGREEMENT
    Route::post(
        '/agreements/{id}/return',
        [AgreementController::class, 'returnAgreement']
    )->middleware('role:coordinator,admin');

    /*
    |--------------------------------------------------------------------------
    | AGREEMENT STATUS ACTIONS
    |--------------------------------------------------------------------------
    */

    // DISABLE AGREEMENT
    Route::patch(
        '/agreements/{id}/disable',
        [AgreementController::class, 'disable']
    );

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/users',
        [AgreementController::class, 'users']
    )->middleware('role:admin');

    // SETTINGS - Agreement reminders (admin only)
    Route::get('/settings/agreements', [AgreementSettingsController::class, 'index'])
        ->middleware('role:admin');

    Route::post('/settings/agreements', [AgreementSettingsController::class, 'update'])
        ->middleware('role:admin');

    Route::get(
        '/users/{id}/agreements',
        [AgreementController::class, 'userAgreements']
    );

    Route::get(
        '/users/create',
        [AgreementController::class, 'createUser']
    )->middleware('role:admin');

    Route::post(
        '/users',
        [AgreementController::class, 'storeUser']
    )->middleware('role:admin');

    Route::patch(
        '/users/{id}/disable',
        [AgreementController::class, 'disableUser']
    )->middleware('role:admin');

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOGS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/activity-logs',
        [AgreementController::class, 'activityLogs']
    );

    // Destructive admin routes removed: clearing activity logs and resetting agreements

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */
    Route::patch('/notifications/{id}/read', [AgreementController::class, 'markNotificationRead']);

    // Admin uploader reconciliation UI (admin only)
    Route::get('/admin/uploader-reconciliation', [UploaderReconciliationController::class, 'index'])
        ->middleware('role:admin');

    Route::post('/admin/uploader-reconciliation/map', [UploaderReconciliationController::class, 'map'])
        ->middleware('role:admin');

});
