<?php

namespace Modules\AppVideoWizard\App\Services;

use Illuminate\Support\Facades\Log;

/**
 * DialogueSceneDecomposerService
 *
 * Decomposes multi-character dialogue scenes into Shot/Reverse Shot pattern.
 * Each shot features ONE character speaking their line, enabling Multitalk lip-sync.
 *
 * Hollywood Pattern:
 * - Two-shot establishing → CU Character A speaks → CU Character B responds → repeat
 * - Emotional intensity increases toward climax
 * - Reaction shots (silent) add breathing room
 *
 * @see ~/.claude/skills/hollywood-cinematography/references/dialogue-patterns.md
 */
class DialogueSceneDecomposerService
{
    /**
     * Minimum dialogue exchanges to trigger dialogue decomposition.
     */
    protected int $minDialogueExchanges = 2;

    /**
     * Shot types for dialogue scenes, ordered by emotional intensity.
     */
    protected array $dialogueShotTypes = [
        'establishing' => ['intensity' => 0.1, 'forSpeaking' => false],
        'two-shot' => ['intensity' => 0.2, 'forSpeaking' => false],
        'wide' => ['intensity' => 0.25, 'forSpeaking' => true],
        'medium' => ['intensity' => 0.4, 'forSpeaking' => true],
        'over-the-shoulder' => ['intensity' => 0.5, 'forSpeaking' => true],
        'medium-close' => ['intensity' => 0.6, 'forSpeaking' => true],
        'close-up' => ['intensity' => 0.8, 'forSpeaking' => true],
        'extreme-close-up' => ['intensity' => 0.95, 'forSpeaking' => true],
    ];

    /**
     * Emotion keywords mapped to intensity values.
     */
    protected array $emotionIntensityMap = [
        // Low intensity (0.1-0.3)
        'calm' => 0.2,
        'neutral' => 0.25,
        'curious' => 0.3,
        'thoughtful' => 0.3,

        // Medium intensity (0.4-0.6)
        'concerned' => 0.45,
        'questioning' => 0.5,
        'determined' => 0.55,
        'serious' => 0.5,
        'hopeful' => 0.45,

        // High intensity (0.7-0.85)
        'urgent' => 0.75,
        'angry' => 0.8,
        'fearful' => 0.75,
        'desperate' => 0.8,
        'confrontational' => 0.85,

        // Peak intensity (0.9-1.0)
        'revelation' => 0.9,
        'climax' => 0.95,
        'shock' => 0.9,
        'emotional' => 0.85,
    ];

    /**
     * Detect if a scene contains multi-character dialogue.
     *
     * @param array $scene The scene data
     * @return bool True if scene has dialogue between multiple characters
     */
    public function isDialogueScene(array $scene): bool
    {
        $narration = $scene['narration'] ?? '';

        if (empty($narration)) {
            return false;
        }

        // Parse dialogue exchanges
        $exchanges = $this->parseDialogueExchanges($narration);

        // Check if we have multiple speakers with at least 2 exchanges
        $speakers = array_unique(array_column($exchanges, 'speaker'));

        return count($speakers) >= 2 && count($exchanges) >= $this->minDialogueExchanges;
    }

    /**
     * Parse narration text to extract dialogue exchanges.
     *
     * Supports formats:
     * - "SPEAKER: dialogue text"
     * - "SPEAKER NAME: dialogue text"
     * - [NARRATOR] narrator text (excluded from dialogue)
     *
     * @param string $narration The scene narration
     * @return array Array of ['speaker' => string, 'text' => string, 'isNarrator' => bool]
     */
    public function parseDialogueExchanges(string $narration): array
    {
        $exchanges = [];
        $lines = preg_split('/\n+/', trim($narration));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Skip narrator tags
            if (preg_match('/^\[NARRATOR\]/i', $line)) {
                continue;
            }

            // Skip parenthetical directions
            if (preg_match('/^\(.+\)$/', $line)) {
                continue;
            }

            // Match "SPEAKER: text" or "Speaker Name: text"
            if (preg_match('/^([A-Z][A-Za-z\s\-\']+):\s*(.+)$/u', $line, $matches)) {
                $speaker = trim($matches[1]);
                $text = trim($matches[2]);

                // Skip if it's narrator
                if (strtoupper($speaker) === 'NARRATOR') {
                    continue;
                }

                if (!empty($text)) {
                    $exchanges[] = [
                        'speaker' => $speaker,
                        'text' => $text,
                        'isNarrator' => false,
                        'wordCount' => str_word_count($text),
                    ];
                }
            }
        }

