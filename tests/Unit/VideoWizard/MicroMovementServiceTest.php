<?php

use Modules\AppVideoWizard\Services\MicroMovementService;

beforeEach(function () {
    $this->service = new MicroMovementService();
});

describe('MicroMovementService', function () {

    describe('MICRO_MOVEMENT_LIBRARY', function () {

        test('contains breathing, eyes, head, and hands categories', function () {
            $categories = array_keys(MicroMovementService::MICRO_MOVEMENT_LIBRARY);

            expect($categories)->toContain('breathing');
            expect($categories)->toContain('eyes');
            expect($categories)->toContain('head');
            expect($categories)->toContain('hands');
        });

        test('breathing has subtle, heavy, and held states', function () {
            $breathing = MicroMovementService::MICRO_MOVEMENT_LIBRARY['breathing'];

            expect($breathing)->toHaveKeys(['subtle', 'heavy', 'held']);
            expect($breathing['subtle'])->toBeString()->not->toBeEmpty();
            expect($breathing['heavy'])->toBeString()->not->toBeEmpty();
            expect($breathing['held'])->toBeString()->not->toBeEmpty();
        });

        test('eyes has natural, focused, and shifting states', function () {
            $eyes = MicroMovementService::MICRO_MOVEMENT_LIBRARY['eyes'];

            expect($eyes)->toHaveKeys(['natural', 'focused', 'shifting']);
            expect($eyes['natural'])->toContain('blink');
            expect($eyes['focused'])->toContain('unblinking');
            expect($eyes['shifting'])->toContain('dart');
        });

        test('each category has descriptive string values', function () {
            foreach (MicroMovementService::MICRO_MOVEMENT_LIBRARY as $category => $variants) {
                expect($variants)->toBeArray()->not->toBeEmpty();
                foreach ($variants as $variant => $description) {
                    expect($description)->toBeString()
                        ->not->toBeEmpty()
                        ->and(strlen($description))->toBeGreaterThan(10);
                }
            }
        });

    });

    describe('SHOT_TYPE_MICRO_MAPPING', function () {

        test('close-up includes eyes, breathing, and head', function () {
            $closeUp = MicroMovementService::SHOT_TYPE_MICRO_MAPPING['close-up'];

            expect($closeUp)->toContain('eyes');
            expect($closeUp)->toContain('breathing');
            expect($closeUp)->toContain('head');
        });

        test('wide-shot returns empty array', function () {
            $wideShot = MicroMovementService::SHOT_TYPE_MICRO_MAPPING['wide-shot'];

            expect($wideShot)->toBeArray()->toBeEmpty();
        });

        test('extreme-wide-shot returns empty array', function () {
            $extremeWide = MicroMovementService::SHOT_TYPE_MICRO_MAPPING['extreme-wide-shot'];

            expect($extremeWide)->toBeArray()->toBeEmpty();
        });

        test('medium-shot includes breathing and hands', function () {
            $mediumShot = MicroMovementService::SHOT_TYPE_MICRO_MAPPING['medium-shot'];

            expect($mediumShot)->toContain('breathing');
            expect($mediumShot)->toContain('hands');
        });

    });

    describe('EMOTION_MICRO_VARIANTS', function () {

        test('tense emotion maps to held breathing', function () {
            $tense = MicroMovementService::EMOTION_MICRO_VARIANTS['tense'];

            expect($tense['breathing'])->toBe('held');
        });

        test('anxious emotion maps to fidget hands', function () {
            $anxious = MicroMovementService::EMOTION_MICRO_VARIANTS['anxious'];

            expect($anxious['hands'])->toBe('fidget');
        });

        test('relaxed emotion maps to subtle breathing', function () {
            $relaxed = MicroMovementService::EMOTION_MICRO_VARIANTS['relaxed'];

            expect($relaxed['breathing'])->toBe('subtle');
        });

        test('each emotion has all four categories mapped', function () {
            foreach (MicroMovementService::EMOTION_MICRO_VARIANTS as $emotion => $mapping) {
                expect($mapping)->toHaveKeys(['breathing', 'eyes', 'head', 'hands']);
            }
        });

    });

    describe('buildMicroMovementLayer', function () {

        test('returns non-empty string for close-up', function () {
            $result = $this->service->buildMicroMovementLayer('close-up', 'tense');

            expect($result)->toBeString()->not->toBeEmpty();
        });

        test('returns empty string for wide-shot', function () {
            $result = $this->service->buildMicroMovementLayer('wide-shot', 'tense');

            expect($result)->toBe('');
        });

        test('returns empty string for extreme-wide-shot', function () {
            $result = $this->service->buildMicroMovementLayer('extreme-wide-shot', 'relaxed');

            expect($result)->toBe('');
        });

        test('includes breathing description for medium shots', function () {
            $result = $this->service->buildMicroMovementLayer('medium-shot', 'relaxed');

            // Relaxed breathing = subtle = "gentle chest rise and fall"
            expect(strtolower($result))->toContain('chest');
        });

        test('includes eye movements for close-up', function () {
            $result = $this->service->buildMicroMovementLayer('close-up', 'anxious');

            // Anxious eyes = shifting = "eyes dart"
            expect(strtolower($result))->toContain('eyes');
        });

        test('normalizes shot type variations', function () {
            $closeUp1 = $this->service->buildMicroMovementLayer('close-up', 'neutral');
            $closeUp2 = $this->service->buildMicroMovementLayer('closeup', 'neutral');
            $closeUp3 = $this->service->buildMicroMovementLayer('close up', 'neutral');

            // All should return the same content
            expect($closeUp1)->toBe($closeUp2);
            expect($closeUp2)->toBe($closeUp3);
        });

    });

    describe('getMicroMovementsForShotType', function () {

        test('returns correct categories for close-up', function () {
            $result = $this->service->getMicroMovementsForShotType('close-up');

            expect($result)->toContain('eyes');
            expect($result)->toContain('breathing');
            expect($result)->toContain('head');
            expect(count($result))->toBe(3);
        });

        test('returns empty array for wide-shot', function () {
            $result = $this->service->getMicroMovementsForShotType('wide-shot');

            expect($result)->toBeEmpty();
        });

        test('returns medium-shot defaults for unknown shot type', function () {
            $result = $this->service->getMicroMovementsForShotType('unknown-shot-type');

            // Should default to medium-shot: breathing and hands
            expect($result)->toContain('breathing');
            expect($result)->toContain('hands');
        });

        test('returns extreme-close-up categories correctly', function () {
            $result = $this->service->getMicroMovementsForShotType('extreme-close-up');

            expect($result)->toContain('eyes');
            expect($result)->toContain('breathing');
            expect($result)->not->toContain('hands'); // Too close for hands
        });

    });

    describe('selectMicroMovementVariant', function () {

        test('selects held breathing for tense emotion', function () {
            $result = $this->service->selectMicroMovementVariant('breathing', 'tense');

            // "held" variant description
            expect(strtolower($result))->toContain('breath held');
        });

        test('selects fidget hands for anxious emotion', function () {
            $result = $this->service->selectMicroMovementVariant('hands', 'anxious');

            // "fidget" variant description
            expect(strtolower($result))->toContain('fingers tap');
        });

        test('falls back to neutral for unknown emotion', function () {
            $result = $this->service->selectMicroMovementVariant('breathing', 'unknown_emotion');

            // Should fall back to neutral -> subtle -> "gentle chest rise and fall"
            expect(strtolower($result))->toContain('gentle');
        });

        test('returns non-empty for all category-emotion combinations', function () {
            $categories = ['breathing', 'eyes', 'head', 'hands'];
            $emotions = ['tense', 'relaxed', 'anxious', 'neutral'];

            foreach ($categories as $category) {
                foreach ($emotions as $emotion) {
                    $result = $this->service->selectMicroMovementVariant($category, $emotion);
                    expect($result)->toBeString()->not->toBeEmpty();
                }
            }
        });

    });

    describe('hasMicroMovementsForShot', function () {

        test('returns true for close-up', function () {
            $result = $this->service->hasMicroMovementsForShot('close-up');

            expect($result)->toBeTrue();
        });

        test('returns false for wide-shot', function () {
            $result = $this->service->hasMicroMovementsForShot('wide-shot');

            expect($result)->toBeFalse();
        });

        test('returns true for medium-shot', function () {
            $result = $this->service->hasMicroMovementsForShot('medium-shot');

            expect($result)->toBeTrue();
        });

    });

    describe('getAvailableCategories', function () {

        test('returns all four categories', function () {
            $result = $this->service->getAvailableCategories();

            expect($result)->toHaveCount(4);
            expect($result)->toContain('breathing');
            expect($result)->toContain('eyes');
            expect($result)->toContain('head');
            expect($result)->toContain('hands');
        });

    });

    describe('getAvailableEmotions', function () {

        test('returns mapped emotions', function () {
            $result = $this->service->getAvailableEmotions();

            expect($result)->toContain('tense');
            expect($result)->toContain('relaxed');
            expect($result)->toContain('anxious');
            expect($result)->toContain('neutral');
        });

    });

});
