<?php

use Modules\AppVideoWizard\Services\VoicePacingService;

beforeEach(function () {
    $this->service = new VoicePacingService();
});

describe('VoicePacingService', function () {

    describe('PAUSE_TYPES constant', function () {

        test('contains five pause types', function () {
            $types = array_keys(VoicePacingService::PAUSE_TYPES);

            expect($types)->toHaveCount(5);
            expect($types)->toContain('beat');
            expect($types)->toContain('short');
            expect($types)->toContain('medium');
            expect($types)->toContain('long');
            expect($types)->toContain('breath');
        });

        test('beat has 0.5s duration and micro-pause description', function () {
            $beat = VoicePacingService::PAUSE_TYPES['beat'];

            expect($beat['duration'])->toBe(0.5);
            expect($beat['notation'])->toBe('[beat]');
            expect($beat['ssml'])->toBe('<break time="500ms"/>');
            expect($beat['description'])->toContain('micro-pause');
        });

        test('medium has 2s duration as standard dramatic pause', function () {
            $medium = VoicePacingService::PAUSE_TYPES['medium'];

            expect($medium['duration'])->toBe(2.0);
            expect($medium['notation'])->toBe('[pause]');
            expect($medium['ssml'])->toBe('<break time="2s"/>');
            expect($medium['description'])->toContain('dramatic');
        });

        test('long has 3s duration for extended silence', function () {
            $long = VoicePacingService::PAUSE_TYPES['long'];

            expect($long['duration'])->toBe(3.0);
            expect($long['notation'])->toBe('[long pause]');
            expect($long['ssml'])->toBe('<break time="3s"/>');
        });

        test('each pause type has required keys', function () {
            foreach (VoicePacingService::PAUSE_TYPES as $type => $config) {
                expect($config)->toHaveKeys(['duration', 'notation', 'ssml', 'description']);
                expect($config['duration'])->toBeNumeric();
                expect($config['notation'])->toBeString();
                expect($config['ssml'])->toContain('<break');
            }
        });

    });

    describe('PACING_MODIFIERS constant', function () {

        test('contains five modifiers', function () {
            $modifiers = array_keys(VoicePacingService::PACING_MODIFIERS);

            expect($modifiers)->toHaveCount(5);
            expect($modifiers)->toContain('slow');
            expect($modifiers)->toContain('measured');
            expect($modifiers)->toContain('normal');
            expect($modifiers)->toContain('urgent');
            expect($modifiers)->toContain('rushed');
        });

        test('slow has rate modifier 0.85', function () {
            $slow = VoicePacingService::PACING_MODIFIERS['slow'];

            expect($slow['rate_modifier'])->toBe(0.85);
            expect($slow['notation'])->toBe('[SLOW]');
            expect($slow['ssml_rate'])->toBe('-15%');
        });

        test('normal has rate modifier 1.0 and empty notation', function () {
            $normal = VoicePacingService::PACING_MODIFIERS['normal'];

            expect($normal['rate_modifier'])->toBe(1.0);
            expect($normal['notation'])->toBe('');
            expect($normal['ssml_rate'])->toBe('0%');
        });

        test('urgent has rate modifier 1.1', function () {
            $urgent = VoicePacingService::PACING_MODIFIERS['urgent'];

            expect($urgent['rate_modifier'])->toBe(1.1);
            expect($urgent['notation'])->toBe('[urgent]');
            expect($urgent['ssml_rate'])->toBe('+10%');
        });

        test('each modifier has required keys', function () {
            foreach (VoicePacingService::PACING_MODIFIERS as $modifier => $config) {
                expect($config)->toHaveKeys(['rate_modifier', 'notation', 'ssml_rate', 'description']);
                expect($config['rate_modifier'])->toBeNumeric();
                expect($config['ssml_rate'])->toBeString();
            }
        });

    });

    describe('insertPauseMarker', function () {

        test('formats correctly for decimal values', function () {
            $result = $this->service->insertPauseMarker(2.5);

            expect($result)->toBe('[PAUSE 2.5s]');
        });

        test('rounds to one decimal place', function () {
            $result = $this->service->insertPauseMarker(2.567);

            expect($result)->toBe('[PAUSE 2.6s]');
        });

        test('formats whole numbers without decimal', function () {
            $result = $this->service->insertPauseMarker(3.0);

            expect($result)->toBe('[PAUSE 3s]');
        });

        test('handles sub-second values', function () {
            $result = $this->service->insertPauseMarker(0.5);

            expect($result)->toBe('[PAUSE 0.5s]');
        });

        test('handles zero', function () {
            $result = $this->service->insertPauseMarker(0);

            expect($result)->toBe('[PAUSE 0s]');
        });

    });

    describe('getPauseNotation', function () {

        test('returns notation for known type', function () {
            $result = $this->service->getPauseNotation('beat');

            expect($result)->toBe('[beat]');
        });

        test('returns empty string for unknown type', function () {
            $result = $this->service->getPauseNotation('unknown');

            expect($result)->toBe('');
        });

        test('handles case insensitivity', function () {
            $result = $this->service->getPauseNotation('MEDIUM');

            expect($result)->toBe('[pause]');
        });

        test('returns correct notation for all types', function () {
            expect($this->service->getPauseNotation('beat'))->toBe('[beat]');
            expect($this->service->getPauseNotation('short'))->toBe('[short pause]');
            expect($this->service->getPauseNotation('medium'))->toBe('[pause]');
            expect($this->service->getPauseNotation('long'))->toBe('[long pause]');
            expect($this->service->getPauseNotation('breath'))->toBe('[breath]');
        });

    });

    describe('getPauseDuration', function () {

        test('returns seconds for known type', function () {
            $result = $this->service->getPauseDuration('medium');

            expect($result)->toBe(2.0);
        });

        test('returns zero for unknown type', function () {
            $result = $this->service->getPauseDuration('unknown');

            expect($result)->toBe(0.0);
        });

        test('returns correct duration for all types', function () {
            expect($this->service->getPauseDuration('beat'))->toBe(0.5);
            expect($this->service->getPauseDuration('short'))->toBe(1.0);
            expect($this->service->getPauseDuration('medium'))->toBe(2.0);
            expect($this->service->getPauseDuration('long'))->toBe(3.0);
            expect($this->service->getPauseDuration('breath'))->toBe(0.3);
        });

    });

    describe('getModifierNotation', function () {

        test('returns notation for slow', function () {
            $result = $this->service->getModifierNotation('slow');

            expect($result)->toBe('[SLOW]');
        });

        test('returns empty string for normal', function () {
            $result = $this->service->getModifierNotation('normal');

            expect($result)->toBe('');
        });

        test('returns empty for unknown modifier', function () {
            $result = $this->service->getModifierNotation('unknown');

            expect($result)->toBe('');
        });

        test('handles case insensitivity', function () {
            $result = $this->service->getModifierNotation('URGENT');

            expect($result)->toBe('[urgent]');
        });

    });

    describe('toSSML', function () {

        test('converts pause markers to SSML break tags', function () {
            $result = $this->service->toSSML('[PAUSE 2s] Hello');

            expect($result)->toBe('<break time="2s"/> Hello');
        });

        test('converts decimal pause markers', function () {
            $result = $this->service->toSSML('[PAUSE 2.5s] world');

            expect($result)->toBe('<break time="2500ms"/> world');
        });

        test('converts sub-second pause markers', function () {
            $result = $this->service->toSSML('[PAUSE 0.5s] pause');

            expect($result)->toBe('<break time="500ms"/> pause');
        });

        test('converts named pauses to SSML', function () {
            $result = $this->service->toSSML('[beat] Hello [pause] world');

            expect($result)->toContain('<break time="500ms"/>');
            expect($result)->toContain('<break time="2s"/>');
            expect($result)->toContain('Hello');
            expect($result)->toContain('world');
        });

        test('preserves text around markers', function () {
            $result = $this->service->toSSML('Before [PAUSE 1s] after');

            expect($result)->toBe('Before <break time="1s"/> after');
        });

        test('handles text without markers', function () {
            $result = $this->service->toSSML('Hello world');

            expect($result)->toBe('Hello world');
        });

        test('converts multiple markers in sequence', function () {
            $result = $this->service->toSSML('[PAUSE 1s][PAUSE 2s]');

            expect($result)->toBe('<break time="1s"/><break time="2s"/>');
        });

        test('case insensitive pause markers', function () {
            $result = $this->service->toSSML('[pause 2s] text');

            expect($result)->toBe('<break time="2s"/> text');
        });

    });

    describe('buildPacingInstruction', function () {

        test('combines modifier and pause', function () {
            $result = $this->service->buildPacingInstruction('slow', 'medium');

            expect($result)->toBe('[SLOW] [pause]');
        });

        test('returns modifier only when no pause', function () {
            $result = $this->service->buildPacingInstruction('slow');

            expect($result)->toBe('[SLOW]');
        });

        test('returns empty for normal modifier without pause', function () {
            $result = $this->service->buildPacingInstruction('normal');

            expect($result)->toBe('');
        });

        test('returns pause only for normal modifier with pause', function () {
            $result = $this->service->buildPacingInstruction('normal', 'beat');

            expect($result)->toBe('[beat]');
        });

        test('handles unknown modifier gracefully', function () {
            $result = $this->service->buildPacingInstruction('unknown', 'beat');

            expect($result)->toBe('[beat]');
        });

    });

    describe('getAvailablePauseTypes', function () {

        test('returns all pause type keys', function () {
            $result = $this->service->getAvailablePauseTypes();

            expect($result)->toHaveCount(5);
            expect($result)->toContain('beat');
            expect($result)->toContain('short');
            expect($result)->toContain('medium');
            expect($result)->toContain('long');
            expect($result)->toContain('breath');
        });

    });

    describe('getAvailableModifiers', function () {

        test('returns all modifier keys', function () {
            $result = $this->service->getAvailableModifiers();

            expect($result)->toHaveCount(5);
            expect($result)->toContain('slow');
            expect($result)->toContain('measured');
            expect($result)->toContain('normal');
            expect($result)->toContain('urgent');
            expect($result)->toContain('rushed');
        });

    });

    describe('estimatePacingDuration', function () {

        test('sums named pauses', function () {
            $result = $this->service->estimatePacingDuration('[pause] [beat]');

            // medium (2.0) + beat (0.5) = 2.5
            expect($result)->toBe(2.5);
        });

        test('sums custom pause markers', function () {
            $result = $this->service->estimatePacingDuration('[PAUSE 1s] [PAUSE 2.5s]');

            expect($result)->toBe(3.5);
        });

        test('combines named and custom pauses', function () {
            $result = $this->service->estimatePacingDuration('[beat] Hello [PAUSE 1s] world');

            // beat (0.5) + custom (1.0) = 1.5
            expect($result)->toBe(1.5);
        });

        test('returns zero for text without pauses', function () {
            $result = $this->service->estimatePacingDuration('Hello world');

            expect($result)->toBe(0.0);
        });

        test('counts multiple occurrences of same pause', function () {
            $result = $this->service->estimatePacingDuration('[beat] one [beat] two [beat]');

            // 3 x beat (0.5) = 1.5
            expect($result)->toBe(1.5);
        });

    });

    describe('getPauseSSML', function () {

        test('returns SSML for known type', function () {
            $result = $this->service->getPauseSSML('beat');

            expect($result)->toBe('<break time="500ms"/>');
        });

        test('returns empty for unknown type', function () {
            $result = $this->service->getPauseSSML('unknown');

            expect($result)->toBe('');
        });

    });

    describe('getRateModifier', function () {

        test('returns rate for slow', function () {
            $result = $this->service->getRateModifier('slow');

            expect($result)->toBe(0.85);
        });

        test('returns 1.0 for unknown modifier', function () {
            $result = $this->service->getRateModifier('unknown');

            expect($result)->toBe(1.0);
        });

    });

    describe('getModifierSSMLRate', function () {

        test('returns SSML rate for slow', function () {
            $result = $this->service->getModifierSSMLRate('slow');

            expect($result)->toBe('-15%');
        });

        test('returns 0% for unknown', function () {
            $result = $this->service->getModifierSSMLRate('unknown');

            expect($result)->toBe('0%');
        });

    });

});
