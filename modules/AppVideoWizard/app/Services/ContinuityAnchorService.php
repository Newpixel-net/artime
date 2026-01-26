<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * ContinuityAnchorService
 *
 * Tracks persistent visual details across shots to ensure Hollywood-level continuity.
 * If a character wears "red wool scarf loosely draped over left shoulder" in shot 1,
 * that exact description persists in shots 2-5.
 *
 * Satisfies IMG-09 (continuity anchors) requirement.
 *
 * Anchors are extracted with specific patterns:
 * - Wardrobe: color + material + item + position
 * - Hair: color + texture + length + style
 * - Accessories: material + item + position
 */
class ContinuityAnchorService
{
    /**
     * Anchor priority levels for persistence across shots.
     *
     * - primary: MUST persist - identity-defining features
     * - secondary: SHOULD persist - character continuity
     * - tertiary: MAY persist - scene continuity
     */
    public const ANCHOR_PRIORITY = [
        'primary' => [  // MUST persist - identity-defining
            'face' => 'facial structure, skin tone, distinctive features',
            'hair' => 'color, length, style, texture',
        ],
        'secondary' => [ // SHOULD persist - character continuity
            'wardrobe' => 'outfit, colors, fit, material texture',
            'accessories' => 'jewelry, glasses, watch, visible items',
            'makeup' => 'style, color palette, level',
        ],
        'tertiary' => [ // MAY persist - scene continuity
            'posture' => 'general body position',
            'props' => 'items being held or interacted with',
            'lighting_position' => 'direction of key light on face',
        ],
    ];

    /**
     * Regex patterns for extracting anchor details from prompts.
     */
    protected const EXTRACTION_PATTERNS = [
        'wardrobe' => [
            // Pattern: color + material + item + position
            '/(?:wearing|dressed in|in)\s+(?:a\s+)?([a-z]+(?:\s+[a-z]+)?)\s+(wool|cotton|silk|leather|denim|cashmere|linen|velvet|satin)\s+([a-z]+(?:\s+[a-z]+)?)\s+(draped|wrapped|tied|hanging|over|across|around)[^,.]+/i',
            // Simple: color + item
            '/(?:wearing|dressed in|in)\s+(?:a\s+)?([a-z]+(?:\s+[a-z]+)?)\s+(jacket|coat|shirt|dress|blouse|sweater|scarf|hat|pants|skirt|suit)[^,.]+/i',
        ],
        'hair' => [
            // Pattern: color + texture + length
            '/([a-z]+(?:\s+[a-z]+)?)\s+(?:colored?\s+)?hair\s+(?:in\s+)?([a-z]+(?:\s+[a-z]+)?)/i',
            // Pattern: hair style description
            '/hair\s+(?:styled\s+)?(?:in\s+)?([a-z]+(?:\s+[a-z]+)?)\s*,?\s*([a-z]+(?:\s+[a-z]+)?)?/i',
        ],
        'accessories' => [
            // Pattern: material + item + position
            '/(silver|gold|leather|metal|wooden|beaded|pearl|diamond)\s+([a-z]+(?:\s+[a-z]+)?)\s+(earrings?|necklace|bracelet|watch|ring|bag|belt|strap|pendant)[^,.]+/i',
            // Simple: item
            '/(?:wearing|with)\s+(?:a\s+)?([a-z]+(?:\s+[a-z]+)?)\s+(earrings?|necklace|bracelet|watch|ring|glasses|sunglasses)/i',
        ],
        'lighting_position' => [
            // Pattern: light direction
            '/(soft|hard|warm|cool|dramatic|gentle)\s+(?:key\s+)?light\s+from\s+(camera-left|camera-right|above|behind|below)[^,.]+/i',
            '/(?:lit|lighting)\s+from\s+(?:the\s+)?(left|right|above|behind|front)/i',
        ],
    ];

    /**
     * Stored anchor sets indexed by character ID.
     *
     * @var array<string, array>
     */
    protected array $anchorStorage = [];

    /**
     * Extract anchors from an existing prompt for a character.
     *
     * Looks for specific patterns:
     * - Wardrobe: color + material + item + position
     * - Hair: color + texture + length + style
     * - Accessories: material + item + position
     *
     * @param string $prompt The prompt text to extract from
     * @param string $characterId Character identifier
     * @return array Extracted anchors
     */
    public function extractAnchorsFromPrompt(string $prompt, string $characterId): array
    {
        $anchors = [];

        // Extract wardrobe
        $wardrobe = $this->extractByPatterns($prompt, self::EXTRACTION_PATTERNS['wardrobe']);
        if (!empty($wardrobe)) {
            $anchors['wardrobe'] = $wardrobe;
        }

        // Extract hair
        $hair = $this->extractByPatterns($prompt, self::EXTRACTION_PATTERNS['hair']);
        if (!empty($hair)) {
            $anchors['hair'] = $hair;
        }

        // Extract accessories
        $accessories = $this->extractByPatterns($prompt, self::EXTRACTION_PATTERNS['accessories']);
        if (!empty($accessories)) {
            $anchors['accessories'] = $accessories;
        }

        // Extract lighting position
        $lighting = $this->extractByPatterns($prompt, self::EXTRACTION_PATTERNS['lighting_position']);
        if (!empty($lighting)) {
            $anchors['lighting_position'] = $lighting;
        }

        Log::debug('ContinuityAnchorService: Extracted anchors from prompt', [
            'character_id' => $characterId,
            'anchors_found' => array_keys($anchors),
            'prompt_length' => strlen($prompt),
        ]);

        return $anchors;
    }

