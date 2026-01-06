<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardAsset;

class ImageGenerationService
{
    /**
     * Image model configurations matching reference implementation.
     */
    protected array $imageModels = [
        'hidream' => [
            'name' => 'HiDream',
            'description' => 'Artistic & cinematic style',
            'tokenCost' => 2,
            'provider' => 'runpod',
            'async' => true,
        ],
        'nanobanana-pro' => [
            'name' => 'NanoBanana Pro',
            'description' => 'High quality, fast generation',
            'tokenCost' => 3,
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash-exp-image-generation',
        ],
        'nanobanana' => [
            'name' => 'NanoBanana',
            'description' => 'Quick drafts, lower cost',
            'tokenCost' => 1,
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash-exp',
        ],
    ];

    /**
     * Resolution configurations for different aspect ratios.
     * Using supported values: 1024x1024, 1024x1536, 1536x1024, and auto.
     */
    protected array $resolutions = [
        '16:9' => [
            'width' => 1536,
            'height' => 1024,
            'size' => '1536x1024',
            'runpod' => ['width' => 1280, 'height' => 720],
        ],
        '9:16' => [
            'width' => 1024,
            'height' => 1536,
            'size' => '1024x1536',
            'runpod' => ['width' => 720, 'height' => 1280],
        ],
        '1:1' => [
            'width' => 1024,
            'height' => 1024,
            'size' => '1024x1024',
            'runpod' => ['width' => 1024, 'height' => 1024],
        ],
        '4:5' => [
            'width' => 1024,
            'height' => 1280,
            'size' => '1024x1536', // Closest supported size
            'runpod' => ['width' => 864, 'height' => 1080],
        ],
    ];

    /**
     * Visual style enhancement configurations.
     */
    protected array $visualStyles = [
        'mood' => [
            'epic' => 'sweeping epic atmosphere, grand scale, heroic feel',
            'intimate' => 'intimate close atmosphere, personal and emotional',
            'mysterious' => 'mysterious atmosphere, enigmatic shadows, intriguing',
            'energetic' => 'dynamic high-energy atmosphere, vibrant movement',
            'contemplative' => 'contemplative mood, thoughtful and serene',
            'tense' => 'tense atmosphere, suspenseful, edge-of-seat feeling',
            'hopeful' => 'hopeful uplifting atmosphere, warm and optimistic',
            'professional' => 'clean professional atmosphere, corporate polished look',
        ],
        'lighting' => [
            'natural' => 'natural daylight, realistic sun illumination',
            'golden-hour' => 'golden hour sunlight, warm amber tones, long shadows',
            'blue-hour' => 'blue hour twilight, cool cyan tones, soft ambient light',
            'high-key' => 'high-key bright lighting, minimal shadows, clean look',
            'low-key' => 'low-key dramatic lighting, deep shadows, noir style',
            'neon' => 'neon cyberpunk lighting, pink and cyan glows, urban night',
        ],
        'colorPalette' => [
            'teal-orange' => 'cinematic teal and orange color grading',
            'warm-tones' => 'warm color palette, reds oranges and yellows',
            'cool-tones' => 'cool color palette, blues and greens',
            'desaturated' => 'desaturated muted colors, subtle tones',
            'vibrant' => 'vibrant saturated colors, bold and eye-catching',
            'pastel' => 'soft pastel colors, gentle and dreamy',
        ],
        'composition' => [
            'wide' => 'wide establishing shot, full environment visible, subject 30-40% of frame',
            'medium' => 'medium shot, waist-up framing, balanced composition',
            'close-up' => 'close-up shot, face and shoulders, intimate framing',
            'extreme-close-up' => 'extreme close-up, facial details, dramatic tight crop',
            'low-angle' => 'low angle shot, looking up, powerful imposing feel',
            'birds-eye' => 'birds eye view, overhead perspective, environmental context',
        ],
    ];

