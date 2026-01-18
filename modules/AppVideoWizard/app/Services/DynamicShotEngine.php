<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Models\VwSetting;

/**
 * DynamicShotEngine - Content-Driven Shot Decomposition
 *
 * Replaces arbitrary min/max limits with intelligent content analysis.
 * Uses Hollywood pacing formula: Characters + Duration + Dialogue + Action + Mood = Shot Count
 *
 * Industry Research Applied:
 * - Average Shot Length (ASL): Action=4s, Dialogue=6s, Emotional=8s, Establishing=10s
 * - "3's Company" theory: More characters = exponentially more coverage needed
 * - Scene position impacts pacing: Opening needs establishing, Climax needs energy
 */
class DynamicShotEngine
{
    /**
     * Target shot lengths by scene type (in seconds)
     * Based on industry ASL (Average Shot Length) data
     */
    protected const TARGET_SHOT_LENGTHS = [
        'action' => 4,      // Fast cuts for intensity
        'dialogue' => 6,    // Standard coverage timing
        'emotional' => 8,   // Longer takes for impact
        'establishing' => 10, // Scene-setting shots
        'montage' => 3,     // Quick cuts for montage
        'default' => 6,     // Balanced default
    ];

    /**
     * Pacing multipliers - affects target shot length
     */
    protected const PACING_MULTIPLIERS = [
        'fast' => 0.7,        // Shorter shots = more cuts
        'balanced' => 1.0,    // Standard pacing
        'contemplative' => 1.4, // Longer shots = slower pace
    ];

    /**
     * Scene type detector service
     */
    protected ?SceneTypeDetectorService $sceneTypeDetector = null;

    /**
     * Constructor with optional scene type detector injection
     */
    public function __construct(?SceneTypeDetectorService $sceneTypeDetector = null)
    {
        $this->sceneTypeDetector = $sceneTypeDetector ?? new SceneTypeDetectorService();
    }

    /**
     * Analyze scene and return dynamic shot recommendation
     *
     * @param array $scene Scene data (narration, visualDescription, duration, mood, etc.)
     * @param array $context Additional context (pacing, characters, sceneIndex, totalScenes, etc.)
     * @return array Shot recommendation with reasoning
     */
    public function analyzeScene(array $scene, array $context = []): array
    {
        // Extract scene characteristics
        $analysis = $this->extractSceneCharacteristics($scene, $context);

        // Calculate dynamic shot count
        $shotCount = $this->calculateDynamicShotCount($analysis);

        // Generate shot distribution
        $shotDistribution = $this->generateShotDistribution($shotCount, $analysis);

        // Calculate total duration
        $totalDuration = array_sum(array_column($shotDistribution, 'duration'));

        $result = [
            'success' => true,
            'shotCount' => $shotCount,
            'shots' => $shotDistribution,
            'totalDuration' => $totalDuration,
            'analysis' => $analysis,
            'reasoning' => $this->buildReasoning($analysis, $shotCount),
            'source' => 'dynamic_engine',
        ];

        Log::info('DynamicShotEngine: Scene analyzed', [
            'sceneType' => $analysis['sceneType'],
            'shotCount' => $shotCount,
            'characterCount' => $analysis['characterCount'],
            'dialogueDensity' => $analysis['dialogueDensity'],
            'actionIntensity' => $analysis['actionIntensity'],
        ]);

        return $result;
    }

