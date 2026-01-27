<?php

namespace Modules\AppVideoWizard\Services;

/**
 * Transition Vocabulary Service
 *
 * Provides vocabulary for shot ending states and transition motivations.
 * VID-07: Transition suggestions describing how shots should end to motivate editorial cuts.
 *
 * NOTE: This is NOT for describing dissolves/wipes in video prompts (those are post-production).
 * This describes how shots END to motivate editorial cuts.
 */
class TransitionVocabulary
{
    /**
     * Transition motivations grouped by cut type.
     * Describes how to end a shot based on the intended transition style.
     */
    public const TRANSITION_MOTIVATIONS = [
        'match_cut_setup' => [
            'shape' => 'ends on circular object or shape that echoes into next shot',
            'motion' => 'ends mid-movement, action continues in next shot',
            'color' => 'ends with dominant color that carries through transition',
            'eyeline' => 'ends with character looking at specific point, next shot reveals object of gaze',
        ],
        'hard_cut_setup' => [
            'action_peak' => 'ends at peak moment of action for impactful cut',
            'look_off' => 'character looks off-frame, motivating cut to what they see',
            'reaction_held' => 'holds on reaction beat for 1-2 seconds, allowing clean cut away',
            'dialogue_end' => 'ends on final word of dialogue, natural pause for cut',
        ],
        'soft_transition_setup' => [
            'settle' => 'action settles to stillness, supporting gentle transition',
            'fade_worthy' => 'ends on contemplative beat, supporting fade to next scene',
            'environmental_rest' => 'camera comes to rest on environmental detail',
        ],
    ];

    /**
     * Shot ending states with placeholder parameters.
     * These describe the visual state at the end of a shot.
     */
    public const SHOT_ENDING_STATES = [
        'look_direction' => 'exits looking {direction}, motivating cut to subject of gaze',
        'mid_motion' => 'ends mid-gesture, next shot can complete action',
        'emotional_peak' => 'holds on emotional high point for {duration} seconds before cut',
        'environmental_pan' => 'camera movement ends revealing new element in environment',
        'settling' => 'subject settles into stillness, breathing visible, ready for transition',
        'departure' => 'subject begins exit from frame, cut before complete departure',
    ];

    /**
     * Suggestions for what the next shot should be based on ending state.
     * Editorial guidance for continuity.
     */
    public const NEXT_SHOT_SUGGESTIONS = [
        'look_direction' => 'Cut to: POV of what character sees, or reaction shot',
        'mid_motion' => 'Cut to: Completion of action from new angle',
        'emotional_peak' => 'Cut to: Reaction shot or environmental breathing room',
        'environmental_pan' => 'Cut to: Detail shot of revealed element',
        'settling' => 'Cut to: Wide establishing or time passage',
        'departure' => 'Cut to: Destination or reaction of those left behind',
    ];

    /**
     * Mood to recommended transition type mapping.
     */
    protected const MOOD_TO_TRANSITION = [
        // Energetic moods favor match cuts
        'energetic' => 'match_cut_setup',
        'exciting' => 'match_cut_setup',
        'action' => 'match_cut_setup',
        'dynamic' => 'match_cut_setup',
        'playful' => 'match_cut_setup',
        'upbeat' => 'match_cut_setup',

        // Tense moods favor hard cuts
        'tense' => 'hard_cut_setup',
        'dramatic' => 'hard_cut_setup',
        'suspenseful' => 'hard_cut_setup',
        'confrontational' => 'hard_cut_setup',
        'urgent' => 'hard_cut_setup',
        'intense' => 'hard_cut_setup',

        // Contemplative moods favor soft transitions
        'contemplative' => 'soft_transition_setup',
        'peaceful' => 'soft_transition_setup',
        'melancholic' => 'soft_transition_setup',
        'reflective' => 'soft_transition_setup',
        'calm' => 'soft_transition_setup',
        'serene' => 'soft_transition_setup',
        'nostalgic' => 'soft_transition_setup',
        'romantic' => 'soft_transition_setup',
    ];

