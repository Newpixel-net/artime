<?php

namespace Modules\AppVideoWizard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardScene;
use Modules\AppVideoWizard\Models\WizardShot;
use Modules\AppVideoWizard\Models\WizardSpeechSegment;

/**
 * Artisan command to migrate JSON scene data to normalized tables.
 *
 * This command reads scene data from the JSON columns (script, storyboard, animation)
 * and creates corresponding records in wizard_scenes, wizard_shots, and
 * wizard_speech_segments tables.
 *
 * Usage:
 *   php artisan wizard:normalize-data                    # Migrate all projects
 *   php artisan wizard:normalize-data --project=123     # Migrate specific project
 *   php artisan wizard:normalize-data --dry-run         # Preview without changes
 *   php artisan wizard:normalize-data --force           # Re-migrate already normalized projects
 */
class NormalizeProjectData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wizard:normalize-data
                            {--project= : Specific project ID to migrate}
                            {--dry-run : Preview changes without saving}
                            {--force : Re-migrate projects that already have normalized data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate JSON scene data to normalized tables (wizard_scenes, wizard_shots, wizard_speech_segments)';

    /**
     * Statistics for reporting.
     */
    protected array $stats = [
        'projects_processed' => 0,
        'projects_skipped' => 0,
        'scenes_created' => 0,
        'shots_created' => 0,
        'speech_segments_created' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->option('project');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        // Build query for projects
        $query = WizardProject::query();
        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->warn($projectId ? "Project #{$projectId} not found." : 'No projects found.');
            return Command::FAILURE;
        }

        $this->info("Found {$projects->count()} project(s) to process...");
        $this->newLine();

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        foreach ($projects as $project) {
            $this->processProject($project, $dryRun, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($dryRun);

        return Command::SUCCESS;
    }

    /**
     * Process a single project for normalization.
     */
    protected function processProject(WizardProject $project, bool $dryRun, bool $force): void
    {
        // Check if already normalized
        if ($project->usesNormalizedData() && !$force) {
            $this->stats['projects_skipped']++;
            return;
        }

        // If force mode, delete existing normalized data first
        if ($force && $project->usesNormalizedData() && !$dryRun) {
            $project->scenes()->delete();
        }

        $script = $project->script ?? [];
        $storyboard = $project->storyboard ?? [];
        $animation = $project->animation ?? [];

        $scriptScenes = $script['scenes'] ?? [];

        if (empty($scriptScenes)) {
            $this->stats['projects_skipped']++;
            return;
        }

        if ($dryRun) {
            $this->simulateNormalization($project, $scriptScenes, $storyboard, $animation);
            return;
        }

        // Wrap in transaction for atomic migration
        DB::transaction(function () use ($project, $scriptScenes, $storyboard, $animation) {
            $this->normalizeProject($project, $scriptScenes, $storyboard, $animation);
        });

        $this->stats['projects_processed']++;
    }

    /**
     * Simulate normalization for dry-run mode.
     */
    protected function simulateNormalization(
        WizardProject $project,
        array $scriptScenes,
        array $storyboard,
        array $animation
    ): void {
        $storyboardScenes = $storyboard['scenes'] ?? [];
        $animationScenes = $animation['scenes'] ?? [];

        foreach ($scriptScenes as $index => $sceneData) {
            $this->stats['scenes_created']++;

            // Count shots
            $sbScene = $storyboardScenes[$index] ?? [];
            $shots = $sbScene['shots'] ?? [];
            $this->stats['shots_created'] += count($shots);

            // Count speech segments
            $speechSegments = $sceneData['speechSegments'] ?? [];
            $this->stats['speech_segments_created'] += count($speechSegments);
        }

        $this->stats['projects_processed']++;
    }

    /**
     * Normalize a project's JSON data to database tables.
     */
    protected function normalizeProject(
        WizardProject $project,
        array $scriptScenes,
        array $storyboard,
        array $animation
    ): void {
        $storyboardScenes = $storyboard['scenes'] ?? [];
        $animationScenes = $animation['scenes'] ?? [];

        foreach ($scriptScenes as $index => $sceneData) {
            $sbScene = $storyboardScenes[$index] ?? [];
            $animScene = $animationScenes[$index] ?? [];

            // Create WizardScene
            $scene = $this->createScene($project, $index, $sceneData, $sbScene, $animScene);
            $this->stats['scenes_created']++;

            // Create WizardShots (from storyboard decomposition)
            $shots = $sbScene['shots'] ?? [];
            foreach ($shots as $shotIndex => $shotData) {
                $this->createShot($scene, $shotIndex, $shotData);
                $this->stats['shots_created']++;
            }

            // Create WizardSpeechSegments
            $speechSegments = $sceneData['speechSegments'] ?? [];
            foreach ($speechSegments as $segIndex => $segData) {
                $this->createSpeechSegment($scene, $segIndex, $segData);
                $this->stats['speech_segments_created']++;
            }
        }
    }

    /**
     * Create a WizardScene record from merged JSON data.
     */
    protected function createScene(
        WizardProject $project,
        int $order,
        array $scriptScene,
        array $storyboardScene,
        array $animationScene
    ): WizardScene {
        // Build metadata for less-frequent fields
        $sceneMetadata = [];
        if (isset($scriptScene['voiceover'])) {
            $sceneMetadata['voiceover'] = $scriptScene['voiceover'];
        }
        if (isset($scriptScene['id'])) {
            $sceneMetadata['original_id'] = $scriptScene['id'];
        }

        return WizardScene::create([
            'project_id' => $project->id,
            'order' => $order,
            // Script fields
            'narration' => $scriptScene['narration'] ?? null,
            'visual_prompt' => $scriptScene['visualPrompt'] ?? null,
            'duration' => $scriptScene['duration'] ?? 8,
            'speech_type' => $scriptScene['speechType'] ?? 'voiceover',
            'transition' => $scriptScene['transition'] ?? 'cut',
            // Storyboard fields
            'image_url' => $storyboardScene['imageUrl'] ?? null,
            'image_status' => $storyboardScene['status'] ?? $storyboardScene['imageStatus'] ?? 'pending',
            'image_prompt' => $storyboardScene['prompt'] ?? $storyboardScene['imagePrompt'] ?? null,
            'image_job_id' => $storyboardScene['jobId'] ?? null,
            // Animation fields
            'video_url' => $animationScene['videoUrl'] ?? null,
            'video_status' => $animationScene['videoStatus'] ?? 'pending',
            'voiceover_url' => $animationScene['voiceoverUrl'] ?? null,
            // Metadata
            'scene_metadata' => !empty($sceneMetadata) ? $sceneMetadata : null,
        ]);
    }

    /**
     * Create a WizardShot record from shot JSON data.
     */
    protected function createShot(WizardScene $scene, int $order, array $shotData): WizardShot
    {
        // Build metadata for less-frequent fields
        $shotMetadata = [];
        if (isset($shotData['speakingCharacters'])) {
            $shotMetadata['speaking_characters'] = $shotData['speakingCharacters'];
        }
        if (isset($shotData['id'])) {
            $shotMetadata['original_id'] = $shotData['id'];
        }
        if (isset($shotData['fromSceneImage'])) {
            $shotMetadata['from_scene_image'] = $shotData['fromSceneImage'];
        }
        if (isset($shotData['fromFrameCapture'])) {
            $shotMetadata['from_frame_capture'] = $shotData['fromFrameCapture'];
        }

        return WizardShot::create([
            'scene_id' => $scene->id,
            'order' => $order,
            // Prompts
            'image_prompt' => $shotData['imagePrompt'] ?? null,
            'video_prompt' => $shotData['videoPrompt'] ?? null,
            // Technical specs
            'camera_movement' => $shotData['cameraMovement'] ?? null,
            'duration' => $shotData['duration'] ?? 5,
            'duration_class' => $shotData['durationClass'] ?? 'short',
            // Generated assets
            'image_url' => $shotData['imageUrl'] ?? null,
            'image_status' => $shotData['imageStatus'] ?? 'pending',
            'video_url' => $shotData['videoUrl'] ?? null,
            'video_status' => $shotData['videoStatus'] ?? 'pending',
            // Dialogue
            'dialogue' => $shotData['dialogue'] ?? null,
            // Metadata
            'shot_metadata' => !empty($shotMetadata) ? $shotMetadata : null,
        ]);
    }

    /**
     * Create a WizardSpeechSegment record from speech segment JSON data.
     */
    protected function createSpeechSegment(WizardScene $scene, int $order, array $segData): WizardSpeechSegment
    {
        return WizardSpeechSegment::create([
            'scene_id' => $scene->id,
            'order' => $order,
            // Segment data
            'type' => $segData['type'] ?? 'narrator',
            'text' => $segData['text'] ?? '',
            'speaker' => $segData['speaker'] ?? null,
            'character_id' => $segData['characterId'] ?? null,
            'voice_id' => $segData['voiceId'] ?? null,
            // Timing
            'start_time' => $segData['startTime'] ?? null,
            'duration' => $segData['duration'] ?? null,
            // Audio
            'audio_url' => $segData['audioUrl'] ?? null,
            // Attributes
            'emotion' => $segData['emotion'] ?? null,
            'needs_lip_sync' => $segData['needsLipSync'] ?? false,
        ]);
    }

    /**
     * Display summary statistics.
     */
    protected function displaySummary(bool $dryRun): void
    {
        $prefix = $dryRun ? 'Would create' : 'Created';

        $this->info('=== Summary ===');
        $this->line("Projects processed: {$this->stats['projects_processed']}");
        $this->line("Projects skipped (already normalized): {$this->stats['projects_skipped']}");
        $this->line("{$prefix} scenes: {$this->stats['scenes_created']}");
        $this->line("{$prefix} shots: {$this->stats['shots_created']}");
        $this->line("{$prefix} speech segments: {$this->stats['speech_segments_created']}");

        if ($dryRun && $this->stats['projects_processed'] > 0) {
            $this->newLine();
            $this->info('Run without --dry-run to apply these changes.');
        }
    }
}
