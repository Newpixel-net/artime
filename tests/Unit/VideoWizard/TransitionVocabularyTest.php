<?php

use Modules\AppVideoWizard\Services\TransitionVocabulary;

beforeEach(function () {
    $this->service = new TransitionVocabulary();
});

describe('TransitionVocabulary', function () {

    describe('TRANSITION_MOTIVATIONS constant', function () {

        test('contains match_cut_setup, hard_cut_setup, and soft_transition_setup types', function () {
            $types = array_keys(TransitionVocabulary::TRANSITION_MOTIVATIONS);

            expect($types)->toContain('match_cut_setup');
            expect($types)->toContain('hard_cut_setup');
            expect($types)->toContain('soft_transition_setup');
        });

        test('match_cut_setup has shape, motion, color, and eyeline variants', function () {
            $variants = array_keys(TransitionVocabulary::TRANSITION_MOTIVATIONS['match_cut_setup']);

            expect($variants)->toContain('shape');
            expect($variants)->toContain('motion');
            expect($variants)->toContain('color');
            expect($variants)->toContain('eyeline');
        });

        test('hard_cut_setup has action_peak, look_off, reaction_held, and dialogue_end variants', function () {
            $variants = array_keys(TransitionVocabulary::TRANSITION_MOTIVATIONS['hard_cut_setup']);

            expect($variants)->toContain('action_peak');
            expect($variants)->toContain('look_off');
            expect($variants)->toContain('reaction_held');
            expect($variants)->toContain('dialogue_end');
        });

    });

    describe('SHOT_ENDING_STATES constant', function () {

        test('contains expected ending state keys', function () {
            $states = array_keys(TransitionVocabulary::SHOT_ENDING_STATES);

            expect($states)->toContain('look_direction');
            expect($states)->toContain('mid_motion');
            expect($states)->toContain('emotional_peak');
            expect($states)->toContain('settling');
            expect($states)->toContain('departure');
        });

    });

    describe('buildTransitionSetup', function () {

        test('returns correct description for match cut shape variant', function () {
            $result = $this->service->buildTransitionSetup('match_cut_setup', 'shape');

            expect($result)->toBe('ends on circular object or shape that echoes into next shot');
        });

        test('returns correct description for match cut motion variant', function () {
            $result = $this->service->buildTransitionSetup('match_cut_setup', 'motion');

            expect($result)->toBe('ends mid-movement, action continues in next shot');
        });

        test('returns correct description for hard cut action_peak variant', function () {
            $result = $this->service->buildTransitionSetup('hard_cut_setup', 'action_peak');

            expect($result)->toBe('ends at peak moment of action for impactful cut');
        });

        test('returns correct description for hard cut look_off variant', function () {
            $result = $this->service->buildTransitionSetup('hard_cut_setup', 'look_off');

            expect($result)->toBe('character looks off-frame, motivating cut to what they see');
        });

        test('returns correct description for soft transition settle variant', function () {
            $result = $this->service->buildTransitionSetup('soft_transition_setup', 'settle');

            expect($result)->toBe('action settles to stillness, supporting gentle transition');
        });

        test('returns empty string for unknown transition type', function () {
            $result = $this->service->buildTransitionSetup('unknown_type', 'shape');

            expect($result)->toBe('');
        });

        test('returns empty string for unknown variant', function () {
            $result = $this->service->buildTransitionSetup('match_cut_setup', 'unknown_variant');

            expect($result)->toBe('');
        });

    });

    describe('suggestTransitionForMood', function () {

        test('returns match_cut_setup for energetic mood', function () {
            $result = $this->service->suggestTransitionForMood('energetic');

            expect($result)->toBe('match_cut_setup');
        });

        test('returns match_cut_setup for action mood', function () {
            $result = $this->service->suggestTransitionForMood('action');

            expect($result)->toBe('match_cut_setup');
        });

        test('returns hard_cut_setup for tense mood', function () {
            $result = $this->service->suggestTransitionForMood('tense');

            expect($result)->toBe('hard_cut_setup');
        });

        test('returns hard_cut_setup for dramatic mood', function () {
            $result = $this->service->suggestTransitionForMood('dramatic');

            expect($result)->toBe('hard_cut_setup');
        });

        test('returns soft_transition_setup for contemplative mood', function () {
            $result = $this->service->suggestTransitionForMood('contemplative');

            expect($result)->toBe('soft_transition_setup');
        });

        test('returns soft_transition_setup for peaceful mood', function () {
            $result = $this->service->suggestTransitionForMood('peaceful');

            expect($result)->toBe('soft_transition_setup');
        });

        test('returns hard_cut_setup for unknown mood as default', function () {
            $result = $this->service->suggestTransitionForMood('unknown_mood');

            expect($result)->toBe('hard_cut_setup');
        });

        test('handles case insensitivity', function () {
            $result1 = $this->service->suggestTransitionForMood('ENERGETIC');
            $result2 = $this->service->suggestTransitionForMood('Contemplative');

            expect($result1)->toBe('match_cut_setup');
            expect($result2)->toBe('soft_transition_setup');
        });

    });

    describe('getNextShotSuggestion', function () {

        test('returns POV suggestion for look_direction', function () {
            $result = $this->service->getNextShotSuggestion('look_direction');

            expect($result)->toBe('Cut to: POV of what character sees, or reaction shot');
        });

        test('returns completion suggestion for mid_motion', function () {
            $result = $this->service->getNextShotSuggestion('mid_motion');

            expect($result)->toBe('Cut to: Completion of action from new angle');
        });

        test('returns reaction suggestion for emotional_peak', function () {
            $result = $this->service->getNextShotSuggestion('emotional_peak');

            expect($result)->toBe('Cut to: Reaction shot or environmental breathing room');
        });

        test('returns destination suggestion for departure', function () {
            $result = $this->service->getNextShotSuggestion('departure');

            expect($result)->toBe('Cut to: Destination or reaction of those left behind');
        });

        test('returns empty string for unknown state', function () {
            $result = $this->service->getNextShotSuggestion('unknown_state');

            expect($result)->toBe('');
        });

    });

    describe('buildEndingStateDescription', function () {

        test('substitutes direction parameter in look_direction', function () {
            $result = $this->service->buildEndingStateDescription('look_direction', ['direction' => 'left']);

            expect($result)->toBe('exits looking left, motivating cut to subject of gaze');
        });

        test('substitutes duration parameter in emotional_peak', function () {
            $result = $this->service->buildEndingStateDescription('emotional_peak', ['duration' => '3']);

            expect($result)->toBe('holds on emotional high point for 3 seconds before cut');
        });

        test('returns template with placeholder when parameter not provided', function () {
            $result = $this->service->buildEndingStateDescription('look_direction');

            expect($result)->toContain('{direction}');
        });

        test('returns description for state without parameters', function () {
            $result = $this->service->buildEndingStateDescription('mid_motion');

            expect($result)->toBe('ends mid-gesture, next shot can complete action');
        });

        test('returns description for settling state', function () {
            $result = $this->service->buildEndingStateDescription('settling');

            expect($result)->toContain('stillness');
            expect($result)->toContain('breathing');
        });

        test('returns empty string for unknown state', function () {
            $result = $this->service->buildEndingStateDescription('unknown_state');

            expect($result)->toBe('');
        });

    });

    describe('helper methods', function () {

        test('getTransitionTypes returns all three types', function () {
            $types = $this->service->getTransitionTypes();

            expect($types)->toHaveCount(3);
            expect($types)->toContain('match_cut_setup');
            expect($types)->toContain('hard_cut_setup');
            expect($types)->toContain('soft_transition_setup');
        });

        test('getVariantsForType returns variants for match_cut_setup', function () {
            $variants = $this->service->getVariantsForType('match_cut_setup');

            expect($variants)->toContain('shape');
            expect($variants)->toContain('motion');
            expect($variants)->toContain('color');
            expect($variants)->toContain('eyeline');
        });

        test('getVariantsForType returns empty for unknown type', function () {
            $variants = $this->service->getVariantsForType('unknown_type');

            expect($variants)->toBeEmpty();
        });

        test('hasTransitionType returns true for valid type', function () {
            expect($this->service->hasTransitionType('match_cut_setup'))->toBeTrue();
            expect($this->service->hasTransitionType('hard_cut_setup'))->toBeTrue();
            expect($this->service->hasTransitionType('soft_transition_setup'))->toBeTrue();
        });

        test('hasTransitionType returns false for invalid type', function () {
            expect($this->service->hasTransitionType('unknown_type'))->toBeFalse();
        });

        test('hasEndingState returns true for valid states', function () {
            expect($this->service->hasEndingState('look_direction'))->toBeTrue();
            expect($this->service->hasEndingState('mid_motion'))->toBeTrue();
            expect($this->service->hasEndingState('settling'))->toBeTrue();
        });

        test('hasEndingState returns false for invalid state', function () {
            expect($this->service->hasEndingState('unknown_state'))->toBeFalse();
        });

    });

});
