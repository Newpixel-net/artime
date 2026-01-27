<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * CharacterDynamicsService
 *
 * Provides multi-character spatial relationships and power dynamics vocabulary.
 * Implements Edward Hall's proxemics research combined with film blocking principles
 * to generate explicit spatial descriptions for AI video models.
 *
 * AI models struggle with implicit relationships like "boss and employee talking" -
 * this service produces explicit spatial vocabulary: "dominant character positioned
 * higher in frame, chin raised, occupying more frame space."
 *
 * Research sources:
 * - Edward Hall's "The Hidden Dimension" (proxemic distances)
 * - Film blocking and power dynamics in cinema
 * - Hollywood staging conventions for character relationships
 */
class CharacterDynamicsService
{
    /**
     * Proxemic zones based on Edward Hall's research.
     *
     * Each zone has specific use cases in storytelling that determine
     * emotional context and relationship dynamics.
     */
    public const PROXEMIC_ZONES = [
        'intimate' => [
            'distance' => '0-18 inches',
            'prompt' => 'close enough to feel breath, faces nearly touching',
            'use_for' => ['love', 'comfort', 'confrontation', 'secrets'],
        ],
        'personal' => [
            'distance' => '18 inches - 4 feet',
            'prompt' => 'at arm\'s length distance, personal space shared',
            'use_for' => ['friends', 'close_conversation', 'collaboration'],
        ],
        'social' => [
            'distance' => '4-12 feet',
            'prompt' => 'at conversational distance, professional spacing',
            'use_for' => ['business', 'formal', 'acquaintances'],
        ],
        'public' => [
            'distance' => '12+ feet',
            'prompt' => 'distant separation, vast space between them',
            'use_for' => ['strangers', 'formal_address', 'isolation'],
        ],
    ];

    /**
     * Power positioning patterns for visual storytelling.
     *
     * Frame positioning communicates power dynamics without dialogue.
     * Dominant characters occupy more space and higher positions.
     */
    public const POWER_POSITIONING = [
        'dominant_over_subordinate' => [
            'dominant' => 'positioned higher in frame, chin raised, occupying more frame space',
            'subordinate' => 'positioned lower, eyeline directed upward, compressed into corner of frame',
        ],
        'equals' => [
            'description' => 'positioned at same height, equal frame space, facing each other directly',
        ],
        'conflict' => [
            'description' => 'bodies angled away but heads turned toward each other, physical barrier between them',
        ],
        'alliance' => [
            'description' => 'mirroring each other\'s posture, bodies angled same direction, shoulders aligned',
        ],
        'protector_protected' => [
            'protector' => 'positioned between threat and protected, body angled as shield',
            'protected' => 'positioned behind protector, partially obscured by their form',
        ],
    ];

    /**
     * Maps relationship types to appropriate proxemic zones.
     *
     * Note: enemies use 'social' distance ironically - in confrontation,
     * they maintain distance that communicates threat assessment.
     */
    public const RELATIONSHIP_TO_PROXIMITY = [
        'lovers' => 'intimate',
        'friends' => 'personal',
        'colleagues' => 'social',
        'strangers' => 'public',
        'enemies' => 'social',
        'mentor_student' => 'personal',
        'boss_employee' => 'social',
        'parent_child' => 'personal',
        'siblings' => 'personal',
        'rivals' => 'social',
        'conspirators' => 'intimate',
    ];

    /**
     * Scene type to suggested dynamics mapping.
     *
     * Provides baseline suggestions for common scene types.
     */
    protected const SCENE_TYPE_DYNAMICS = [
        'confrontation' => [
            'proximity' => 'social',
            'power' => 'conflict',
            'notes' => 'Characters maintain distance for threat assessment',
        ],
        'romantic' => [
            'proximity' => 'intimate',
            'power' => 'equals',
            'notes' => 'Close proximity with balanced frame positioning',
        ],
        'business' => [
            'proximity' => 'social',
            'power' => 'dominant_over_subordinate',
            'notes' => 'Professional distance with clear hierarchy',
        ],
        'casual' => [
            'proximity' => 'personal',
            'power' => 'equals',
            'notes' => 'Relaxed spacing with equal positioning',
        ],
        'secretive' => [
            'proximity' => 'intimate',
            'power' => 'alliance',
            'notes' => 'Very close for whispered conversation',
        ],
        'protective' => [
            'proximity' => 'personal',
            'power' => 'protector_protected',
            'notes' => 'Close enough to shield, with clear positioning',
        ],
        'interview' => [
            'proximity' => 'social',
            'power' => 'dominant_over_subordinate',
            'notes' => 'Formal distance with interviewer in dominant position',
        ],
        'reunion' => [
            'proximity' => 'intimate',
            'power' => 'equals',
            'notes' => 'Close embrace distance, balanced power',
        ],
        'argument' => [
            'proximity' => 'personal',
            'power' => 'conflict',
            'notes' => 'Closer than comfortable, physical tension',
        ],
        'teaching' => [
            'proximity' => 'personal',
            'power' => 'dominant_over_subordinate',
            'notes' => 'Mentor positioned slightly higher but accessible',
        ],
    ];

