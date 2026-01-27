<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * VoicePacingService
 *
 * Provides pacing infrastructure for Hollywood-quality voice prompt timing.
 * Supports both human-readable notation like `[PAUSE 2.5s]` and SSML-compatible
 * `<break>` tags for providers that support them.
 *
 * VOC-02: Voice prompts include pacing markers with specific timing
 *
 * Research notes:
 * - Too many break tags cause TTS instability; prefer punctuation for natural pauses
 * - Use named types (beat, short, medium, long) that adapt to context
 * - SSML break tags have provider-specific limits (ElevenLabs warns about instability)
 */
class VoicePacingService
{
    /**
     * Named pause types with duration and notation.
     *
     * Each pause type includes:
     * - duration: Time in seconds
     * - notation: Human-readable marker for prompts
     * - ssml: SSML break tag for compatible providers
     * - description: Purpose/usage guidance
     */
    public const PAUSE_TYPES = [
        'beat' => [
            'duration' => 0.5,
            'notation' => '[beat]',
            'ssml' => '<break time="500ms"/>',
            'description' => 'micro-pause for emphasis',
        ],
        'short' => [
            'duration' => 1.0,
            'notation' => '[short pause]',
            'ssml' => '<break time="1s"/>',
            'description' => 'brief pause for breath',
        ],
        'medium' => [
            'duration' => 2.0,
            'notation' => '[pause]',
            'ssml' => '<break time="2s"/>',
            'description' => 'standard dramatic pause',
        ],
        'long' => [
            'duration' => 3.0,
            'notation' => '[long pause]',
            'ssml' => '<break time="3s"/>',
            'description' => 'extended dramatic silence',
        ],
        'breath' => [
            'duration' => 0.3,
            'notation' => '[breath]',
            'ssml' => '<break time="300ms"/>',
            'description' => 'natural breathing pause',
        ],
    ];

    /**
     * Pacing modifiers for rate control.
     *
     * Each modifier includes:
     * - rate_modifier: Multiplier for speaking rate (1.0 = normal)
     * - notation: Human-readable marker
     * - ssml_rate: SSML prosody rate value
     * - description: Effect on delivery
     */
    public const PACING_MODIFIERS = [
        'slow' => [
            'rate_modifier' => 0.85,
            'notation' => '[SLOW]',
            'ssml_rate' => '-15%',
            'description' => 'deliberate, measured delivery',
        ],
        'measured' => [
            'rate_modifier' => 0.9,
            'notation' => '[measured]',
            'ssml_rate' => '-10%',
            'description' => 'careful, thoughtful pace',
        ],
        'normal' => [
            'rate_modifier' => 1.0,
            'notation' => '',
            'ssml_rate' => '0%',
            'description' => 'standard speaking pace',
        ],
        'urgent' => [
            'rate_modifier' => 1.1,
            'notation' => '[urgent]',
            'ssml_rate' => '+10%',
            'description' => 'pressured, time-sensitive',
        ],
        'rushed' => [
            'rate_modifier' => 1.2,
            'notation' => '[rushed]',
            'ssml_rate' => '+20%',
            'description' => 'hurried, breathless delivery',
        ],
    ];

    /**
     * Insert a pause marker with specific timing.
     *
     * @param float $seconds Duration in seconds
     * @return string Formatted pause marker like [PAUSE 2.5s]
     */
    public function insertPauseMarker(float $seconds): string
    {
        // Round to one decimal place
        $rounded = round($seconds, 1);

        // Format without decimal if it's a whole number
        if ($rounded == (int) $rounded) {
            return sprintf('[PAUSE %ds]', (int) $rounded);
        }

        return sprintf('[PAUSE %ss]', $rounded);
    }

    /**
     * Get the notation for a named pause type.
     *
     * @param string $type Pause type name (beat, short, medium, long, breath)
     * @return string Notation string or empty if unknown
     */
    public function getPauseNotation(string $type): string
    {
        $type = strtolower(trim($type));

        return self::PAUSE_TYPES[$type]['notation'] ?? '';
    }

    /**
     * Get the duration in seconds for a named pause type.
     *
     * @param string $type Pause type name
     * @return float Duration in seconds, or 0 if unknown
     */
    public function getPauseDuration(string $type): float
    {
        $type = strtolower(trim($type));

        return self::PAUSE_TYPES[$type]['duration'] ?? 0.0;
    }

    /**
     * Get the notation for a rate modifier.
     *
     * @param string $modifier Modifier name (slow, measured, normal, urgent, rushed)
     * @return string Notation string or empty if unknown/normal
     */
    public function getModifierNotation(string $modifier): string
    {
        $modifier = strtolower(trim($modifier));

        return self::PACING_MODIFIERS[$modifier]['notation'] ?? '';
    }