    /**
     * Apply anchors to a prompt by injecting a CONTINUITY ANCHORS block.
     *
     * @param string $basePrompt The base prompt to enhance
     * @param array $anchors The anchors to apply
     * @param string $priority Priority level: 'primary', 'secondary', or 'tertiary'
     * @return string Enhanced prompt with continuity block
     */
    public function applyAnchorsToPrompt(string $basePrompt, array $anchors, string $priority = 'secondary'): string
    {
        if (empty($anchors)) {
            return $basePrompt;
        }

        // Filter anchors based on priority level
        $filteredAnchors = $this->filterAnchorsByPriority($anchors, $priority);

        if (empty($filteredAnchors)) {
            return $basePrompt;
        }

        // Build the continuity block
        $anchorLines = [];
        foreach ($filteredAnchors as $category => $description) {
            $label = strtoupper($category);
            $anchorLines[] = "- {$label}: {$description}";
        }

        $continuityBlock = "\n\nCONTINUITY ANCHORS (MUST MATCH previous shots):\n" . implode("\n", $anchorLines);

        Log::debug('ContinuityAnchorService: Applied anchors to prompt', [
            'priority' => $priority,
            'anchor_categories' => array_keys($filteredAnchors),
            'block_length' => strlen($continuityBlock),
        ]);

        return $basePrompt . $continuityBlock;
    }

    /**
     * Get anchors for a character at a specific scene index.
     *
     * @param string $characterId Character identifier
     * @param int $sceneIndex Current scene index
     * @param array $storedAnchors Previously stored anchors (from external storage)
     * @return array Anchors for the character
     */
    public function getAnchorsForCharacter(string $characterId, int $sceneIndex, array $storedAnchors = []): array
    {
        // Check internal storage first
        if (isset($this->anchorStorage[$characterId])) {
            return $this->anchorStorage[$characterId]['anchors'] ?? [];
        }

        // Check provided storage
        if (isset($storedAnchors[$characterId])) {
            return $storedAnchors[$characterId]['anchors'] ?? [];
        }

        // Find the most recent anchors for this character from any scene
        foreach ($storedAnchors as $id => $anchorSet) {
            if ($id === $characterId && isset($anchorSet['anchors'])) {
                // Only use anchors from earlier scenes
                $extractedFrom = $anchorSet['extracted_from_shot'] ?? 0;
                if ($extractedFrom <= $sceneIndex) {
                    return $anchorSet['anchors'];
                }
            }
        }

        return [];
    }

    /**
     * Build anchor description from Character Bible data.
     *
     * @param array $character Character data from Bible
     * @param int $shotIndex The shot index (0 = first shot, establishes anchors)
     * @return string Formatted anchor description block
     */
    public function buildAnchorDescription(array $character, int $shotIndex): string
    {
        $anchors = [];

        // Extract wardrobe anchor
        $wardrobe = $character['wardrobe'] ?? [];
        if (!empty($wardrobe)) {
            $wardrobeParts = [];
            if (!empty($wardrobe['outfit'])) {
                $wardrobeParts[] = $wardrobe['outfit'];
            }
            if (!empty($wardrobe['colors'])) {
                $wardrobeParts[] = "in {$wardrobe['colors']}";
            }
            if (!empty($wardrobe['style'])) {
                $wardrobeParts[] = "{$wardrobe['style']} style";
            }
            if (!empty($wardrobeParts)) {
                $anchors['wardrobe'] = implode(', ', $wardrobeParts);
            }
        }

        // Extract hair anchor
        $hair = $character['hair'] ?? [];
        if (!empty($hair)) {
            $hairParts = [];
            if (!empty($hair['color'])) {
                $hairParts[] = $hair['color'];
            }
            if (!empty($hair['texture'])) {
                $hairParts[] = $hair['texture'];
            }
            if (!empty($hair['length'])) {
                $hairParts[] = $hair['length'];
            }
            if (!empty($hair['style'])) {
                $hairParts[] = $hair['style'];
            }
            if (!empty($hairParts)) {
                $anchors['hair'] = implode(' ', $hairParts) . ' hair';
            }
        }

        // Extract accessories anchor
        $accessories = $character['accessories'] ?? [];
        if (!empty($accessories) && is_array($accessories)) {
            $anchors['accessories'] = implode(', ', $accessories);
        }

        // Extract physical features (primary anchors)
        $physical = $character['physical'] ?? [];
        if (!empty($physical['distinctive_features'])) {
            $anchors['face'] = $physical['distinctive_features'];
        }

        // Store anchors for this character
        $characterId = $character['id'] ?? $character['name'] ?? 'unknown';
        $this->storeAnchors($characterId, $shotIndex, $anchors);

        // Build formatted description
        if (empty($anchors)) {
            return '';
        }

        $lines = [];
        foreach ($anchors as $category => $description) {
            $label = strtoupper($category);
            $lines[] = "{$label}: {$description}";
        }

        return implode("\n", $lines);
    }