    /**
     * Extract all relevant characteristics from scene content
     */
    protected function extractSceneCharacteristics(array $scene, array $context): array
    {
        $narration = $scene['narration'] ?? '';
        $visualDescription = $scene['visualDescription'] ?? $scene['visual'] ?? '';
        $fullText = $narration . ' ' . $visualDescription;

        // Detect scene type using SceneTypeDetectorService
        $sceneTypeResult = $this->detectSceneType($scene, $context);

        // Calculate scene duration from narration if not explicitly set
        // Average speaking rate is ~150 words per minute (2.5 words per second)
        // Add buffer for visual pacing
        $sceneDuration = $this->calculateSceneDurationFromContent($scene, $context);

        // Extract characteristics
        $characteristics = [
            // Scene identification
            'sceneType' => $sceneTypeResult['sceneType'] ?? 'default',
            'sceneTypeConfidence' => $sceneTypeResult['confidence'] ?? 50,
            'coveragePattern' => $sceneTypeResult['patternSlug'] ?? null,

            // Duration - calculated from content
            'sceneDuration' => $sceneDuration,

            // Character analysis
            'characterCount' => $this->detectCharacterCount($fullText, $context),

            // Content density
            'dialogueDensity' => $this->calculateDialogueDensity($fullText),
            'actionIntensity' => $this->calculateActionIntensity($fullText),
            'emotionalIntensity' => $this->calculateEmotionalIntensity($fullText, $scene),

            // Pacing context
            'pacing' => $context['pacing'] ?? 'balanced',
            'tensionCurve' => $context['tensionCurve'] ?? 'balanced',

            // Narrative position
            'sceneIndex' => $context['sceneIndex'] ?? 0,
            'totalScenes' => $context['totalScenes'] ?? 1,
            'scenePosition' => $this->determineScenePosition($context),

            // Mood
            'mood' => $scene['mood'] ?? $context['mood'] ?? 'neutral',

            // Genre influence
            'genre' => $context['genre'] ?? 'general',
        ];

        return $characteristics;
    }

    /**
     * Calculate scene duration from content (narration word count)
     * Uses speaking rate and visual pacing to determine appropriate duration
     */
    protected function calculateSceneDurationFromContent(array $scene, array $context): int
    {
        // If scene has explicit duration set, use it
        if (!empty($scene['duration']) && $scene['duration'] > 0) {
            return (int) $scene['duration'];
        }

        $narration = $scene['narration'] ?? '';
        $wordCount = str_word_count($narration);

        // Speaking rate: ~2.5 words per second (150 WPM)
        // Add 20% buffer for visual pacing and transitions
        $speakingDuration = $wordCount / 2.5;
        $withBuffer = $speakingDuration * 1.2;

        // Minimum 20 seconds, maximum 120 seconds per scene
        $calculatedDuration = max(20, min(120, (int) ceil($withBuffer)));

        // If narration is very short, use a reasonable default based on context
        if ($wordCount < 10) {
            $calculatedDuration = $context['defaultSceneDuration'] ?? 35;
        }

        Log::debug('DynamicShotEngine: Calculated scene duration from content', [
            'wordCount' => $wordCount,
            'speakingDuration' => $speakingDuration,
            'calculatedDuration' => $calculatedDuration,
        ]);

        return $calculatedDuration;
    }

