<?php

namespace Modules\AppVideoWizard\Services\Voice;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Services\VoiceRegistryService;
use Modules\AppVideoWizard\Services\SpeechSegment;

/**
 * MultiSpeakerDialogueBuilder - Builds unified multi-speaker dialogue audio (VOC-10).
 *
 * Handles conversations with 2+ characters in single generation by:
 * - Structuring dialogue turns with speaker assignments
 * - Looking up correct voice from registry per speaker
 * - Tracking timing offsets for speaker transitions
 * - Formatting for TTS providers (ElevenLabs, OpenAI)
 *
 * Usage:
 * ```php
 * $builder = app(MultiSpeakerDialogueBuilder::class);
 * $dialogue = $builder->buildDialogue($segments, $characterBible, 'fable');
 * // Returns structured turns with voice assignments and timing
 * ```
 */
class MultiSpeakerDialogueBuilder
{
    /**
     * Default words per minute for duration estimation.
     */
    protected const DEFAULT_WPM = 150;

    /**
     * Pause between speakers in seconds.
     */
    protected const SPEAKER_TRANSITION_PAUSE = 0.3;

    /**
     * Build structured dialogue from raw segments.
     *
     * Takes an array of dialogue segments (text, speaker, emotion) and builds
     * a unified dialogue structure with voice assignments and timing.
     *
     * @param array $segments Raw segments with speaker and text
     * @param array $characterBible Character Bible for voice lookup
     * @param string $narratorVoice Default narrator voice
     * @return array{turns: array, speakers: array, estimatedDuration: float, statistics: array}
     */
    public function buildDialogue(array $segments, array $characterBible, string $narratorVoice): array
    {
        $registry = app(VoiceRegistryService::class);
        $registry->initializeFromCharacterBible($characterBible, $narratorVoice);

        $turns = [];
        $speakersUsed = [];
        $currentTime = 0;

        foreach ($segments as $index => $segment) {
            $segmentData = $this->normalizeSegment($segment, $index);

            // Skip empty text
            if (empty(trim($segmentData['text']))) {
                continue;
            }

            // Resolve voice for speaker
            $voiceId = $this->resolveVoice(
                $segmentData['speaker'],
                $characterBible,
                $narratorVoice,
                $registry
            );

            // Calculate duration
            $duration = $this->estimateDuration([$segmentData], self::DEFAULT_WPM);

            $turn = [
                'id' => $segmentData['id'] ?? 'turn-' . Str::random(6),
                'order' => $index,
                'speaker' => $segmentData['speaker'],
                'text' => $segmentData['text'],
                'voiceId' => $voiceId,
                'emotion' => $segmentData['emotion'] ?? null,
                'startTime' => $currentTime,
                'duration' => $duration,
                'endTime' => $currentTime + $duration,
                'type' => $segmentData['type'] ?? SpeechSegment::TYPE_DIALOGUE,
                'needsLipSync' => $this->needsLipSync($segmentData['type'] ?? SpeechSegment::TYPE_DIALOGUE),
            ];

            $turns[] = $turn;
            $speakersUsed[$segmentData['speaker']] = $voiceId;

            // Advance time with transition pause
            $currentTime += $duration + self::SPEAKER_TRANSITION_PAUSE;
        }

        $totalDuration = empty($turns) ? 0 : end($turns)['endTime'];

        Log::info('MultiSpeakerDialogueBuilder: Dialogue built (VOC-10)', [
            'turnCount' => count($turns),
            'speakerCount' => count($speakersUsed),
            'estimatedDuration' => $totalDuration,
        ]);

        return [
            'turns' => $turns,
            'speakers' => $speakersUsed,
            'estimatedDuration' => $totalDuration,
            'statistics' => [
                'turnCount' => count($turns),
                'speakerCount' => count($speakersUsed),
                'lipSyncTurns' => count(array_filter($turns, fn($t) => $t['needsLipSync'])),
                'voiceoverTurns' => count(array_filter($turns, fn($t) => !$t['needsLipSync'])),
            ],
        ];
    }