    /**
     * Build complete spatial dynamics description for multi-character scene.
     *
     * Combines proxemic zone with power positioning to create explicit
     * spatial vocabulary that AI models can understand and render.
     *
     * @param string $relationship The relationship type between characters
     * @param string $proximityZone The proxemic zone (intimate, personal, social, public)
     * @param array $characters Character names [['name' => 'Marcus'], ['name' => 'Elena']]
     * @return string Full spatial dynamics description
     */
    public function buildSpatialDynamics(string $relationship, string $proximityZone, array $characters): string
    {
        $parts = [];

        // Get proxemic description
        $zone = strtolower(trim($proximityZone));
        $proxemicData = self::PROXEMIC_ZONES[$zone] ?? self::PROXEMIC_ZONES['social'];
        $parts[] = "Characters positioned {$proxemicData['prompt']}";

        // Determine power dynamic from relationship
        $powerDynamic = $this->inferPowerDynamicFromRelationship($relationship);

        // Build power positioning with character names
        $powerDescription = $this->buildPowerDescription($powerDynamic, $characters);
        if (!empty($powerDescription)) {
            $parts[] = $powerDescription;
        }

        $result = implode('. ', $parts) . '.';

        Log::debug('CharacterDynamicsService: Built spatial dynamics', [
            'relationship' => $relationship,
            'proximity_zone' => $zone,
            'power_dynamic' => $powerDynamic,
            'character_count' => count($characters),
        ]);

        return $result;
    }

    /**
     * Get the appropriate proxemic zone for a relationship type.
     *
     * @param string $relationship The relationship type
     * @return string The proxemic zone name
     */
    public function getProximityForRelationship(string $relationship): string
    {
        $relationship = strtolower(trim($relationship));

        if (isset(self::RELATIONSHIP_TO_PROXIMITY[$relationship])) {
            return self::RELATIONSHIP_TO_PROXIMITY[$relationship];
        }

        // Fuzzy matching for variations
        $aliases = [
            'lover' => 'lovers',
            'friend' => 'friends',
            'colleague' => 'colleagues',
            'stranger' => 'strangers',
            'enemy' => 'enemies',
            'mentor' => 'mentor_student',
            'student' => 'mentor_student',
            'boss' => 'boss_employee',
            'employee' => 'boss_employee',
            'parent' => 'parent_child',
            'child' => 'parent_child',
            'sibling' => 'siblings',
            'rival' => 'rivals',
            'conspirator' => 'conspirators',
            'romantic' => 'lovers',
            'business' => 'colleagues',
            'professional' => 'colleagues',
        ];

        $normalizedRelationship = $aliases[$relationship] ?? null;

        if ($normalizedRelationship && isset(self::RELATIONSHIP_TO_PROXIMITY[$normalizedRelationship])) {
            return self::RELATIONSHIP_TO_PROXIMITY[$normalizedRelationship];
        }

        // Default to social for unknown relationships
        Log::info('CharacterDynamicsService: Unknown relationship, defaulting to social', [
            'relationship' => $relationship,
        ]);

        return 'social';
    }