    /**
     * Convert text with pacing markers to SSML format.
     *
     * Converts:
     * - [PAUSE Xs] → <break time="Xs"/>
     * - [beat], [pause], etc. → <break time="..."/>
     *
     * @param string $textWithMarkers Text containing pacing markers
     * @return string Text with SSML break tags
     */
    public function toSSML(string $textWithMarkers): string
    {
        $result = $textWithMarkers;

        // Convert [PAUSE Xs] format to SSML
        // Matches: [PAUSE 2s], [PAUSE 2.5s], [PAUSE 0.5s]
        $result = preg_replace_callback(
            '/\[PAUSE\s+(\d+(?:\.\d+)?)s\]/i',
            function ($matches) {
                $seconds = (float) $matches[1];

                // Use milliseconds for sub-second precision
                if ($seconds < 1) {
                    $ms = (int) ($seconds * 1000);

                    return sprintf('<break time="%dms"/>', $ms);
                }

                // Use seconds for whole numbers, otherwise milliseconds
                if ($seconds == (int) $seconds) {
                    return sprintf('<break time="%ds"/>', (int) $seconds);
                }

                $ms = (int) ($seconds * 1000);

                return sprintf('<break time="%dms"/>', $ms);
            },
            $result
        );

        // Convert named pause types to SSML
        foreach (self::PAUSE_TYPES as $type => $config) {
            $notation = $config['notation'];
            $ssml = $config['ssml'];

            // Escape special regex characters in notation
            $escapedNotation = preg_quote($notation, '/');
            $result = preg_replace('/' . $escapedNotation . '/i', $ssml, $result);
        }

        Log::debug('VoicePacingService: Converted to SSML', [
            'input_length' => strlen($textWithMarkers),
            'output_length' => strlen($result),
        ]);

        return $result;
    }

    /**
     * Build a pacing instruction combining modifier and optional pause.
     *
     * @param string $modifier Rate modifier (slow, urgent, etc.)
     * @param string|null $pauseBefore Optional pause type to prepend
     * @return string Combined instruction like "[SLOW] [pause]"
     */
    public function buildPacingInstruction(string $modifier, ?string $pauseBefore = null): string
    {
        $parts = [];

        // Add modifier notation
        $modifierNotation = $this->getModifierNotation($modifier);
        if (!empty($modifierNotation)) {
            $parts[] = $modifierNotation;
        }

        // Add pause notation if specified
        if ($pauseBefore !== null) {
            $pauseNotation = $this->getPauseNotation($pauseBefore);
            if (!empty($pauseNotation)) {
                $parts[] = $pauseNotation;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Get all available pause type keys.
     *
     * @return array<string>
     */
    public function getAvailablePauseTypes(): array
    {
        return array_keys(self::PAUSE_TYPES);
    }

    /**
     * Get all available modifier keys.
     *
     * @return array<string>
     */
    public function getAvailableModifiers(): array
    {
        return array_keys(self::PACING_MODIFIERS);
    }

    /**
     * Estimate total pause duration from markers in text.
     *
     * Sums up all pause durations found in the text:
     * - Named pauses: [beat], [pause], [short pause], etc.
     * - Custom pauses: [PAUSE 2.5s]
     *
     * @param string $text Text containing pacing markers
     * @return float Total pause duration in seconds
     */
    public function estimatePacingDuration(string $text): float
    {
        $totalDuration = 0.0;

        // Match [PAUSE Xs] format
        if (preg_match_all('/\[PAUSE\s+(\d+(?:\.\d+)?)s\]/i', $text, $matches)) {
            foreach ($matches[1] as $seconds) {
                $totalDuration += (float) $seconds;
            }
        }

        // Match named pause types
        foreach (self::PAUSE_TYPES as $type => $config) {
            $notation = $config['notation'];
            $duration = $config['duration'];

            // Count occurrences of this notation
            $count = substr_count(strtolower($text), strtolower($notation));
            $totalDuration += $count * $duration;
        }

        Log::debug('VoicePacingService: Estimated pacing duration', [
            'text_length' => strlen($text),
            'total_duration' => $totalDuration,
        ]);

        return $totalDuration;
    }

    /**
     * Get SSML break tag for a named pause type.
     *
     * @param string $type Pause type name
     * @return string SSML break tag or empty string
     */
    public function getPauseSSML(string $type): string
    {
        $type = strtolower(trim($type));

        return self::PAUSE_TYPES[$type]['ssml'] ?? '';
    }

    /**
     * Get the rate modifier value for a modifier type.
     *
     * @param string $modifier Modifier name
     * @return float Rate modifier (1.0 = normal)
     */
    public function getRateModifier(string $modifier): float
    {
        $modifier = strtolower(trim($modifier));

        return self::PACING_MODIFIERS[$modifier]['rate_modifier'] ?? 1.0;
    }

    /**
     * Get SSML rate value for a modifier.
     *
     * @param string $modifier Modifier name
     * @return string SSML rate value like "-15%" or "+10%"
     */
    public function getModifierSSMLRate(string $modifier): string
    {
        $modifier = strtolower(trim($modifier));

        return self::PACING_MODIFIERS[$modifier]['ssml_rate'] ?? '0%';
    }
}