    /**
     * Detect scene type using the SceneTypeDetectorService
     */
    protected function detectSceneType(array $scene, array $context): array
    {
        try {
            if ($this->sceneTypeDetector) {
                return $this->sceneTypeDetector->detectSceneType($scene, $context);
            }
        } catch (\Throwable $e) {
            Log::warning('DynamicShotEngine: Scene type detection failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to basic detection
        return $this->basicSceneTypeDetection($scene);
    }

    /**
     * Basic scene type detection fallback
     */
    protected function basicSceneTypeDetection(array $scene): array
    {
        $text = strtolower(($scene['narration'] ?? '') . ' ' . ($scene['visualDescription'] ?? ''));

        // Action keywords
        $actionKeywords = ['fight', 'chase', 'run', 'explode', 'crash', 'battle', 'attack', 'escape', 'pursuit'];
        $actionScore = $this->countKeywords($text, $actionKeywords) * 15;

        // Dialogue keywords
        $dialogueKeywords = ['says', 'tells', 'asks', 'replies', 'whispers', 'shouts', 'explains', 'argues', '"'];
        $dialogueScore = $this->countKeywords($text, $dialogueKeywords) * 12;

        // Emotional keywords
        $emotionalKeywords = ['tears', 'cries', 'laughs', 'embraces', 'heartbreak', 'joy', 'grief', 'love', 'fear'];
        $emotionalScore = $this->countKeywords($text, $emotionalKeywords) * 10;

        // Establishing keywords
        $establishingKeywords = ['establishing', 'overview', 'aerial', 'panorama', 'landscape', 'cityscape', 'sunrise', 'sunset'];
        $establishingScore = $this->countKeywords($text, $establishingKeywords) * 20;

        // Determine winner
        $scores = [
            'action' => $actionScore,
            'dialogue' => $dialogueScore,
            'emotional' => $emotionalScore,
            'establishing' => $establishingScore,
        ];

        $maxScore = max($scores);
        $sceneType = $maxScore > 20 ? array_search($maxScore, $scores) : 'default';

        return [
            'sceneType' => $sceneType,
            'confidence' => min(100, $maxScore + 40),
            'patternSlug' => null,
        ];
    }

    /**
     * Count keyword occurrences in text
     */
    protected function countKeywords(string $text, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $keyword) {
            $count += substr_count($text, $keyword);
        }
        return $count;
    }

    /**
     * Detect number of characters in scene
     */
    protected function detectCharacterCount(string $text, array $context): int
    {
        // Use provided character data if available
        if (!empty($context['characters']) && is_array($context['characters'])) {
            return count($context['characters']);
        }

        // Fallback: detect from text patterns
        // Look for character name patterns (capitalized names before dialogue)
        preg_match_all('/\b([A-Z][a-z]+)\s+(says|said|asks|asked|tells|told|replies|replied)\b/', $text, $matches);
        $detectedCharacters = array_unique($matches[1] ?? []);

        // Also check for "he/she/they" pronouns as character indicators
        $pronounCount = preg_match_all('/\b(he|she|they)\s+(says|said|looks|walks|runs)\b/i', $text);

        $count = max(count($detectedCharacters), min(3, $pronounCount));

        // Default to at least 1 if scene has content
        return max(1, $count);
    }

    /**
     * Calculate dialogue density (0-100)
     */
    protected function calculateDialogueDensity(string $text): int
    {
        $text = strtolower($text);

        // Direct speech indicators
        $quoteCount = substr_count($text, '"') / 2; // Pairs of quotes
        $dialogueVerbs = ['says', 'said', 'asks', 'asked', 'tells', 'told', 'replies', 'replied', 'whispers', 'shouts', 'exclaims'];

        $verbCount = 0;
        foreach ($dialogueVerbs as $verb) {
            $verbCount += substr_count($text, $verb);
        }

        // Calculate density based on text length
        $wordCount = str_word_count($text);
        if ($wordCount === 0) return 0;

        $dialogueIndicators = $quoteCount + $verbCount;
        $density = min(100, ($dialogueIndicators / max(1, $wordCount / 20)) * 100);

        return (int) $density;
    }

    /**
     * Calculate action intensity (0-100)
     */
    protected function calculateActionIntensity(string $text): int
    {
        $text = strtolower($text);

        $highActionWords = ['explodes', 'crashes', 'fights', 'battles', 'attacks', 'destroys'];
        $mediumActionWords = ['runs', 'chases', 'jumps', 'climbs', 'escapes', 'pursues', 'races'];
        $lowActionWords = ['walks', 'moves', 'enters', 'exits', 'approaches', 'crosses'];

        $highCount = 0;
        foreach ($highActionWords as $word) {
            $highCount += substr_count($text, $word);
        }

        $mediumCount = 0;
        foreach ($mediumActionWords as $word) {
            $mediumCount += substr_count($text, $word);
        }

        $lowCount = 0;
        foreach ($lowActionWords as $word) {
            $lowCount += substr_count($text, $word);
        }

        // Weighted score
        $score = ($highCount * 30) + ($mediumCount * 15) + ($lowCount * 5);

        return min(100, $score);
    }

    /**
     * Calculate emotional intensity (0-100)
     */
    protected function calculateEmotionalIntensity(string $text, array $scene): int
    {
        $text = strtolower($text);

        // Check scene mood first
        $moodIntensity = [
            'dramatic' => 80,
            'tense' => 70,
            'romantic' => 60,
            'melancholic' => 60,
            'joyful' => 50,
            'suspenseful' => 70,
            'peaceful' => 20,
            'neutral' => 30,
        ];

        $mood = strtolower($scene['mood'] ?? 'neutral');
        $baseMoodScore = $moodIntensity[$mood] ?? 30;

        // Emotional words in text
        $emotionalWords = ['tears', 'cries', 'sobbing', 'laughing', 'screaming', 'embracing',
                          'heartbroken', 'devastated', 'overjoyed', 'terrified', 'furious'];

        $emotionCount = 0;
        foreach ($emotionalWords as $word) {
            $emotionCount += substr_count($text, $word);
        }

        $textScore = min(50, $emotionCount * 15);

        return min(100, $baseMoodScore + $textScore);
    }

    /**
     * Determine narrative position of scene
     */
    protected function determineScenePosition(array $context): string
    {
        $index = $context['sceneIndex'] ?? 0;
        $total = $context['totalScenes'] ?? 1;

        if ($total <= 1) return 'standalone';

        $position = $index / max(1, $total - 1);

        if ($index === 0) return 'opening';
        if ($index === $total - 1) return 'resolution';
        if ($position < 0.25) return 'setup';
        if ($position < 0.5) return 'rising_action';
        if ($position < 0.6) return 'midpoint';
        if ($position < 0.85) return 'climax_buildup';
        return 'climax';
    }

    /**
     * Calculate dynamic shot count based on all characteristics
     *
     * Core Formula:
     * baseCount = sceneDuration / targetShotLength
     * + characterModifier (more characters = more coverage)
     * + dialogueModifier (dialogue needs close-ups)
     * + actionModifier (action needs cuts)
     * + positionModifier (climax = more energy)
     */
    protected function calculateDynamicShotCount(array $analysis): int
    {
        // Get target shot length based on scene type
        $sceneType = $analysis['sceneType'] ?? 'default';
        $baseTargetLength = self::TARGET_SHOT_LENGTHS[$sceneType] ?? self::TARGET_SHOT_LENGTHS['default'];

        // Apply pacing multiplier
        $pacing = $analysis['pacing'] ?? 'balanced';
        $pacingMultiplier = self::PACING_MULTIPLIERS[$pacing] ?? 1.0;
        $targetLength = $baseTargetLength * $pacingMultiplier;

        // Base shot count from duration
        $sceneDuration = $analysis['sceneDuration'];
        $baseCount = ceil($sceneDuration / max(3, $targetLength));

        // Character modifier: "3's company" theory
        // More characters = exponentially more shot variety needed
        $characterCount = $analysis['characterCount'];
        $characterModifier = 0;
        if ($characterCount > 2) {
            $characterModifier = ($characterCount - 2) * 1.5; // +1.5 shots per extra character
        }

        // Dialogue modifier: dialogue needs coverage (close-ups, reactions)
        $dialogueDensity = $analysis['dialogueDensity'];
        $dialogueModifier = 0;
        if ($dialogueDensity > 50) {
            $dialogueModifier = ceil($baseCount * 0.25); // +25% for heavy dialogue
        } elseif ($dialogueDensity > 25) {
            $dialogueModifier = ceil($baseCount * 0.15); // +15% for moderate dialogue
        }

        // Action modifier: action needs more cuts for dynamics
        $actionIntensity = $analysis['actionIntensity'];
        $actionModifier = 0;
        if ($actionIntensity > 60) {
            $actionModifier = ceil($baseCount * 0.30); // +30% for high action
        } elseif ($actionIntensity > 30) {
            $actionModifier = ceil($baseCount * 0.15); // +15% for moderate action
        }

        // Position modifier: climax needs more energy
        $position = $analysis['scenePosition'];
        $positionModifier = 0;
        if (in_array($position, ['climax', 'climax_buildup'])) {
            $positionModifier = ceil($baseCount * 0.20); // +20% for climax
        } elseif ($position === 'opening') {
            $positionModifier = 1; // +1 for establishing shot
        }

        // Tension curve modifier
        $tensionCurve = $analysis['tensionCurve'] ?? 'balanced';
        $tensionModifier = 0;
        if ($tensionCurve === 'rising' || $tensionCurve === 'peak') {
            $tensionModifier = ceil($baseCount * 0.10); // +10% for high tension
        }

        // Calculate final count
        $finalCount = $baseCount + $characterModifier + $dialogueModifier +
                      $actionModifier + $positionModifier + $tensionModifier;

        // Apply reasonable bounds (not arbitrary min/max, but practical limits)
        // Minimum: 2 shots (at least an establishing + something)
        // Maximum: 20 shots (practical limit for any scene)
        $finalCount = max(2, min(20, (int) round($finalCount)));

        Log::debug('DynamicShotEngine: Shot count calculation', [
            'baseCount' => $baseCount,
            'targetLength' => $targetLength,
            'characterModifier' => $characterModifier,
            'dialogueModifier' => $dialogueModifier,
            'actionModifier' => $actionModifier,
            'positionModifier' => $positionModifier,
            'tensionModifier' => $tensionModifier,
            'finalCount' => $finalCount,
        ]);

        return $finalCount;
    }

    /**
     * Generate shot distribution with types and durations
     */
    protected function generateShotDistribution(int $shotCount, array $analysis): array
    {
        $shots = [];
        $sceneType = $analysis['sceneType'];
        $sceneDuration = $analysis['sceneDuration'];
        $position = $analysis['scenePosition'];
        $emotionalIntensity = $analysis['emotionalIntensity'];

        // Determine shot type sequence based on scene type
        $shotSequence = $this->getShotSequenceForSceneType($sceneType, $shotCount, $position);

        // Calculate base duration per shot for pacing reference
        // Minimum 5 since that's the shortest available video duration
        $baseDuration = max(5, (int) ceil($sceneDuration / $shotCount));

        Log::debug('DynamicShotEngine: Generating shot distribution', [
            'shotCount' => $shotCount,
            'sceneDuration' => $sceneDuration,
            'baseDuration' => $baseDuration,
            'sceneType' => $sceneType,
        ]);

        for ($i = 0; $i < $shotCount; $i++) {
            $shotType = $shotSequence[$i] ?? 'medium';
            $duration = $this->getDurationForShotType($shotType, $baseDuration, $analysis);

            $shots[] = [
                'index' => $i,
                'type' => $shotType,
                'duration' => $duration,
                'purpose' => $this->getShotPurpose($shotType, $i, $shotCount),
            ];
        }

        return $shots;
    }

    /**
     * Get shot type sequence based on scene type
     */
    protected function getShotSequenceForSceneType(string $sceneType, int $shotCount, string $position): array
    {
        // Opening scenes always start with establishing
        $startsWithEstablishing = in_array($position, ['opening', 'setup']);

        // Define sequences by scene type
        $sequences = [
            'action' => ['wide', 'medium', 'close-up', 'wide', 'medium', 'close-up', 'detail', 'reaction'],
            'dialogue' => ['wide', 'medium', 'close-up', 'close-up', 'medium', 'reaction', 'close-up', 'medium'],
            'emotional' => ['medium', 'close-up', 'close-up', 'extreme-close-up', 'medium', 'close-up', 'wide', 'close-up'],
            'establishing' => ['extreme-wide', 'wide', 'medium', 'wide', 'medium', 'detail'],
            'montage' => ['wide', 'detail', 'medium', 'detail', 'close-up', 'detail', 'wide', 'detail'],
            'default' => ['establishing', 'wide', 'medium', 'close-up', 'medium', 'close-up', 'detail', 'wide'],
        ];

        $baseSequence = $sequences[$sceneType] ?? $sequences['default'];

        // Build sequence for required shot count
        $result = [];
        for ($i = 0; $i < $shotCount; $i++) {
            if ($i === 0 && $startsWithEstablishing) {
                $result[] = 'establishing';
            } else {
                $adjustedIndex = $startsWithEstablishing ? $i - 1 : $i;
                $result[] = $baseSequence[$adjustedIndex % count($baseSequence)];
            }
        }

        // Last shot often works well as detail or reaction
        if ($shotCount > 2) {
            $lastShotOptions = ['detail', 'reaction', 'close-up'];
            $result[$shotCount - 1] = $lastShotOptions[array_rand($lastShotOptions)];
        }

        return $result;
    }

    /**
     * Get duration for shot type with variation
     * Uses cinematic conventions: establishing shots are longer, reactions are quicker
     */
    protected function getDurationForShotType(string $shotType, int $baseDuration, array $analysis): int
    {
        // Available video generation durations
        $availableDurations = [5, 6, 10];

        // Direct mapping based on shot type for cinematic pacing
        // This ensures variety regardless of baseDuration calculation
        $shotTypeDurations = [
            'establishing' => 10,     // Longer for scene setting
            'extreme-wide' => 10,     // Epic establishing shots
            'wide' => 6,              // Standard wide shots
            'medium' => 6,            // Workhorse shot
            'medium-close' => 6,      // Character focus
            'close-up' => 5,          // Quick emotional impact
            'extreme-close-up' => 5,  // Brief intense moment
            'detail' => 5,            // Quick insert
            'reaction' => 5,          // Quick reaction cuts
            'insert' => 5,            // Brief cutaway
        ];

        // Get base duration from shot type
        $typeDuration = $shotTypeDurations[$shotType] ?? 6;

        // If baseDuration suggests longer shots (scene is slow-paced), allow upgrades
        if ($baseDuration >= 8 && $typeDuration < 10) {
            // Slow pacing - upgrade some shots
            if (in_array($shotType, ['establishing', 'extreme-wide', 'wide', 'medium'])) {
                $typeDuration = 10;
            } elseif (in_array($shotType, ['medium-close', 'close-up'])) {
                $typeDuration = 6;
            }
        }

        // If baseDuration is very short (fast-paced action), keep most shots at 5
        if ($baseDuration <= 4) {
            if (!in_array($shotType, ['establishing', 'extreme-wide'])) {
                $typeDuration = 5;
            } else {
                $typeDuration = 6; // Even establishing shots are shorter in fast pacing
            }
        }

        // Ensure duration is in available options
        if (!in_array($typeDuration, $availableDurations)) {
            // Find closest
            $closest = 6;
            $minDiff = PHP_INT_MAX;
            foreach ($availableDurations as $avail) {
                $diff = abs($typeDuration - $avail);
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $closest = $avail;
                }
            }
            $typeDuration = $closest;
        }

        return $typeDuration;
    }