    /**
     * Detect conflicts between new and existing anchors.
     *
     * @param array $newAnchors Anchors from current shot
     * @param array $existingAnchors Anchors from previous shots
     * @return array Detected conflicts with details
     */
    public function detectAnchorConflicts(array $newAnchors, array $existingAnchors): array
    {
        $conflicts = [];

        foreach ($newAnchors as $category => $newValue) {
            if (!isset($existingAnchors[$category])) {
                continue;
            }

            $existingValue = $existingAnchors[$category];

            // Normalize for comparison
            $normalizedNew = strtolower(trim($newValue));
            $normalizedExisting = strtolower(trim($existingValue));

            // Skip if identical
            if ($normalizedNew === $normalizedExisting) {
                continue;
            }

            // Check for significant differences
            $similarity = similar_text($normalizedNew, $normalizedExisting, $percent);

            if ($percent < 70) {
                // Significant difference - flag as conflict
                $conflicts[] = [
                    'category' => $category,
                    'existing' => $existingValue,
                    'new' => $newValue,
                    'similarity' => round($percent, 1),
                    'severity' => $this->calculateConflictSeverity($category, $percent),
                ];

                Log::warning('ContinuityAnchorService: Anchor conflict detected', [
                    'category' => $category,
                    'existing' => $existingValue,
                    'new' => $newValue,
                    'similarity_percent' => round($percent, 1),
                ]);
            }
        }

        return $conflicts;
    }

    /**
     * Store anchors internally for a character.
     *
     * @param string $characterId Character identifier
     * @param int $sceneIndex Scene where anchors were extracted
     * @param array $anchors The anchor data
     */
    public function storeAnchors(string $characterId, int $sceneIndex, array $anchors): void
    {
        $this->anchorStorage[$characterId] = [
            'character_id' => $characterId,
            'scene_index' => $sceneIndex,
            'anchors' => $anchors,
            'extracted_from_shot' => $sceneIndex,
            'stored_at' => now()->toIso8601String(),
        ];

        Log::debug('ContinuityAnchorService: Stored anchors', [
            'character_id' => $characterId,
            'scene_index' => $sceneIndex,
            'anchor_count' => count($anchors),
        ]);
    }

    /**
     * Get all stored anchor sets.
     *
     * @return array All stored anchors indexed by character ID
     */
    public function getAllStoredAnchors(): array
    {
        return $this->anchorStorage;
    }

    /**
     * Clear stored anchors (useful for testing or scene resets).
     */
    public function clearStoredAnchors(): void
    {
        $this->anchorStorage = [];
    }

    /**
     * Extract text matching patterns.
     *
     * @param string $text Text to search
     * @param array $patterns Regex patterns to try
     * @return string First match found or empty string
     */
    protected function extractByPatterns(string $text, array $patterns): string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Return the full match, trimmed
                return trim($matches[0]);
            }
        }

        return '';
    }

    /**
     * Filter anchors based on priority level.
     *
     * @param array $anchors All anchors
     * @param string $priority Priority level
     * @return array Filtered anchors
     */
    protected function filterAnchorsByPriority(array $anchors, string $priority): array
    {
        $allowedCategories = [];

        // Primary always included
        $allowedCategories = array_merge($allowedCategories, array_keys(self::ANCHOR_PRIORITY['primary']));

        // Secondary included if priority is secondary or tertiary
        if (in_array($priority, ['secondary', 'tertiary'])) {
            $allowedCategories = array_merge($allowedCategories, array_keys(self::ANCHOR_PRIORITY['secondary']));
        }

        // Tertiary only included if priority is tertiary
        if ($priority === 'tertiary') {
            $allowedCategories = array_merge($allowedCategories, array_keys(self::ANCHOR_PRIORITY['tertiary']));
        }

        return array_intersect_key($anchors, array_flip($allowedCategories));
    }

    /**
     * Calculate conflict severity based on category and similarity.
     *
     * @param string $category Anchor category
     * @param float $similarityPercent Similarity percentage
     * @return string Severity: 'critical', 'high', 'medium', 'low'
     */
    protected function calculateConflictSeverity(string $category, float $similarityPercent): string
    {
        // Primary anchors (face, hair) are more critical
        if (isset(self::ANCHOR_PRIORITY['primary'][$category])) {
            return $similarityPercent < 50 ? 'critical' : 'high';
        }

        // Secondary anchors (wardrobe, accessories, makeup)
        if (isset(self::ANCHOR_PRIORITY['secondary'][$category])) {
            return $similarityPercent < 50 ? 'high' : 'medium';
        }

        // Tertiary anchors (posture, props, lighting)
        return $similarityPercent < 50 ? 'medium' : 'low';
    }
}