    /**
     * Assemble dialogue structure from raw segment data.
     *
     * Simpler version of buildDialogue that just structures segments
     * without full voice registry initialization.
     *
     * @param array $rawSegments Raw segments (arrays or SpeechSegment objects)
     * @param array $characterBible Character Bible for speaker validation
     * @return array{segments: array, speakers: array, wordCount: int}
     */
    public function assembleFromSegments(array $rawSegments, array $characterBible): array
    {
        $segments = [];
        $speakers = [];
        $wordCount = 0;

        foreach ($rawSegments as $index => $raw) {
            $segment = $this->normalizeSegment($raw, $index);

            if (empty(trim($segment['text']))) {
                continue;
            }

            $segments[] = $segment;
            $wordCount += str_word_count($segment['text']);

            if (!empty($segment['speaker'])) {
                $speakerKey = strtoupper(trim($segment['speaker']));
                if (!isset($speakers[$speakerKey])) {
                    $speakers[$speakerKey] = [
                        'name' => $segment['speaker'],
                        'turnCount' => 0,
                        'characterId' => $this->findCharacterId($segment['speaker'], $characterBible),
                    ];
                }
                $speakers[$speakerKey]['turnCount']++;
            }
        }

        return [
            'segments' => $segments,
            'speakers' => array_values($speakers),
            'wordCount' => $wordCount,
        ];
    }

    /**
     * Format dialogue for ElevenLabs Projects API.
     *
     * ElevenLabs Projects API expects a specific format with speaker tags
     * and voice IDs for multi-speaker synthesis.
     *
     * @param array $dialogue Dialogue structure from buildDialogue()
     * @return array{speakers: array, script: string, chapters: array}
     */
    public function formatForElevenLabs(array $dialogue): array
    {
        $speakers = [];
        $chapters = [];
        $script = '';

        // Build speakers list
        foreach ($dialogue['speakers'] as $name => $voiceId) {
            $speakers[] = [
                'name' => $name,
                'voice_id' => $voiceId,
            ];
        }

        // Build script with speaker markers
        foreach ($dialogue['turns'] as $turn) {
            $speakerTag = strtoupper($turn['speaker']);
            $text = $turn['text'];

            // Add speaker tag and text
            $script .= "<speaker name=\"{$speakerTag}\">{$text}</speaker>\n";

            $chapters[] = [
                'speaker' => $turn['speaker'],
                'text' => $turn['text'],
                'voice_id' => $turn['voiceId'],
                'start_time' => $turn['startTime'],
            ];
        }

        return [
            'speakers' => $speakers,
            'script' => trim($script),
            'chapters' => $chapters,
        ];
    }

    /**
     * Estimate total duration for dialogue segments.
     *
     * Calculates based on word count and speaking rate.
     *
     * @param array $segments Segments to estimate
     * @param float $wordsPerMinute Speaking rate (default 150 WPM)
     * @return float Estimated duration in seconds
     */
    public function estimateDuration(array $segments, float $wordsPerMinute = self::DEFAULT_WPM): float
    {
        $totalWords = 0;

        foreach ($segments as $segment) {
            $text = is_array($segment) ? ($segment['text'] ?? '') : ($segment->text ?? '');
            $totalWords += str_word_count($text);
        }

        // Words per minute to seconds
        $duration = ($totalWords / $wordsPerMinute) * 60;

        // Add transition pauses
        $transitionCount = max(0, count($segments) - 1);
        $duration += $transitionCount * self::SPEAKER_TRANSITION_PAUSE;

        return round($duration, 2);
    }

