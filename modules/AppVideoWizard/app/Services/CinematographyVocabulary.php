<?php

namespace Modules\AppVideoWizard\Services;

/**
 * CinematographyVocabulary
 *
 * Professional cinematography constants for Hollywood-quality prompt generation.
 * Provides lens psychology, lighting ratios, color temperatures, and framing geometry.
 *
 * This vocabulary extends the foundation laid by StructuredPromptBuilderService::CAMERA_PRESETS
 * and LIGHTING_PRESETS with deeper psychological reasoning and quantified specifications.
 */
class CinematographyVocabulary
{
    /**
     * Lens psychology by focal length.
     *
     * Each lens creates distinct psychological effects on the viewer.
     * These are based on established cinematography principles.
     */
    public const LENS_PSYCHOLOGY = [
        '24mm' => [
            'effect' => 'environmental context, dramatic perspective distortion',
            'psychology' => 'creates epic scale, emphasizes space and environment, slight distortion adds tension',
            'use_for' => ['wide', 'establishing', 'action'],
        ],
        '35mm' => [
            'effect' => 'natural perspective, subtle environmental inclusion',
            'psychology' => 'feels documentary-like, honest and authentic, grounds subject in their world',
            'use_for' => ['medium', 'wide', 'documentary'],
        ],
        '50mm' => [
            'effect' => 'closest to human eye, natural proportions',
            'psychology' => 'creates neutral observer perspective, comfortable and familiar, balanced context',
            'use_for' => ['medium', 'medium-close'],
        ],
        '85mm' => [
            'effect' => 'flattering compression, creamy bokeh separation',
            'psychology' => 'creates intimacy, isolates subject from background, evokes emotional connection',
            'use_for' => ['close-up', 'medium-close', 'portrait'],
        ],
        '135mm' => [
            'effect' => 'extreme compression, dramatic background blur',
            'psychology' => 'creates voyeuristic distance, intense subject isolation, heightens emotional impact',
            'use_for' => ['extreme-close-up', 'close-up', 'detail'],
        ],
    ];

    /**
     * Lighting ratios and their emotional qualities.
     *
     * Ratio represents key light to fill light intensity.
     * Higher ratios create more contrast and drama.
     */
    public const LIGHTING_RATIOS = [
        '1:1' => [
            'description' => 'flat even lighting, key equals fill',
            'mood' => 'commercial, beauty, innocence, optimistic',
            'stops_difference' => 0,
        ],
        '2:1' => [
            'description' => 'subtle modeling, gentle shadows',
            'mood' => 'natural, approachable, friendly, soft drama',
            'stops_difference' => 1,
        ],
        '4:1' => [
            'description' => 'pronounced shadows, clear dimensional modeling',
            'mood' => 'dramatic, serious, artistic, tension',
            'stops_difference' => 2,
        ],
        '8:1' => [
            'description' => 'deep shadows, chiaroscuro effect',
            'mood' => 'noir, mysterious, dangerous, high tension',
            'stops_difference' => 3,
        ],
    ];

    /**
     * Color temperatures in Kelvin with conditions.
     *
     * These match real-world lighting conditions for authentic visuals.
     */
    public const COLOR_TEMPERATURES = [
        'candlelight' => [
            'kelvin' => 1900,
            'description' => 'warm intimate candlelight glow',
        ],
        'tungsten' => [
            'kelvin' => 3200,
            'description' => 'classic incandescent warmth',
        ],
        'golden_hour' => [
            'kelvin' => 3500,
            'description' => 'golden hour warmth',
        ],
        'daylight' => [
            'kelvin' => 5600,
            'description' => 'neutral daylight balance',
        ],
        'overcast' => [
            'kelvin' => 6500,
            'description' => 'cool overcast diffusion',
        ],
        'shade' => [
            'kelvin' => 7500,
            'description' => 'open shade blue cast',
        ],
    ];

    /**
     * Framing geometry for compositional descriptions.
     *
     * Provides both rule-of-thirds positions and frame percentage terminology.
     */
    public const FRAMING_GEOMETRY = [
        'thirds' => [
            'left third intersection',
            'right third intersection',
            'upper third',
            'lower third',
            'center frame',
            'upper left intersection',
            'upper right intersection',
            'lower left intersection',
            'lower right intersection',
        ],
        'frame_percentages' => [
            10 => 'distant in frame',
            20 => 'distant subject',
            30 => 'moderate presence',
            40 => 'balanced presence',
            50 => 'prominent subject',
            60 => 'dominant subject',
            70 => 'commanding presence',
            80 => 'filling frame',
            90 => 'near edge-to-edge',
        ],
    ];

