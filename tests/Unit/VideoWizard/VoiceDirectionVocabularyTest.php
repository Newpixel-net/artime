<?php

use Modules\AppVideoWizard\Services\VoiceDirectionVocabulary;
use Modules\AppVideoWizard\Services\CharacterPsychologyService;

beforeEach(function () {
    $this->service = new VoiceDirectionVocabulary();
});

describe('VoiceDirectionVocabulary', function () {

    describe('EMOTIONAL_DIRECTION constant', function () {

        test('contains all 8 expected emotions', function () {
            $emotions = array_keys(VoiceDirectionVocabulary::EMOTIONAL_DIRECTION);

            expect($emotions)->toContain('trembling');
            expect($emotions)->toContain('whisper');
            expect($emotions)->toContain('cracking');
            expect($emotions)->toContain('grief');
            expect($emotions)->toContain('anxiety');
            expect($emotions)->toContain('fear');
            expect($emotions)->toContain('contempt');
            expect($emotions)->toContain('joy');
            expect(count($emotions))->toBeGreaterThanOrEqual(8);
        });

        test('each emotion has tag, elevenlabs_tag, and description', function () {
            foreach (VoiceDirectionVocabulary::EMOTIONAL_DIRECTION as $emotion => $data) {
                expect($data)->toHaveKey('tag');
                expect($data)->toHaveKey('elevenlabs_tag');
                expect($data)->toHaveKey('description');
                expect($data['tag'])->toBeString();
                expect($data['elevenlabs_tag'])->toBeString();
                expect($data['description'])->toBeString();
            }
        });

        test('tags are properly bracketed', function () {
            foreach (VoiceDirectionVocabulary::EMOTIONAL_DIRECTION as $emotion => $data) {
                expect($data['tag'])->toStartWith('[');
                expect($data['tag'])->toEndWith(']');
                expect($data['elevenlabs_tag'])->toStartWith('[');
                expect($data['elevenlabs_tag'])->toEndWith(']');
            }
        });

    });

    describe('VOCAL_QUALITIES constant', function () {

        test('contains all 7 expected qualities', function () {
            $qualities = array_keys(VoiceDirectionVocabulary::VOCAL_QUALITIES);

            expect($qualities)->toContain('gravelly');
            expect($qualities)->toContain('exhausted');
            expect($qualities)->toContain('breathless');
            expect($qualities)->toContain('steely');
            expect($qualities)->toContain('honeyed');
            expect($qualities)->toContain('raspy');
            expect($qualities)->toContain('resonant');
            expect(count($qualities))->toBeGreaterThanOrEqual(7);
        });

        test('each quality has a string description', function () {
            foreach (VoiceDirectionVocabulary::VOCAL_QUALITIES as $quality => $description) {
                expect($description)->toBeString();
                expect(strlen($description))->toBeGreaterThan(10);
            }
        });

    });

    describe('NON_VERBAL_SOUNDS constant', function () {

        test('contains all 7 expected sounds', function () {
            $sounds = array_keys(VoiceDirectionVocabulary::NON_VERBAL_SOUNDS);

            expect($sounds)->toContain('sigh');
            expect($sounds)->toContain('gasp');
            expect($sounds)->toContain('stammer');
            expect($sounds)->toContain('laugh');
            expect($sounds)->toContain('sob');
            expect($sounds)->toContain('scoff');
            expect($sounds)->toContain('hesitate');
            expect(count($sounds))->toBeGreaterThanOrEqual(7);
        });

        test('each sound has tag and description', function () {
            foreach (VoiceDirectionVocabulary::NON_VERBAL_SOUNDS as $sound => $data) {
                expect($data)->toHaveKey('tag');
                expect($data)->toHaveKey('description');
                expect($data['tag'])->toBeString();
                expect($data['description'])->toBeString();
            }
        });

        test('includes breath markers (sigh, gasp, sob)', function () {
            $sounds = VoiceDirectionVocabulary::NON_VERBAL_SOUNDS;

            expect($sounds)->toHaveKey('sigh');
            expect($sounds)->toHaveKey('gasp');
            expect($sounds)->toHaveKey('sob');

            // Verify they have appropriate descriptions
            expect($sounds['sigh']['description'])->toContain('exhale');
            expect($sounds['gasp']['description'])->toContain('inhale');
            expect($sounds['sob']['description'])->toContain('crying');
        });

    });

    describe('getDirectionForEmotion', function () {

        test('returns data for known emotion', function () {
            $result = $this->service->getDirectionForEmotion('grief');

            expect($result)->toBeArray();
            expect($result)->toHaveKey('tag');
            expect($result)->toHaveKey('elevenlabs_tag');
            expect($result)->toHaveKey('description');
            expect($result['tag'])->toBe('[grieving]');
            expect($result['elevenlabs_tag'])->toBe('[crying]');
        });

        test('returns empty array for unknown emotion', function () {
            $result = $this->service->getDirectionForEmotion('unknown_emotion');

            expect($result)->toBeArray();
            expect($result)->toBeEmpty();
        });

        test('handles case insensitivity', function () {
            $result1 = $this->service->getDirectionForEmotion('GRIEF');
            $result2 = $this->service->getDirectionForEmotion('Anxiety');
            $result3 = $this->service->getDirectionForEmotion('fear');

            expect($result1)->not->toBeEmpty();
            expect($result2)->not->toBeEmpty();
            expect($result3)->not->toBeEmpty();
        });

        test('trims whitespace', function () {
            $result = $this->service->getDirectionForEmotion('  grief  ');

            expect($result)->not->toBeEmpty();
            expect($result['tag'])->toBe('[grieving]');
        });

    });

    describe('getVocalQuality', function () {

        test('returns description for known quality', function () {
            $result = $this->service->getVocalQuality('gravelly');

            expect($result)->toBe('rough, low texture with gravel undertone');
        });

        test('returns empty string for unknown quality', function () {
            $result = $this->service->getVocalQuality('unknown_quality');

            expect($result)->toBe('');
        });

        test('handles case insensitivity', function () {
            $result = $this->service->getVocalQuality('EXHAUSTED');

            expect($result)->not->toBeEmpty();
            expect($result)->toContain('drained');
        });

    });

    describe('getNonVerbalSound', function () {

        test('returns data for known sound', function () {
            $result = $this->service->getNonVerbalSound('sigh');

            expect($result)->toBeArray();
            expect($result)->toHaveKey('tag');
            expect($result)->toHaveKey('description');
            expect($result['tag'])->toBe('[sighs]');
        });

        test('returns empty array for unknown sound', function () {
            $result = $this->service->getNonVerbalSound('unknown_sound');

            expect($result)->toBeArray();
            expect($result)->toBeEmpty();
        });

    });

    describe('wrapWithDirection', function () {

        test('adds elevenlabs tag for grief', function () {
            $result = $this->service->wrapWithDirection('Hello', 'grief', 'elevenlabs');

            expect($result)->toBe('[crying] Hello');
        });

        test('adds elevenlabs tag for whisper', function () {
            $result = $this->service->wrapWithDirection('I have a secret', 'whisper', 'elevenlabs');

            expect($result)->toBe('[whispers] I have a secret');
        });

        test('returns unchanged text for openai provider', function () {
            $result = $this->service->wrapWithDirection('Hello', 'grief', 'openai');

            expect($result)->toBe('Hello');
        });

        test('handles unknown emotion gracefully', function () {
            $result = $this->service->wrapWithDirection('Hello', 'unknown_emotion', 'elevenlabs');

            expect($result)->toBe('Hello');
        });

        test('uses generic tag for unknown provider', function () {
            $result = $this->service->wrapWithDirection('Hello', 'grief', 'other_provider');

            expect($result)->toBe('[grieving] Hello');
        });

        test('default provider is elevenlabs', function () {
            $result = $this->service->wrapWithDirection('Hello', 'anxiety');

            expect($result)->toBe('[nervous] Hello');
        });

        test('handles provider case insensitivity', function () {
            $result = $this->service->wrapWithDirection('Hello', 'grief', 'ELEVENLABS');

            expect($result)->toBe('[crying] Hello');
        });

    });

    describe('getAvailableEmotions', function () {

        test('returns all emotion keys', function () {
            $emotions = $this->service->getAvailableEmotions();

            expect($emotions)->toContain('trembling');
            expect($emotions)->toContain('whisper');
            expect($emotions)->toContain('grief');
            expect($emotions)->toContain('anxiety');
            expect($emotions)->toContain('fear');
            expect($emotions)->toContain('contempt');
            expect($emotions)->toContain('joy');
            expect(count($emotions))->toBeGreaterThanOrEqual(8);
        });

    });

    describe('getAvailableQualities', function () {

        test('returns all quality keys', function () {
            $qualities = $this->service->getAvailableQualities();

            expect($qualities)->toContain('gravelly');
            expect($qualities)->toContain('exhausted');
            expect($qualities)->toContain('breathless');
            expect(count($qualities))->toBeGreaterThanOrEqual(7);
        });

    });

    describe('getAvailableSounds', function () {

        test('returns all sound keys', function () {
            $sounds = $this->service->getAvailableSounds();

            expect($sounds)->toContain('sigh');
            expect($sounds)->toContain('gasp');
            expect($sounds)->toContain('sob');
            expect(count($sounds))->toBeGreaterThanOrEqual(7);
        });

    });

    describe('emotion keys align with CharacterPsychologyService', function () {

        test('grief exists in both services', function () {
            $voiceEmotions = $this->service->getAvailableEmotions();
            $psychologyEmotions = array_keys(CharacterPsychologyService::EMOTION_MANIFESTATIONS);

            expect($voiceEmotions)->toContain('grief');
            expect($psychologyEmotions)->toContain('grief');
        });

        test('anxiety exists in both services', function () {
            $voiceEmotions = $this->service->getAvailableEmotions();
            $psychologyEmotions = array_keys(CharacterPsychologyService::EMOTION_MANIFESTATIONS);

            expect($voiceEmotions)->toContain('anxiety');
            expect($psychologyEmotions)->toContain('anxiety');
        });

        test('fear exists in both services', function () {
            $voiceEmotions = $this->service->getAvailableEmotions();
            $psychologyEmotions = array_keys(CharacterPsychologyService::EMOTION_MANIFESTATIONS);

            expect($voiceEmotions)->toContain('fear');
            expect($psychologyEmotions)->toContain('fear');
        });

        test('contempt exists in both services', function () {
            $voiceEmotions = $this->service->getAvailableEmotions();
            $psychologyEmotions = array_keys(CharacterPsychologyService::EMOTION_MANIFESTATIONS);

            expect($voiceEmotions)->toContain('contempt');
            expect($psychologyEmotions)->toContain('contempt');
        });

    });

    describe('has* helper methods', function () {

        test('hasEmotion returns true for valid emotions', function () {
            expect($this->service->hasEmotion('grief'))->toBeTrue();
            expect($this->service->hasEmotion('anxiety'))->toBeTrue();
            expect($this->service->hasEmotion('joy'))->toBeTrue();
        });

        test('hasEmotion returns false for invalid emotions', function () {
            expect($this->service->hasEmotion('unknown'))->toBeFalse();
        });

        test('hasVocalQuality returns true for valid qualities', function () {
            expect($this->service->hasVocalQuality('gravelly'))->toBeTrue();
            expect($this->service->hasVocalQuality('exhausted'))->toBeTrue();
        });

        test('hasVocalQuality returns false for invalid qualities', function () {
            expect($this->service->hasVocalQuality('unknown'))->toBeFalse();
        });

        test('hasNonVerbalSound returns true for valid sounds', function () {
            expect($this->service->hasNonVerbalSound('sigh'))->toBeTrue();
            expect($this->service->hasNonVerbalSound('gasp'))->toBeTrue();
        });

        test('hasNonVerbalSound returns false for invalid sounds', function () {
            expect($this->service->hasNonVerbalSound('unknown'))->toBeFalse();
        });

    });

    describe('buildVoiceInstruction', function () {

        test('returns description for known emotion', function () {
            $result = $this->service->buildVoiceInstruction('grief');

            expect($result)->toContain('sorrow');
        });

        test('combines emotion and vocal quality', function () {
            $result = $this->service->buildVoiceInstruction('grief', 'gravelly');

            expect($result)->toContain('sorrow');
            expect($result)->toContain('gravel');
        });

        test('returns empty string for unknown emotion', function () {
            $result = $this->service->buildVoiceInstruction('unknown');

            expect($result)->toBe('');
        });

        test('ignores unknown vocal quality but keeps emotion', function () {
            $result = $this->service->buildVoiceInstruction('grief', 'unknown');

            expect($result)->toContain('sorrow');
            expect($result)->not->toContain('unknown');
        });

    });

});
