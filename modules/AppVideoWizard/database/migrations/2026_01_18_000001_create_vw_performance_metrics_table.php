<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: Performance Metrics Table
 *
 * Stores performance metrics for Video Wizard operations
 * including timing data, query counts, API calls, and memory usage.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vw_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('step_name', 50)->nullable()->index();

            // Operation metrics
            $table->unsignedInteger('operation_count')->default(0);
            $table->decimal('total_duration_ms', 12, 2)->default(0);

            // Database metrics
            $table->unsignedInteger('query_count')->default(0);
            $table->decimal('query_duration_ms', 10, 2)->default(0);

            // API metrics
            $table->unsignedInteger('api_call_count')->default(0);

            // Memory metrics
            $table->decimal('memory_peak_mb', 8, 2)->default(0);

            // Detailed metrics JSON
            $table->json('metrics_json')->nullable();

            $table->timestamp('created_at')->useCurrent()->index();

            // Foreign keys (optional - may not exist in all installations)
            // $table->foreign('project_id')->references('id')->on('wizard_projects')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Create index for performance analysis queries
        Schema::table('vw_performance_metrics', function (Blueprint $table) {
            $table->index(['created_at', 'step_name'], 'idx_vw_perf_time_step');
            $table->index(['project_id', 'created_at'], 'idx_vw_perf_project_time');
            $table->index(['total_duration_ms'], 'idx_vw_perf_slow_ops');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_performance_metrics');
    }
};
