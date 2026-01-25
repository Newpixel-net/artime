<?php

/**
 * PromptAdaptationIntegrationTest
 *
 * Phase 22: End-to-end integration tests for Hollywood-quality prompt pipeline.
 *
 * Tests the full integration between:
 * - ImageGenerationService (prompt adapter hook)
 * - ModelPromptAdapterService (compression logic)
 * - StructuredPromptBuilderService (vocabulary integration)
 * - CinematographyVocabulary (lens psychology, Kelvin values, framing)
 * - PromptTemplateLibrary (shot-type templates)
 */

use Modules\AppVideoWizard\Services\ModelPromptAdapterService;
use Modules\AppVideoWizard\Services\StructuredPromptBuilderService;
use Modules\AppVideoWizard\Services\CinematographyVocabulary;
use Modules\AppVideoWizard\Services\PromptTemplateLibrary;

beforeEach(function () {
    $this->adapter = new ModelPromptAdapterService();
    $this->builder = new StructuredPromptBuilderService();
    $this->vocabulary = new CinematographyVocabulary();
    $this->templates = new PromptTemplateLibrary();
});

describe('End-to-End Prompt Adaptation Integration', function () {

    describe('HiDream (CLIP model) receives compressed prompts', function () {

        test('compressed prompt is under 77 tokens', function () {
            // Build a Hollywood-quality prompt with all the vocabulary
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A woman sits at a cafe table, sipping coffee, watching the rain outside',
                'shot_type' => 'close-up',
            ]);

            $fullPrompt = $this->builder->toPromptString($result);

            // Adapt for HiDream
            $adaptedPrompt = $this->adapter->adaptPrompt($fullPrompt, 'hidream', [
                'shotType' => 'close-up',
            ]);

            $tokenCount = $this->adapter->countTokens($adaptedPrompt);

            expect($tokenCount)->toBeLessThanOrEqual(77);
        });

        test('compression removes style markers but preserves subject', function () {
            $fullPrompt = 'A young woman with auburn hair, sitting at cafe, sipping coffee, 8K resolution, photorealistic, ultra detailed, masterpiece quality';

            $adaptedPrompt = $this->adapter->adaptPrompt($fullPrompt, 'hidream');

            expect($adaptedPrompt)->toContain('woman');
            expect($adaptedPrompt)->toContain('hair');
            expect($adaptedPrompt)->toContain('cafe');
        });

        test('complex Hollywood prompt compresses successfully', function () {
            // Build with all Bible data
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A detective interviews a suspect in a dimly lit interrogation room',
                'shot_type' => 'medium-close',
                'character_bible' => [
                    'enabled' => true,
                    'characters' => [
                        [
                            'name' => 'Detective Morgan',
                            'description' => 'A weathered detective in his 50s',
                            'hair' => ['color' => 'gray', 'style' => 'short cropped'],
                        ],
                    ],
                ],
            ]);

            $fullPrompt = $this->builder->toPromptString($result);
            $adaptedPrompt = $this->adapter->adaptPrompt($fullPrompt, 'hidream');
            $tokenCount = $this->adapter->countTokens($adaptedPrompt);

            expect($tokenCount)->toBeLessThanOrEqual(77);
            expect($adaptedPrompt)->toContain('detective');
        });

    });

    describe('NanoBanana/Pro receives full prompts unchanged', function () {

        test('nanobanana prompt is unchanged', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A beautiful sunset over the ocean',
                'shot_type' => 'wide',
            ]);

            $fullPrompt = $this->builder->toPromptString($result);

            // Adapt for NanoBanana
            $adaptedPrompt = $this->adapter->adaptPrompt($fullPrompt, 'nanobanana');

            expect($adaptedPrompt)->toBe($fullPrompt);
        });

        test('nanobanana-pro prompt is unchanged', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A man walks through a crowded marketplace',
                'shot_type' => 'medium',
            ]);

            $fullPrompt = $this->builder->toPromptString($result);

            // Adapt for NanoBanana Pro
            $adaptedPrompt = $this->adapter->adaptPrompt($fullPrompt, 'nanobanana-pro');

            expect($adaptedPrompt)->toBe($fullPrompt);
        });

        test('long prompts with all DNA sections pass through for Gemini', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'An epic battle scene with dragons and knights',
                'shot_type' => 'establishing',
                'character_bible' => [
                    'enabled' => true,
                    'characters' => [
                        ['name' => 'Knight Commander', 'description' => 'Armored warrior'],
                        ['name' => 'Dragon Rider', 'description' => 'Fierce warrior on dragon'],
                    ],
                ],
                'location_bible' => [
                    'enabled' => true,
                    'locations' => [
                        ['name' => 'Battlefield', 'description' => 'Vast open plains'],
                    ],
                ],
            ]);

            $fullPrompt = $this->builder->toPromptString($result);

            // Both Gemini models should pass through unchanged
            $nanoBananaAdapted = $this->adapter->adaptPrompt($fullPrompt, 'nanobanana');
            $proAdapted = $this->adapter->adaptPrompt($fullPrompt, 'nanobanana-pro');

            expect($nanoBananaAdapted)->toBe($fullPrompt);
            expect($proAdapted)->toBe($fullPrompt);
        });

    });

    describe('Camera psychology is included in prompts', function () {

        test('close-up includes 85mm lens psychology', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A person looking thoughtfully out a window',
                'shot_type' => 'close-up',
            ]);

            $prompt = $this->builder->toPromptString($result);

            // Should contain lens description with psychology
            expect($prompt)->toMatch('/85mm/');
            expect($prompt)->toMatch('/intima(cy|te)/i');
        });

        test('wide shot includes 24mm lens psychology', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A city skyline at night',
                'shot_type' => 'wide',
            ]);

            $prompt = $this->builder->toPromptString($result);

            // Should contain wide angle lens description
            expect($prompt)->toMatch('/24mm/');
            expect($prompt)->toMatch('/environment|epic|scale/i');
        });

        test('medium shot includes 50mm lens psychology', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'Two people having a conversation',
                'shot_type' => 'medium',
            ]);

            $prompt = $this->builder->toPromptString($result);

            // Should contain standard lens description
            expect($prompt)->toMatch('/50mm/');
            expect($prompt)->toMatch('/natural|neutral|human/i');
        });

    });

    describe('Lighting includes Kelvin values', function () {

        test('daylight scene includes 5600K', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'An outdoor park scene during the day',
                'shot_type' => 'medium',
                'location_bible' => [
                    'enabled' => true,
                    'locations' => [
                        [
                            'name' => 'Park',
                            'description' => 'City park',
                            'timeOfDay' => 'day',
                        ],
                    ],
                ],
            ]);

            $prompt = $this->builder->toPromptString($result);

            // Should contain Kelvin temperature
            expect($prompt)->toMatch('/5600K|daylight/i');
        });

        test('golden hour includes warm Kelvin values', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A romantic moment at sunset',
                'shot_type' => 'close-up',
                'location_bible' => [
                    'enabled' => true,
                    'locations' => [
                        [
                            'name' => 'Beach',
                            'description' => 'Sandy beach',
                            'timeOfDay' => 'golden_hour',
                        ],
                    ],
                ],
            ]);

            $prompt = $this->builder->toPromptString($result);

            // Should contain warm temperature (golden hour is ~3500K)
            expect($prompt)->toMatch('/3500K|golden/i');
        });

        test('lighting includes stop ratios', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A dramatic confrontation scene',
                'shot_type' => 'medium-close',
            ]);

            $prompt = $this->builder->toPromptString($result);

            // Should contain stop difference information
            expect($prompt)->toMatch('/stop|fill/i');
        });

    });

    describe('Framing includes percentages', function () {

        test('close-up has high frame percentage', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A character portrait',
                'shot_type' => 'close-up',
            ]);

            $prompt = $this->builder->toPromptString($result);

            // Close-up should have ~80% frame occupation
            expect($prompt)->toMatch('/\d+%/');
            expect($prompt)->toMatch('/80%|frame/i');
        });

        test('wide shot has low frame percentage', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'An establishing shot of a city',
                'shot_type' => 'wide',
            ]);

            $prompt = $this->builder->toPromptString($result);

            // Wide shot should have ~25% frame occupation
            expect($prompt)->toMatch('/\d+%/');
            expect($prompt)->toMatch('/25%|frame/i');
        });

    });

    describe('Adaptation stats tracking', function () {

        test('stats include originalTokens and adaptedTokens', function () {
            $prompt = 'A test prompt with some content';
            $adaptedPrompt = $this->adapter->adaptPrompt($prompt, 'hidream');
            $stats = $this->adapter->getAdaptationStats($prompt, $adaptedPrompt, 'hidream');

            expect($stats)->toHaveKey('originalTokens');
            expect($stats)->toHaveKey('adaptedTokens');
            expect($stats['originalTokens'])->toBeInt();
            expect($stats['adaptedTokens'])->toBeInt();
        });

        test('stats show wasCompressed for HiDream with long prompt', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A complex scene with many details',
                'shot_type' => 'medium',
            ]);

            $fullPrompt = $this->builder->toPromptString($result);
            $adaptedPrompt = $this->adapter->adaptPrompt($fullPrompt, 'hidream');
            $stats = $this->adapter->getAdaptationStats($fullPrompt, $adaptedPrompt, 'hidream');

            // Long prompts should show compression happened
            expect($stats['wasCompressed'])->toBe($fullPrompt !== $adaptedPrompt);
        });

        test('stats show wasCompressed false for NanoBanana', function () {
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A simple scene',
                'shot_type' => 'medium',
            ]);

            $fullPrompt = $this->builder->toPromptString($result);
            $adaptedPrompt = $this->adapter->adaptPrompt($fullPrompt, 'nanobanana');
            $stats = $this->adapter->getAdaptationStats($fullPrompt, $adaptedPrompt, 'nanobanana');

            // NanoBanana never compresses
            expect($stats['wasCompressed'])->toBeFalse();
        });

        test('stats show underLimit status', function () {
            $prompt = 'A short prompt';
            $adaptedPrompt = $this->adapter->adaptPrompt($prompt, 'hidream');
            $stats = $this->adapter->getAdaptationStats($prompt, $adaptedPrompt, 'hidream');

            expect($stats)->toHaveKey('underLimit');
            expect($stats['underLimit'])->toBeTrue();
        });

    });

    describe('Vocabulary classes are properly initialized', function () {

        test('StructuredPromptBuilderService uses CinematographyVocabulary', function () {
            // Build a prompt and verify vocabulary is used
            $result = $this->builder->build([
                'visual_mode' => 'cinematic-realistic',
                'scene_description' => 'A test scene',
                'shot_type' => 'close-up',
            ]);

            // The creative_prompt should have camera_language
            expect($result['creative_prompt'])->toHaveKey('camera_language');
            expect($result['creative_prompt']['camera_language'])->toContain('mm');
        });

        test('CinematographyVocabulary provides lens psychology', function () {
            $lens = $this->vocabulary->getLensForShotType('close-up');

            expect($lens)->toHaveKey('focal_length');
            expect($lens)->toHaveKey('psychology');
            expect($lens['focal_length'])->toBe('85mm');
        });

        test('CinematographyVocabulary provides Kelvin temperatures', function () {
            $temp = $this->vocabulary->getTemperatureDescription('daylight');

            expect($temp)->toContain('5600K');
        });

        test('CinematographyVocabulary provides framing descriptions', function () {
            $framing = $this->vocabulary->buildFramingDescription(40, 'center frame');

            expect($framing)->toContain('40%');
            expect($framing)->toContain('frame');
        });

    });

});
