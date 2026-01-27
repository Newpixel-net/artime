<?php

use Modules\AppVideoWizard\Services\CameraMovementService;
use Modules\AppVideoWizard\Models\VwCameraMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CameraMovementService();

    // Create test camera movement
    VwCameraMovement::create([
        'slug' => 'dolly-in',
        'name' => 'Dolly In',
        'category' => 'dolly',
        'description' => 'Camera moves toward subject',
        'prompt_syntax' => 'camera smoothly dollies in toward subject',
        'intensity' => 'moderate',
        'typical_duration_min' => 3,
        'typical_duration_max' => 8,
        'stackable_with' => ['tilt-up', 'pan-left'],
        'best_for_shot_types' => ['medium', 'close-up'],
        'best_for_emotions' => ['intimate', 'dramatic'],
        'natural_continuation' => 'dolly-out',
        'ending_state' => 'close to subject',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    VwCameraMovement::create([
        'slug' => 'crane-up',
        'name' => 'Crane Up',
        'category' => 'crane',
        'description' => 'Camera rises vertically',
        'prompt_syntax' => 'camera cranes up revealing scene from above',
        'intensity' => 'dynamic',
        'typical_duration_min' => 4,
        'typical_duration_max' => 10,
        'stackable_with' => ['dolly-in'],
        'best_for_shot_types' => ['wide', 'establishing'],
        'best_for_emotions' => ['epic', 'powerful'],
        'natural_continuation' => 'crane-down',
        'ending_state' => 'elevated perspective',
        'is_active' => true,
        'sort_order' => 2,
    ]);
});

