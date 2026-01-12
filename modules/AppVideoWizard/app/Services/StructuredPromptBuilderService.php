<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * StructuredPromptBuilderService
 *
 * Builds structured JSON-based prompts for photorealistic image generation.
 * This service creates prompts optimized for NanoBanana Pro and other
 * high-quality image generation models.
 *
 * The structured approach provides:
 * - Granular control over subject, attire, pose, environment
 * - Explicit negative prompts to avoid AI artifacts
 * - Authenticity markers for realistic output
 * - Technical specifications for quality control
 *
 * ╔══════════════════════════════════════════════════════════════════════════════╗
 * ║  WARNING: DO NOT MODIFY THE PHOTOREALISTIC PROMPT STRUCTURE WITHOUT TESTING  ║
 * ╠══════════════════════════════════════════════════════════════════════════════╣
 * ║  The JSON-structured prompts in toPromptString(), buildJsonPrompt(),         ║
 * ║  buildCameraJson(), and buildStyleJson() are CAREFULLY TUNED to produce      ║
 * ║  TRUE PHOTOREALISTIC results. These were developed through extensive         ║
 * ║  testing and regression fixes.                                               ║
 * ║                                                                              ║
 * ║  CRITICAL ELEMENTS THAT MUST NOT BE CHANGED:                                 ║
 * ║  1. "hyper-realistic" directive (NOT "photorealistic" - it's weaker)         ║
 * ║  2. Explicit aperture values (f/1.8, f/2.0, f/2.8)                           ║
 * ║  3. Explicit DOF descriptions                                                ║
 * ║  4. Skin texture requirements ("visible pores", "micro-imperfections")       ║
 * ║  5. Anti-retouching directive ("NO smoothing, NO airbrushing")               ║
 * ║  6. JSON structure format (Gemini responds better to JSON than prose)        ║
 * ║                                                                              ║
 * ║  Changing these WILL cause images to look cartoonish/stylized instead of     ║
 * ║  photorealistic, even when "Cinematic Realistic" mode is selected.           ║
 * ║                                                                              ║
 * ║  If you must modify, TEST THOROUGHLY with multiple image generations         ║
 * ║  to verify realism is maintained.                                            ║
 * ╚══════════════════════════════════════════════════════════════════════════════╝
 */
class StructuredPromptBuilderService
{
    /**
     * Visual mode configurations with structured templates.
     * These define the base settings for each visual style.
     */
    public const VISUAL_MODE_TEMPLATES = [
        'cinematic-realistic' => [
            'prompt_type' => 'photorealistic_cinematic',
            'output_settings' => [
                'resolution_target' => 'ultra_high_res',
                'render_style' => 'ultra_photoreal_cinematic_film_still',
                'sharpness' => 'crisp_but_natural',
                'film_grain' => 'subtle_35mm',
                'color_grade' => 'cinematic_color_science',
                'dynamic_range' => 'natural_cinematic',
                'skin_rendering' => 'real_texture_no_retouch',
            ],
            'global_rules' => [
                'camera_language' => 'shot on ARRI Alexa Mini LF with Zeiss Master Prime 50mm lens, cinematic framing, professional cinematography, natural bokeh with real optical characteristics',
                'authenticity_markers' => 'subtle 35mm film grain visible at pixel level, gentle halation bleeding on overexposed highlights, organic chromatic aberration at frame edges, realistic skin with visible pores and texture, micro-imperfections like tiny freckles or slight skin blemishes, natural hair with individual strands visible, fabric weave texture clearly rendered, environmental dust particles in light rays',
                'lighting_language' => 'motivated natural or practical lighting only, cinematographer-quality light shaping with real falloff, realistic shadows with bounce light detail, no artificial studio perfection',
            ],
            'quality_keywords' => [
                '8K UHD',
                'ultra high resolution',
                'photorealistic',
                'hyperdetailed',
                'cinematic depth of field',
                'HDR',
                'professional color grading',
                'award-winning cinematography',
                'film still quality',
                'natural skin texture with pores',
                'real fabric texture',
                'authentic lighting',
            ],
            'negative_prompt' => [
                'cartoon',
                'anime',
                'illustrated',
                'stylized',
                '3D render',
                'CGI look',
                'digital painting',
                'concept art',
                'plastic skin',
                'beauty retouch',
                'over-sharpened',
                'AI glow',
                'fake bokeh',
                'perfect symmetry',
                'watermark',
                'text',
                'logo',
                'blurry',
                'low quality',
                'oversaturated',
                'airbrushed skin',
                'waxy complexion',
                'doll-like features',
                'uncanny valley',
                'smooth plastic texture',
                'stock photo look',
                'generic lighting',
                'artificial perfection',
                'instagram filter',
                'hyperreal gloss',
                'mannequin appearance',
            ],
        ],
        'documentary-realistic' => [
            'prompt_type' => 'photorealistic_documentary',
            'output_settings' => [
                'resolution_target' => 'ultra_high_res',
                'render_style' => 'ultra_photoreal_candid_documentary',
                'sharpness' => 'crisp_but_natural',
                'film_grain' => 'subtle_35mm',
                'color_grade' => 'restrained_cinematic_realism',
                'dynamic_range' => 'natural_not_hdr',
                'skin_rendering' => 'real_texture_no_retouch',
            ],
            'global_rules' => [
                'camera_language' => '35mm lens equivalent shot on Leica M or Canon R5, eye-level candid framing, slightly imperfect composition suggesting real moment capture, focus on eyes when person is present',
                'authenticity_markers' => 'subtle halation on bright highlights, visible environmental texture (dust, steam, mist), realistic background clutter, slight motion blur on secondary elements, natural skin imperfections, wrinkles and expression lines, worn fabric and real material textures, environmental context (rain droplets, snow, leaves)',
                'lighting_language' => 'motivated natural light only - window light, overcast daylight, practical lamps, golden hour sun, deep shadows with visible detail, no artificial fill light',
            ],
            'quality_keywords' => [
                '4K Ultra HD',
                'high resolution',
                'photorealistic',
                'documentary photography',
                'street photography style',
                'natural lighting',
                'candid moment captured',
                'authentic real-world scene',
                'environmental portrait',
                'genuine human moment',
            ],
            'negative_prompt' => [
                'studio lighting',
                'beauty retouch',
                'plastic skin',
                'over-sharpened',
                'HDR overprocessed',
                'AI glow',
                'perfect symmetry',
                'overly clean background',
                'fake bokeh',
                'text',
                'logo',
                'watermark',
                'cartoon',
                'painterly',
                'posed model',
                'artificial',
                'fashion photography',
                'commercial look',
                'sterile environment',
                'perfect lighting',
                'flawless skin',
                'magazine cover',
            ],
        ],
        'stylized-animation' => [
            'prompt_type' => 'stylized_animation',
            'output_settings' => [
                'resolution_target' => 'high_res',
                'render_style' => '3d_animation_stylized',
                'sharpness' => 'clean_crisp',
                'film_grain' => 'none',
                'color_grade' => 'vibrant_animated',
                'dynamic_range' => 'enhanced',
                'skin_rendering' => 'stylized_clean',
            ],
            'global_rules' => [
                'camera_language' => 'animated film camera work, dynamic angles, expressive framing',
                'authenticity_markers' => 'clean lines, stylized proportions, animated film quality',
                'lighting_language' => 'stylized lighting, rim lights, dramatic color lighting',
            ],
            'quality_keywords' => [
                '4K',
                'Pixar quality',
                'DreamWorks style',
                'professional 3D animation',
                'stylized',
                'vibrant colors',
                'clean rendering',
            ],
            'negative_prompt' => [
                'photorealistic',
                'live-action',
                'real person',
                'documentary',
                'film grain',
                'natural lighting',
                'low quality',
                'blurry',
            ],
        ],
        'mixed-hybrid' => [
            'prompt_type' => 'mixed_hybrid',
            'output_settings' => [
                'resolution_target' => 'ultra_high_res',
                'render_style' => 'flexible_creative',
                'sharpness' => 'balanced',
                'film_grain' => 'optional_subtle',
                'color_grade' => 'creative_flexible',
                'dynamic_range' => 'balanced',
                'skin_rendering' => 'context_appropriate',
            ],
            'global_rules' => [
                'camera_language' => 'flexible camera work appropriate to scene',
                'authenticity_markers' => 'style-appropriate markers',
                'lighting_language' => 'context-appropriate lighting',
            ],
            'quality_keywords' => [
                'high quality',
                'detailed',
                'professional',
                'well-composed',
            ],
            'negative_prompt' => [
                'low quality',
                'blurry',
                'watermark',
                'text',
                'logo',
            ],
        ],
    ];

    /**
     * Preset lighting configurations.
     */
    public const LIGHTING_PRESETS = [
        'golden_hour' => [
            'lighting' => 'warm golden hour sunlight streaming at low angle, long soft shadows, golden rim lighting on subject',
            'mood' => 'warm, romantic, nostalgic',
            'color_palette' => 'warm oranges, golden yellows, soft shadows with blue fill',
        ],
        'blue_hour' => [
            'lighting' => 'soft blue hour twilight, ambient blue tones, city lights beginning to glow',
            'mood' => 'contemplative, serene, mysterious',
            'color_palette' => 'deep blues, purple undertones, warm accent lights',
        ],
        'overcast_natural' => [
            'lighting' => 'soft overcast daylight, diffused natural illumination, no harsh shadows',
            'mood' => 'natural, authentic, approachable',
            'color_palette' => 'neutral tones, soft contrast, natural colors',
        ],
        'dramatic_low_key' => [
            'lighting' => 'dramatic low-key chiaroscuro lighting, deep shadows with selective highlights, motivated single source',
            'mood' => 'dramatic, intense, cinematic',
            'color_palette' => 'high contrast, deep blacks, selective bright highlights',
        ],
        'studio_soft' => [
            'lighting' => 'professional studio soft box lighting, beauty dish key light, fill and rim lights',
            'mood' => 'polished, professional, commercial',
            'color_palette' => 'clean whites, accurate skin tones, controlled shadows',
        ],
        'neon_night' => [
            'lighting' => 'neon signs casting colored light, urban night illumination, practical light sources',
            'mood' => 'urban, energetic, modern',
            'color_palette' => 'neon pinks, cyans, purples against dark backgrounds',
        ],
        'window_light' => [
            'lighting' => 'soft natural window light, diffused daylight through curtains, gentle gradients',
            'mood' => 'intimate, peaceful, natural',
            'color_palette' => 'soft neutral tones, gentle shadows, natural warmth',
        ],
    ];

    /**
     * Camera/lens presets for different shot types.
     */
    public const CAMERA_PRESETS = [
        'portrait_85mm' => [
            'lens' => '85mm f/1.4',
            'depth_of_field' => 'shallow, creamy bokeh',
            'description' => '85mm portrait lens with beautiful bokeh and flattering compression',
        ],
        'wide_24mm' => [
            'lens' => '24mm f/2.8',
            'depth_of_field' => 'medium to deep',
            'description' => '24mm wide angle for establishing shots and environmental portraits',
        ],
        'standard_50mm' => [
            'lens' => '50mm f/1.8',
            'depth_of_field' => 'medium shallow',
            'description' => '50mm natural perspective, close to human eye',
        ],
        'telephoto_135mm' => [
            'lens' => '135mm f/2',
            'depth_of_field' => 'very shallow, strong background separation',
            'description' => '135mm telephoto for dramatic compression and isolation',
        ],
        'cinematic_anamorphic' => [
            'lens' => 'anamorphic 40mm',
            'depth_of_field' => 'oval bokeh, horizontal flares',
            'description' => 'anamorphic lens with characteristic oval bokeh and lens flares',
        ],
        'street_35mm' => [
            'lens' => '35mm f/2',
            'depth_of_field' => 'medium, environmental context',
            'description' => '35mm street photography lens, subject in context',
        ],
    ];

    /**
     * Build a complete structured prompt from scene data.
     *
     * @param array $options Configuration options
     * @return array Structured prompt data
     */
    public function build(array $options): array
    {
        $visualMode = $options['visual_mode'] ?? 'cinematic-realistic';
        $template = self::VISUAL_MODE_TEMPLATES[$visualMode] ?? self::VISUAL_MODE_TEMPLATES['cinematic-realistic'];

        // Get aspect ratio from project
        $aspectRatio = $options['aspect_ratio'] ?? '16:9';

        // Build the structured prompt
        $structuredPrompt = [
            'meta_data' => [
                'prompt_type' => $template['prompt_type'],
                'visual_mode' => $visualMode,
                'version' => 'v2.0_STRUCTURED_PROMPT',
            ],
            'output_settings' => array_merge($template['output_settings'], [
                'aspect_ratio' => $aspectRatio,
                'orientation' => $this->getOrientation($aspectRatio),
            ]),
            'global_rules' => $template['global_rules'],
            'creative_prompt' => $this->buildCreativePrompt($options, $template),
            'technical_specifications' => $this->buildTechnicalSpecs($options, $template),
            'negative_prompt' => $this->buildNegativePrompt($options, $template),
        ];

        Log::debug('StructuredPromptBuilder: Built structured prompt', [
            'visualMode' => $visualMode,
            'hasSubject' => !empty($structuredPrompt['creative_prompt']['subject']),
            'hasEnvironment' => !empty($structuredPrompt['creative_prompt']['environment']),
        ]);

        return $structuredPrompt;
    }

    /**
     * Build the creative prompt section.
     */
    protected function buildCreativePrompt(array $options, array $template): array
    {
        $sceneDescription = $options['scene_description'] ?? '';
        $characterBible = $options['character_bible'] ?? null;
        $locationBible = $options['location_bible'] ?? null;
        $styleBible = $options['style_bible'] ?? null;
        $sceneIndex = $options['scene_index'] ?? 0;

        // Build subject from character bible
        $subject = $this->buildSubjectDescription($characterBible, $sceneIndex, $sceneDescription);

        // Build environment from location bible
        $environment = $this->buildEnvironmentDescription($locationBible, $sceneIndex, $sceneDescription);

        // Build lighting from style bible and location
        $lighting = $this->buildLightingDescription($styleBible, $locationBible, $sceneIndex);

        // Build scene summary
        $sceneSummary = $this->buildSceneSummary($sceneDescription, $subject, $environment);

        // Build Character DNA for Hollywood-level consistency
        $characterDNA = $this->buildCharacterDNA($characterBible, $sceneIndex);

        // Build Style DNA for visual consistency
        $styleDNA = $this->buildStyleDNA($styleBible);

        // Build Location DNA for environmental consistency
        $locationDNA = $this->buildLocationDNA($locationBible, $sceneIndex);

        return [
            'scene_summary' => $sceneSummary,
            'subject' => $subject,
            'environment' => $environment,
            'lighting_and_atmosphere' => $lighting,
            'authenticity_markers' => $template['global_rules']['authenticity_markers'],
            'mood' => $styleBible['atmosphere'] ?? $lighting['mood'] ?? 'cinematic, dramatic',
            'character_dna' => $characterDNA, // Array of DNA blocks for each character
            'style_dna' => $styleDNA,         // Style DNA block for visual consistency
            'location_dna' => $locationDNA,   // Location DNA block for environmental consistency
        ];
    }

    /**
     * Build detailed subject description from character bible.
     */
    protected function buildSubjectDescription(?array $characterBible, ?int $sceneIndex, string $sceneDescription): array
    {
        $subject = [
            'description' => '',
            'physical_details' => '',
            'expression' => 'natural, authentic expression',
            'attire' => '',
            'pose' => '',
            'micro_action' => '',
        ];

        if (!$characterBible || !($characterBible['enabled'] ?? false)) {
            // Extract subject hints from scene description
            $subject['description'] = $this->extractSubjectFromDescription($sceneDescription);
            return $subject;
        }

        $characters = $characterBible['characters'] ?? [];
        $sceneCharacters = [];

        foreach ($characters as $character) {
            // Support multiple field names for scene assignment (matching ImageGenerationService)
            $appliedScenes = $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];

            // Include character if:
            // - sceneIndex is null (no scene context, e.g., portrait generation) - include all
            // - Empty appliedScenes array means "applies to ALL scenes" (per UI design)
            // - Character is specifically assigned to this scene
            if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                $sceneCharacters[] = $character;
            }
        }

        if (empty($sceneCharacters)) {
            $subject['description'] = $this->extractSubjectFromDescription($sceneDescription);
            return $subject;
        }

        // Build detailed subject from first/main character
        $mainCharacter = $sceneCharacters[0];

        $subject['description'] = $mainCharacter['description'] ?? '';
        $subject['physical_details'] = $this->extractPhysicalDetails($mainCharacter);
        $subject['expression'] = $mainCharacter['defaultExpression'] ?? 'natural, authentic expression';
        $subject['attire'] = $mainCharacter['attire'] ?? '';

        // Extract pose from scene description if available
        $subject['pose'] = $this->extractPoseFromDescription($sceneDescription);
        $subject['micro_action'] = $this->extractMicroAction($sceneDescription);

        // If multiple characters, add secondary
        if (count($sceneCharacters) > 1) {
            $secondaryDescriptions = [];
            for ($i = 1; $i < count($sceneCharacters); $i++) {
                $secondaryDescriptions[] = $sceneCharacters[$i]['description'] ?? '';
            }
            $subject['secondary_subjects'] = implode(', also featuring ', array_filter($secondaryDescriptions));
        }

        return $subject;
    }

    /**
     * Extract physical details from character data.
     */
    protected function extractPhysicalDetails(array $character): string
    {
        $details = [];

        // Try to extract structured physical details if available
        if (!empty($character['physical'])) {
            $physical = $character['physical'];
            if (!empty($physical['skin_tone'])) $details[] = $physical['skin_tone'] . ' skin tone';
            if (!empty($physical['hair'])) $details[] = $physical['hair'];
            if (!empty($physical['eye_color'])) $details[] = $physical['eye_color'] . ' eyes';
            if (!empty($physical['build'])) $details[] = $physical['build'] . ' build';
            if (!empty($physical['age_range'])) $details[] = 'approximately ' . $physical['age_range'] . ' years old';
        }

        // Fall back to description parsing
        if (empty($details) && !empty($character['description'])) {
            return 'with natural appearance and realistic features';
        }

        return implode(', ', $details);
    }

    /**
     * Build environment description from location bible.
     */
    protected function buildEnvironmentDescription(?array $locationBible, ?int $sceneIndex, string $sceneDescription): array
    {
        $environment = [
            'location' => '',
            'background' => '',
            'details' => '',
            'atmosphere' => '',
            'time_of_day' => 'day',
            'weather' => 'clear',
        ];

        if (!$locationBible || !($locationBible['enabled'] ?? false)) {
            // Extract environment hints from scene description
            $environment['location'] = $this->extractLocationFromDescription($sceneDescription);
            return $environment;
        }

        $locations = $locationBible['locations'] ?? [];
        $sceneLocation = null;

        foreach ($locations as $location) {
            // Support multiple field names for scene assignment (matching ImageGenerationService)
            $appliedScenes = $location['scenes'] ?? $location['appliedScenes'] ?? $location['appearsInScenes'] ?? [];

            // Include location if:
            // - sceneIndex is null (no scene context) - use first available
            // - Empty appliedScenes array means "applies to ALL scenes" (per UI design)
            // - Location is specifically assigned to this scene
            if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                $sceneLocation = $location;
                break;
            }
        }

        // Fall back to first location if no specific assignment
        if (!$sceneLocation && !empty($locations)) {
            $sceneLocation = $locations[0];
        }

        if (!$sceneLocation) {
            $environment['location'] = $this->extractLocationFromDescription($sceneDescription);
            return $environment;
        }

        $environment['location'] = $sceneLocation['name'] ?? '';
        $environment['background'] = $sceneLocation['description'] ?? '';
        $environment['details'] = $sceneLocation['details'] ?? $this->extractEnvironmentDetails($sceneDescription);
        $environment['atmosphere'] = $sceneLocation['atmosphere'] ?? '';
        $environment['time_of_day'] = $sceneLocation['timeOfDay'] ?? 'day';
        $environment['weather'] = $sceneLocation['weather'] ?? 'clear';

        // Include location state if available
        if (!empty($sceneLocation['sceneStates'][$sceneIndex])) {
            $state = $sceneLocation['sceneStates'][$sceneIndex];
            if (!empty($state['stateDescription'])) {
                $environment['details'] .= '. ' . $state['stateDescription'];
            }
        }

        return $environment;
    }

    /**
     * Build lighting description.
     */
    protected function buildLightingDescription(?array $styleBible, ?array $locationBible, ?int $sceneIndex): array
    {
        $lighting = [
            'lighting' => '',
            'mood' => '',
            'color_palette' => '',
        ];

        // Get time of day from location
        $timeOfDay = 'day';
        if ($locationBible && ($locationBible['enabled'] ?? false)) {
            $locations = $locationBible['locations'] ?? [];
            foreach ($locations as $location) {
                $appliedScenes = $location['scenes'] ?? $location['appliedScenes'] ?? $location['appearsInScenes'] ?? [];

                // Match location if no scene context (use first), empty assignment (applies to all), or specific match
                if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                    $timeOfDay = $location['timeOfDay'] ?? 'day';
                    break;
                }
            }
        }

        // Map time of day to lighting preset
        $lightingPreset = $this->getTimeOfDayLighting($timeOfDay);
        $lighting = array_merge($lighting, $lightingPreset);

        // Override with style bible if available
        if ($styleBible && ($styleBible['enabled'] ?? false)) {
            if (!empty($styleBible['atmosphere'])) {
                $lighting['mood'] = $styleBible['atmosphere'];
            }
            if (!empty($styleBible['colorGrade'])) {
                $lighting['color_palette'] = $styleBible['colorGrade'];
            }
        }

        return $lighting;
    }

    /**
     * Get lighting preset based on time of day.
     */
    protected function getTimeOfDayLighting(string $timeOfDay): array
    {
        $mappings = [
            'dawn' => self::LIGHTING_PRESETS['golden_hour'],
            'morning' => self::LIGHTING_PRESETS['overcast_natural'],
            'day' => self::LIGHTING_PRESETS['overcast_natural'],
            'afternoon' => self::LIGHTING_PRESETS['overcast_natural'],
            'golden_hour' => self::LIGHTING_PRESETS['golden_hour'],
            'sunset' => self::LIGHTING_PRESETS['golden_hour'],
            'dusk' => self::LIGHTING_PRESETS['blue_hour'],
            'evening' => self::LIGHTING_PRESETS['blue_hour'],
            'night' => self::LIGHTING_PRESETS['neon_night'],
        ];

        return $mappings[$timeOfDay] ?? self::LIGHTING_PRESETS['overcast_natural'];
    }

    /**
     * Build the scene summary.
     */
    protected function buildSceneSummary(string $sceneDescription, array $subject, array $environment): string
    {
        $parts = [];

        // Start with scene type
        $parts[] = 'Cinematic photorealistic scene';

        // Add location context
        if (!empty($environment['location'])) {
            $parts[] = 'set in ' . $environment['location'];
        }

        // Add subject context
        if (!empty($subject['description'])) {
            $parts[] = 'featuring ' . $subject['description'];
        }

        // Add the visual description
        if (!empty($sceneDescription)) {
            $parts[] = $sceneDescription;
        }

        // Add atmosphere
        if (!empty($environment['atmosphere'])) {
            $parts[] = $environment['atmosphere'];
        }

        return implode('. ', array_filter($parts));
    }

    /**
     * Build technical specifications.
     */
    protected function buildTechnicalSpecs(array $options, array $template): array
    {
        $styleBible = $options['style_bible'] ?? null;

        $specs = [
            'quality' => implode(', ', $template['quality_keywords']),
            'style' => $template['prompt_type'] === 'photorealistic_cinematic'
                ? 'Realistic photography, cinematic film still, Hollywood quality'
                : 'High quality professional image',
            'focus' => 'Sharp focus on subject with cinematic depth of field, natural bokeh',
            'camera' => $template['global_rules']['camera_language'],
        ];

        // Override with style bible if available
        if ($styleBible && ($styleBible['enabled'] ?? false)) {
            if (!empty($styleBible['camera'])) {
                $specs['camera'] = $styleBible['camera'];
            }
            if (!empty($styleBible['visualDNA'])) {
                $specs['quality'] = $styleBible['visualDNA'] . ', ' . $specs['quality'];
            }
        }

        return $specs;
    }

    /**
     * Build negative prompt array.
     */
    protected function buildNegativePrompt(array $options, array $template): array
    {
        $negativePrompt = $template['negative_prompt'];

        // Add custom negative prompts from style bible
        $styleBible = $options['style_bible'] ?? null;
        if ($styleBible && !empty($styleBible['negativePrompt'])) {
            $customNegatives = array_map('trim', explode(',', $styleBible['negativePrompt']));
            $negativePrompt = array_merge($negativePrompt, $customNegatives);
        }

        return array_unique($negativePrompt);
    }

    /**
     * Convert structured prompt to optimized JSON-format string for API.
     *
     * This format produces TRUE photorealistic results by using explicit
     * camera specifications (aperture, focal length, DOF) and explicit
     * "hyper-realistic" directives that Gemini responds to better than
     * generic "photorealistic" prose.
     */
    public function toPromptString(array $structuredPrompt): string
    {
        $creative = $structuredPrompt['creative_prompt'] ?? [];
        $techSpecs = $structuredPrompt['technical_specifications'] ?? [];
        $visualMode = $structuredPrompt['meta_data']['visual_mode'] ?? 'cinematic-realistic';

        // For non-photorealistic modes, use prose format
        if ($visualMode === 'stylized-animation') {
            return $this->toProseString($structuredPrompt);
        }

        // Build JSON-structured prompt for photorealistic modes
        // This format produces TRUE photorealistic results
        $jsonPrompt = $this->buildJsonPrompt($structuredPrompt);

        // Add DNA blocks for character/style/location consistency
        $dnaBlocks = $this->buildDnaBlocks($creative);

        if (!empty($dnaBlocks)) {
            $jsonPrompt .= "\n\n" . $dnaBlocks;
        }

        return $jsonPrompt;
    }

    /**
     * Build JSON-structured prompt for photorealistic output.
     * Based on proven prompts that produce TRUE photorealistic results.
     *
     * ⚠️  CRITICAL: DO NOT MODIFY without thorough testing!
     *     This JSON structure produces TRUE photorealistic results.
     *     Changing to prose or removing elements WILL cause cartoon-like output.
     */
    protected function buildJsonPrompt(array $structuredPrompt): string
    {
        $creative = $structuredPrompt['creative_prompt'] ?? [];
        $techSpecs = $structuredPrompt['technical_specifications'] ?? [];
        $subject = $creative['subject'] ?? [];
        $environment = $creative['environment'] ?? [];
        $lighting = $creative['lighting_and_atmosphere'] ?? [];

        // Build subject section
        $subjectJson = $this->buildSubjectJson($subject);

        // Build setting section
        $settingJson = $this->buildSettingJson($environment, $lighting);

        // Build camera section with explicit aperture and DOF
        $cameraJson = $this->buildCameraJson($techSpecs);

        // Build style section with hyper-realistic directive
        $styleJson = $this->buildStyleJson($structuredPrompt);

        // Scene summary for context
        $sceneSummary = $creative['scene_summary'] ?? 'Cinematic photorealistic scene';

        // Build the JSON-structured prompt
        $jsonPrompt = <<<EOT
{
  "scene": "{$sceneSummary}",
  "subject": {$subjectJson},
  "setting": {$settingJson},
  "camera": {$cameraJson},
  "style": {$styleJson},
  "critical_requirements": {
    "realism": "HYPER-REALISTIC - must look like a real photograph, not AI-generated",
    "skin": "high-fidelity skin detail with natural color variation, visible pores, micro-imperfections",
    "texture": "real fabric texture, natural hair with individual strands visible",
    "post_processing": "NO retouching, NO smoothing, NO airbrushing - preserve natural skin texture",
    "lighting": "motivated natural or practical lighting only, realistic shadows with bounce light detail"
  }
}
EOT;

        return $jsonPrompt;
    }

    /**
     * Build subject JSON section.
     *
     * ⚠️  CRITICAL: The "skin" field with explicit pore/texture requirements
     *     is ESSENTIAL for photorealism. DO NOT remove it!
     */
    protected function buildSubjectJson(array $subject): string
    {
        $parts = [];

        if (!empty($subject['description'])) {
            $parts[] = '"description": "' . $this->escapeJson($subject['description']) . '"';
        }

        if (!empty($subject['physical_details'])) {
            $parts[] = '"physical_details": "' . $this->escapeJson($subject['physical_details']) . '"';
        }

        if (!empty($subject['expression'])) {
            $parts[] = '"expression": "' . $this->escapeJson($subject['expression']) . '"';
        }

        if (!empty($subject['attire'])) {
            $parts[] = '"attire": "' . $this->escapeJson($subject['attire']) . '"';
        }

        if (!empty($subject['pose'])) {
            $parts[] = '"pose": "' . $this->escapeJson($subject['pose']) . '"';
        }

        // CRITICAL: Add skin requirement for realism
        $parts[] = '"skin": "high-fidelity detail with natural color variation and realistic pores, visible micro-texture"';

        if (empty($parts)) {
            return '{}';
        }

        return "{\n    " . implode(",\n    ", $parts) . "\n  }";
    }

    /**
     * Build setting JSON section.
     */
    protected function buildSettingJson(array $environment, array $lighting): string
    {
        $parts = [];

        $envDescription = [];
        if (!empty($environment['location'])) {
            $envDescription[] = $environment['location'];
        }
        if (!empty($environment['background'])) {
            $envDescription[] = $environment['background'];
        }
        if (!empty($environment['details'])) {
            $envDescription[] = $environment['details'];
        }

        if (!empty($envDescription)) {
            $parts[] = '"environment": "' . $this->escapeJson(implode(', ', $envDescription)) . '"';
        }

        if (!empty($lighting['lighting'])) {
            $parts[] = '"lighting": "' . $this->escapeJson($lighting['lighting']) . '"';
        }

        if (!empty($lighting['mood'])) {
            $parts[] = '"atmosphere": "' . $this->escapeJson($lighting['mood']) . '"';
        }

        if (!empty($environment['time_of_day'])) {
            $parts[] = '"time_of_day": "' . $this->escapeJson($environment['time_of_day']) . '"';
        }

        if (empty($parts)) {
            return '{}';
        }

        return "{\n    " . implode(",\n    ", $parts) . "\n  }";
    }

    /**
     * Build camera JSON section with EXPLICIT aperture and DOF settings.
     * These explicit settings are CRITICAL for producing true photorealistic results.
     *
     * ⚠️  CRITICAL: The aperture values (f/1.8, f/2.0, f/2.8) and DOF descriptions
     *     are ESSENTIAL for photorealism. DO NOT remove or simplify them!
     */
    protected function buildCameraJson(array $techSpecs): string
    {
        // Default to portrait lens settings for realistic output
        // 85mm f/1.8 is proven to produce the most photorealistic results
        $lens = '85mm portrait lens';
        $aperture = 'f/1.8';
        $dof = 'shallow depth of field with subject in sharp focus and background softly blurred';
        $cameraType = 'Professional DSLR';

        // Parse camera info from tech specs if available
        $cameraInfo = $techSpecs['camera'] ?? '';

        // Extract lens if mentioned
        if (preg_match('/(\d+mm)/i', $cameraInfo, $matches)) {
            $focalLength = $matches[1];
            $lens = $focalLength . ' lens';

            // Adjust aperture and DOF based on focal length
            $focalNum = (int) filter_var($focalLength, FILTER_SANITIZE_NUMBER_INT);
            if ($focalNum <= 35) {
                // Wide angle - deeper DOF for establishing shots
                $aperture = 'f/2.8';
                $dof = 'medium depth of field with environmental context visible';
                $lens = $focalLength . ' wide angle lens';
            } elseif ($focalNum >= 85) {
                // Portrait/telephoto - shallow DOF for subject isolation
                $aperture = 'f/1.8';
                $dof = 'very shallow depth of field with face in sharp focus and background softly blurred';
                $lens = $focalLength . ' portrait lens';
            } else {
                // Standard 50mm - balanced DOF
                $aperture = 'f/2.0';
                $dof = 'shallow depth of field, natural bokeh';
                $lens = $focalLength . ' standard lens';
            }
        }

        // Check for anamorphic mentions
        if (stripos($cameraInfo, 'anamorphic') !== false) {
            $lens = 'anamorphic 40mm lens';
            $aperture = 'f/2.0';
            $dof = 'oval bokeh characteristic of anamorphic lenses, horizontal lens flares';
        }

        return <<<EOT
{
    "type": "{$cameraType}",
    "lens": "{$lens}",
    "aperture": "{$aperture}",
    "dof": "{$dof}",
    "angle": "eye level, natural perspective"
  }
EOT;
    }

    /**
     * Build style JSON section with EXPLICIT hyper-realistic directive.
     *
     * ⚠️  CRITICAL: Must use "hyper-realistic" NOT "photorealistic"!
     *     "photorealistic" is a weaker directive and produces stylized/cartoon results.
     *     This was discovered through extensive regression testing.
     */
    protected function buildStyleJson(array $structuredPrompt): string
    {
        $visualMode = $structuredPrompt['meta_data']['visual_mode'] ?? 'cinematic-realistic';

        // CRITICAL: Use "hyper-realistic" not just "photorealistic"
        // This stronger directive produces much better results
        // DO NOT CHANGE THIS - it will cause cartoon-like output!
        $realism = 'hyper-realistic';
        $quality = '8K resolution, extremely detailed, masterful composition';
        $colorGrade = 'subtle natural tones, accurate skin colors, cinematic color science';
        $postProcessing = 'NO visible retouching or smoothing, preserves natural skin texture and imperfections';

        // Adjust based on visual mode
        if ($visualMode === 'documentary-realistic') {
            $realism = 'documentary-realistic';
            $colorGrade = 'restrained natural colors, documentary authenticity';
        }

        return <<<EOT
{
    "realism": "{$realism}",
    "quality": "{$quality}",
    "color_grade": "{$colorGrade}",
    "post_processing": "{$postProcessing}"
  }
EOT;
    }

    /**
     * Build DNA blocks for character/style/location consistency.
     */
    protected function buildDnaBlocks(array $creative): string
    {
        $blocks = [];

        // Style DNA
        $styleDNA = $creative['style_dna'] ?? '';
        if (!empty($styleDNA)) {
            $blocks[] = $styleDNA;
        }

        // Location DNA
        $locationDNA = $creative['location_dna'] ?? '';
        if (!empty($locationDNA)) {
            $blocks[] = $locationDNA;
        }

        // Character DNA
        $characterDNA = $creative['character_dna'] ?? [];
        if (!empty($characterDNA)) {
            $blocks = array_merge($blocks, $characterDNA);
        }

        return implode("\n\n", $blocks);
    }

    /**
     * Escape string for JSON embedding.
     */
    protected function escapeJson(string $text): string
    {
        // Remove newlines and escape quotes
        $text = str_replace(["\n", "\r", '"'], [' ', '', '\\"'], $text);
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Legacy prose format for non-photorealistic modes (e.g., stylized-animation).
     */
    protected function toProseString(array $structuredPrompt): string
    {
        $parts = [];

        // Opening with quality and style
        $techSpecs = $structuredPrompt['technical_specifications'] ?? [];
        $parts[] = $techSpecs['quality'] ?? '8K UHD, photorealistic';

        // Camera setup
        if (!empty($techSpecs['camera'])) {
            $parts[] = $techSpecs['camera'];
        }

        // Scene summary
        $creative = $structuredPrompt['creative_prompt'] ?? [];
        if (!empty($creative['scene_summary'])) {
            $parts[] = $creative['scene_summary'];
        }

        // Subject details
        $subject = $creative['subject'] ?? [];
        if (!empty($subject['description'])) {
            $subjectParts = ['featuring ' . $subject['description']];
            if (!empty($subject['physical_details'])) {
                $subjectParts[] = $subject['physical_details'];
            }
            if (!empty($subject['expression'])) {
                $subjectParts[] = $subject['expression'];
            }
            if (!empty($subject['attire'])) {
                $subjectParts[] = 'wearing ' . $subject['attire'];
            }
            if (!empty($subject['pose'])) {
                $subjectParts[] = $subject['pose'];
            }
            if (!empty($subject['micro_action'])) {
                $subjectParts[] = $subject['micro_action'];
            }
            $parts[] = implode(', ', array_filter($subjectParts));
        }

        // Environment
        $environment = $creative['environment'] ?? [];
        if (!empty($environment['location']) || !empty($environment['background'])) {
            $envParts = [];
            if (!empty($environment['location'])) {
                $envParts[] = 'set in ' . $environment['location'];
            }
            if (!empty($environment['background'])) {
                $envParts[] = $environment['background'];
            }
            if (!empty($environment['details'])) {
                $envParts[] = $environment['details'];
            }
            $parts[] = implode(', ', array_filter($envParts));
        }

        // Lighting and atmosphere
        $lighting = $creative['lighting_and_atmosphere'] ?? [];
        if (!empty($lighting['lighting'])) {
            $parts[] = $lighting['lighting'];
        }
        if (!empty($lighting['color_palette'])) {
            $parts[] = $lighting['color_palette'];
        }
        if (!empty($lighting['mood'])) {
            $parts[] = $lighting['mood'] . ' mood';
        }

        // Authenticity markers
        if (!empty($creative['authenticity_markers'])) {
            $parts[] = $creative['authenticity_markers'];
        }

        // Focus and technical
        if (!empty($techSpecs['focus'])) {
            $parts[] = $techSpecs['focus'];
        }

        // Combine base prompt
        $basePrompt = implode('. ', array_filter($parts));

        // Add DNA blocks
        $dnaBlocks = $this->buildDnaBlocks($creative);
        if (!empty($dnaBlocks)) {
            $basePrompt .= "\n\n" . $dnaBlocks;
        }

        return $basePrompt;
    }

    /**
     * Get negative prompt as string.
     */
    public function getNegativePromptString(array $structuredPrompt): string
    {
        $negatives = $structuredPrompt['negative_prompt'] ?? [];
        return implode(', ', $negatives);
    }

    /**
     * Get orientation from aspect ratio.
     */
    protected function getOrientation(string $aspectRatio): string
    {
        $parts = explode(':', $aspectRatio);
        if (count($parts) !== 2) return 'landscape';

        $width = (int) $parts[0];
        $height = (int) $parts[1];

        if ($width > $height) return 'landscape';
        if ($height > $width) return 'portrait';
        return 'square';
    }

    // =========================================================================
    // HELPER METHODS FOR EXTRACTING INFO FROM DESCRIPTIONS
    // =========================================================================

    protected function extractSubjectFromDescription(string $description): string
    {
        // Basic extraction - can be enhanced with NLP
        if (stripos($description, 'person') !== false ||
            stripos($description, 'man') !== false ||
            stripos($description, 'woman') !== false ||
            stripos($description, 'character') !== false) {
            return 'a person in the scene';
        }
        return '';
    }

    protected function extractLocationFromDescription(string $description): string
    {
        // Basic extraction - can be enhanced
        $locationKeywords = ['in', 'at', 'inside', 'outside', 'near', 'by the'];
        foreach ($locationKeywords as $keyword) {
            if (preg_match("/{$keyword}\s+(?:the\s+)?([^,.]+)/i", $description, $matches)) {
                return trim($matches[1]);
            }
        }
        return '';
    }

    protected function extractEnvironmentDetails(string $description): string
    {
        // Extract environmental details from description
        return '';
    }

    protected function extractPoseFromDescription(string $description): string
    {
        $poseKeywords = ['standing', 'sitting', 'walking', 'running', 'lying', 'crouching', 'leaning'];
        foreach ($poseKeywords as $keyword) {
            if (stripos($description, $keyword) !== false) {
                return $keyword;
            }
        }
        return '';
    }

    protected function extractMicroAction(string $description): string
    {
        // Look for action verbs
        $actionPatterns = [
            '/looking at ([^,.]+)/i',
            '/holding ([^,.]+)/i',
            '/touching ([^,.]+)/i',
            '/reaching for ([^,.]+)/i',
        ];

        foreach ($actionPatterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return $matches[0];
            }
        }
        return '';
    }

    // =========================================================================
    // CHARACTER DNA METHODS - Hollywood-level consistency
    // =========================================================================

    /**
     * Build Character DNA blocks for all characters in a scene.
     * This ensures Hollywood-level visual consistency across shots.
     */
    protected function buildCharacterDNA(?array $characterBible, ?int $sceneIndex): array
    {
        if (!$characterBible || !($characterBible['enabled'] ?? false)) {
            return [];
        }

        $characters = $characterBible['characters'] ?? [];
        $dnaBlocks = [];

        foreach ($characters as $character) {
            $appliedScenes = $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];

            // Include if: no scene context, applies to all scenes, or matches this scene
            if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                $dna = $this->buildSingleCharacterDNA($character);
                if (!empty($dna)) {
                    $dnaBlocks[] = $dna;
                }
            }
        }

        return $dnaBlocks;
    }

    /**
     * Build DNA template for a single character.
     */
    protected function buildSingleCharacterDNA(array $character): string
    {
        $name = $character['name'] ?? 'Character';
        $parts = [];

        // Identity/Face section
        $identityParts = [];
        if (!empty($character['description'])) {
            $identityParts[] = $character['description'];
        }
        $identityParts[] = "Same skin tone, same complexion, same facial proportions";
        $identityParts[] = "Same body type and build";
        $parts[] = "IDENTITY: " . implode('. ', $identityParts);

        // Hair section - critical for visual consistency
        $hair = $character['hair'] ?? [];
        if (!empty(array_filter($hair))) {
            $hairParts = [];
            if (!empty($hair['color'])) $hairParts[] = $hair['color'];
            if (!empty($hair['length'])) $hairParts[] = $hair['length'];
            if (!empty($hair['style'])) $hairParts[] = $hair['style'];
            if (!empty($hair['texture'])) $hairParts[] = $hair['texture'] . ' texture';
            if (!empty($hairParts)) {
                $parts[] = "HAIR (EXACT MATCH): " . implode(', ', $hairParts);
            }
        }

        // Wardrobe section
        $wardrobe = $character['wardrobe'] ?? [];
        if (!empty(array_filter($wardrobe))) {
            $wardrobeParts = [];
            if (!empty($wardrobe['outfit'])) $wardrobeParts[] = $wardrobe['outfit'];
            if (!empty($wardrobe['colors'])) $wardrobeParts[] = "in " . $wardrobe['colors'];
            if (!empty($wardrobe['style'])) $wardrobeParts[] = $wardrobe['style'] . ' style';
            if (!empty($wardrobe['footwear'])) $wardrobeParts[] = $wardrobe['footwear'];
            if (!empty($wardrobeParts)) {
                $parts[] = "WARDROBE (EXACT MATCH): " . implode(', ', $wardrobeParts);
            }
        }

        // Makeup section
        $makeup = $character['makeup'] ?? [];
        if (!empty(array_filter($makeup))) {
            $makeupParts = [];
            if (!empty($makeup['style'])) $makeupParts[] = $makeup['style'];
            if (!empty($makeup['details'])) $makeupParts[] = $makeup['details'];
            if (!empty($makeupParts)) {
                $parts[] = "MAKEUP (EXACT MATCH): " . implode(', ', $makeupParts);
            }
        }

        // Accessories section
        $accessories = $character['accessories'] ?? [];
        if (!empty($accessories)) {
            $accessoryList = is_array($accessories) ? implode(', ', $accessories) : $accessories;
            if (!empty(trim($accessoryList))) {
                $parts[] = "ACCESSORIES (EXACT MATCH): " . $accessoryList;
            }
        }

        // Traits section - personality and physical characteristics
        $traits = $character['traits'] ?? [];
        if (!empty($traits)) {
            $traitList = is_array($traits) ? implode(', ', $traits) : $traits;
            if (!empty(trim($traitList))) {
                $parts[] = "TRAITS/CHARACTERISTICS: " . $traitList;
            }
        }

        // Default expression if specified
        if (!empty($character['defaultExpression'])) {
            $parts[] = "DEFAULT EXPRESSION: " . $character['defaultExpression'];
        }

        if (count($parts) <= 1) {
            return ''; // Only identity, no detailed DNA
        }

        return "CHARACTER DNA - {$name} (MUST MATCH EXACTLY):\n" . implode("\n", $parts);
    }

    // =========================================================================
    // STYLE DNA METHODS - Visual consistency enforcement
    // =========================================================================

    /**
     * Build Style DNA block for visual consistency enforcement.
     * This ensures consistent visual style across all generated images.
     */
    protected function buildStyleDNA(?array $styleBible): string
    {
        if (!$styleBible || !($styleBible['enabled'] ?? false)) {
            return '';
        }

        $parts = [];

        // Visual Style section
        if (!empty($styleBible['style'])) {
            $parts[] = "VISUAL STYLE: " . $styleBible['style'];
        }

        // Color Grading section
        if (!empty($styleBible['colorGrade'])) {
            $parts[] = "COLOR GRADE (CONSISTENT): " . $styleBible['colorGrade'];
        }

        // Lighting section - structured if available
        $lighting = $styleBible['lighting'] ?? [];
        if (!empty(array_filter($lighting))) {
            $lightingParts = [];
            if (!empty($lighting['setup'])) $lightingParts[] = $lighting['setup'];
            if (!empty($lighting['intensity'])) $lightingParts[] = $lighting['intensity'] . ' intensity';
            if (!empty($lighting['type'])) $lightingParts[] = $lighting['type'] . ' lighting';
            if (!empty($lighting['mood'])) $lightingParts[] = $lighting['mood'] . ' mood';
            if (!empty($lightingParts)) {
                $parts[] = "LIGHTING (CONSISTENT): " . implode(', ', $lightingParts);
            }
        }

        // Atmosphere section
        if (!empty($styleBible['atmosphere'])) {
            $parts[] = "ATMOSPHERE: " . $styleBible['atmosphere'];
        }

        // Camera Language section
        if (!empty($styleBible['camera'])) {
            $parts[] = "CAMERA: " . $styleBible['camera'];
        }

        // Visual DNA (quality keywords)
        if (!empty($styleBible['visualDNA'])) {
            $parts[] = "QUALITY MARKERS: " . $styleBible['visualDNA'];
        }

        if (empty($parts)) {
            return '';
        }

        return "STYLE DNA - VISUAL CONSISTENCY ANCHOR:\n" . implode("\n", $parts);
    }

    // =========================================================================
    // LOCATION DNA METHODS - Environmental consistency enforcement
    // =========================================================================

    /**
     * Build Location DNA blocks for environmental consistency.
     * This ensures consistent location appearance across shots.
     */
    protected function buildLocationDNA(?array $locationBible, ?int $sceneIndex): string
    {
        if (!$locationBible || !($locationBible['enabled'] ?? false)) {
            return '';
        }

        $locations = $locationBible['locations'] ?? [];
        if (empty($locations)) {
            return '';
        }

        // Find location for this scene
        $sceneLocation = null;
        foreach ($locations as $location) {
            $appliedScenes = $location['scenes'] ?? $location['appliedScenes'] ?? $location['appearsInScenes'] ?? [];

            // Include if: no scene context, applies to all scenes, or matches this scene
            if ($sceneIndex === null || empty($appliedScenes) || in_array($sceneIndex, $appliedScenes)) {
                $sceneLocation = $location;
                break;
            }
        }

        if (!$sceneLocation) {
            return '';
        }

        return $this->buildSingleLocationDNA($sceneLocation, $sceneIndex);
    }

    /**
     * Build DNA template for a single location.
     */
    protected function buildSingleLocationDNA(array $location, ?int $sceneIndex): string
    {
        $name = $location['name'] ?? 'Location';
        $parts = [];

        // Environment section
        if (!empty($location['description'])) {
            $parts[] = "ENVIRONMENT: " . $location['description'];
        }

        // Type section
        if (!empty($location['type'])) {
            $typeMap = [
                'interior' => 'Interior space',
                'exterior' => 'Exterior/outdoor location',
                'abstract' => 'Abstract/stylized environment',
            ];
            $parts[] = "TYPE: " . ($typeMap[$location['type']] ?? $location['type']);
        }

        // Time and Weather - critical for visual consistency
        $timeWeather = [];
        if (!empty($location['timeOfDay'])) {
            $timeMap = [
                'day' => 'Daytime',
                'night' => 'Nighttime',
                'dawn' => 'Dawn/early morning',
                'dusk' => 'Dusk/twilight',
                'golden_hour' => 'Golden hour',
            ];
            $timeWeather[] = $timeMap[$location['timeOfDay']] ?? $location['timeOfDay'];
        }
        if (!empty($location['weather'])) {
            $weatherMap = [
                'clear' => 'clear sky',
                'cloudy' => 'overcast/cloudy',
                'rainy' => 'rainy conditions',
                'foggy' => 'foggy/misty atmosphere',
                'stormy' => 'stormy weather',
                'snowy' => 'snowy conditions',
            ];
            $timeWeather[] = $weatherMap[$location['weather']] ?? $location['weather'];
        }
        if (!empty($timeWeather)) {
            $parts[] = "TIME/WEATHER (CONSISTENT): " . implode(', ', $timeWeather);
        }

        // Atmosphere
        if (!empty($location['atmosphere'])) {
            $parts[] = "ATMOSPHERE: " . $location['atmosphere'];
        }

        // Mood
        if (!empty($location['mood'])) {
            $parts[] = "MOOD: " . $location['mood'];
        }

        // Lighting style if specified
        if (!empty($location['lightingStyle'])) {
            $parts[] = "LIGHTING: " . $location['lightingStyle'];
        }

        // Scene state if available (support both old and new field names for backwards compatibility)
        if ($sceneIndex !== null && !empty($location['stateChanges'])) {
            foreach ($location['stateChanges'] as $stateChange) {
                // Support both 'sceneIndex' (new) and 'scene' (old) field names
                $changeSceneIndex = $stateChange['sceneIndex'] ?? $stateChange['scene'] ?? null;
                // Support both 'stateDescription' (new) and 'state' (old) field names
                $changeDescription = $stateChange['stateDescription'] ?? $stateChange['state'] ?? '';

                if ($changeSceneIndex === $sceneIndex && !empty($changeDescription)) {
                    $parts[] = "CURRENT STATE: " . $changeDescription;
                    break;
                }
            }
        }

        if (count($parts) <= 1) {
            return '';
        }

        return "LOCATION DNA - {$name} (MAINTAIN CONSISTENCY):\n" . implode("\n", $parts);
    }
}