    /**
     * Get recommended lens and psychology for a shot type.
     *
     * @param string $shotType The shot type (close-up, medium, wide, etc.)
     * @return array{focal_length: string, effect: string, psychology: string}
     */
    public function getLensForShotType(string $shotType): array
    {
        $shotType = strtolower(trim($shotType));

        // Map shot types to preferred lenses
        $mapping = [
            'extreme-close-up' => '135mm',
            'close-up' => '85mm',
            'medium-close' => '85mm',
            'medium' => '50mm',
            'medium-wide' => '35mm',
            'wide' => '24mm',
            'establishing' => '24mm',
            'portrait' => '85mm',
            'action' => '24mm',
            'documentary' => '35mm',
            'detail' => '135mm',
        ];

        $focalLength = $mapping[$shotType] ?? '50mm';
        $lens = self::LENS_PSYCHOLOGY[$focalLength];

        return [
            'focal_length' => $focalLength,
            'effect' => $lens['effect'],
            'psychology' => $lens['psychology'],
        ];
    }

    /**
     * Get lighting ratio for a mood.
     *
     * @param string $mood The desired mood
     * @return array{ratio: string, description: string, mood: string, stops_difference: int}
     */
    public function getRatioForMood(string $mood): array
    {
        $mood = strtolower(trim($mood));

        // Map moods to ratios based on emotional connotation
        $moodMapping = [
            // 1:1 moods
            'commercial' => '1:1',
            'beauty' => '1:1',
            'innocence' => '1:1',
            'optimistic' => '1:1',
            'bright' => '1:1',
            'happy' => '1:1',

            // 2:1 moods
            'natural' => '2:1',
            'approachable' => '2:1',
            'friendly' => '2:1',
            'soft' => '2:1',
            'gentle' => '2:1',
            'casual' => '2:1',

            // 4:1 moods
            'dramatic' => '4:1',
            'serious' => '4:1',
            'artistic' => '4:1',
            'tension' => '4:1',
            'intense' => '4:1',
            'emotional' => '4:1',

            // 8:1 moods
            'noir' => '8:1',
            'mysterious' => '8:1',
            'dangerous' => '8:1',
            'dark' => '8:1',
            'sinister' => '8:1',
            'thriller' => '8:1',
        ];

        $ratio = $moodMapping[$mood] ?? '2:1';
        $ratioData = self::LIGHTING_RATIOS[$ratio];

        return [
            'ratio' => $ratio,
            'description' => $ratioData['description'],
            'mood' => $ratioData['mood'],
            'stops_difference' => $ratioData['stops_difference'],
        ];
    }

    /**
     * Get temperature description for a lighting condition.
     *
     * @param string $condition The lighting condition
     * @return string Formatted as "5600K daylight balance"
     */
    public function getTemperatureDescription(string $condition): string
    {
        $condition = strtolower(trim($condition));

        if (!isset(self::COLOR_TEMPERATURES[$condition])) {
            $condition = 'daylight';
        }

        $temp = self::COLOR_TEMPERATURES[$condition];

        return "{$temp['kelvin']}K {$temp['description']}";
    }

    /**
     * Build a framing description with percentage and position.
     *
     * @param int $subjectPercentage How much of the frame the subject occupies (10-90)
     * @param string $position Where the subject is positioned
     * @return string Formatted framing description
     */
    public function buildFramingDescription(int $subjectPercentage, string $position): string
    {
        // Clamp percentage to valid range
        $percentage = max(10, min(90, $subjectPercentage));

        // Find the closest percentage description
        $percentages = self::FRAMING_GEOMETRY['frame_percentages'];
        $closestKey = 40; // default
        $closestDiff = PHP_INT_MAX;

        foreach (array_keys($percentages) as $key) {
            $diff = abs($key - $percentage);
            if ($diff < $closestDiff) {
                $closestDiff = $diff;
                $closestKey = $key;
            }
        }

        // Validate position is in our thirds list
        $validPositions = self::FRAMING_GEOMETRY['thirds'];
        $position = strtolower(trim($position));

        if (!in_array($position, array_map('strtolower', $validPositions))) {
            $position = 'center frame';
        }

        return "subject occupies {$percentage}% of frame, positioned at {$position}";
    }

    /**
     * Get all lens focal lengths available.
     *
     * @return array<string>
     */
    public function getAvailableLenses(): array
    {
        return array_keys(self::LENS_PSYCHOLOGY);
    }

    /**
     * Get all lighting ratios available.
     *
     * @return array<string>
     */
    public function getAvailableRatios(): array
    {
        return array_keys(self::LIGHTING_RATIOS);
    }

    /**
     * Get all color temperature conditions.
     *
     * @return array<string>
     */
    public function getAvailableTemperatures(): array
    {
        return array_keys(self::COLOR_TEMPERATURES);
    }

    /**
     * Build a complete lighting description combining ratio and temperature.
     *
     * @param string $ratio The lighting ratio (e.g., '4:1')
     * @param string $condition The color temperature condition
     * @return string Complete lighting description
     */
    public function buildLightingDescription(string $ratio, string $condition): string
    {
        $ratioData = self::LIGHTING_RATIOS[$ratio] ?? self::LIGHTING_RATIOS['2:1'];
        $tempDesc = $this->getTemperatureDescription($condition);

        return "key light at {$tempDesc}, {$ratioData['description']}, fill at -{$ratioData['stops_difference']} stops";
    }
}
