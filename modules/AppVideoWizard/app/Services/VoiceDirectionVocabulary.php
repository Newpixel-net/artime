<?php

namespace Modules\AppVideoWizard\Services;

/**
 * VoiceDirectionVocabulary
 *
 * Provides emotional direction tags, vocal quality descriptions, and non-verbal sound markers
 * for Hollywood-quality voice prompts. This vocabulary follows the established pattern from
 * CinematographyVocabulary and TransitionVocabulary.
 *
 * Requirements covered:
 * - VOC-01: Emotional direction tags [trembling], [whisper], [voice cracks]
 * - VOC-03: Vocal quality descriptions (gravelly, exhausted, breathless)
 * - VOC-05: Breath and non-verbal sound markers [sighs], [gasps], [stammers]
 *
 * IMPORTANT: Does NOT use FACS AU codes (research confirmed they don't work for TTS).
 * Limit to 1-2 emotional tags per segment to avoid instability.
 */
class VoiceDirectionVocabulary
{
    /**
     * Emotional direction tags with provider-specific mappings.
     *
     * Each emotion maps to:
     * - tag: Generic bracketed direction tag
     * - elevenlabs_tag: ElevenLabs-specific tag format
     * - description: Natural language description of the vocal quality
     *
     * Aligned with CharacterPsychologyService emotions: grief, anxiety, fear, contempt.
     */
    public const EMOTIONAL_DIRECTION = [
        'trembling' => [
            'tag' => '[trembling]',
            'elevenlabs_tag' => '[nervous]',
            'description' => 'voice shaking with suppressed emotion',
        ],
        'whisper' => [
            'tag' => '[whisper]',
            'elevenlabs_tag' => '[whispers]',
            'description' => 'hushed intimate tone',
        ],
        'cracking' => [
            'tag' => '[voice cracks]',
            'elevenlabs_tag' => '[crying]',
            'description' => 'emotional break mid-word',
        ],
        'grief' => [
            'tag' => '[grieving]',
            'elevenlabs_tag' => '[crying]',
            'description' => 'heavy with sorrow, barely held together',
        ],
        'anxiety' => [
            'tag' => '[anxious]',
            'elevenlabs_tag' => '[nervous]',
            'description' => 'tight, rapid, slightly higher pitch',
        ],
        'fear' => [
            'tag' => '[fearful]',
            'elevenlabs_tag' => '[nervous]',
            'description' => 'tremor in voice, catching breath',
        ],
        'contempt' => [
            'tag' => '[dismissive]',
            'elevenlabs_tag' => '[sarcastic]',
            'description' => 'cold, clipped, superior',
        ],
        'joy' => [
            'tag' => '[joyful]',
            'elevenlabs_tag' => '[excited]',
            'description' => 'bright, lifted, warm',
        ],
    ];

    /**
     * Vocal quality descriptions.
     *
     * These are natural language descriptions of vocal textures,
     * NOT bracketed tags. Used to describe how the voice should sound.
     */
    public const VOCAL_QUALITIES = [
        'gravelly' => 'rough, low texture with gravel undertone',
        'exhausted' => 'drained, barely above a whisper, labored breathing',
        'breathless' => 'gasping between words, urgent, out of breath',
        'steely' => 'cold, controlled, emotionless precision',
        'honeyed' => 'smooth, warm, persuasive sweetness',
        'raspy' => 'dry, scratchy, strained throat',
        'resonant' => 'deep, full, commanding presence',
    ];

    /**
     * Non-verbal sound markers.
     *
     * Breath and non-verbal markers for realistic vocal performance.
     * Each sound maps to:
     * - tag: Bracketed marker for inline placement
     * - description: What the sound conveys
     */
    public const NON_VERBAL_SOUNDS = [
        'sigh' => [
            'tag' => '[sighs]',
            'description' => 'audible exhale of resignation or relief',
        ],
        'gasp' => [
            'tag' => '[gasps]',
            'description' => 'sharp inhale of surprise or shock',
        ],
        'stammer' => [
            'tag' => '[stammers]',
            'description' => 'I-I hesitation, nervous repetition',
        ],
        'laugh' => [
            'tag' => '[laughs]',
            'description' => 'audible laughter (match to context)',
        ],
        'sob' => [
            'tag' => '[sobs]',
            'description' => 'crying with audible breath catching',
        ],
        'scoff' => [
            'tag' => '[scoffs]',
            'description' => 'dismissive half-laugh exhale',
        ],
        'hesitate' => [
            'tag' => '[hesitates]',
            'description' => 'pause with visible uncertainty',
        ],
    ];

