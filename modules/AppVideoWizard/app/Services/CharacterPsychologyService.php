<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * CharacterPsychologyService
 *
 * Maps emotional states to physical manifestations for Hollywood-quality image prompts.
 * Emotions are expressed through visible physicality (brow tension, posture, breath)
 * rather than abstract labels ("angry").
 *
 * Research confirmed image models respond to physical descriptions, NOT FACS AU codes.
 *
 * Future integration: Plan 04 will wire buildEnhancedEmotionDescription to merge
 * Character Bible defining_features (scars, facial structure) into psychology output.
 */
class CharacterPsychologyService
{
    /**
     * Physical manifestations for each emotional state.
     *
     * Each emotion maps to four physical components that image models understand:
     * - face: Facial muscle states and expressions
     * - eyes: Eye state, movement, and focus
     * - body: Posture, hand position, and physical tension
     * - breath: Breathing pattern (visible chest/shoulder movement)
     */
    public const EMOTION_MANIFESTATIONS = [
        'suppressed_anger' => [
            'face' => 'jaw muscles visibly tensed, brow lowered creating vertical crease between eyebrows',
            'eyes' => 'narrowed gaze with slight lid tension, focused intensity',
            'body' => 'shoulders rigid and raised, hands clenched at sides or gripping nearby object',
            'breath' => 'controlled shallow breathing, chest barely moving',
        ],
        'anxiety' => [
            'face' => 'micro-tension around mouth, lips pressed slightly together',
            'eyes' => 'rapid small movements, slightly widened, frequent blinking',
            'body' => 'shoulders hunched forward, fingers fidgeting or gripping armrest',
            'breath' => 'shallow and quick, visible chest movement',
        ],
        'hidden_joy' => [
            'face' => 'slight upturn at corner of lips quickly suppressed, dimple forming',
            'eyes' => 'brightening with suppressed sparkle, crow\'s feet beginning to crinkle',
            'body' => 'subtle straightening of posture, weight shifting forward',
            'breath' => 'deeper inhale, slight catch',
        ],
        'grief' => [
            'face' => 'downturned mouth corners, trembling lower lip',
            'eyes' => 'glistening with unshed tears, heavy lids, distant focus',
            'body' => 'collapsed posture, shoulders rounded inward, head bowed',
            'breath' => 'irregular, shuddering inhales',
        ],
        'forced_composure' => [
            'face' => 'deliberately neutral expression, jaw held tight',
            'eyes' => 'slightly too wide, forced steadiness',
            'body' => 'rigid upright posture, hands clasped tightly',
            'breath' => 'consciously even, controlled',
        ],
        'fear' => [
            'face' => 'pale complexion, tension around mouth and jaw',
            'eyes' => 'widened, pupils dilated, whites visible',
            'body' => 'frozen stillness or trembling, pulled back posture',
            'breath' => 'held or rapid shallow gasps',
        ],
        'contempt' => [
            'face' => 'one corner of lip raised asymmetrically, slight nostril flare',
            'eyes' => 'half-lidded, looking down or sideways',
            'body' => 'slight backward lean, chin raised',
            'breath' => 'slow, dismissive exhale through nose',
        ],
        'genuine_happiness' => [
            'face' => 'full smile reaching cheeks, laugh lines visible',
            'eyes' => 'crinkled at corners, bright and engaged',
            'body' => 'open relaxed posture, animated gestures',
            'breath' => 'deep and easy',
        ],
    ];

    /**
     * Intensity modifiers for graduated physical descriptions.
     *
     * Applied to physical manifestations to scale intensity:
     * - subtle: Barely perceptible, micro-expressions
     * - moderate: Clearly visible but not exaggerated
     * - intense: Pronounced, dramatic physicality
     */
    public const INTENSITY_MODIFIERS = [
        'subtle' => ['slightly', 'barely visible', 'hint of', 'trace of'],
        'moderate' => ['noticeably', 'clearly', 'visibly', 'apparent'],
        'intense' => ['deeply', 'dramatically', 'extremely', 'pronounced'],
    ];

    /**
     * Get physical manifestations for an emotion.
     *
     * @param string $emotion Emotion key from EMOTION_MANIFESTATIONS
     * @return array{face?: string, eyes?: string, body?: string, breath?: string} Physical components or empty array
     */
    public function getManifestationsForEmotion(string $emotion): array
    {
        $emotion = strtolower(trim($emotion));

        if (!isset(self::EMOTION_MANIFESTATIONS[$emotion])) {
            Log::debug('CharacterPsychologyService: Unknown emotion requested', [
                'emotion' => $emotion,
                'available' => array_keys(self::EMOTION_MANIFESTATIONS),
            ]);

            return [];
        }

        return self::EMOTION_MANIFESTATIONS[$emotion];
    }

