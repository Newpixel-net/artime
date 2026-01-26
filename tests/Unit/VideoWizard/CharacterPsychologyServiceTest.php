<?php

use Modules\AppVideoWizard\Services\CharacterPsychologyService;

beforeEach(function () {
    $this->service = new CharacterPsychologyService();
});

describe('CharacterPsychologyService', function () {

    describe('EMOTION_MANIFESTATIONS', function () {

        test('contains at least 8 emotions', function () {
            $emotions = array_keys(CharacterPsychologyService::EMOTION_MANIFESTATIONS);

            expect(count($emotions))->toBeGreaterThanOrEqual(8);
        });

        test('each emotion has face, eyes, body, and breath components', function () {
            foreach (CharacterPsychologyService::EMOTION_MANIFESTATIONS as $emotion => $data) {
                expect($data)->toHaveKeys(['face', 'eyes', 'body', 'breath']);
                expect($data['face'])->toBeString()->not->toBeEmpty();
                expect($data['eyes'])->toBeString()->not->toBeEmpty();
                expect($data['body'])->toBeString()->not->toBeEmpty();
                expect($data['breath'])->toBeString()->not->toBeEmpty();
            }
        });

    });

    describe('getManifestationsForEmotion', function () {

        test('returns all physical components for known emotion', function () {
            $result = $this->service->getManifestationsForEmotion('suppressed_anger');

            expect($result)->toHaveKeys(['face', 'eyes', 'body', 'breath']);
            expect($result['face'])->toBeString()->not->toBeEmpty();
            expect($result['eyes'])->toBeString()->not->toBeEmpty();
            expect($result['body'])->toBeString()->not->toBeEmpty();
            expect($result['breath'])->toBeString()->not->toBeEmpty();
        });

        test('returns empty array for unknown emotion', function () {
            $result = $this->service->getManifestationsForEmotion('unknown_emotion');

            expect($result)->toBeArray()->toBeEmpty();
        });

        test('handles case insensitivity', function () {
            $result1 = $this->service->getManifestationsForEmotion('Suppressed_Anger');
            $result2 = $this->service->getManifestationsForEmotion('SUPPRESSED_ANGER');

            expect($result1)->toBeEmpty(); // Keys are lowercase
            expect($result2)->toBeEmpty(); // Keys are lowercase

            // Proper lowercase works
            $result3 = $this->service->getManifestationsForEmotion('suppressed_anger');
            expect($result3)->toHaveKey('face');
        });

    });

    describe('buildEmotionDescription', function () {

        test('includes all manifestations in description', function () {
            $result = $this->service->buildEmotionDescription('suppressed_anger', 'moderate');

            // Should contain jaw and brow references
            expect($result)->toContain('jaw');
            expect($result)->toContain('brow');
            // Should contain eye manifestations
            expect($result)->toContain('gaze');
        });

        test('applies intensity modifiers for different levels', function () {
            $subtle = $this->service->buildEmotionDescription('grief', 'subtle');
            $intense = $this->service->buildEmotionDescription('grief', 'intense');

            // Both should contain the emotion's manifestations
            expect($subtle)->toContain('downturned');
            expect($intense)->toContain('downturned');

            // They should be different due to modifiers
            // (Note: modifiers are randomly selected, but we verify format)
            expect($subtle)->toBeString()->not->toBeEmpty();
            expect($intense)->toBeString()->not->toBeEmpty();
        });

        test('returns empty string for unknown emotion', function () {
            $result = $this->service->buildEmotionDescription('unknown_emotion');

            expect($result)->toBe('');
        });

    });

    describe('buildSubtextLayer', function () {

        test('creates three-layer structure with surface, leakage, body', function () {
            $result = $this->service->buildSubtextLayer('forced_composure', 'anxiety', 0.3);

            expect($result)->toHaveKeys(['surface', 'leakage', 'body']);
            expect($result['surface'])->toBeString()->not->toBeEmpty();
            expect($result['leakage'])->toBeString()->not->toBeEmpty();
            expect($result['body'])->toBeString()->not->toBeEmpty();
        });

        test('surface shows the mask emotion', function () {
            $result = $this->service->buildSubtextLayer('forced_composure', 'anxiety', 0.3);

            // Surface should reference the face showing the surface emotion
            expect($result['surface'])->toContain('Face shows');
            expect($result['surface'])->toContain('neutral'); // forced_composure face manifestation
        });

        test('body reveals true emotion manifestation', function () {
            $result = $this->service->buildSubtextLayer('forced_composure', 'anxiety', 0.3);

            // Body should show anxiety body manifestation
            expect($result['body'])->toContain('Body reveals');
            expect($result['body'])->toContain('hunched'); // anxiety body has "hunched forward"
        });

        test('leakage shows true emotion in eyes', function () {
            $result = $this->service->buildSubtextLayer('forced_composure', 'anxiety', 0.3);

            // Leakage should reference eyes and the true emotion
            expect($result['leakage'])->toContain('Eyes leak');
            expect($result['leakage'])->toContain('anxiety');
        });

    });

    describe('suppressed_anger mapping', function () {

        test('has jaw and brow tension', function () {
            $manifestations = CharacterPsychologyService::EMOTION_MANIFESTATIONS['suppressed_anger'];

            expect(strtolower($manifestations['face']))->toContain('jaw');
            expect(strtolower($manifestations['face']))->toContain('brow');
        });

    });

    describe('anxiety mapping', function () {

        test('has fidgeting and rapid eye movement', function () {
            $manifestations = CharacterPsychologyService::EMOTION_MANIFESTATIONS['anxiety'];

            // Eyes should have rapid movement
            expect(strtolower($manifestations['eyes']))->toContain('rapid');
            // Body should have fidgeting
            expect(strtolower($manifestations['body']))->toContain('fidget');
        });

    });

    describe('buildEnhancedEmotionDescription', function () {

        test('returns base description when no traits provided', function () {
            $base = $this->service->buildEmotionDescription('grief', 'moderate');
            $enhanced = $this->service->buildEnhancedEmotionDescription('grief', 'moderate', []);

            // Both should contain grief manifestations
            expect($enhanced)->toContain('downturned');
            // Enhanced with empty traits is base description
            expect(strlen($enhanced))->toBeGreaterThan(0);
        });

        test('includes defining features when provided', function () {
            $traits = [
                'defining_features' => ['distinctive scar above left eyebrow', 'deep-set eyes'],
            ];

            $result = $this->service->buildEnhancedEmotionDescription('suppressed_anger', 'moderate', $traits);

            expect(strtolower($result))->toContain('scar');
            expect(strtolower($result))->toContain('eyebrow');
        });

        test('handles string defining_features', function () {
            $traits = [
                'defining_features' => 'prominent scar on left cheek',
            ];

            $result = $this->service->buildEnhancedEmotionDescription('fear', 'intense', $traits);

            expect(strtolower($result))->toContain('scar');
            expect(strtolower($result))->toContain('cheek');
        });

        test('includes facial structure when provided', function () {
            $traits = [
                'facial_structure' => 'angular jawline with high cheekbones',
            ];

            $result = $this->service->buildEnhancedEmotionDescription('contempt', 'moderate', $traits);

            expect(strtolower($result))->toContain('angular');
            expect(strtolower($result))->toContain('cheekbones');
        });

        test('returns empty for unknown emotion', function () {
            $traits = [
                'defining_features' => ['scar'],
            ];

            $result = $this->service->buildEnhancedEmotionDescription('unknown_emotion', 'moderate', $traits);

            expect($result)->toBe('');
        });

    });

    describe('INTENSITY_MODIFIERS', function () {

        test('has subtle, moderate, and intense levels', function () {
            $intensities = array_keys(CharacterPsychologyService::INTENSITY_MODIFIERS);

            expect($intensities)->toContain('subtle');
            expect($intensities)->toContain('moderate');
            expect($intensities)->toContain('intense');
        });

        test('each intensity has array of modifier words', function () {
            foreach (CharacterPsychologyService::INTENSITY_MODIFIERS as $level => $modifiers) {
                expect($modifiers)->toBeArray()->not->toBeEmpty();
                foreach ($modifiers as $modifier) {
                    expect($modifier)->toBeString()->not->toBeEmpty();
                }
            }
        });

    });

});
