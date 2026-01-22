<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Str;

/**
 * SpeechSegment - Data class representing a single speech segment within a scene.
 *
 * A scene can contain multiple speech segments, each with its own type, speaker,
 * and lip-sync requirements. This enables mixed narration/dialogue/internal thoughts
 * within a single scene, like professional Hollywood screenplays.
 *
 * Speech Types:
 * - narrator: External voiceover describing the scene (no lip-sync)
 * - dialogue: Character speaking to others (requires lip-sync)
 * - internal: Character's inner thoughts as V.O. (no lip-sync)
 * - monologue: Character speaking alone/to camera (requires lip-sync)
 */
class SpeechSegment
{
    /**
     * Valid speech types.
     */
    public const TYPE_NARRATOR = 'narrator';
    public const TYPE_DIALOGUE = 'dialogue';
    public const TYPE_INTERNAL = 'internal';
    public const TYPE_MONOLOGUE = 'monologue';

    /**
     * All valid speech types.
     */
    public const VALID_TYPES = [
        self::TYPE_NARRATOR,
        self::TYPE_DIALOGUE,
        self::TYPE_INTERNAL,
        self::TYPE_MONOLOGUE,
    ];

    /**
     * Maximum text length per segment (characters).
     * Longer segments should be split for optimal TTS quality.
     */
    public const MAX_TEXT_LENGTH = 2000;

    /**
     * Maximum segments per scene for performance.
     */
    public const MAX_SEGMENTS_PER_SCENE = 50;

    /**
     * Types that require lip-sync animation.
     */
    public const LIP_SYNC_TYPES = [
        self::TYPE_DIALOGUE,
        self::TYPE_MONOLOGUE,
    ];

    /**
     * Types that are voiceover only (no lip movement).
     */
    public const VOICEOVER_ONLY_TYPES = [
        self::TYPE_NARRATOR,
        self::TYPE_INTERNAL,
    ];

    /**
     * Unique identifier for this segment.
     */
    public string $id;

    /**
     * Speech type: narrator, dialogue, internal, monologue.
     */
    public string $type;

    /**
     * The spoken text content.
     */
    public string $text;

    /**
     * Speaker name (null for narrator).
     */
    public ?string $speaker;

    /**
     * Reference to Character Bible entry ID.
     */
    public ?string $characterId;

    /**
     * TTS voice ID to use for this segment.
     */
    public ?string $voiceId;

    /**
     * Whether this segment requires lip-sync animation.
     * Calculated based on type: dialogue/monologue = true, narrator/internal = false.
     */
    public bool $needsLipSync;

    /**
     * Start time within the scene (seconds). Set after audio generation.
     */
    public ?float $startTime;

    /**
     * Duration of this segment (seconds). Set after audio generation.
     */
    public ?float $duration;

    /**
     * Generated audio URL. Set after TTS generation.
     */
    public ?string $audioUrl;

    /**
     * Order/position within the scene's segments array.
     */
    public int $order;

    /**
     * Optional emotion/tone hint for TTS (e.g., "whispering", "angry").
     */
    public ?string $emotion;

    /**
     * Create a new SpeechSegment instance.
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? 'seg-' . Str::random(8);
        $this->type = $data['type'] ?? self::TYPE_NARRATOR;
        $this->text = $data['text'] ?? '';
        $this->speaker = $data['speaker'] ?? null;
        $this->characterId = $data['characterId'] ?? null;
        $this->voiceId = $data['voiceId'] ?? null;
        $this->needsLipSync = $data['needsLipSync'] ?? $this->calculateNeedsLipSync();
        $this->startTime = $data['startTime'] ?? null;
        $this->duration = $data['duration'] ?? null;
        $this->audioUrl = $data['audioUrl'] ?? null;
        $this->order = $data['order'] ?? 0;
        $this->emotion = $data['emotion'] ?? null;
    }

    /**
     * Create a SpeechSegment from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Create multiple SpeechSegments from an array of arrays.
     */
    public static function fromArrayMultiple(array $segments): array
    {
        return array_map(fn($data) => self::fromArray($data), $segments);
    }

    /**
     * Convert the segment to an array for storage/serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'text' => $this->text,
            'speaker' => $this->speaker,
            'characterId' => $this->characterId,
            'voiceId' => $this->voiceId,
            'needsLipSync' => $this->needsLipSync,
            'startTime' => $this->startTime,
            'duration' => $this->duration,
            'audioUrl' => $this->audioUrl,
            'order' => $this->order,
            'emotion' => $this->emotion,
        ];
    }

    /**
     * Calculate whether this segment needs lip-sync based on its type.
     *
     * Lip-sync required for:
     * - dialogue: Character speaking to others, lips must move
     * - monologue: Character speaking alone, lips must move
     *
     * No lip-sync for:
     * - narrator: External V.O., character not speaking
     * - internal: Character's thoughts, lips don't move
     */
    public function calculateNeedsLipSync(): bool
    {
        return in_array($this->type, self::LIP_SYNC_TYPES, true);
    }