    /**
     * Build a full physical description for an emotion at a given intensity.
     *
     * @param string $emotion Emotion key from EMOTION_MANIFESTATIONS
     * @param string $intensity One of: subtle, moderate, intense
     * @return string Full physical description combining all manifestations
     */
    public function buildEmotionDescription(string $emotion, string $intensity = 'moderate'): string
    {
        $manifestations = $this->getManifestationsForEmotion($emotion);

        if (empty($manifestations)) {
            return '';
        }

        // Get intensity modifier
        $intensity = strtolower(trim($intensity));
        $modifiers = self::INTENSITY_MODIFIERS[$intensity] ?? self::INTENSITY_MODIFIERS['moderate'];
        $modifier = $modifiers[array_rand($modifiers)];

        // Build description from all physical components
        $parts = [];

        if (!empty($manifestations['face'])) {
            $parts[] = "{$modifier} {$manifestations['face']}";
        }

        if (!empty($manifestations['eyes'])) {
            $parts[] = $manifestations['eyes'];
        }

        if (!empty($manifestations['body'])) {
            $parts[] = $manifestations['body'];
        }

        if (!empty($manifestations['breath'])) {
            $parts[] = $manifestations['breath'];
        }

        return implode(', ', $parts);
    }

    /**
     * Build emotion description enhanced with character-specific traits from Bible.
     *
     * @param string $emotion Emotion key from EMOTION_MANIFESTATIONS
     * @param string $intensity One of: subtle, moderate, intense
     * @param array $characterTraits Character-specific traits from Bible ['defining_features' => [...], 'facial_structure' => '...']
     * @return string Full physical description with character traits woven in
     */
    public function buildEnhancedEmotionDescription(string $emotion, string $intensity = 'moderate', array $characterTraits = []): string
    {
        $base = $this->buildEmotionDescription($emotion, $intensity);

        if (empty($base)) {
            return '';
        }

        if (empty($characterTraits)) {
            return $base;
        }

        // Weave in defining features (scar, birthmark, etc.)
        $definingFeatures = $characterTraits['defining_features'] ?? [];
        if (!empty($definingFeatures)) {
            $features = is_array($definingFeatures) ? implode(', ', $definingFeatures) : $definingFeatures;
            $base .= ". Character distinctive features visible: {$features}.";
        }

        // Weave in facial structure if provided
        $facialStructure = $characterTraits['facial_structure'] ?? null;
        if (!empty($facialStructure)) {
            $base .= " Facial structure: {$facialStructure}.";
        }

        return $base;
    }

    /**
     * Build a subtext layer showing surface vs true emotion.
     *
     * Hollywood "body betrays face" pattern: Character's face shows one emotion
     * while their body reveals their true emotional state.
     *
     * @param string $surfaceEmotion The emotion being shown on the face
     * @param string $trueEmotion The actual underlying emotion
     * @param float $leakageLevel How much the true emotion leaks through (0.0 to 1.0)
     * @return array{surface: string, leakage: string, body: string} Three-layer subtext structure
     */
    public function buildSubtextLayer(string $surfaceEmotion, string $trueEmotion, float $leakageLevel = 0.3): array
    {
        $surfaceManifestations = $this->getManifestationsForEmotion($surfaceEmotion);
        $trueManifestations = $this->getManifestationsForEmotion($trueEmotion);

        // Determine leakage intensity based on level
        $leakageIntensity = $leakageLevel < 0.3 ? 'subtle' : ($leakageLevel < 0.7 ? 'moderate' : 'intense');
        $modifiers = self::INTENSITY_MODIFIERS[$leakageIntensity];
        $modifier = $modifiers[array_rand($modifiers)];

        // Surface: Face shows the mask emotion
        $surfaceFace = $surfaceManifestations['face'] ?? 'neutral expression';
        $surface = "Face shows {$surfaceFace}";

        // Leakage: Eyes leak the true emotion
        $trueEyes = $trueManifestations['eyes'] ?? 'unreadable gaze';
        $leakage = "Eyes leak {$trueEmotion} - {$modifier} {$trueEyes}";

        // Body: Body reveals true emotion in posture/hands
        $trueBody = $trueManifestations['body'] ?? 'tense posture';
        $body = "Body reveals {$trueBody}";

        Log::debug('CharacterPsychologyService: Built subtext layer', [
            'surface_emotion' => $surfaceEmotion,
            'true_emotion' => $trueEmotion,
            'leakage_level' => $leakageLevel,
            'leakage_intensity' => $leakageIntensity,
        ]);

        return [
            'surface' => $surface,
            'leakage' => $leakage,
            'body' => $body,
        ];
    }

    /**
     * Get all available emotions.
     *
     * @return array<string>
     */
    public function getAvailableEmotions(): array
    {
        return array_keys(self::EMOTION_MANIFESTATIONS);
    }

    /**
     * Get all available intensity levels.
     *
     * @return array<string>
     */
    public function getAvailableIntensities(): array
    {
        return array_keys(self::INTENSITY_MODIFIERS);
    }
}
