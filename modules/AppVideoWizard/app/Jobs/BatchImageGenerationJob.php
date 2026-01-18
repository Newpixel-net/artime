<?php

declare(strict_types=1);

namespace Modules\AppVideoWizard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Services\ImageGenerationService;
use Modules\AppVideoWizard\Services\QueuedJobsManager;
use Modules\AppVideoWizard\Services\PerformanceMonitoringService;
use Exception;

/**
 * Batch Image Generation Job
 *
 * PHASE 5 OPTIMIZATION: Background job for generating images in batches.
 *
 * Features:
 * - Processes multiple scenes in sequence
 * - Reports progress for real-time UI updates
 * - Supports cancellation
 * - Integrates with performance monitoring
 * - Handles failures gracefully with partial results
 */
class BatchImageGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800; // 30 minutes

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 1;

    /**
     * Project ID
     */
    protected int $projectId;

    /**
     * Job ID for tracking
     */
    protected string $jobId;

    /**
     * Scenes to generate images for
     */
    protected array $scenes;

    /**
     * Generation options
     */
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $projectId,
        string $jobId,
        array $scenes,
        array $options = []
    ) {
        $this->projectId = $projectId;
        $this->jobId = $jobId;
        $this->scenes = $scenes;
        $this->options = $options;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "batch_image_{$this->projectId}_{$this->jobId}";
    }

    /**
     * Execute the job.
     */
    public function handle(
        ImageGenerationService $imageService,
        QueuedJobsManager $jobsManager,
        PerformanceMonitoringService $performanceService
    ): void {
        Log::info("[BatchImageGenerationJob:{$this->jobId}] Starting batch image generation", [
            'projectId' => $this->projectId,
            'sceneCount' => count($this->scenes),
        ]);

        // Start performance tracking
        $timerId = $performanceService->startOperation('batch_image_generation', [
            'project_id' => $this->projectId,
            'scene_count' => count($this->scenes),
        ]);

        $jobsManager->markJobStarted($this->jobId);

        try {
            $project = WizardProject::findOrFail($this->projectId);
            $totalScenes = count($this->scenes);
            $processedScenes = 0;
            $successfulGenerations = 0;
            $failedGenerations = 0;
            $generatedImages = [];

            foreach ($this->scenes as $index => $scene) {
                // Check for cancellation
                if ($jobsManager->isCancellationRequested($this->jobId)) {
                    Log::info("[BatchImageGenerationJob:{$this->jobId}] Cancellation requested, stopping");
                    $jobsManager->updateJobStatus(
                        $this->jobId,
                        'cancelled',
                        (int) (($processedScenes / $totalScenes) * 100),
                        "Cancelled after processing {$processedScenes} of {$totalScenes} scenes",
                        [
                            'processed' => $processedScenes,
                            'successful' => $successfulGenerations,
                            'failed' => $failedGenerations,
                            'generated_images' => $generatedImages,
                        ]
                    );
                    return;
                }

                // Update progress
                $progress = (int) (($processedScenes / $totalScenes) * 100);
                $jobsManager->updateJobStatus(
                    $this->jobId,
                    'processing',
                    $progress,
                    "Generating image {$processedScenes + 1} of {$totalScenes}..."
                );

                try {
                    // Start timing individual image generation
                    $imageTimerId = $performanceService->startOperation('single_image_generation', [
                        'scene_index' => $index,
                    ]);

                    // Generate the image
                    $sceneOptions = array_merge($this->options, [
                        'sceneIndex' => $scene['index'] ?? $index,
                        'teamId' => $project->team_id ?? session('current_team_id', 0),
                    ]);

                    $result = $imageService->generateSceneImage($project, $scene, $sceneOptions);

                    // Stop timing
                    $performanceService->stopOperation($imageTimerId, [
                        'success' => isset($result['url']),
                        'model' => $sceneOptions['model'] ?? 'default',
                    ]);

                    if (isset($result['url'])) {
                        $generatedImages[] = [
                            'scene_index' => $scene['index'] ?? $index,
                            'url' => $result['url'],
                            'asset_id' => $result['asset_id'] ?? null,
                        ];
                        $successfulGenerations++;
                    } else {
                        $failedGenerations++;
                        Log::warning("[BatchImageGenerationJob:{$this->jobId}] Image generation returned no URL", [
                            'sceneIndex' => $index,
                            'result' => $result,
                        ]);
                    }
                } catch (Exception $e) {
                    $failedGenerations++;
                    Log::error("[BatchImageGenerationJob:{$this->jobId}] Failed to generate image for scene", [
                        'sceneIndex' => $index,
                        'error' => $e->getMessage(),
                    ]);
                }

                $processedScenes++;

                // Add a small delay between generations to avoid rate limiting
                if ($processedScenes < $totalScenes) {
                    usleep(500000); // 0.5 second delay
                }
            }

            // Stop performance tracking
            $performanceService->stopOperation($timerId, [
                'successful' => $successfulGenerations,
                'failed' => $failedGenerations,
            ]);

            // Persist metrics
            $performanceService->persistMetrics(
                $this->projectId,
                $project->user_id ?? null,
                'batch_image_generation'
            );

            // Mark job as completed
            $jobsManager->markJobCompleted($this->jobId, [
                'total_scenes' => $totalScenes,
                'successful' => $successfulGenerations,
                'failed' => $failedGenerations,
                'generated_images' => $generatedImages,
            ]);

            // Remove from active tracking
            $jobsManager->removeFromActiveTracking($this->projectId, $this->jobId);

            Log::info("[BatchImageGenerationJob:{$this->jobId}] Batch image generation completed", [
                'successful' => $successfulGenerations,
                'failed' => $failedGenerations,
            ]);

        } catch (Exception $e) {
            Log::error("[BatchImageGenerationJob:{$this->jobId}] Job failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $performanceService->stopOperation($timerId, ['error' => $e->getMessage()]);

            $jobsManager->markJobFailed($this->jobId, $e->getMessage(), [
                'exception_class' => get_class($e),
            ]);

            $jobsManager->removeFromActiveTracking($this->projectId, $this->jobId);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("[BatchImageGenerationJob:{$this->jobId}] Job failed permanently", [
            'error' => $exception?->getMessage(),
        ]);

        try {
            $jobsManager = app(QueuedJobsManager::class);
            $jobsManager->markJobFailed(
                $this->jobId,
                $exception?->getMessage() ?? 'Unknown error',
                ['permanent_failure' => true]
            );
            $jobsManager->removeFromActiveTracking($this->projectId, $this->jobId);
        } catch (Exception $e) {
            Log::error("[BatchImageGenerationJob:{$this->jobId}] Failed to update job status on failure", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'batch_image_generation',
            "project:{$this->projectId}",
            "job:{$this->jobId}",
        ];
    }
}
