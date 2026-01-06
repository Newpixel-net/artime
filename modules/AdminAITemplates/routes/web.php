<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminAITemplates\Http\Controllers\AdminAITemplatesController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "admin"], function () {
        Route::group(["prefix" => "ai/templates"], function () {
            // Resource routes provide: index, create, store, show, edit, update, destroy
            Route::resource('', AdminAITemplatesController::class)->names('admin.ai.templates');
            // Custom routes (not provided by resource)
            Route::post('list', [AdminAITemplatesController::class, 'list'])->name('admin.ai.templates.list');
            Route::post('save', [AdminAITemplatesController::class, 'save'])->name('admin.ai.templates.save');
            Route::post('status/{any}', [AdminAITemplatesController::class, 'status'])->name('admin.ai.templates.status');
        });

    });
});
