<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\User\Portfolios\PortfoliosController;
use App\Http\Controllers\User\Portfolios\PortfolioTransactionsController;

/*
|--------------------------------------------------------------------------
| User Support ROUTES
|--------------------------------------------------------------------------
|
| Users that have access to these routes must be logged in.
|
*/

Route::group(['middleware' => ['auth:sanctum']], function () {


    // Portfolios

    Route::get('/list-portfolios', [PortfoliosController::class, 'listPortfolios']);

    Route::get('/portfolio-card-summaries', [PortfoliosController::class, 'portfolioCardSummaries']);

    Route::get('/view-portfolio/{id}', [PortfoliosController::class, 'viewPortfolio']);

    Route::get('/get-create-portfolio-form-config', [PortfoliosController::class, 'getCreatePortfolioFormConfig']);

    Route::post('/create-portfolio', [PortfoliosController::class, 'createPortfolio']);

    Route::get('/get-update-portfolio-form-config/{id}', [PortfoliosController::class, 'getUpdatePortfolioFormConfig']);

    Route::put('/update-portfolio/{id}', [PortfoliosController::class, 'updatePortfolio']);

    Route::delete('/delete-portfolio/{id}', [PortfoliosController::class, 'deletePortfolio']);


    // portfolio Transactions


    Route::get('/list-portfolio-transactions/{portfolio_id}', [PortfolioTransactionsController::class, 'listPortfolioTransactions']);

    Route::get('/get-create-portfolio-transaction-form-config/{portfolio_id}', [PortfolioTransactionsController::class, 'getCreatePortfolioTransactionFormConfig']);

    Route::post('/create-portfolio-transaction/{portfolio_id}', [PortfolioTransactionsController::class, 'createPortfolioTransaction']);

    Route::get('/get-update-portfolio-transaction-form-config/{id}', [PortfolioTransactionsController::class, 'getUpdatePortfolioTransactionFormConfig']);

    Route::put('/update-portfolio-transaction/{id}', [PortfolioTransactionsController::class, 'updatePortfolioTransaction']);

    Route::delete('/delete-portfolio-transaction/{id}', [PortfolioTransactionsController::class, 'deletePortfolioTransaction']);

    Route::delete('/delete-all-portfolio-transactions/{portfolio_id}', [PortfolioTransactionsController::class, 'deleteAllPortfolioTransactions']);

    // user bulk upload of portfolio transactions

    Route::post('/csv-upload-portfolio-transactions/{portfolio_id}', [PortfolioTransactionsController::class, 'csvUploadTransactions']);

    // import portfolio transactions

    Route::get('/get-import-portfolio-transactions-config', [PortfolioTransactionsController::class, 'getImportPortfolioTransactionsConfig']);

    Route::post('/import-portfolio-transactions/{portfolio_id}', [PortfolioTransactionsController::class, 'importPortfolioTransactions']);

    Route::get('/export-portfolio-transactions/{portfolio_id}', [PortfolioTransactionsController::class, 'exportPortfolioTransactions']);
});
