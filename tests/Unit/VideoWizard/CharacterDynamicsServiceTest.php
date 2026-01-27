<?php

use Modules\AppVideoWizard\Services\CharacterDynamicsService;

beforeEach(function () {
    $this->service = new CharacterDynamicsService();
});

describe('CharacterDynamicsService', function () {

    describe('PROXEMIC_ZONES', function () {

        test('contains all four Edward Hall zones', function () {
            $zones = array_keys(CharacterDynamicsService::PROXEMIC_ZONES);

            expect($zones)->toHaveCount(4);
            expect($zones)->toContain('intimate');
            expect($zones)->toContain('personal');
            expect($zones)->toContain('social');
            expect($zones)->toContain('public');
        });

        test('each zone has distance, prompt, and use_for', function () {
            foreach (CharacterDynamicsService::PROXEMIC_ZONES as $zone => $data) {
                expect($data)->toHaveKeys(['distance', 'prompt', 'use_for']);
                expect($data['distance'])->toBeString()->not->toBeEmpty();
                expect($data['prompt'])->toBeString()->not->toBeEmpty();
                expect($data['use_for'])->toBeArray()->not->toBeEmpty();
            }
        });

        test('intimate zone describes very close distance', function () {
            $intimate = CharacterDynamicsService::PROXEMIC_ZONES['intimate'];

            expect($intimate['distance'])->toContain('0-18 inches');
            expect(strtolower($intimate['prompt']))->toContain('close');
        });

        test('public zone describes distant separation', function () {
            $public = CharacterDynamicsService::PROXEMIC_ZONES['public'];

            expect($public['distance'])->toContain('12+');
            expect(strtolower($public['prompt']))->toContain('distant');
        });

    });

    describe('POWER_POSITIONING', function () {

        test('contains dominant_over_subordinate, equals, conflict, alliance, protector_protected', function () {
            $dynamics = array_keys(CharacterDynamicsService::POWER_POSITIONING);

            expect($dynamics)->toContain('dominant_over_subordinate');
            expect($dynamics)->toContain('equals');
            expect($dynamics)->toContain('conflict');
            expect($dynamics)->toContain('alliance');
            expect($dynamics)->toContain('protector_protected');
        });

        test('dominant_over_subordinate has dominant and subordinate descriptions', function () {
            $data = CharacterDynamicsService::POWER_POSITIONING['dominant_over_subordinate'];

            expect($data)->toHaveKeys(['dominant', 'subordinate']);
            expect(strtolower($data['dominant']))->toContain('higher');
            expect(strtolower($data['subordinate']))->toContain('lower');
        });

        test('alliance describes mirroring posture', function () {
            $data = CharacterDynamicsService::POWER_POSITIONING['alliance'];

            expect(strtolower($data['description']))->toContain('mirroring');
        });

    });

    describe('buildSpatialDynamics', function () {

        test('builds description for dominant over subordinate with proxemics', function () {
            $characters = [
                ['name' => 'Marcus'],
                ['name' => 'Elena'],
            ];

            $result = $this->service->buildSpatialDynamics(
                'boss_employee',
                'social',
                $characters
            );

            // Should include proxemic description
            expect(strtolower($result))->toContain('conversational distance');

            // Should include power positioning with character names
            expect($result)->toContain('Marcus');
            expect(strtolower($result))->toContain('higher');
        });

        test('builds description for equals relationship', function () {
            $characters = [
                ['name' => 'Alex'],
                ['name' => 'Jordan'],
            ];

            $result = $this->service->buildSpatialDynamics(
                'friends',
                'personal',
                $characters
            );

            // Should include proxemic description
            expect(strtolower($result))->toContain('arm\'s length');

            // Should include equals positioning
            expect(strtolower($result))->toContain('same height');
        });

        test('handles string character names', function () {
            $characters = ['Marcus', 'Elena'];

            $result = $this->service->buildSpatialDynamics(
                'boss_employee',
                'social',
                $characters
            );

            expect($result)->toContain('Marcus');
        });

        test('defaults to social zone for unknown proximity', function () {
            $characters = [['name' => 'A'], ['name' => 'B']];

            $result = $this->service->buildSpatialDynamics(
                'colleagues',
                'unknown-zone',
                $characters
            );

            // Should use social zone default (conversational distance)
            expect(strtolower($result))->toContain('conversational');
        });

    });

    describe('getProximityForRelationship', function () {

        test('lovers maps to intimate', function () {
            expect($this->service->getProximityForRelationship('lovers'))->toBe('intimate');
        });

        test('friends maps to personal', function () {
            expect($this->service->getProximityForRelationship('friends'))->toBe('personal');
        });

        test('colleagues maps to social', function () {
            expect($this->service->getProximityForRelationship('colleagues'))->toBe('social');
        });

        test('strangers maps to public', function () {
            expect($this->service->getProximityForRelationship('strangers'))->toBe('public');
        });

        test('enemies maps to social (confrontation distance)', function () {
            expect($this->service->getProximityForRelationship('enemies'))->toBe('social');
        });

        test('handles singular aliases', function () {
            expect($this->service->getProximityForRelationship('friend'))->toBe('personal');
            expect($this->service->getProximityForRelationship('lover'))->toBe('intimate');
            expect($this->service->getProximityForRelationship('stranger'))->toBe('public');
        });

        test('handles case insensitivity', function () {
            expect($this->service->getProximityForRelationship('LOVERS'))->toBe('intimate');
            expect($this->service->getProximityForRelationship('Friends'))->toBe('personal');
        });

        test('defaults to social for unknown relationship', function () {
            expect($this->service->getProximityForRelationship('unknown-relation'))->toBe('social');
        });

    });

    describe('buildPowerDescription', function () {

        test('includes character names for dominant/subordinate', function () {
            $characters = [
                ['name' => 'Director Smith'],
                ['name' => 'Intern Jones'],
            ];

            $result = $this->service->buildPowerDescription('dominant_over_subordinate', $characters);

            expect($result)->toContain('Director Smith');
            expect($result)->toContain('Intern Jones');
            expect(strtolower($result))->toContain('higher');
            expect(strtolower($result))->toContain('lower');
        });

        test('includes character names for protector/protected', function () {
            $characters = [
                ['name' => 'Guardian'],
                ['name' => 'Child'],
            ];

            $result = $this->service->buildPowerDescription('protector_protected', $characters);

            expect($result)->toContain('Guardian');
            expect($result)->toContain('Child');
            expect(strtolower($result))->toContain('shield');
        });

        test('returns description for equals without names', function () {
            $characters = [['name' => 'A'], ['name' => 'B']];

            $result = $this->service->buildPowerDescription('equals', $characters);

            expect(strtolower($result))->toContain('same height');
            expect(strtolower($result))->toContain('equal frame space');
        });

        test('returns empty string for unknown power dynamic', function () {
            $result = $this->service->buildPowerDescription('unknown-power', [['name' => 'A']]);

            expect($result)->toBe('');
        });

    });

    describe('suggestDynamicsForScene', function () {

        test('confrontation scene suggests social distance and conflict power', function () {
            $characters = [['name' => 'Hero'], ['name' => 'Villain']];

            $result = $this->service->suggestDynamicsForScene('confrontation', $characters);

            expect($result['proximity'])->toBe('social');
            expect($result['power'])->toBe('conflict');
            expect($result)->toHaveKey('notes');
            expect($result)->toHaveKey('full_description');
        });

        test('romantic scene suggests intimate distance and equals power', function () {
            $characters = [['name' => 'Lover1'], ['name' => 'Lover2']];

            $result = $this->service->suggestDynamicsForScene('romantic', $characters);

            expect($result['proximity'])->toBe('intimate');
            expect($result['power'])->toBe('equals');
        });

        test('business scene suggests social distance and hierarchy', function () {
            $characters = [['name' => 'CEO'], ['name' => 'Employee']];

            $result = $this->service->suggestDynamicsForScene('business', $characters);

            expect($result['proximity'])->toBe('social');
            expect($result['power'])->toBe('dominant_over_subordinate');
        });

        test('secretive scene suggests intimate proximity for whispered conversation', function () {
            $characters = [['name' => 'Spy1'], ['name' => 'Spy2']];

            $result = $this->service->suggestDynamicsForScene('secretive', $characters);

            expect($result['proximity'])->toBe('intimate');
            expect($result['power'])->toBe('alliance');
        });

        test('protective scene suggests personal distance with protector positioning', function () {
            $characters = [['name' => 'Protector'], ['name' => 'Vulnerable']];

            $result = $this->service->suggestDynamicsForScene('protective', $characters);

            expect($result['proximity'])->toBe('personal');
            expect($result['power'])->toBe('protector_protected');
        });

        test('full_description contains complete spatial vocabulary', function () {
            $characters = [['name' => 'Boss'], ['name' => 'Worker']];

            $result = $this->service->suggestDynamicsForScene('interview', $characters);

            expect($result['full_description'])->toContain('positioned');
            expect(strlen($result['full_description']))->toBeGreaterThan(50);
        });

        test('unknown scene type returns defaults', function () {
            $characters = [['name' => 'A'], ['name' => 'B']];

            $result = $this->service->suggestDynamicsForScene('unknown-scene-type', $characters);

            expect($result['proximity'])->toBe('social');
            expect($result['power'])->toBe('equals');
        });

    });

    describe('getAvailableProximityZones', function () {

        test('returns all four zones', function () {
            $zones = $this->service->getAvailableProximityZones();

            expect($zones)->toHaveCount(4);
            expect($zones)->toContain('intimate');
            expect($zones)->toContain('personal');
            expect($zones)->toContain('social');
            expect($zones)->toContain('public');
        });

    });

    describe('getAvailablePowerDynamics', function () {

        test('returns all power dynamics', function () {
            $dynamics = $this->service->getAvailablePowerDynamics();

            expect(count($dynamics))->toBeGreaterThanOrEqual(5);
            expect($dynamics)->toContain('dominant_over_subordinate');
            expect($dynamics)->toContain('equals');
            expect($dynamics)->toContain('conflict');
            expect($dynamics)->toContain('alliance');
            expect($dynamics)->toContain('protector_protected');
        });

    });

    describe('buildPromptBlock', function () {

        test('returns formatted spatial dynamics block', function () {
            $characters = [['name' => 'Marcus'], ['name' => 'Elena']];

            $result = $this->service->buildPromptBlock('lovers', $characters);

            expect($result)->toContain('[SPATIAL-DYNAMICS:');
            expect($result)->toContain('lovers');
            expect($result)->toContain('positioned');
        });

    });

});
