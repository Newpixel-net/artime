<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Jobs\BatchImageGenerationJob;
use Modules\AppVideoWizard\Jobs\BatchScriptGenerationJob;
use Modules\AppVideoWizard\Models\WizardProject;

/**
 * Queued Jobs Manager
 *
 * PHASE 5 OPTIMIZATION: Centralized management for async/queued operations.
 *
 * Key responsibilities:
 * 1. Dispatch expensive operations to background queues
 * 2. Track job progress for real-time UI updates
 * 3. Handle job retries and failures gracefully
 * 4. Provide unified API for checking job status
 * 5. Support cancellation of running jobs
 */
class QueuedJobsManager
{
    /**
     * Cache prefix for job status tracking
     */
    protected const STATUS_CACHE_PREFIX = 'vw_job_status_';

    /**
     * Cache TTL for job status (24 hours)
     */
    protected const STATUS_CACHE_TTL = 86400;

    /**
     * Dispatch a batch image generation job.
     *
     * @param WizardProject $project The wizard project
     * @param array $scenes Scenes to generate images for
     * @param array $options Generation options
     * @return string Job ID for tracking
     */
    public function dispatchImageGeneration(WizardProject $project, array $scenes, array $options = []): string
    {
        $jobId = $this->generateJobId('img');

        // Initialize job status
        $this->initializeJobStatus($jobId, [
            'type' => 'image_generation',
            'project_id' => $project->id,
            'total_items' => count($scenes),
            'options' => $options,
        ]);

        // Dispatch the job
        BatchImageGenerationJob::dispatch(
            $project->id,
            $jobId,
            $scenes,
            $options
        )->onQueue('video-wizard-images');

        Log::info('QueuedJobsManager: Dispatched image generation job', [
            'jobId' => $jobId,
            'projectId' => $project->id,
            'sceneCount' => count($scenes),
        ]);

        return $jobId;
    }

    /**
     * Dispatch a batch script generation job.
     *
     * @param WizardProject $project The wizard project
     * @param array $options Generation options
     * @return string Job ID for tracking
     */
    public function dispatchScriptGeneration(WizardProject $project, array $options = []): string
    {
        $jobId = $this->generateJobId('script');

        // Initialize job status
        $this->initializeJobStatus($jobId, [
            'type' => 'script_generation',
            'project_id' => $project->id,
            'options' => $options,
        ]);

        // Dispatch the job
        BatchScriptGenerationJob::dispatch(
            $project->id,
            $jobId,
            $options
        )->onQueue('video-wizard-scripts');

        Log::info('QueuedJobsManager: Dispatched script generation job', [
            'jobId' => $jobId,
            'projectId' => $project->id,
        ]);

        return $jobId;
    }

    /**
     * Get the status of a job.
     *
     * @param string $jobId Job ID
     * @return array|null Job status or null if not found
     */
    public function getJobStatus(string $jobId): ?array
    {
        return Cache::get(self::STATUS_CACHE_PREFIX . $jobId);
    }

    /**
     * Update job status.
     *
     * @param string $jobId Job ID
     * @param string $status Status (pending, processing, completed, failed, cancelled)
     * @param int $progress Progress percentage (0-100)
     * @param string|null $message Status message
     * @param array $data Additional data
     */
    public function updateJobStatus(
        string $jobId,
        string $status,
        int $progress,
        ?string $message = null,
        array $data = []
    ): void {
        $currentStatus = $this->getJobStatus($jobId) ?? [];

        $updatedStatus = array_merge($currentStatus, [
            'status' => $status,
            'progress' => $progress,
            'message' => $message ?? $currentStatus['message'] ?? '',
            'updated_at' => now()->toIso8601String(),
        ], $data);

        // Track completion time
        if ($status === 'completed' && !isset($updatedStatus['completed_at'])) {
            $updatedStatus['completed_at'] = now()->toIso8601String();
            $startedAt = $updatedStatus['started_at'] ?? $updatedStatus['created_at'] ?? null;
            if ($startedAt) {
                $updatedStatus['duration_seconds'] = now()->diffInSeconds($startedAt);
            }
        }

        Cache::put(
            self::STATUS_CACHE_PREFIX . $jobId,
            $updatedStatus,
            self::STATUS_CACHE_TTL
        );
    }

