<?php

namespace Modules\AppVideoWizard\Livewire\Components;

use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\AppVideoWizard\Models\WizardScene;

/**
 * SceneCard - Lazy-loaded Scene Display Component
 *
 * This component implements Livewire 3's lazy loading pattern to defer scene
 * data loading until the card enters the viewport. This reduces initial page
 * payload from ~2MB to ~50KB for projects with many scenes (PERF-07).
 *
 * The component supports dual-mode data access:
 * - Normalized mode: Loads scene from database via WizardScene model
 * - JSON mode: Uses scene data passed directly from parent (legacy support)
 *
 * This is a READ-ONLY display component. All editing operations are dispatched
 * to the parent VideoWizard component via Livewire events.
 *
 * @package Modules\AppVideoWizard\Livewire\Components
 */
#[Lazy]
class SceneCard extends Component
{
    // =========================================================================
    // PROPS FROM PARENT
    // =========================================================================

    /**
     * Scene identifier - database ID (normalized) or array index (JSON)
     */
    #[Locked]
    public int|string $sceneId;

    /**
     * Project ID for context
     */
    #[Locked]
    public int $projectId;

    /**
     * Scene index for display (0-based order in the scene list)
     */
    #[Locked]
    public int $sceneIndex;

    /**
     * Whether this scene uses normalized data from database
     */
    #[Locked]
    public bool $isNormalized = false;

    /**
     * JSON scene data passed from parent (for non-normalized projects)
     * Contains both script and storyboard data for the scene
     */
    public ?array $jsonSceneData = null;

    /**
     * Storyboard data for the scene (image URL, status, etc.)
     */
    public ?array $storyboardData = null;

    /**
     * Multi-shot decomposition data if available
     */
    public ?array $multiShotData = null;

    // =========================================================================
    // LIFECYCLE
    // =========================================================================

    public function mount(
        int|string $sceneId,
        int $projectId,
        int $sceneIndex,
        bool $isNormalized = false,
        ?array $jsonSceneData = null,
        ?array $storyboardData = null,
        ?array $multiShotData = null
    ): void {
        $this->sceneId = $sceneId;
        $this->projectId = $projectId;
        $this->sceneIndex = $sceneIndex;
        $this->isNormalized = $isNormalized;
        $this->jsonSceneData = $jsonSceneData;
        $this->storyboardData = $storyboardData;
        $this->multiShotData = $multiShotData;
    }

    // =========================================================================
    // COMPUTED PROPERTIES
    // =========================================================================

    /**
     * Get scene data in a normalized array format.
     * Loads from database for normalized projects, or returns JSON data directly.
     */
    #[Computed]
    public function scene(): ?array
    {
        if ($this->isNormalized) {
            $scene = WizardScene::with(['shots', 'speechSegments'])
                ->find($this->sceneId);

            if (!$scene) {
                return null;
            }

            return $this->normalizedToArray($scene);
        }

        // Return JSON data directly (merged script + storyboard)
        return $this->jsonSceneData;
    }

    /**
     * Get the image URL for display
     */
    #[Computed]
    public function imageUrl(): ?string
    {
        if ($this->isNormalized) {
            return $this->scene['imageUrl'] ?? null;
        }

        return $this->storyboardData['imageUrl'] ?? null;
    }

    /**
     * Get the image status (pending, generating, ready, error)
     */
    #[Computed]
    public function imageStatus(): string
    {
        if ($this->isNormalized) {
            return $this->scene['imageStatus'] ?? 'pending';
        }

        return $this->storyboardData['status'] ?? 'pending';
    }

    /**
     * Get image source type (ai, stock, stock-video)
     */
    #[Computed]
    public function imageSource(): string
    {
        if ($this->isNormalized) {
            return $this->scene['source'] ?? 'ai';
        }

        return $this->storyboardData['source'] ?? 'ai';
    }

    /**
     * Check if this scene has multi-shot decomposition
     */
    #[Computed]
    public function hasMultiShot(): bool
    {
        if ($this->isNormalized) {
            return !empty($this->scene['shots']);
        }

        return !empty($this->multiShotData['shots']);
    }

    /**
     * Get multi-shot data
     */
    #[Computed]
    public function decomposed(): ?array
    {
        if ($this->isNormalized) {
            return !empty($this->scene['shots']) ? ['shots' => $this->scene['shots']] : null;
        }

        return $this->multiShotData;
    }

    // =========================================================================
    // DATA TRANSFORMATION
    // =========================================================================

    /**
     * Transform a WizardScene model to the legacy array format.
     * This ensures backward compatibility with existing blade templates.
     */
    protected function normalizedToArray(WizardScene $scene): array
    {
        return [
            'id' => $scene->id,
            'narration' => $scene->narration,
            'visualPrompt' => $scene->visual_prompt,
            'duration' => $scene->duration,
            'speechType' => $scene->speech_type,
            'transition' => $scene->transition,
            'imageUrl' => $scene->image_url,
            'imageStatus' => $scene->image_status,
            'prompt' => $scene->image_prompt,
            'source' => $scene->scene_metadata['source'] ?? 'ai',
            'videoUrl' => $scene->video_url,
            'videoStatus' => $scene->video_status,
            'voiceoverUrl' => $scene->voiceover_url,
            'speechSegments' => $scene->speechSegments->map(fn($s) => [
                'id' => $s->id,
                'type' => $s->type,
                'text' => $s->text,
                'speaker' => $s->speaker,
                'characterId' => $s->character_id,
                'voiceId' => $s->voice_id,
            ])->toArray(),
            'shots' => $scene->shots->map(fn($shot) => [
                'id' => $shot->id,
                'imagePrompt' => $shot->image_prompt,
                'videoPrompt' => $shot->video_prompt,
                'cameraMovement' => $shot->camera_movement,
                'duration' => $shot->duration,
                'imageUrl' => $shot->image_url,
                'videoUrl' => $shot->video_url,
                'imageStatus' => $shot->image_status,
                'videoStatus' => $shot->video_status,
            ])->toArray(),
        ];
    }

    // =========================================================================
    // PLACEHOLDER (for lazy loading)
    // =========================================================================

    /**
     * Render placeholder while component is loading.
     * This is displayed until the component enters the viewport.
     */
    public function placeholder(): \Illuminate\Contracts\View\View
    {
        return view('appvideowizard::livewire.components.scene-card-placeholder', [
            'sceneIndex' => $this->sceneIndex,
        ]);
    }

    // =========================================================================
    // RENDER
    // =========================================================================

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('appvideowizard::livewire.components.scene-card');
    }
}