        return $exchanges;
    }

    /**
     * Decompose a dialogue scene into Shot/Reverse Shot pattern.
     *
     * @param array $scene The scene data
     * @param array $characterBible The Character Bible data
     * @param array $options Additional options
     * @return array Array of shots following dialogue pattern
     */
    public function decomposeDialogueScene(array $scene, array $characterBible = [], array $options = []): array
    {
        $narration = $scene['narration'] ?? '';
        $exchanges = $this->parseDialogueExchanges($narration);

        if (empty($exchanges)) {
            Log::warning('DialogueDecomposer: No dialogue exchanges found', [
                'sceneId' => $scene['id'] ?? 'unknown',
            ]);
            return [];
        }

        $shots = [];
        $speakers = array_unique(array_column($exchanges, 'speaker'));
        $totalExchanges = count($exchanges);

        // Build character lookup from Character Bible
        $characterLookup = $this->buildCharacterLookup($characterBible, $speakers);

        Log::info('DialogueDecomposer: Decomposing dialogue scene', [
            'sceneId' => $scene['id'] ?? 'unknown',
            'exchangeCount' => $totalExchanges,
            'speakers' => $speakers,
        ]);

        // SHOT 1: Establishing two-shot (if more than 2 exchanges)
        if ($totalExchanges > 2 && ($options['includeEstablishing'] ?? true)) {
            $shots[] = $this->createEstablishingShot($scene, $speakers, $characterLookup);
        }

        // Generate shots for each dialogue exchange
        foreach ($exchanges as $index => $exchange) {
            $position = $this->calculateDialoguePosition($index, $totalExchanges);
            $emotionalIntensity = $this->calculateEmotionalIntensity($exchange, $position, $scene);

            // Create the speaking shot
            $shot = $this->createDialogueShot(
                $exchange,
                $index,
                $emotionalIntensity,
                $position,
                $characterLookup,
                $scene
            );

            $shots[] = $shot;

            // Add reaction shot before climax (optional breathing room)
            if ($this->shouldAddReactionShot($position, $emotionalIntensity, $options)) {
                $nextSpeaker = $this->getNextSpeaker($exchanges, $index);
                if ($nextSpeaker && $nextSpeaker !== $exchange['speaker']) {
                    $shots[] = $this->createReactionShot(
                        $nextSpeaker,
                        $characterLookup,
                        $scene,
                        $emotionalIntensity
                    );
                }
            }
        }

        // Calculate durations based on dialogue length
        $shots = $this->calculateShotDurations($shots);

        // Assign shot indices
        foreach ($shots as $idx => &$shot) {
            $shot['shotIndex'] = $idx;
            $shot['totalShots'] = count($shots);
        }

        Log::info('DialogueDecomposer: Created dialogue shots', [
            'sceneId' => $scene['id'] ?? 'unknown',
            'shotCount' => count($shots),
            'speakingShots' => count(array_filter($shots, fn($s) => $s['useMultitalk'] ?? false)),
        ]);

        return $shots;
    }

    /**
     * Build character lookup from Character Bible.
     */
    protected function buildCharacterLookup(array $characterBible, array $speakers): array
    {
        $lookup = [];
        $characters = $characterBible['characters'] ?? [];

        foreach ($speakers as $speaker) {
            $speakerUpper = strtoupper(trim($speaker));
            $found = false;

            foreach ($characters as $index => $char) {
                $charName = strtoupper(trim($char['name'] ?? ''));
                if ($charName === $speakerUpper || str_contains($charName, $speakerUpper) || str_contains($speakerUpper, $charName)) {
                    $lookup[$speaker] = [
                        'characterIndex' => $index,
                        'name' => $char['name'] ?? $speaker,
                        'voiceId' => $this->getCharacterVoiceId($char),
                        'gender' => $char['gender'] ?? $char['voice']['gender'] ?? 'unknown',
                        'hasReference' => !empty($char['referenceImageBase64']),
                        'characterData' => $char,
                    ];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Create default entry for unknown speaker
                $lookup[$speaker] = [
                    'characterIndex' => null,
                    'name' => $speaker,
                    'voiceId' => $this->inferVoiceFromName($speaker),
                    'gender' => 'unknown',
                    'hasReference' => false,
                    'characterData' => null,
                ];
            }
        }

        return $lookup;
    }

    /**
     * Get voice ID for a character.
     */
    protected function getCharacterVoiceId(array $character): string
    {
        // Check for configured voice
        if (is_array($character['voice'] ?? null) && !empty($character['voice']['id'])) {
            return $character['voice']['id'];
        }
        if (is_string($character['voice'] ?? null) && !empty($character['voice'])) {
            return $character['voice'];
        }

        // Infer from gender
        $gender = strtolower($character['gender'] ?? $character['voice']['gender'] ?? '');
        if (str_contains($gender, 'female') || str_contains($gender, 'woman')) {
            return 'nova';
        } elseif (str_contains($gender, 'male') || str_contains($gender, 'man')) {
            return 'onyx';
        }

        // Default
        return 'echo';
    }

    /**
     * Infer voice from speaker name.
     */
    protected function inferVoiceFromName(string $name): string
    {
        // Common female names
        $femalePatterns = ['sarah', 'emma', 'anna', 'maria', 'lisa', 'nina', 'julia', 'sophia', 'elena', 'maya'];
        $nameLower = strtolower($name);

        foreach ($femalePatterns as $pattern) {
            if (str_contains($nameLower, $pattern)) {
                return 'nova';
            }
        }

        // Use hash for consistent assignment
        $hash = crc32(strtoupper($name));
        $voices = ['echo', 'onyx', 'nova', 'alloy'];
        return $voices[$hash % count($voices)];
    }

    /**
     * Calculate dialogue position (beginning, middle, climax, resolution).
     */
    protected function calculateDialoguePosition(int $index, int $total): string
    {
        $progress = $total > 1 ? $index / ($total - 1) : 0;

        if ($progress < 0.2) {
            return 'opening';
        } elseif ($progress < 0.5) {
            return 'building';
        } elseif ($progress < 0.8) {
            return 'climax';
        } else {
            return 'resolution';
        }
    }

    /**
     * Calculate emotional intensity for a dialogue exchange.
     */
    protected function calculateEmotionalIntensity(array $exchange, string $position, array $scene): float
    {
        // Base intensity from position
        $positionIntensity = match($position) {
            'opening' => 0.3,
            'building' => 0.5,
            'climax' => 0.85,
            'resolution' => 0.5,
            default => 0.5,
        };

        // Adjust based on scene mood
        $mood = strtolower($scene['mood'] ?? 'neutral');
        $moodIntensity = $this->emotionIntensityMap[$mood] ?? 0.5;

        // Adjust based on dialogue content (exclamations, questions increase intensity)
        $text = $exchange['text'] ?? '';
        $textModifier = 0;

        if (str_contains($text, '!')) {
            $textModifier += 0.15;
        }
        if (str_contains($text, '?')) {
            $textModifier += 0.05;
        }
        if (str_contains($text, '...')) {
            $textModifier -= 0.1; // Trailing off = lower intensity
        }

        // Combine factors
        $intensity = ($positionIntensity * 0.5) + ($moodIntensity * 0.3) + ($textModifier);

        return max(0.1, min(1.0, $intensity));
    }

    /**
     * Create establishing two-shot.
     */
    protected function createEstablishingShot(array $scene, array $speakers, array $characterLookup): array
    {
        $characterNames = array_map(fn($s) => $characterLookup[$s]['name'] ?? $s, $speakers);

        return [
            'type' => 'two-shot',
            'purpose' => 'establishing',
            'speakingCharacter' => null,
            'characterIndex' => null,
            'dialogue' => null,
            'monologue' => null,
            'voiceId' => null,
            'useMultitalk' => false,
            'emotionalIntensity' => 0.2,
            'position' => 'opening',
            'description' => 'Two-shot establishing ' . implode(' and ', array_slice($characterNames, 0, 2)),
            'visualPromptAddition' => 'Two characters visible in frame, establishing their spatial relationship',
            'charactersInShot' => array_slice($speakers, 0, 2),
            'duration' => 3, // Establishing shots are typically 2-4 seconds
            'needsLipSync' => false,
        ];
    }

    /**
     * Create a dialogue shot for a speaking character.
     */
    protected function createDialogueShot(
        array $exchange,
        int $index,
        float $emotionalIntensity,
        string $position,
        array $characterLookup,
        array $scene
    ): array {
        $speaker = $exchange['speaker'];
        $charData = $characterLookup[$speaker] ?? [];

        // Select shot type based on emotional intensity
        $shotType = $this->selectShotTypeForIntensity($emotionalIntensity, $position);

        // Build visual description for the speaking character
        $visualPromptAddition = $this->buildSpeakingVisualPrompt($exchange, $shotType, $charData, $position);

        return [
            'type' => $shotType,
            'purpose' => 'dialogue',
            'speakingCharacter' => $speaker,
            'characterIndex' => $charData['characterIndex'] ?? null,
            'dialogue' => $exchange['text'],
            'monologue' => $exchange['text'], // Same as dialogue for Multitalk
            'voiceId' => $charData['voiceId'] ?? 'echo',
            'useMultitalk' => true,
            'emotionalIntensity' => $emotionalIntensity,
            'position' => $position,
            'dialogueIndex' => $index,
            'description' => "{$shotType} of {$speaker} speaking",
            'visualPromptAddition' => $visualPromptAddition,
            'charactersInShot' => [$speaker],
            'wordCount' => $exchange['wordCount'] ?? str_word_count($exchange['text']),
            'needsLipSync' => true,
            'characterData' => $charData['characterData'] ?? null,
        ];
    }

    /**
     * Create a reaction shot (silent, no dialogue).
     */
    protected function createReactionShot(
        string $speaker,
        array $characterLookup,
        array $scene,
        float $precedingIntensity
    ): array {
        $charData = $characterLookup[$speaker] ?? [];

        return [
            'type' => 'close-up',
            'purpose' => 'reaction',
            'speakingCharacter' => $speaker, // Character shown, but not speaking
            'characterIndex' => $charData['characterIndex'] ?? null,
            'dialogue' => null,
            'monologue' => null,
            'voiceId' => null,
            'useMultitalk' => false, // No lip-sync for reaction shots
            'emotionalIntensity' => $precedingIntensity * 0.9, // Slightly lower
            'position' => 'reaction',
            'description' => "Reaction shot of {$speaker}",
            'visualPromptAddition' => "{$speaker} reacting to what was just said, processing the information, subtle facial expression showing their emotional response",
            'charactersInShot' => [$speaker],
            'duration' => 2, // Reaction shots are typically 1-3 seconds
            'needsLipSync' => false,
            'characterData' => $charData['characterData'] ?? null,
        ];
    }

    /**
     * Select shot type based on emotional intensity.
     */
    protected function selectShotTypeForIntensity(float $intensity, string $position): string
    {
        // Climax moments get tighter framing
        if ($position === 'climax' && $intensity >= 0.8) {
            return 'extreme-close-up';
        }

        if ($intensity >= 0.75) {
            return 'close-up';
        } elseif ($intensity >= 0.55) {
            return 'medium-close';
        } elseif ($intensity >= 0.4) {
            return 'over-the-shoulder';
        } elseif ($intensity >= 0.25) {
            return 'medium';
        } else {
            return 'wide';
        }
    }

    /**
     * Build visual prompt addition for speaking character.
     */
    protected function buildSpeakingVisualPrompt(
        array $exchange,
        string $shotType,
        array $charData,
        string $position
    ): string {
        $speaker = $exchange['speaker'];
        $text = $exchange['text'];

        // Determine expression based on dialogue content
        $expression = 'speaking';
        if (str_contains($text, '!')) {
            $expression = 'speaking with intensity';
        } elseif (str_contains($text, '?')) {
            $expression = 'speaking questioningly';
        } elseif (str_contains($text, '...')) {
            $expression = 'speaking thoughtfully';
        }

        // Build prompt based on shot type
        $prompt = match($shotType) {
            'extreme-close-up' => "{$speaker}'s face in extreme close-up, {$expression}, eyes conveying deep emotion, every facial detail visible",
            'close-up' => "{$speaker} in close-up, {$expression}, clear view of facial expression and mouth movement",
            'medium-close' => "{$speaker} from chest up, {$expression}, gesturing naturally while talking",
            'over-the-shoulder' => "Over-the-shoulder shot focused on {$speaker}, {$expression}, slight blur on foreground shoulder",
            'medium' => "{$speaker} from waist up, {$expression}, body language visible during conversation",
            default => "{$speaker} {$expression}, engaged in dialogue",
        };

        // Add position context
        if ($position === 'climax') {
            $prompt .= ', dramatic lighting emphasizing the crucial moment';
        }

        return $prompt;
    }

    /**
     * Determine if a reaction shot should be added.
     */
    protected function shouldAddReactionShot(string $position, float $intensity, array $options): bool
    {
        // Don't add reaction shots if disabled
        if (!($options['includeReactionShots'] ?? true)) {
            return false;
        }

        // Add reaction before climax for dramatic pause
        if ($position === 'building' && $intensity >= 0.6) {
            return true;
        }

        // Randomly add reactions (30% chance) for natural pacing
        if ($position === 'building' && mt_rand(1, 100) <= 30) {
            return true;
        }

        return false;
    }

    /**
     * Get the next speaker in the dialogue sequence.
     */
    protected function getNextSpeaker(array $exchanges, int $currentIndex): ?string
    {
        $nextIndex = $currentIndex + 1;
        return $exchanges[$nextIndex]['speaker'] ?? null;
    }

    /**
     * Calculate shot durations based on dialogue length.
     * Speaking rate: ~150 words per minute = 2.5 words per second
     */
    protected function calculateShotDurations(array $shots): array
    {
        foreach ($shots as &$shot) {
            if ($shot['useMultitalk'] ?? false) {
                // Calculate based on word count (150 wpm = 2.5 words/second)
                $wordCount = $shot['wordCount'] ?? str_word_count($shot['dialogue'] ?? '');
                $speakingDuration = $wordCount / 2.5;

                // Add buffer for natural pacing (0.5s before + 0.5s after)
                $shot['duration'] = max(3, ceil($speakingDuration + 1));
                $shot['calculatedFromWords'] = true;
            } else {
                // Non-speaking shots get default duration
                $shot['duration'] = $shot['duration'] ?? 3;
            }
        }

        return $shots;
    }

    /**
     * Get summary of dialogue decomposition.
     */
    public function getDecompositionSummary(array $shots): array
    {
        $speakingShots = array_filter($shots, fn($s) => $s['useMultitalk'] ?? false);
        $reactionShots = array_filter($shots, fn($s) => ($s['purpose'] ?? '') === 'reaction');
        $speakers = array_unique(array_filter(array_column($shots, 'speakingCharacter')));

        $totalDuration = array_sum(array_column($shots, 'duration'));
        $dialogueDuration = array_sum(array_column($speakingShots, 'duration'));

        return [
            'totalShots' => count($shots),
            'speakingShots' => count($speakingShots),
            'reactionShots' => count($reactionShots),
            'speakers' => $speakers,
            'speakerCount' => count($speakers),
            'totalDuration' => $totalDuration,
            'dialogueDuration' => $dialogueDuration,
            'hasEstablishing' => !empty(array_filter($shots, fn($s) => ($s['purpose'] ?? '') === 'establishing')),
        ];
    }

    /**
     * Validate dialogue scene decomposition.
     */
    public function validateDecomposition(array $shots): array
    {
        $errors = [];
        $warnings = [];

        // Check for empty shots
        foreach ($shots as $idx => $shot) {
            if (($shot['useMultitalk'] ?? false) && empty($shot['dialogue'])) {
                $errors[] = "Shot {$idx} marked for Multitalk but has no dialogue";
            }

            if (($shot['useMultitalk'] ?? false) && empty($shot['voiceId'])) {
                $warnings[] = "Shot {$idx} has no voice ID assigned, will use default";
            }
        }

        // Check for speaker coverage
        $speakingShots = array_filter($shots, fn($s) => $s['useMultitalk'] ?? false);
        if (count($speakingShots) < 2) {
            $warnings[] = "Dialogue scene has fewer than 2 speaking shots";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
