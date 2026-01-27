<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * MicroMovementService
 *
 * Provides micro-movement vocabulary for subtle life motion in video prompts.
 * Breathing, blinking, and small movements prevent the "statue" effect where
 * characters appear frozen or artificial in generated video.
 *
 * Research confirmed: Shot type determines which micro-movements are visible.
 * Close-ups show eye micro-movements; wide shots show none (too far away).
 *
 * VID-06: Video prompts include breath and micro-movements for realism
 */
class MicroMovementService
{
    /**
     * Micro-movement vocabulary for subtle life motion.
     *
     * Each category contains variants for different emotional states:
     * - breathing: Chest/shoulder movement patterns
     * - eyes: Blink patterns and micro-eye movements
     * - head: Subtle head position adjustments
     * - hands: Finger and hand micro-movements
     */
    public const MICRO_MOVEMENT_LIBRARY = [
        'breathing' => [
            'subtle' => 'gentle chest rise and fall with natural breathing rhythm',
            'heavy' => 'deep visible breaths, shoulders rising and falling',
            'held' => 'breath held, chest still, tension visible in shoulders',
            'irregular' => 'uneven breathing pattern, occasional deep inhale',
            'rapid' => 'quick shallow breaths, chest moving rapidly',
        ],
        'eyes' => [
            'natural' => 'natural blink pattern, eyes alive with micro-movements',
            'focused' => 'unblinking focused gaze, slight eye narrowing',
            'shifting' => 'eyes dart briefly to sides, returning to center',
            'softening' => 'eyes soften, gaze becomes warm and open',
            'averted' => 'gaze drops or slides away, reluctant eye contact',
            'widening' => 'eyes gradually widen, pupils dilating',
        ],
        'head' => [
            'settle' => 'subtle head settling, micro-adjustments of position',
            'tilt' => 'slight head tilt indicating thought or curiosity',
            'nod' => 'almost imperceptible nod of acknowledgment',
            'turn' => 'gentle head turn toward point of interest',
            'drop' => 'slight lowering of chin, weighted head position',
            'lift' => 'subtle chin lift, head rising with attention',
        ],
        'hands' => [
            'fidget' => 'fingers tap lightly, hands shift position',
            'grip' => 'fingers tighten slightly on held object',
            'release' => 'hands gradually relax, fingers uncurl',
            'reach' => 'hand moves subtly toward another person',
            'clasp' => 'hands come together, fingers interlacing',
            'still' => 'hands deliberately held motionless, controlled',
        ],
    ];

    /**
     * Shot type to visible micro-movement mapping.
     *
     * Determines which micro-movement categories are visible at each shot scale.
     * Close-ups show face details; wide shots show nothing (too far away).
     */
    public const SHOT_TYPE_MICRO_MAPPING = [
        'extreme-close-up' => ['eyes', 'breathing'],
        'close-up' => ['eyes', 'breathing', 'head'],
        'medium-close-up' => ['breathing', 'head', 'hands'],
        'medium-shot' => ['breathing', 'hands'],
        'medium-wide-shot' => ['hands'],
        'wide-shot' => [],  // No visible micro-movements at this scale
        'extreme-wide-shot' => [],
    ];

    /**
     * Emotion to micro-movement variant mapping.
     *
     * Maps emotional states to appropriate micro-movement variants.
     * E.g., "tense" emotion uses "held" breathing and "grip" hands.
     */
    public const EMOTION_MICRO_VARIANTS = [
        'tense' => [
            'breathing' => 'held',
            'eyes' => 'focused',
            'head' => 'still',
            'hands' => 'grip',
        ],
        'relaxed' => [
            'breathing' => 'subtle',
            'eyes' => 'natural',
            'head' => 'settle',
            'hands' => 'release',
        ],
        'anxious' => [
            'breathing' => 'rapid',
            'eyes' => 'shifting',
            'head' => 'turn',
            'hands' => 'fidget',
        ],
        'curious' => [
            'breathing' => 'subtle',
            'eyes' => 'widening',
            'head' => 'tilt',
            'hands' => 'still',
        ],
        'sad' => [
            'breathing' => 'irregular',
            'eyes' => 'averted',
            'head' => 'drop',
            'hands' => 'clasp',
        ],
        'happy' => [
            'breathing' => 'subtle',
            'eyes' => 'softening',
            'head' => 'lift',
            'hands' => 'release',
        ],
        'angry' => [
            'breathing' => 'heavy',
            'eyes' => 'focused',
            'head' => 'settle',
            'hands' => 'grip',
        ],
        'fearful' => [
            'breathing' => 'rapid',
            'eyes' => 'widening',
            'head' => 'turn',
            'hands' => 'grip',
        ],
        'neutral' => [
            'breathing' => 'subtle',
            'eyes' => 'natural',
            'head' => 'settle',
            'hands' => 'still',
        ],
    ];

