<?php

namespace Modules\AppVideoWizard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\AppVideoWizard\Services\PerformanceMonitoringService;

/**
 * Track Performance Metrics Middleware
 *
 * PHASE 5 OPTIMIZATION: Automatically tracks performance metrics
 * for all Video Wizard requests.
 *
 * Features:
 * - Tracks request duration
 * - Monitors database queries
 * - Collects memory usage statistics
 * - Persists metrics for historical analysis
 */
class TrackPerformanceMetrics
{
    protected PerformanceMonitoringService $performanceService;

    public function __construct(PerformanceMonitoringService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Reset metrics for new request
        $this->performanceService->reset();

        // Enable query logging
        $this->performanceService->enableQueryLogging();

        // Start tracking the request
        $timerId = $this->performanceService->startOperation('http_request', [
            'uri' => $request->getRequestUri(),
            'method' => $request->method(),
            'is_livewire' => $request->hasHeader('X-Livewire'),
        ]);

        $response = $next($request);

        // Stop tracking and collect metrics
        $this->performanceService->stopOperation($timerId, [
            'status_code' => $response->getStatusCode(),
        ]);

        // Extract project/user info from request if available
        $projectId = $this->extractProjectId($request);
        $userId = $request->user()?->id;
        $stepName = $this->extractStepName($request);

        // Persist metrics for significant requests (not static assets)
        if ($this->shouldPersistMetrics($request)) {
            $this->performanceService->persistMetrics($projectId, $userId, $stepName);
        }

        return $response;
    }

    /**
     * Extract project ID from the request.
     */
    protected function extractProjectId(Request $request): ?int
    {
        // Try route parameter
        if ($request->route('project')) {
            return (int) $request->route('project');
        }

        // Try request data (for Livewire)
        if ($request->has('projectId')) {
            return (int) $request->input('projectId');
        }

        // Try to extract from Livewire snapshot
        $updates = $request->input('components.0.snapshot');
        if ($updates) {
            $snapshot = json_decode($updates, true);
            if (isset($snapshot['data']['projectId'])) {
                return (int) $snapshot['data']['projectId'];
            }
        }

        return null;
    }

    /**
     * Extract the current wizard step name from the request.
     */
    protected function extractStepName(Request $request): ?string
    {
        // Try Livewire calls for step changes
        $calls = $request->input('components.0.calls', []);
        foreach ($calls as $call) {
            if (isset($call['method'])) {
                // Common step change methods
                if (str_starts_with($call['method'], 'goto') || str_starts_with($call['method'], 'navigate')) {
                    return $call['method'];
                }
                // Generation methods
                if (str_contains($call['method'], 'generate') || str_contains($call['method'], 'Generate')) {
                    return $call['method'];
                }
                // Save methods
                if (str_contains($call['method'], 'save') || str_contains($call['method'], 'Save')) {
                    return $call['method'];
                }
            }
        }

        // Try to extract from snapshot
        $updates = $request->input('components.0.snapshot');
        if ($updates) {
            $snapshot = json_decode($updates, true);
            if (isset($snapshot['data']['currentStep'])) {
                return 'step_' . $snapshot['data']['currentStep'];
            }
        }

        return null;
    }

    /**
     * Determine if metrics should be persisted for this request.
     */
    protected function shouldPersistMetrics(Request $request): bool
    {
        // Don't persist for static assets
        $uri = $request->getRequestUri();
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)/', $uri)) {
            return false;
        }

        // Only persist for Video Wizard routes
        if (!str_contains($uri, 'video-wizard') && !str_contains($uri, 'livewire')) {
            return false;
        }

        // For Livewire requests, check if it's a Video Wizard component
        if ($request->hasHeader('X-Livewire')) {
            $component = $request->input('components.0.snapshot');
            if ($component) {
                $snapshot = json_decode($component, true);
                $class = $snapshot['memo']['name'] ?? '';
                if (!str_contains($class, 'video-wizard') && !str_contains($class, 'VideoWizard')) {
                    return false;
                }
            }
        }

        return true;
    }
}