    /**
     * Get direction data for an emotion.
     *
     * @param string $emotion The emotion key
     * @return array{tag?: string, elevenlabs_tag?: string, description?: string} Direction data or empty array
     */
    public function getDirectionForEmotion(string $emotion): array
    {
        $emotion = strtolower(trim($emotion));

        return self::EMOTIONAL_DIRECTION[$emotion] ?? [];
    }

    /**
     * Get vocal quality description.
     *
     * @param string $quality The vocal quality key
     * @return string The description or empty string if not found
     */
    public function getVocalQuality(string $quality): string
    {
        $quality = strtolower(trim($quality));

        return self::VOCAL_QUALITIES[$quality] ?? '';
    }

    /**
     * Get non-verbal sound data.
     *
     * @param string $sound The sound key
     * @return array{tag?: string, description?: string} Sound data or empty array
     */
    public function getNonVerbalSound(string $sound): array
    {
        $sound = strtolower(trim($sound));

        return self::NON_VERBAL_SOUNDS[$sound] ?? [];
    }

    /**
     * Wrap text with provider-appropriate emotional direction tags.
     *
     * For ElevenLabs: Prepends the provider-specific tag before the text.
     * For OpenAI: Returns text unchanged (OpenAI uses instructions, not inline tags).
     *
     * @param string $text The text to wrap
     * @param string $emotion The emotion to apply
     * @param string $provider The TTS provider (elevenlabs, openai)
     * @return string Text with direction tag, or unchanged if unknown emotion/provider
     */
    public function wrapWithDirection(string $text, string $emotion, string $provider = 'elevenlabs'): string
    {
        $direction = $this->getDirectionForEmotion($emotion);

        if (empty($direction)) {
            return $text;
        }

        $provider = strtolower(trim($provider));

        // OpenAI uses instructions in the system prompt, not inline tags
        if ($provider === 'openai') {
            return $text;
        }

        // ElevenLabs supports inline tags
        if ($provider === 'elevenlabs') {
            return $direction['elevenlabs_tag'] . ' ' . $text;
        }

        // Default: use generic tag
        return $direction['tag'] . ' ' . $text;
    }

    /**
     * Get all available emotion keys.
     *
     * @return array<string>
     */
    public function getAvailableEmotions(): array
    {
        return array_keys(self::EMOTIONAL_DIRECTION);
    }

    /**
     * Get all available vocal quality keys.
     *
     * @return array<string>
     */
    public function getAvailableQualities(): array
    {
        return array_keys(self::VOCAL_QUALITIES);
    }

    /**
     * Get all available non-verbal sound keys.
     *
     * @return array<string>
     */
    public function getAvailableSounds(): array
    {
        return array_keys(self::NON_VERBAL_SOUNDS);
    }

    /**
     * Check if an emotion exists in the vocabulary.
     *
     * @param string $emotion The emotion to check
     * @return bool True if the emotion exists
     */
    public function hasEmotion(string $emotion): bool
    {
        $emotion = strtolower(trim($emotion));

        return isset(self::EMOTIONAL_DIRECTION[$emotion]);
    }

    /**
     * Check if a vocal quality exists in the vocabulary.
     *
     * @param string $quality The quality to check
     * @return bool True if the quality exists
     */
    public function hasVocalQuality(string $quality): bool
    {
        $quality = strtolower(trim($quality));

        return isset(self::VOCAL_QUALITIES[$quality]);
    }

    /**
     * Check if a non-verbal sound exists in the vocabulary.
     *
     * @param string $sound The sound to check
     * @return bool True if the sound exists
     */
    public function hasNonVerbalSound(string $sound): bool
    {
        $sound = strtolower(trim($sound));

        return isset(self::NON_VERBAL_SOUNDS[$sound]);
    }

    /**
     * Build a voice direction instruction string for a provider.
     *
     * This is useful for building system prompts or voice instructions
     * that describe the desired emotional quality.
     *
     * @param string $emotion The primary emotion
     * @param string|null $vocalQuality Optional vocal quality to layer in
     * @return string Natural language direction instruction
     */
    public function buildVoiceInstruction(string $emotion, ?string $vocalQuality = null): string
    {
        $direction = $this->getDirectionForEmotion($emotion);
        $parts = [];

        if (!empty($direction)) {
            $parts[] = $direction['description'];
        }

        if ($vocalQuality !== null) {
            $quality = $this->getVocalQuality($vocalQuality);
            if (!empty($quality)) {
                $parts[] = $quality;
            }
        }

        if (empty($parts)) {
            return '';
        }

        return implode(', ', $parts);
    }
}