    /**
     * Prompt builder for professional image generation.
     */
    protected array $promptBuilder = [
        'cameraSpecs' => [
            'portrait' => 'Shot with Canon EOS R5, 85mm f/1.4 lens, shallow depth of field',
            'wide' => 'Shot with Sony A7IV, 24mm wide-angle lens, deep focus',
            'closeup' => 'Shot with Canon 100mm macro lens, f/2.8, precise focus on subject',
            'medium' => 'Shot with 50mm prime lens, f/2.0, natural perspective',
            'cinematic' => 'Shot with ARRI Alexa, anamorphic lens, 2.39:1 cinematic aspect',
        ],
        'compositionBuzzwords' => [
            'Pulitzer-prize-winning photograph',
            'Vanity Fair cover portrait',
            'National Geographic documentary still',
            'professional cinematography',
            'rule of thirds composition',
            'masterful negative space',
        ],
        'realismConstraints' => [
            'natural skin texture with visible pores',
            'film grain texture',
            'real-world physics',
            'authentic fabric texture',
            'natural hair strands',
        ],
        'negativePrompt' => 'blurry, low quality, ugly, distorted, watermark, nsfw, text, words, logo, cartoon, anime, 3D render, CGI, artificial, plastic skin, airbrushed',
    ];

    /**
     * Generate an image for a scene.
     */
    public function generateSceneImage(WizardProject $project, array $scene, array $options = []): array
    {
        $visualDescription = $scene['visualDescription'] ?? $scene['narration'] ?? '';
        $styleBible = $project->storyboard['styleBible'] ?? null;
        $visualStyle = $project->storyboard['visualStyle'] ?? [];
        $imageModel = $project->storyboard['imageModel'] ?? 'nanobanana-pro';
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        // Build the enhanced image prompt
        $prompt = $this->buildEnhancedPrompt($visualDescription, $styleBible, $visualStyle, $project->aspect_ratio);

        // Get resolution based on aspect ratio and model
        $resolution = $this->getResolution($project->aspect_ratio, $imageModel);

        // Get model configuration
        $modelConfig = $this->imageModels[$imageModel] ?? $this->imageModels['nanobanana-pro'];

        // Generate image based on provider
        if ($modelConfig['provider'] === 'runpod') {
            return $this->generateWithRunPod($project, $scene, $prompt, $resolution, $options);
        } else {
            return $this->generateWithGemini($project, $scene, $prompt, $resolution, $modelConfig, $options);
        }
    }