describe('CameraMovementService Temporal Features', function () {

    describe('MOVEMENT_PSYCHOLOGY constant', function () {

        test('contains expected psychological purposes', function () {
            $psychology = CameraMovementService::MOVEMENT_PSYCHOLOGY;

            expect($psychology)->toHaveKey('intimacy');
            expect($psychology)->toHaveKey('tension');
            expect($psychology)->toHaveKey('reveal');
            expect($psychology)->toHaveKey('isolation');
            expect($psychology)->toHaveKey('power');
            expect($psychology)->toHaveKey('vulnerability');
            expect($psychology)->toHaveKey('urgency');
            expect($psychology)->toHaveKey('contemplation');
            expect($psychology)->toHaveKey('discovery');
            expect($psychology)->toHaveKey('departure');
        });

        test('each psychology has descriptive phrase', function () {
            foreach (CameraMovementService::MOVEMENT_PSYCHOLOGY as $key => $description) {
                expect($description)->toBeString()->not->toBeEmpty();
                // Should contain descriptive language
                expect(strlen($description))->toBeGreaterThan(20);
            }
        });

        test('intimacy describes emotional connection', function () {
            $intimacy = CameraMovementService::MOVEMENT_PSYCHOLOGY['intimacy'];

            expect($intimacy)->toContain('emotional connection');
        });

        test('power describes dominance', function () {
            $power = CameraMovementService::MOVEMENT_PSYCHOLOGY['power'];

            expect($power)->toContain('dominance');
        });

    });

    describe('buildTemporalMovementPrompt', function () {

        test('includes duration in seconds', function () {
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 4, 'intimacy');

            expect($result)->toContain('over 4 seconds');
        });

        test('includes psychological purpose', function () {
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 4, 'intimacy');

            expect($result)->toContain('closing distance as emotional connection deepens');
        });

        test('builds complete temporal prompt with movement, duration, and psychology', function () {
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 4, 'intimacy');

            // Should have all three components
            expect($result)->toContain('doll');  // movement type
            expect($result)->toContain('seconds');  // duration
            expect($result)->toContain('emotional');  // psychology
        });

        test('clamps duration to typical duration min', function () {
            // dolly-in has min 3, max 8
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 1, 'intimacy');

            // Should clamp to minimum 3
            expect($result)->toContain('over 3 seconds');
        });

        test('clamps duration to typical duration max', function () {
            // dolly-in has min 3, max 8
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 15, 'intimacy');

            // Should clamp to maximum 8
            expect($result)->toContain('over 8 seconds');
        });

        test('respects different movement duration ranges', function () {
            // crane-up has min 4, max 10
            $result = $this->service->buildTemporalMovementPrompt('crane-up', 6, 'power');

            expect($result)->toContain('over 6 seconds');
        });

        test('handles unknown movement gracefully', function () {
            $result = $this->service->buildTemporalMovementPrompt('unknown-movement', 4, 'intimacy');

            expect($result)->toBe('camera remains static');
        });

        test('handles unknown psychology gracefully', function () {
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 4, 'unknown_psychology');

            // Should still have movement and duration, just no psychology appended
            expect($result)->toContain('over 4 seconds');
            // Should not contain any psychology phrase
            expect($result)->not->toContain('closing distance');
        });

        test('adds intensity modifier for non-moderate intensity', function () {
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 4, 'intimacy', 'intense');

            expect($result)->toContain('dramatically');
        });

        test('adds dynamic intensity modifier', function () {
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 4, 'tension', 'dynamic');

            expect($result)->toContain('dynamically');
        });

        test('adds subtle intensity modifier', function () {
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 4, 'contemplation', 'subtle');

            expect($result)->toContain('gently');
        });

        test('moderate intensity does not add extra modifier', function () {
            $result = $this->service->buildTemporalMovementPrompt('dolly-in', 4, 'intimacy', 'moderate');

            expect($result)->not->toContain('dramatically');
            expect($result)->not->toContain('dynamically');
            expect($result)->not->toContain('gently');
        });

    });

    describe('getRecommendedDuration', function () {

        test('returns duration within movement typical range', function () {
            $result = $this->service->getRecommendedDuration('dolly-in', 10);

            // dolly-in has min 3, max 8
            expect($result)->toBeGreaterThanOrEqual(3);
            expect($result)->toBeLessThanOrEqual(8);
        });

        test('scales to clip length respecting 80% rule', function () {
            // 5 second clip = 4 seconds max (80%)
            // dolly-in min 3, max 8
            // Result should be 4 (clamped to available range and 80%)
            $result = $this->service->getRecommendedDuration('dolly-in', 5);

            expect($result)->toBe(4);
        });

        test('respects movement minimum duration', function () {
            // Very short clip
            // dolly-in has min 3
            $result = $this->service->getRecommendedDuration('dolly-in', 2);

            // Should return min duration
            expect($result)->toBe(3);
        });

        test('respects movement maximum duration', function () {
            // Very long clip
            // dolly-in has max 8
            $result = $this->service->getRecommendedDuration('dolly-in', 30);

            // Should return max duration
            expect($result)->toBe(8);
        });

        test('handles different movement ranges', function () {
            // crane-up has min 4, max 10
            $result = $this->service->getRecommendedDuration('crane-up', 15);

            // 15 * 0.8 = 12, but max is 10
            expect($result)->toBe(10);
        });

        test('handles unknown movement with default calculation', function () {
            $result = $this->service->getRecommendedDuration('unknown-movement', 10);

            // Default: min(4, 10 * 0.8) = min(4, 8) = 4
            expect($result)->toBe(4);
        });

        test('handles very short clip for unknown movement', function () {
            $result = $this->service->getRecommendedDuration('unknown-movement', 3);

            // Default: min(4, 3 * 0.8) = min(4, 2.4) = 2
            expect($result)->toBe(2);
        });

    });

    describe('helper methods', function () {

        test('getAvailablePsychology returns all psychology keys', function () {
            $keys = $this->service->getAvailablePsychology();

            expect($keys)->toContain('intimacy');
            expect($keys)->toContain('tension');
            expect($keys)->toContain('power');
            expect($keys)->toContain('reveal');
            expect($keys)->toHaveCount(10);
        });

        test('getPsychologyDescription returns description for valid key', function () {
            $description = $this->service->getPsychologyDescription('intimacy');

            expect($description)->toBe('closing distance as emotional connection deepens');
        });

        test('getPsychologyDescription returns empty for invalid key', function () {
            $description = $this->service->getPsychologyDescription('invalid_key');

            expect($description)->toBe('');
        });

    });

});