    /**
     * Build micro-movement layer for video prompt.
     *
     * Generates micro-movement descriptions appropriate for the shot type
     * and emotional state. Wide shots return empty string (no visible micro-movements).
     *
     * @param string $shotType Shot scale (close-up, medium-shot, wide-shot, etc.)
     * @param string $emotion Emotional state for variant selection
     * @param array $options Optional overrides ['include_categories' => [...], 'intensity' => 'subtle|moderate|intense']
     * @return string Micro-movement prompt section or empty string
     */
    public function buildMicroMovementLayer(string $shotType, string $emotion, array $options = []): string
    {
        $shotType = $this->normalizeShortType($shotType);
        $emotion = strtolower(trim($emotion));

        // Get applicable categories for this shot type
        $categories = $this->getMicroMovementsForShotType($shotType);

        if (empty($categories)) {
            Log::debug('MicroMovementService: No micro-movements for shot type', [
                'shot_type' => $shotType,
            ]);

            return '';
        }

        // Allow category override via options
        if (!empty($options['include_categories'])) {
            $categories = array_intersect($categories, (array) $options['include_categories']);
        }

        if (empty($categories)) {
            return '';
        }

        // Build descriptions for each applicable category
        $descriptions = [];
        foreach ($categories as $category) {
            $variant = $this->selectMicroMovementVariant($category, $emotion);
            if (!empty($variant)) {
                $descriptions[] = $variant;
            }
        }

        if (empty($descriptions)) {
            return '';
        }

        $result = implode(', ', $descriptions);

        Log::debug('MicroMovementService: Built micro-movement layer', [
            'shot_type' => $shotType,
            'emotion' => $emotion,
            'categories' => $categories,
            'description_count' => count($descriptions),
        ]);

        return $result;
    }

    /**
     * Get micro-movement categories visible for a shot type.
     *
     * @param string $shotType Shot scale
     * @return array<string> Array of visible category names
     */
    public function getMicroMovementsForShotType(string $shotType): array
    {
        $shotType = $this->normalizeShortType($shotType);

        if (!isset(self::SHOT_TYPE_MICRO_MAPPING[$shotType])) {
            Log::debug('MicroMovementService: Unknown shot type, using medium-shot defaults', [
                'shot_type' => $shotType,
                'available' => array_keys(self::SHOT_TYPE_MICRO_MAPPING),
            ]);

            // Default to medium-shot if unknown
            return self::SHOT_TYPE_MICRO_MAPPING['medium-shot'] ?? [];
        }

        return self::SHOT_TYPE_MICRO_MAPPING[$shotType];
    }

    /**
     * Select micro-movement variant based on emotion.
     *
     * Returns the description text for the appropriate variant,
     * or the 'natural'/'subtle' default if emotion not mapped.
     *
     * @param string $category Category name (breathing, eyes, head, hands)
     * @param string $emotion Emotional state
     * @return string Description text for the selected variant
     */
    public function selectMicroMovementVariant(string $category, string $emotion): string
    {
        $category = strtolower(trim($category));
        $emotion = strtolower(trim($emotion));

        // Get the variant name for this emotion+category
        $variantName = null;
        if (isset(self::EMOTION_MICRO_VARIANTS[$emotion][$category])) {
            $variantName = self::EMOTION_MICRO_VARIANTS[$emotion][$category];
        }

        // Fall back to neutral emotion mapping
        if ($variantName === null && isset(self::EMOTION_MICRO_VARIANTS['neutral'][$category])) {
            $variantName = self::EMOTION_MICRO_VARIANTS['neutral'][$category];
        }

        // Fall back to first available variant in category
        if ($variantName === null && isset(self::MICRO_MOVEMENT_LIBRARY[$category])) {
            $variantName = array_key_first(self::MICRO_MOVEMENT_LIBRARY[$category]);
        }

        if ($variantName === null) {
            return '';
        }

        // Return the description for the variant
        return self::MICRO_MOVEMENT_LIBRARY[$category][$variantName] ?? '';
    }

    /**
     * Normalize shot type string to standard format.
     *
     * Handles variations like "close up", "closeup", "CLOSE-UP" etc.
     *
     * @param string $shotType Input shot type
     * @return string Normalized shot type (lowercase, hyphenated)
     */
    private function normalizeShortType(string $shotType): string
    {
        $shotType = strtolower(trim($shotType));

        // Common normalizations
        $normalizations = [
            'closeup' => 'close-up',
            'close up' => 'close-up',
            'extreme closeup' => 'extreme-close-up',
            'extreme close up' => 'extreme-close-up',
            'extremecloseup' => 'extreme-close-up',
            'medium closeup' => 'medium-close-up',
            'medium close up' => 'medium-close-up',
            'mediumcloseup' => 'medium-close-up',
            'medium shot' => 'medium-shot',
            'mediumshot' => 'medium-shot',
            'medium wide' => 'medium-wide-shot',
            'medium wide shot' => 'medium-wide-shot',
            'mediumwideshot' => 'medium-wide-shot',
            'wide shot' => 'wide-shot',
            'wideshot' => 'wide-shot',
            'extreme wide' => 'extreme-wide-shot',
            'extreme wide shot' => 'extreme-wide-shot',
            'extremewideshot' => 'extreme-wide-shot',
        ];

        return $normalizations[$shotType] ?? $shotType;
    }

    /**
     * Get all available micro-movement categories.
     *
     * @return array<string>
     */
    public function getAvailableCategories(): array
    {
        return array_keys(self::MICRO_MOVEMENT_LIBRARY);
    }

    /**
     * Get all variants for a category.
     *
     * @param string $category Category name
     * @return array<string, string> Variant name => description mapping
     */
    public function getVariantsForCategory(string $category): array
    {
        $category = strtolower(trim($category));

        return self::MICRO_MOVEMENT_LIBRARY[$category] ?? [];
    }

    /**
     * Get all mapped emotions.
     *
     * @return array<string>
     */
    public function getAvailableEmotions(): array
    {
        return array_keys(self::EMOTION_MICRO_VARIANTS);
    }

    /**
     * Check if a shot type has any visible micro-movements.
     *
     * @param string $shotType Shot scale
     * @return bool True if micro-movements are visible at this scale
     */
    public function hasMicroMovementsForShot(string $shotType): bool
    {
        return !empty($this->getMicroMovementsForShotType($shotType));
    }
}
