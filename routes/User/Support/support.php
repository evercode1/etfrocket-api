<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\User\Support\UserSupportController;
use App\Http\Controllers\User\Support\UserHelpArticleController;

/*
|--------------------------------------------------------------------------
| User Support ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {


    // Support

    Route::get('/my-support-tickets', [UserSupportController::class, 'index']);
    Route::get('/my-support-ticket/{id}', [UserSupportController::class, 'show']);
    Route::get('/my-support-response', [UserSupportController::class, 'showResponse']);
    Route::post('/mark-support-response-as-read', [UserSupportController::class, 'markAsRead']);
    Route::get('/new-support-ticket-form', [UserSupportController::class, 'newTicketFormConfig']);
    Route::post('/create-support-ticket', [UserSupportController::class, 'store']);
    Route::get('/new-support-response-to-ticket-form', [UserSupportController::class, 'newResponseFormConfig']);
    Route::post('/respond-to-support-response', [UserSupportController::class, 'respondToSupport']);

    Route::get('/unread-support-responses', [UserSupportController::class, 'unreadResponses']);
});

/*
|--------------------------------------------------------------------------
| Public Help Center
|--------------------------------------------------------------------------
*/

Route::get('/help-articles', [UserHelpArticleController::class, 'index']);

Route::get('/help-article/{slug}', [UserHelpArticleController::class, 'show']);