    /**
     * Fallback voice lookup when voice not found in registry.
     *
     * Uses Character Bible first, then falls back to gender-based or
     * hash-based voice assignment for consistency.
     *
     * @param string $name Speaker name
     * @param array $characterBible Character Bible data
     * @param string $narratorVoice Default narrator voice
     * @return string Voice ID
     */
    public function fallbackVoiceLookup(string $name, array $characterBible, string $narratorVoice): string
    {
        $nameUpper = strtoupper(trim($name));

        // Narrator case
        if ($nameUpper === 'NARRATOR') {
            return $narratorVoice;
        }

        // Search in Character Bible
        foreach ($characterBible['characters'] ?? [] as $char) {
            $charName = strtoupper(trim($char['name'] ?? ''));
            if ($charName === $nameUpper) {
                // Extract voice ID from various formats
                if (is_array($char['voice'] ?? null) && !empty($char['voice']['id'])) {
                    return $char['voice']['id'];
                }
                if (is_string($char['voice'] ?? null) && !empty($char['voice'])) {
                    return $char['voice'];
                }

                // Gender-based fallback
                $gender = strtolower($char['gender'] ?? $char['voice']['gender'] ?? '');
                if (str_contains($gender, 'female') || str_contains($gender, 'woman')) {
                    return 'nova';
                } elseif (str_contains($gender, 'male') || str_contains($gender, 'man')) {
                    return 'onyx';
                }
            }
        }

        // Hash-based fallback for consistent voice per character
        $hash = crc32($nameUpper);
        $voices = ['echo', 'onyx', 'nova', 'shimmer', 'alloy'];
        return $voices[$hash % count($voices)];
    }

    /**
     * Normalize segment data to consistent format.
     *
     * @param mixed $segment SpeechSegment object or array
     * @param int $index Segment index for order
     * @return array Normalized segment data
     */
    protected function normalizeSegment($segment, int $index): array
    {
        if ($segment instanceof SpeechSegment) {
            return [
                'id' => $segment->id,
                'speaker' => $segment->speaker ?? 'NARRATOR',
                'text' => $segment->text,
                'type' => $segment->type,
                'emotion' => $segment->emotion,
                'order' => $segment->order ?? $index,
                'characterId' => $segment->characterId,
            ];
        }

        return [
            'id' => $segment['id'] ?? 'seg-' . Str::random(6),
            'speaker' => $segment['speaker'] ?? $segment['name'] ?? 'NARRATOR',
            'text' => $segment['text'] ?? '',
            'type' => $segment['type'] ?? SpeechSegment::TYPE_DIALOGUE,
            'emotion' => $segment['emotion'] ?? null,
            'order' => $segment['order'] ?? $index,
            'characterId' => $segment['characterId'] ?? null,
        ];
    }

    /**
     * Resolve voice ID for a speaker.
     *
     * @param string $speaker Speaker name
     * @param array $characterBible Character Bible data
     * @param string $narratorVoice Default narrator voice
     * @param VoiceRegistryService $registry Voice registry instance
     * @return string Voice ID
     */
    protected function resolveVoice(
        string $speaker,
        array $characterBible,
        string $narratorVoice,
        VoiceRegistryService $registry
    ): string {
        return $registry->getVoiceForCharacter(
            $speaker,
            fn($name) => $this->fallbackVoiceLookup($name, $characterBible, $narratorVoice)
        );
    }

    /**
     * Check if segment type requires lip sync.
     *
     * @param string $type Segment type
     * @return bool True if lip sync needed
     */
    protected function needsLipSync(string $type): bool
    {
        return in_array($type, SpeechSegment::LIP_SYNC_TYPES, true);
    }

    /**
     * Find character ID from Character Bible.
     *
     * @param string $speaker Speaker name
     * @param array $characterBible Character Bible data
     * @return string|null Character ID if found
     */
    protected function findCharacterId(string $speaker, array $characterBible): ?string
    {
        $speakerUpper = strtoupper(trim($speaker));

        foreach ($characterBible['characters'] ?? [] as $index => $char) {
            $charName = strtoupper(trim($char['name'] ?? ''));
            if ($charName === $speakerUpper) {
                return $char['id'] ?? "char-{$index}";
            }
        }

        return null;
    }
}
