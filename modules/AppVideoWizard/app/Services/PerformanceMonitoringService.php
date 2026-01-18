<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Performance Monitoring Service
 *
 * PHASE 5 OPTIMIZATION: Centralized performance metrics collection and analysis.
 *
 * Key responsibilities:
 * 1. Track operation durations across all Video Wizard components
 * 2. Monitor API call latencies and costs
 * 3. Track memory usage and database query counts
 * 4. Provide real-time metrics for dashboard display
 * 5. Store historical data for trend analysis
 */
class PerformanceMonitoringService
{
    /**
     * Active timers for tracking operation durations
     */
    protected array $activeTimers = [];

    /**
     * Collected metrics for the current request
     */
    protected array $requestMetrics = [];

    /**
     * Query count for the current request
     */
    protected int $queryCount = 0;

    /**
     * Query duration total for the current request (ms)
     */
    protected float $queryDuration = 0;

    /**
     * Whether query logging is enabled
     */
    protected bool $queryLoggingEnabled = false;

    /**
     * Start tracking a timed operation.
     *
     * @param string $operation Operation identifier (e.g., 'script_generation', 'image_generation')
     * @param array $context Additional context data
     * @return string Timer ID for stopping the operation
     */
    public function startOperation(string $operation, array $context = []): string
    {
        $timerId = $operation . '_' . Str::random(8);

        $this->activeTimers[$timerId] = [
            'operation' => $operation,
            'startTime' => microtime(true),
            'startMemory' => memory_get_usage(true),
            'context' => $context,
        ];

        return $timerId;
    }

    /**
     * Stop tracking a timed operation and record the metrics.
     *
     * @param string $timerId Timer ID from startOperation
     * @param array $additionalContext Additional context to merge
     * @return array|null The recorded metrics, or null if timer not found
     */
    public function stopOperation(string $timerId, array $additionalContext = []): ?array
    {
        if (!isset($this->activeTimers[$timerId])) {
            Log::warning('PerformanceMonitoringService: Timer not found', ['timerId' => $timerId]);
            return null;
        }

        $timer = $this->activeTimers[$timerId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'operation' => $timer['operation'],
            'duration_ms' => round(($endTime - $timer['startTime']) * 1000, 2),
            'memory_delta_bytes' => $endMemory - $timer['startMemory'],
            'memory_peak_bytes' => memory_get_peak_usage(true),
            'context' => array_merge($timer['context'], $additionalContext),
            'recorded_at' => now()->toIso8601String(),
        ];

        // Remove the timer
        unset($this->activeTimers[$timerId]);

        // Store in request metrics
        $this->requestMetrics[] = $metrics;

        // Log for debugging
        Log::debug('PerformanceMonitoringService: Operation completed', [
            'operation' => $metrics['operation'],
            'duration_ms' => $metrics['duration_ms'],
            'memory_delta_mb' => round($metrics['memory_delta_bytes'] / 1024 / 1024, 2),
        ]);

        return $metrics;
    }