    /**
     * Recalculate and update the needsLipSync flag.
     */
    public function refreshLipSyncFlag(): self
    {
        $this->needsLipSync = $this->calculateNeedsLipSync();
        return $this;
    }

    /**
     * Check if this is a narrator segment.
     */
    public function isNarrator(): bool
    {
        return $this->type === self::TYPE_NARRATOR;
    }

    /**
     * Check if this is a dialogue segment.
     */
    public function isDialogue(): bool
    {
        return $this->type === self::TYPE_DIALOGUE;
    }

    /**
     * Check if this is an internal thought segment.
     */
    public function isInternal(): bool
    {
        return $this->type === self::TYPE_INTERNAL;
    }

    /**
     * Check if this is a monologue segment.
     */
    public function isMonologue(): bool
    {
        return $this->type === self::TYPE_MONOLOGUE;
    }

    /**
     * Check if this segment has a specific speaker (not narrator).
     */
    public function hasSpeaker(): bool
    {
        return !empty($this->speaker) && strtoupper($this->speaker) !== 'NARRATOR';
    }

    /**
     * Get the display label for the segment type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_NARRATOR => 'Narrator',
            self::TYPE_DIALOGUE => 'Dialogue',
            self::TYPE_INTERNAL => 'Internal',
            self::TYPE_MONOLOGUE => 'Monologue',
            default => 'Unknown',
        };
    }

    /**
     * Get the icon/emoji for the segment type.
     */
    public function getTypeIcon(): string
    {
        return match ($this->type) {
            self::TYPE_NARRATOR => "\u{1F399}", // Microphone emoji
            self::TYPE_DIALOGUE => "\u{1F4AC}", // Speech bubble emoji
            self::TYPE_INTERNAL => "\u{1F4AD}", // Thought bubble emoji
            self::TYPE_MONOLOGUE => "\u{1F5E3}", // Speaking head emoji
            default => "\u{2753}", // Question mark emoji
        };
    }

    /**
     * Estimate duration based on word count (150 WPM baseline).
     */
    public function estimateDuration(int $wordsPerMinute = 150): float
    {
        $wordCount = str_word_count($this->text);
        return round(($wordCount / $wordsPerMinute) * 60, 2);
    }

    /**
     * Set the type and automatically refresh the lip-sync flag.
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        $this->refreshLipSyncFlag();
        return $this;
    }

    /**
     * Validate the segment data.
     *
     * @return array List of validation error messages (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        // Text validation
        if (empty(trim($this->text))) {
            $errors[] = 'Segment text cannot be empty';
        } elseif (strlen($this->text) > self::MAX_TEXT_LENGTH) {
            $errors[] = sprintf(
                'Segment text exceeds maximum length (%d characters). Consider splitting into multiple segments.',
                self::MAX_TEXT_LENGTH
            );
        }

        // Type validation
        if (!in_array($this->type, self::VALID_TYPES, true)) {
            $errors[] = sprintf(
                'Invalid speech type: "%s". Valid types are: %s',
                $this->type,
                implode(', ', self::VALID_TYPES)
            );
        }

        // Speaker validation based on type
        if (in_array($this->type, [self::TYPE_DIALOGUE, self::TYPE_MONOLOGUE, self::TYPE_INTERNAL], true)) {
            if (empty($this->speaker)) {
                $errors[] = sprintf('%s segments must have a speaker', ucfirst($this->type));
            } elseif (strlen($this->speaker) > 100) {
                $errors[] = 'Speaker name is too long (max 100 characters)';
            }
        }

        // ID validation
        if (empty($this->id)) {
            $errors[] = 'Segment must have an ID';
        }

        // Timing validation (if set)
        if ($this->startTime !== null && $this->startTime < 0) {
            $errors[] = 'Start time cannot be negative';
        }
        if ($this->duration !== null && $this->duration <= 0) {
            $errors[] = 'Duration must be positive';
        }

        return $errors;
    }

    /**
     * Check if the segment is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Create a narrator segment.
     */
    public static function narrator(string $text, array $options = []): self
    {
        return new self(array_merge($options, [
            'type' => self::TYPE_NARRATOR,
            'text' => $text,
            'speaker' => null,
        ]));
    }

    /**
     * Create a dialogue segment.
     */
    public static function dialogue(string $speaker, string $text, array $options = []): self
    {
        return new self(array_merge($options, [
            'type' => self::TYPE_DIALOGUE,
            'text' => $text,
            'speaker' => $speaker,
        ]));
    }

    /**
     * Create an internal thought segment.
     */
    public static function internal(string $speaker, string $text, array $options = []): self
    {
        return new self(array_merge($options, [
            'type' => self::TYPE_INTERNAL,
            'text' => $text,
            'speaker' => $speaker,
        ]));
    }

    /**
     * Create a monologue segment.
     */
    public static function monologue(string $speaker, string $text, array $options = []): self
    {
        return new self(array_merge($options, [
            'type' => self::TYPE_MONOLOGUE,
            'text' => $text,
            'speaker' => $speaker,
        ]));
    }
}
