<?php

use App\Http\Controllers\Api\ArticleTypeController;
use App\Http\Controllers\Api\ArticleViewsController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Web-backed API endpoints
Route::middleware('web')->group(function () {
    Route::get('/attachments', [AttachmentController::class, 'index'])->name('api.attachments.index');
    Route::post('/attachments', [AttachmentController::class, 'store'])->name('api.attachments.store');
    Route::get('/attachments/{id}', [AttachmentController::class, 'show'])->name('api.attachments.show');
    Route::put('/attachments/{id}', [AttachmentController::class, 'update'])->name('api.attachments.update');
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy'])->name('api.attachments.destroy');

    Route::get('/tags', [TagController::class, 'index'])->name('api.tags.index');
    Route::get('/tags/fetch', [TagController::class, 'fetch'])->name('api.tags.fetch');
    Route::get('/{locale}/header/tags', [TagController::class, 'header'])->name('api.header.tags');
    Route::get('/{locale}/article-types/dropdown', [ArticleTypeController::class, 'dropdown'])->name('api.types.dropdown');

});

Route::get('/views/{article}', [ArticleViewsController::class, 'getViews'])->name('api.get.article.views');
Route::post('/views/{locale}/{article}', [ArticleViewsController::class, 'setView'])->name('api.set.article.view');

Route::get('/search/{locale}', [SearchController::class, 'search'])->name('api.search');

Route::get('/auth', function(){
//    abort(403);     // todo  should use in static mode
    return "ok";  // todo for test only
})->name('api.authcheck');

// For special cases
Route::get('/{locale}/article/{id}/rest', [ArticleViewsController::class, 'restContent'])->name('api.article.rest');