    /**
     * Get shot purpose description
     */
    protected function getShotPurpose(string $shotType, int $index, int $totalShots): string
    {
        $purposes = [
            'establishing' => 'Set the scene location and atmosphere',
            'extreme-wide' => 'Show the full environment and scale',
            'wide' => 'Establish spatial relationships',
            'medium' => 'Show character interaction and body language',
            'medium-close' => 'Focus on character emotion and dialogue',
            'close-up' => 'Capture emotional intensity and reactions',
            'extreme-close-up' => 'Emphasize critical emotional moment',
            'detail' => 'Highlight important objects or actions',
            'reaction' => 'Show character response to events',
            'insert' => 'Provide visual detail or emphasis',
        ];

        $basePurpose = $purposes[$shotType] ?? 'Continue the narrative';

        // Add position context
        if ($index === 0) {
            return $basePurpose . ' (opening shot)';
        }
        if ($index === $totalShots - 1) {
            return $basePurpose . ' (closing shot)';
        }

        return $basePurpose;
    }

    /**
     * Build human-readable reasoning for the recommendation
     */
    protected function buildReasoning(array $analysis, int $shotCount): string
    {
        $reasons = [];

        // Scene type
        $sceneType = $analysis['sceneType'];
        $confidence = $analysis['sceneTypeConfidence'];
        $reasons[] = ucfirst($sceneType) . " scene detected ({$confidence}% confidence)";

        // Duration impact
        $duration = $analysis['sceneDuration'];
        $reasons[] = "{$duration}s scene duration";

        // Character impact
        $charCount = $analysis['characterCount'];
        if ($charCount > 2) {
            $reasons[] = "{$charCount} characters require varied coverage";
        } elseif ($charCount > 1) {
            $reasons[] = "{$charCount} characters present";
        }

        // Dialogue impact
        $dialogue = $analysis['dialogueDensity'];
        if ($dialogue > 50) {
            $reasons[] = "Heavy dialogue needs close-up coverage";
        } elseif ($dialogue > 25) {
            $reasons[] = "Moderate dialogue content";
        }

        // Action impact
        $action = $analysis['actionIntensity'];
        if ($action > 60) {
            $reasons[] = "High action intensity needs dynamic cuts";
        } elseif ($action > 30) {
            $reasons[] = "Moderate action sequences";
        }

        // Position impact
        $position = str_replace('_', ' ', $analysis['scenePosition']);
        $reasons[] = ucfirst($position) . " position in narrative";

        return implode('. ', $reasons) . ". Recommended {$shotCount} shots for optimal pacing.";
    }

    /**
     * Quick recommendation for UI display
     */
    public function getQuickRecommendation(array $scene, array $context = []): array
    {
        $analysis = $this->extractSceneCharacteristics($scene, $context);
        $shotCount = $this->calculateDynamicShotCount($analysis);

        return [
            'shotCount' => $shotCount,
            'sceneType' => $analysis['sceneType'],
            'confidence' => $analysis['sceneTypeConfidence'],
            'pacing' => $analysis['pacing'],
            'summary' => $this->buildReasoning($analysis, $shotCount),
        ];
    }

    /**
     * Recalculate with adjusted pacing (for UI slider)
     */
    public function recalculateWithPacing(array $scene, array $context, string $newPacing): array
    {
        $context['pacing'] = $newPacing;
        return $this->analyzeScene($scene, $context);
    }
}
