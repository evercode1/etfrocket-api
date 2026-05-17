<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Support\SupportController;
use App\Http\Controllers\Admin\Support\ManageUsersController;
use App\Http\Controllers\Admin\Support\UserStatsController;
use App\Http\Controllers\Admin\Support\ManageHelpArticlesController;

/*
|--------------------------------------------------------------------------
| ADMIN SUPPORT ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be Admin and logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum', 'isAdmin']], function () {

    // Manage Users

    Route::get('/manage-users', [ManageUsersController::class, 'index']);
    Route::get('/manage-user/{id}', [ManageUsersController::class, 'show']);
    Route::get('/manage-user/edit/{id}', [ManageUsersController::class, 'editFormConfig']);
    Route::post('/manage-user/{id}', [ManageUsersController::class, 'update']);
    Route::delete('/delete-user/{id}', [ManageUsersController::class, 'destroy']);
    Route::get('/manage-users/search/{keyword}', [ManageUsersController::class, 'search']);

    // User Stats

    Route::get('/user-signup-stats', [UserStatsController::class, 'userSignupStats']);


    // Support

    Route::get('/get-support-tickets', [SupportController::class, 'index']);
    Route::get('/support-ticket/{id}', [SupportController::class, 'show']);
    Route::get('/get-support-reply-form', [SupportController::class, 'getSupportReplyFormConfig']);
    Route::post('/support-reply-to-ticket', [SupportController::class, 'supportReplyToTicket']);
    Route::post('/close-ticket', [SupportController::class, 'closeTicket']);

    Route::get('/open-support-ticket-count', [SupportController::class, 'openTicketCount']);

    // Help Articles

    Route::get('/manage-help-articles', [ManageHelpArticlesController::class, 'index']);

    Route::get('/manage-help-article/{id}', [ManageHelpArticlesController::class, 'show']);

    Route::post('/create-help-article', [ManageHelpArticlesController::class, 'store']);

    Route::post('/update-help-article/{id}', [ManageHelpArticlesController::class, 'update']);

    Route::delete('/delete-help-article/{id}', [ManageHelpArticlesController::class, 'destroy']);
});