    /**
     * Build power positioning description with character names substituted.
     *
     * @param string $powerDynamic The power dynamic type
     * @param array $characters Array of characters with 'name' keys
     * @return string Power positioning description with names
     */
    public function buildPowerDescription(string $powerDynamic, array $characters): string
    {
        $powerDynamic = strtolower(trim($powerDynamic));
        $positioningData = self::POWER_POSITIONING[$powerDynamic] ?? null;

        if (!$positioningData) {
            return '';
        }

        // Extract character names
        $characterNames = [];
        foreach ($characters as $char) {
            if (is_array($char) && isset($char['name'])) {
                $characterNames[] = $char['name'];
            } elseif (is_string($char)) {
                $characterNames[] = $char;
            }
        }

        // Build description based on power dynamic type
        if (isset($positioningData['description'])) {
            // Simple description (equals, conflict, alliance)
            return $positioningData['description'];
        }

        if (isset($positioningData['dominant']) && isset($positioningData['subordinate'])) {
            // Dominant/subordinate dynamic
            $dominant = $characterNames[0] ?? 'dominant character';
            $subordinate = $characterNames[1] ?? 'subordinate character';

            return "{$dominant} {$positioningData['dominant']}. {$subordinate} {$positioningData['subordinate']}";
        }

        if (isset($positioningData['protector']) && isset($positioningData['protected'])) {
            // Protector/protected dynamic
            $protector = $characterNames[0] ?? 'protector';
            $protected = $characterNames[1] ?? 'protected character';

            return "{$protector} {$positioningData['protector']}. {$protected} {$positioningData['protected']}";
        }

        return '';
    }

    /**
     * Suggest appropriate dynamics for a given scene type.
     *
     * Returns recommended proximity zone and power dynamic
     * for common scene types like confrontation, romantic, business, etc.
     *
     * @param string $sceneType The type of scene
     * @param array $characters Array of characters in the scene
     * @return array{proximity: string, power: string, notes: string, full_description: string}
     */
    public function suggestDynamicsForScene(string $sceneType, array $characters): array
    {
        $sceneType = strtolower(trim($sceneType));

        $dynamics = self::SCENE_TYPE_DYNAMICS[$sceneType] ?? [
            'proximity' => 'social',
            'power' => 'equals',
            'notes' => 'Default social distance with equal positioning',
        ];

        // Build full description using the suggested dynamics
        $fullDescription = $this->buildSpatialDynamics(
            $sceneType,
            $dynamics['proximity'],
            $characters
        );

        return [
            'proximity' => $dynamics['proximity'],
            'power' => $dynamics['power'],
            'notes' => $dynamics['notes'],
            'full_description' => $fullDescription,
        ];
    }

    /**
     * Get all available proxemic zones.
     *
     * @return array<string>
     */
    public function getAvailableProximityZones(): array
    {
        return array_keys(self::PROXEMIC_ZONES);
    }

    /**
     * Get all available power dynamics.
     *
     * @return array<string>
     */
    public function getAvailablePowerDynamics(): array
    {
        return array_keys(self::POWER_POSITIONING);
    }

    /**
     * Infer power dynamic from relationship type.
     *
     * @param string $relationship The relationship type
     * @return string The inferred power dynamic
     */
    protected function inferPowerDynamicFromRelationship(string $relationship): string
    {
        $relationship = strtolower(trim($relationship));

        $powerMap = [
            'lovers' => 'equals',
            'friends' => 'equals',
            'colleagues' => 'equals',
            'strangers' => 'equals',
            'enemies' => 'conflict',
            'mentor_student' => 'dominant_over_subordinate',
            'boss_employee' => 'dominant_over_subordinate',
            'parent_child' => 'protector_protected',
            'siblings' => 'equals',
            'rivals' => 'conflict',
            'conspirators' => 'alliance',
            'confrontation' => 'conflict',
            'romantic' => 'equals',
            'business' => 'dominant_over_subordinate',
            'secretive' => 'alliance',
            'protective' => 'protector_protected',
        ];

        return $powerMap[$relationship] ?? 'equals';
    }

    /**
     * Build a prompt-ready spatial dynamics block.
     *
     * @param string $relationship Relationship between characters
     * @param array $characters Array of characters
     * @return string Formatted block for image/video generation prompts
     */
    public function buildPromptBlock(string $relationship, array $characters): string
    {
        $proximity = $this->getProximityForRelationship($relationship);
        $dynamics = $this->buildSpatialDynamics($relationship, $proximity, $characters);

        return "[SPATIAL-DYNAMICS: {$relationship}] {$dynamics}";
    }
}