    /**
     * Mark a job as started.
     *
     * @param string $jobId Job ID
     */
    public function markJobStarted(string $jobId): void
    {
        $this->updateJobStatus($jobId, 'processing', 0, 'Starting...', [
            'started_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Mark a job as completed.
     *
     * @param string $jobId Job ID
     * @param array $result Job result data
     */
    public function markJobCompleted(string $jobId, array $result = []): void
    {
        $this->updateJobStatus($jobId, 'completed', 100, 'Completed successfully', [
            'result' => $result,
        ]);
    }

    /**
     * Mark a job as failed.
     *
     * @param string $jobId Job ID
     * @param string $error Error message
     * @param array $context Additional context
     */
    public function markJobFailed(string $jobId, string $error, array $context = []): void
    {
        $this->updateJobStatus($jobId, 'failed', 0, $error, [
            'error' => $error,
            'error_context' => $context,
            'failed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Request cancellation of a job.
     *
     * @param string $jobId Job ID
     * @return bool True if cancellation was requested
     */
    public function requestCancellation(string $jobId): bool
    {
        $status = $this->getJobStatus($jobId);
        if (!$status) {
            return false;
        }

        // Only pending or processing jobs can be cancelled
        if (!in_array($status['status'], ['pending', 'processing'])) {
            return false;
        }

        $this->updateJobStatus($jobId, 'cancelling', $status['progress'] ?? 0, 'Cancellation requested');

        // Set a cancellation flag that jobs should check
        Cache::put(self::STATUS_CACHE_PREFIX . $jobId . '_cancel', true, 3600);

        Log::info('QueuedJobsManager: Cancellation requested', ['jobId' => $jobId]);

        return true;
    }

    /**
     * Check if a job has been cancelled.
     *
     * @param string $jobId Job ID
     * @return bool True if cancellation was requested
     */
    public function isCancellationRequested(string $jobId): bool
    {
        return Cache::get(self::STATUS_CACHE_PREFIX . $jobId . '_cancel', false);
    }

    /**
     * Get all active jobs for a project.
     *
     * @param int $projectId Project ID
     * @return array Active jobs
     */
    public function getActiveJobsForProject(int $projectId): array
    {
        // This is a simple implementation - in production you might want a more sophisticated approach
        // For now, we track active jobs in a project-specific cache key
        $activeJobIds = Cache::get("vw_active_jobs_project_{$projectId}", []);

        $activeJobs = [];
        foreach ($activeJobIds as $jobId) {
            $status = $this->getJobStatus($jobId);
            if ($status && in_array($status['status'], ['pending', 'processing'])) {
                $activeJobs[] = $status;
            }
        }

        return $activeJobs;
    }

    /**
     * Get summary of all jobs for a project.
     *
     * @param int $projectId Project ID
     * @param int $limit Max jobs to return
     * @return array Job summary
     */
    public function getJobSummaryForProject(int $projectId, int $limit = 10): array
    {
        $allJobIds = Cache::get("vw_all_jobs_project_{$projectId}", []);

        // Get most recent jobs
        $recentJobIds = array_slice(array_reverse($allJobIds), 0, $limit);

        $jobs = [];
        foreach ($recentJobIds as $jobId) {
            $status = $this->getJobStatus($jobId);
            if ($status) {
                $jobs[] = [
                    'job_id' => $jobId,
                    'type' => $status['type'] ?? 'unknown',
                    'status' => $status['status'],
                    'progress' => $status['progress'],
                    'message' => $status['message'] ?? '',
                    'created_at' => $status['created_at'] ?? null,
                    'completed_at' => $status['completed_at'] ?? null,
                    'duration_seconds' => $status['duration_seconds'] ?? null,
                ];
            }
        }

        return [
            'jobs' => $jobs,
            'total_count' => count($allJobIds),
            'active_count' => count(array_filter($jobs, fn($j) => in_array($j['status'], ['pending', 'processing']))),
        ];
    }

    /**
     * Clean up old job status entries.
     *
     * @param int $hoursOld Hours old to consider for cleanup
     * @return int Number of entries cleaned
     */
    public function cleanupOldJobs(int $hoursOld = 48): int
    {
        // In a real implementation, you'd iterate through tracked jobs
        // For now, cache entries will auto-expire based on TTL
        Log::info('QueuedJobsManager: Cleanup triggered', ['hoursOld' => $hoursOld]);
        return 0;
    }

    /**
     * Generate a unique job ID.
     *
     * @param string $prefix Job type prefix
     * @return string Unique job ID
     */
    protected function generateJobId(string $prefix): string
    {
        return $prefix . '_' . Str::random(16) . '_' . time();
    }

    /**
     * Initialize job status in cache.
     *
     * @param string $jobId Job ID
     * @param array $data Initial data
     */
    protected function initializeJobStatus(string $jobId, array $data): void
    {
        $status = array_merge($data, [
            'job_id' => $jobId,
            'status' => 'pending',
            'progress' => 0,
            'message' => 'Queued for processing',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        Cache::put(self::STATUS_CACHE_PREFIX . $jobId, $status, self::STATUS_CACHE_TTL);

        // Track job in project's job list
        if (isset($data['project_id'])) {
            $this->addJobToProjectTracking($data['project_id'], $jobId);
        }
    }

    /**
     * Add a job to the project's tracking list.
     *
     * @param int $projectId Project ID
     * @param string $jobId Job ID
     */
    protected function addJobToProjectTracking(int $projectId, string $jobId): void
    {
        // Track in all jobs list
        $allJobIds = Cache::get("vw_all_jobs_project_{$projectId}", []);
        $allJobIds[] = $jobId;
        Cache::put("vw_all_jobs_project_{$projectId}", $allJobIds, self::STATUS_CACHE_TTL);

        // Track in active jobs list
        $activeJobIds = Cache::get("vw_active_jobs_project_{$projectId}", []);
        $activeJobIds[] = $jobId;
        Cache::put("vw_active_jobs_project_{$projectId}", $activeJobIds, 3600);
    }

    /**
     * Remove a job from active tracking (called on completion/failure).
     *
     * @param int $projectId Project ID
     * @param string $jobId Job ID
     */
    public function removeFromActiveTracking(int $projectId, string $jobId): void
    {
        $activeJobIds = Cache::get("vw_active_jobs_project_{$projectId}", []);
        $activeJobIds = array_filter($activeJobIds, fn($id) => $id !== $jobId);
        Cache::put("vw_active_jobs_project_{$projectId}", array_values($activeJobIds), 3600);
    }
}
