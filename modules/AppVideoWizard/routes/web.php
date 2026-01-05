<?php

use Illuminate\Support\Facades\Route;
use Modules\AppVideoWizard\Http\Controllers\VideoWizardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->group(function () {
    // Main wizard page
    Route::get('app/video-wizard', [VideoWizardController::class, 'index'])->name('app.video-wizard.index');

    // Project management
    Route::get('app/video-wizard/projects', [VideoWizardController::class, 'projects'])->name('app.video-wizard.projects');
    Route::get('app/video-wizard/project/{id}', [VideoWizardController::class, 'edit'])->name('app.video-wizard.edit');
    Route::delete('app/video-wizard/project/{id}', [VideoWizardController::class, 'destroy'])->name('app.video-wizard.destroy');

    // API endpoints for wizard operations
    Route::post('app/video-wizard/save', [VideoWizardController::class, 'saveProject'])->name('app.video-wizard.save');
    Route::get('app/video-wizard/load/{id}', [VideoWizardController::class, 'loadProject'])->name('app.video-wizard.load');

    // AI operations
    Route::post('app/video-wizard/improve-concept', [VideoWizardController::class, 'improveConcept'])->name('app.video-wizard.improve-concept');
    Route::post('app/video-wizard/generate-script', [VideoWizardController::class, 'generateScript'])->name('app.video-wizard.generate-script');
    Route::post('app/video-wizard/generate-image', [VideoWizardController::class, 'generateImage'])->name('app.video-wizard.generate-image');
    Route::post('app/video-wizard/generate-voiceover', [VideoWizardController::class, 'generateVoiceover'])->name('app.video-wizard.generate-voiceover');
    Route::post('app/video-wizard/generate-animation', [VideoWizardController::class, 'generateAnimation'])->name('app.video-wizard.generate-animation');

    // Export operations
    Route::post('app/video-wizard/export/start', [VideoWizardController::class, 'startExport'])->name('app.video-wizard.export.start');
    Route::get('app/video-wizard/export/status/{jobId}', [VideoWizardController::class, 'exportStatus'])->name('app.video-wizard.export.status');
});