    /**
     * Record a metric directly without timing.
     *
     * @param string $operation Operation name
     * @param float $value Metric value
     * @param string $unit Unit of measurement
     * @param array $context Additional context
     */
    public function recordMetric(string $operation, float $value, string $unit = 'ms', array $context = []): void
    {
        $this->requestMetrics[] = [
            'operation' => $operation,
            'value' => $value,
            'unit' => $unit,
            'context' => $context,
            'recorded_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Record an API call metric.
     *
     * @param string $provider API provider (openai, gemini, claude, etc.)
     * @param string $endpoint API endpoint or operation type
     * @param float $durationMs Call duration in milliseconds
     * @param array $context Additional context (tokens, cost estimate, etc.)
     */
    public function recordApiCall(string $provider, string $endpoint, float $durationMs, array $context = []): void
    {
        $metric = [
            'operation' => "api_call_{$provider}",
            'duration_ms' => $durationMs,
            'provider' => $provider,
            'endpoint' => $endpoint,
            'context' => $context,
            'recorded_at' => now()->toIso8601String(),
        ];

        $this->requestMetrics[] = $metric;

        // Update cache for real-time dashboard
        $this->incrementApiCallCounter($provider);
    }

    /**
     * Enable query logging to track database performance.
     */
    public function enableQueryLogging(): void
    {
        if ($this->queryLoggingEnabled) {
            return;
        }

        $this->queryLoggingEnabled = true;

        DB::listen(function ($query) {
            $this->queryCount++;
            $this->queryDuration += $query->time;

            // Log slow queries (> 100ms)
            if ($query->time > 100) {
                Log::warning('PerformanceMonitoringService: Slow query detected', [
                    'sql' => Str::limit($query->sql, 200),
                    'time_ms' => $query->time,
                    'bindings_count' => count($query->bindings),
                ]);
            }
        });
    }

    /**
     * Get query statistics for the current request.
     *
     * @return array Query stats
     */
    public function getQueryStats(): array
    {
        return [
            'count' => $this->queryCount,
            'total_duration_ms' => round($this->queryDuration, 2),
            'average_duration_ms' => $this->queryCount > 0
                ? round($this->queryDuration / $this->queryCount, 2)
                : 0,
        ];
    }

    /**
     * Get all metrics collected for the current request.
     *
     * @return array All request metrics
     */
    public function getRequestMetrics(): array
    {
        return [
            'operations' => $this->requestMetrics,
            'queries' => $this->getQueryStats(),
            'memory' => [
                'current_bytes' => memory_get_usage(true),
                'peak_bytes' => memory_get_peak_usage(true),
                'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ],
        ];
    }

    /**
     * Persist metrics to database for historical analysis.
     *
     * @param int|null $projectId Associated project ID
     * @param int|null $userId Associated user ID
     * @param string|null $stepName Current wizard step
     * @return bool Success status
     */
    public function persistMetrics(?int $projectId = null, ?int $userId = null, ?string $stepName = null): bool
    {
        try {
            $requestMetrics = $this->getRequestMetrics();

            // Only persist if we have meaningful data
            if (empty($requestMetrics['operations']) && $requestMetrics['queries']['count'] === 0) {
                return true;
            }

            // Calculate summary metrics
            $totalDuration = array_sum(array_column(
                array_filter($requestMetrics['operations'], fn($m) => isset($m['duration_ms'])),
                'duration_ms'
            ));

            $apiCalls = array_filter($requestMetrics['operations'], fn($m) =>
                str_starts_with($m['operation'] ?? '', 'api_call_')
            );

            DB::table('vw_performance_metrics')->insert([
                'project_id' => $projectId,
                'user_id' => $userId,
                'step_name' => $stepName,
                'operation_count' => count($requestMetrics['operations']),
                'total_duration_ms' => round($totalDuration, 2),
                'query_count' => $requestMetrics['queries']['count'],
                'query_duration_ms' => $requestMetrics['queries']['total_duration_ms'],
                'api_call_count' => count($apiCalls),
                'memory_peak_mb' => $requestMetrics['memory']['peak_mb'],
                'metrics_json' => json_encode($requestMetrics['operations']),
                'created_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('PerformanceMonitoringService: Failed to persist metrics', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Increment the API call counter in cache (for real-time dashboard).
     *
     * @param string $provider API provider
     */
    protected function incrementApiCallCounter(string $provider): void
    {
        $cacheKey = "vw_api_calls_{$provider}_" . now()->format('Y-m-d');
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, 86400); // 24 hours
    }

    /**
     * Get real-time performance summary for dashboard.
     *
     * @param int $hours Hours to look back
     * @return array Performance summary
     */
    public function getDashboardSummary(int $hours = 24): array
    {
        try {
            $since = now()->subHours($hours);

            // Get aggregated metrics from database
            $metrics = DB::table('vw_performance_metrics')
                ->where('created_at', '>=', $since)
                ->selectRaw('
                    COUNT(*) as request_count,
                    AVG(total_duration_ms) as avg_duration_ms,
                    MAX(total_duration_ms) as max_duration_ms,
                    SUM(query_count) as total_queries,
                    AVG(query_duration_ms) as avg_query_duration_ms,
                    SUM(api_call_count) as total_api_calls,
                    AVG(memory_peak_mb) as avg_memory_mb,
                    MAX(memory_peak_mb) as max_memory_mb
                ')
                ->first();

            // Get step-by-step breakdown
            $stepMetrics = DB::table('vw_performance_metrics')
                ->where('created_at', '>=', $since)
                ->whereNotNull('step_name')
                ->groupBy('step_name')
                ->selectRaw('
                    step_name,
                    COUNT(*) as count,
                    AVG(total_duration_ms) as avg_duration_ms,
                    AVG(query_count) as avg_queries
                ')
                ->orderBy('avg_duration_ms', 'desc')
                ->limit(10)
                ->get();

            // Get API call counts from cache
            $apiCallCounts = [];
            foreach (['openai', 'gemini', 'claude', 'fal', 'minimax'] as $provider) {
                $cacheKey = "vw_api_calls_{$provider}_" . now()->format('Y-m-d');
                $apiCallCounts[$provider] = Cache::get($cacheKey, 0);
            }

            return [
                'summary' => [
                    'request_count' => $metrics->request_count ?? 0,
                    'avg_duration_ms' => round($metrics->avg_duration_ms ?? 0, 2),
                    'max_duration_ms' => round($metrics->max_duration_ms ?? 0, 2),
                    'total_queries' => $metrics->total_queries ?? 0,
                    'avg_query_duration_ms' => round($metrics->avg_query_duration_ms ?? 0, 2),
                    'total_api_calls' => $metrics->total_api_calls ?? 0,
                    'avg_memory_mb' => round($metrics->avg_memory_mb ?? 0, 2),
                    'max_memory_mb' => round($metrics->max_memory_mb ?? 0, 2),
                ],
                'steps' => $stepMetrics,
                'api_calls_today' => $apiCallCounts,
                'period_hours' => $hours,
                'generated_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('PerformanceMonitoringService: Failed to get dashboard summary', [
                'error' => $e->getMessage(),
            ]);
            return [
                'error' => 'Failed to load metrics',
                'generated_at' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get slow operations for debugging.
     *
     * @param int $thresholdMs Minimum duration to consider slow
     * @param int $limit Max results
     * @return array Slow operations
     */
    public function getSlowOperations(int $thresholdMs = 1000, int $limit = 20): array
    {
        try {
            return DB::table('vw_performance_metrics')
                ->where('total_duration_ms', '>=', $thresholdMs)
                ->orderBy('total_duration_ms', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($row) {
                    $row->metrics_json = json_decode($row->metrics_json, true);
                    return $row;
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('PerformanceMonitoringService: Failed to get slow operations', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Clean up old metrics data.
     *
     * @param int $daysToKeep Days of data to retain
     * @return int Number of deleted records
     */
    public function cleanupOldMetrics(int $daysToKeep = 30): int
    {
        try {
            $cutoff = now()->subDays($daysToKeep);
            return DB::table('vw_performance_metrics')
                ->where('created_at', '<', $cutoff)
                ->delete();
        } catch (\Exception $e) {
            Log::error('PerformanceMonitoringService: Failed to cleanup old metrics', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Reset metrics for a new request.
     */
    public function reset(): void
    {
        $this->activeTimers = [];
        $this->requestMetrics = [];
        $this->queryCount = 0;
        $this->queryDuration = 0;
    }
}
