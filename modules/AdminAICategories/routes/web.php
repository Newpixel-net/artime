<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminAICategories\Http\Controllers\AdminAICategoriesController;


Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "admin"], function () {
        Route::group(["prefix" => "ai/categories"], function () {
            // Resource routes provide: index, create, store, show, edit, update, destroy
            Route::resource('/', AdminAICategoriesController::class)->names('admin.ai.categories');
            // Custom routes (not provided by resource)
            Route::post('save', [AdminAICategoriesController::class, 'save'])->name('admin.ai.categories.save');
            Route::post('list', [AdminAICategoriesController::class, 'list'])->name('admin.ai.categories.list');
            Route::post('status/{any}', [AdminAICategoriesController::class, 'status'])->name('app.ai.categories.status');
        });
    });
});