    /**
     * Generate image using Gemini API (NanoBanana models).
     */
    protected function generateWithGemini(
        WizardProject $project,
        array $scene,
        string $prompt,
        array $resolution,
        array $modelConfig,
        array $options
    ): array {
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        // Use ArTime's AI service which handles Gemini
        $result = AI::process($prompt, 'image', [
            'size' => $resolution['size'],
            'model' => $modelConfig['model'] ?? null,
        ], $teamId);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        // Extract image URL from result
        $imageData = $result['data'][0] ?? null;
        if (!$imageData) {
            throw new \Exception('No image generated');
        }

        $imageUrl = is_array($imageData) ? ($imageData['url'] ?? null) : $imageData;

        // Download and store the image
        $storedPath = $this->storeImage($imageUrl, $project, $scene['id']);

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_IMAGE,
            'name' => $scene['title'] ?? $scene['id'],
            'path' => $storedPath,
            'url' => Storage::disk('public')->url($storedPath),
            'mime_type' => 'image/png',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'],
            'metadata' => [
                'prompt' => $prompt,
                'width' => $resolution['width'],
                'height' => $resolution['height'],
                'aspectRatio' => $project->aspect_ratio,
                'model' => $modelConfig['name'],
                'provider' => 'gemini',
                'source' => 'ai',
            ],
        ]);

        return [
            'success' => true,
            'imageUrl' => $asset->url,
            'assetId' => $asset->id,
            'prompt' => $prompt,
            'status' => 'ready',
            'source' => 'ai',
        ];
    }

    /**
     * Generate image using RunPod API (HiDream model).
     */
    protected function generateWithRunPod(
        WizardProject $project,
        array $scene,
        string $prompt,
        array $resolution,
        array $options
    ): array {
        $runpodKey = setting('runpod_api_key') ?? config('services.runpod.key');

        if (!$runpodKey) {
            // Fallback to Gemini if RunPod not configured
            Log::warning('RunPod API key not configured, falling back to Gemini');
            return $this->generateWithGemini(
                $project,
                $scene,
                $prompt,
                $resolution,
                $this->imageModels['nanobanana-pro'],
                $options
            );
        }

        $dimensions = $resolution['runpod'] ?? ['width' => 1280, 'height' => 720];
        $seed = random_int(0, 999999999);

        // Generate signed URL for upload (using local storage for now)
        $filename = Str::slug($scene['id']) . '-' . time() . '-' . $seed . '.png';
        $storedPath = "wizard-projects/{$project->id}/images/{$filename}";

        // Build RunPod input
        $runpodInput = [
            'positive_prompt' => $prompt,
            'negative_prompt' => $this->promptBuilder['negativePrompt'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'batch_size' => 1,
            'shift' => 3.0,
            'seed' => $seed,
            'steps' => 30,
            'cfg' => 5,
            'sampler_name' => 'euler',
            'scheduler' => 'simple',
            'denoise' => 1,
        ];

        // Call RunPod API
        $runpodEndpoint = 'https://api.runpod.ai/v2/rgq0go2nkcfx4h/run';

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$runpodKey}",
                ])
                ->post($runpodEndpoint, ['input' => $runpodInput]);

            if (!$response->successful()) {
                throw new \Exception('RunPod API error: ' . $response->body());
            }

            $data = $response->json();
            $jobId = $data['id'] ?? null;
            $status = $data['status'] ?? 'UNKNOWN';

            if (!$jobId) {
                throw new \Exception('No job ID returned from RunPod');
            }

            // Store job info for polling
            $asset = WizardAsset::create([
                'project_id' => $project->id,
                'user_id' => $project->user_id,
                'type' => WizardAsset::TYPE_IMAGE,
                'name' => $scene['title'] ?? $scene['id'],
                'path' => $storedPath,
                'url' => null, // Will be updated when job completes
                'mime_type' => 'image/png',
                'scene_index' => $options['sceneIndex'] ?? null,
                'scene_id' => $scene['id'],
                'metadata' => [
                    'prompt' => $prompt,
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'aspectRatio' => $project->aspect_ratio,
                    'model' => 'HiDream',
                    'provider' => 'runpod',
                    'jobId' => $jobId,
                    'status' => $status,
                    'source' => 'ai',
                ],
            ]);

            return [
                'success' => true,
                'jobId' => $jobId,
                'status' => 'generating',
                'assetId' => $asset->id,
                'prompt' => $prompt,
                'checkEndpoint' => "https://api.runpod.ai/v2/rgq0go2nkcfx4h/status/{$jobId}",
                'source' => 'ai',
            ];

        } catch (\Exception $e) {
            Log::error('RunPod generation failed: ' . $e->getMessage());
            // Fallback to Gemini
            return $this->generateWithGemini(
                $project,
                $scene,
                $prompt,
                $resolution,
                $this->imageModels['nanobanana-pro'],
                $options
            );
        }
    }

    /**
     * Check RunPod job status.
     */
    public function checkRunPodJobStatus(string $jobId): array
    {
        $runpodKey = setting('runpod_api_key') ?? config('services.runpod.key');

        if (!$runpodKey) {
            throw new \Exception('RunPod API key not configured');
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$runpodKey}",
                ])
                ->get("https://api.runpod.ai/v2/rgq0go2nkcfx4h/status/{$jobId}");

            if (!$response->successful()) {
                throw new \Exception('Failed to check job status');
            }

            $data = $response->json();

            return [
                'success' => true,
                'jobId' => $jobId,
                'status' => $data['status'] ?? 'UNKNOWN',
                'output' => $data['output'] ?? null,
                'error' => $data['error'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build enhanced prompt with visual style and professional techniques.
     */
    public function buildEnhancedPrompt(
        string $visualDescription,
        ?array $styleBible,
        array $visualStyle,
        string $aspectRatio
    ): string {
        $parts = [];

        // 1. Professional composition buzzword
        $buzzword = $this->promptBuilder['compositionBuzzwords'][array_rand($this->promptBuilder['compositionBuzzwords'])];
        $parts[] = $buzzword;

        // 2. Visual style enhancements
        if (!empty($visualStyle['mood']) && isset($this->visualStyles['mood'][$visualStyle['mood']])) {
            $parts[] = $this->visualStyles['mood'][$visualStyle['mood']];
        }

        if (!empty($visualStyle['composition']) && isset($this->visualStyles['composition'][$visualStyle['composition']])) {
            $parts[] = $this->visualStyles['composition'][$visualStyle['composition']];
        }

        // 3. Style Bible elements
        if ($styleBible && !empty($styleBible['enabled'])) {
            if (!empty($styleBible['style'])) {
                $parts[] = $styleBible['style'];
            }
            if (!empty($styleBible['colorGrade'])) {
                $parts[] = $styleBible['colorGrade'];
            }
            if (!empty($styleBible['atmosphere'])) {
                $parts[] = $styleBible['atmosphere'];
            }
        }

        // 4. Main visual description
        $parts[] = $visualDescription;

        // 5. Lighting style
        if (!empty($visualStyle['lighting']) && isset($this->visualStyles['lighting'][$visualStyle['lighting']])) {
            $parts[] = $this->visualStyles['lighting'][$visualStyle['lighting']];
        }

        // 6. Color palette
        if (!empty($visualStyle['colorPalette']) && isset($this->visualStyles['colorPalette'][$visualStyle['colorPalette']])) {
            $parts[] = $this->visualStyles['colorPalette'][$visualStyle['colorPalette']];
        }

        // 7. Camera specifications based on composition
        $compositionType = $visualStyle['composition'] ?? 'medium';
        $cameraType = $this->mapCompositionToCamera($compositionType);
        if (isset($this->promptBuilder['cameraSpecs'][$cameraType])) {
            $parts[] = $this->promptBuilder['cameraSpecs'][$cameraType];
        }

        // 8. Technical quality
        $parts[] = '4K ultra high definition, sharp focus, professional quality';

        // 9. Realism constraints
        $parts[] = implode(', ', array_slice($this->promptBuilder['realismConstraints'], 0, 3));

        // Build final prompt
        $prompt = implode('. ', array_filter($parts));

        return $prompt;
    }

    /**
     * Map composition type to camera specification type.
     */
    protected function mapCompositionToCamera(string $composition): string
    {
        return match ($composition) {
            'wide', 'birds-eye' => 'wide',
            'close-up', 'extreme-close-up' => 'closeup',
            'medium', 'low-angle' => 'medium',
            default => 'cinematic',
        };
    }

    /**
     * Get resolution configuration for aspect ratio.
     */
    public function getResolution(string $aspectRatio, string $model = 'nanobanana-pro'): array
    {
        return $this->resolutions[$aspectRatio] ?? $this->resolutions['16:9'];
    }

    /**
     * Store image from URL to local storage.
     */
    protected function storeImage(string $imageUrl, WizardProject $project, string $sceneId): string
    {
        $contents = file_get_contents($imageUrl);

        $filename = Str::slug($sceneId) . '-' . time() . '.png';
        $path = "wizard-projects/{$project->id}/images/{$filename}";

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Regenerate an image with modifications.
     */
    public function regenerateImage(WizardProject $project, array $scene, string $modification = ''): array
    {
        $originalPrompt = $scene['prompt'] ?? $scene['visualDescription'] ?? '';

        if (!empty($modification)) {
            $modifiedPrompt = "{$originalPrompt}. {$modification}";
        } else {
            $modifiedPrompt = $originalPrompt;
        }

        return $this->generateSceneImage($project, array_merge($scene, [
            'visualDescription' => $modifiedPrompt,
        ]));
    }

    /**
     * Generate images for all scenes in batch.
     */
    public function generateAllSceneImages(WizardProject $project, callable $progressCallback = null): array
    {
        $scenes = $project->getScenes();
        $results = [];
        $delayMs = 500; // Rate limiting delay between requests

        foreach ($scenes as $index => $scene) {
            try {
                // Rate limiting delay
                if ($index > 0) {
                    usleep($delayMs * 1000);
                }

                $result = $this->generateSceneImage($project, $scene, ['sceneIndex' => $index]);
                $results[$scene['id']] = $result;

                if ($progressCallback) {
                    $progressCallback($index + 1, count($scenes), $scene['id'], $result);
                }

            } catch (\Exception $e) {
                Log::error("Failed to generate image for scene {$index}: " . $e->getMessage());
                $results[$scene['id']] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                if ($progressCallback) {
                    $progressCallback($index + 1, count($scenes), $scene['id'], [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * Get available image models.
     */
    public function getImageModels(): array
    {
        return $this->imageModels;
    }

    /**
     * Get visual style options.
     */
    public function getVisualStyles(): array
    {
        return $this->visualStyles;
    }
}