    /**
     * Build a transition setup description for a given type and variant.
     *
     * @param string $transitionType Type: match_cut_setup, hard_cut_setup, soft_transition_setup
     * @param string $variant Variant within the type (e.g., 'shape', 'motion', 'action_peak')
     * @param array $parameters Optional parameters to substitute in the description
     * @return string The transition setup description, or empty string if not found
     */
    public function buildTransitionSetup(string $transitionType, string $variant, array $parameters = []): string
    {
        if (!isset(self::TRANSITION_MOTIVATIONS[$transitionType])) {
            return '';
        }

        if (!isset(self::TRANSITION_MOTIVATIONS[$transitionType][$variant])) {
            return '';
        }

        $description = self::TRANSITION_MOTIVATIONS[$transitionType][$variant];

        // Substitute any parameters
        foreach ($parameters as $key => $value) {
            $description = str_replace('{' . $key . '}', $value, $description);
        }

        return $description;
    }

    /**
     * Suggest a transition type based on the scene mood.
     *
     * @param string $mood The emotional mood of the scene
     * @return string Recommended transition type
     */
    public function suggestTransitionForMood(string $mood): string
    {
        $moodLower = strtolower($mood);

        // Direct match
        if (isset(self::MOOD_TO_TRANSITION[$moodLower])) {
            return self::MOOD_TO_TRANSITION[$moodLower];
        }

        // Check if mood contains known keywords
        foreach (self::MOOD_TO_TRANSITION as $keyword => $transitionType) {
            if (str_contains($moodLower, $keyword)) {
                return $transitionType;
            }
        }

        // Default to hard cut (most versatile)
        return 'hard_cut_setup';
    }

    /**
     * Get editorial guidance for the next shot based on ending state.
     *
     * @param string $endingState The shot ending state
     * @return string Editorial suggestion for next shot, or empty string if not found
     */
    public function getNextShotSuggestion(string $endingState): string
    {
        return self::NEXT_SHOT_SUGGESTIONS[$endingState] ?? '';
    }

    /**
     * Build an ending state description with parameters substituted.
     *
     * @param string $state The ending state key
     * @param array $parameters Parameters to substitute (e.g., 'direction' => 'left', 'duration' => '2')
     * @return string The ending state description with parameters filled in
     */
    public function buildEndingStateDescription(string $state, array $parameters = []): string
    {
        if (!isset(self::SHOT_ENDING_STATES[$state])) {
            return '';
        }

        $description = self::SHOT_ENDING_STATES[$state];

        // Substitute parameters
        foreach ($parameters as $key => $value) {
            $description = str_replace('{' . $key . '}', $value, $description);
        }

        return $description;
    }

    /**
     * Get all available transition types.
     *
     * @return array List of transition type keys
     */
    public function getTransitionTypes(): array
    {
        return array_keys(self::TRANSITION_MOTIVATIONS);
    }

    /**
     * Get all variants for a transition type.
     *
     * @param string $transitionType The transition type
     * @return array List of variant keys, or empty array if type not found
     */
    public function getVariantsForType(string $transitionType): array
    {
        if (!isset(self::TRANSITION_MOTIVATIONS[$transitionType])) {
            return [];
        }

        return array_keys(self::TRANSITION_MOTIVATIONS[$transitionType]);
    }

    /**
     * Get all ending states.
     *
     * @return array Associative array of state key => template description
     */
    public function getEndingStates(): array
    {
        return self::SHOT_ENDING_STATES;
    }

    /**
     * Check if a transition type exists.
     *
     * @param string $transitionType The transition type to check
     * @return bool True if the type exists
     */
    public function hasTransitionType(string $transitionType): bool
    {
        return isset(self::TRANSITION_MOTIVATIONS[$transitionType]);
    }

    /**
     * Check if an ending state exists.
     *
     * @param string $state The ending state to check
     * @return bool True if the state exists
     */
    public function hasEndingState(string $state): bool
    {
        return isset(self::SHOT_ENDING_STATES[$state]);
    }
}
