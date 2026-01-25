<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;

/**
 * Bible Ordering & Visual Consistency Service - Phase 5 of Hollywood Upgrade Plan
 *
 * Provides:
 * - 5.1: Character Bible Smart Ordering (role priority, scene count)
 * - 5.2: Location Bible Smart Ordering (first appearance, frequency, alphabetical)
 * - 5.3: Visual Consistency Dashboard (scores, status, issues)
 */
class BibleOrderingService
{
    /**
     * Check if a bible item (character, location, style) has a reference image.
     *
     * Supports both:
     * - New storage key format (Phase 19): referenceImageStorageKey
     * - Legacy format: referenceImageBase64
     *
     * @param array $item The item to check
     * @param bool $requireReady Whether to require 'ready' status
     * @return bool True if has a reference image
     */
    protected function hasReferenceImage(array $item, bool $requireReady = false): bool
    {
        if ($requireReady) {
            $isReady = ($item['referenceImageStatus'] ?? '') === 'ready';
            if (!$isReady) {
                return false;
            }
        }

        // Check new storage key format (Phase 19)
        if (!empty($item['referenceImageStorageKey'])) {
            return true;
        }

        // Check legacy base64 format
        if (!empty($item['referenceImageBase64'])) {
            return true;
        }

        return false;
    }

    /**
     * Role priority mapping (lower = more important)
     */
    const ROLE_PRIORITY = [
        'Protagonist' => 1,
        'protagonist' => 1,
        'Main' => 1,
        'main' => 1,
        'Lead' => 1,
        'lead' => 1,
        'Primary' => 1,
        'primary' => 1,
        'Supporting' => 2,
        'supporting' => 2,
        'Secondary' => 2,
        'secondary' => 2,
        'Recurring' => 2,
        'recurring' => 2,
        'Background' => 3,
        'background' => 3,
        'Extra' => 4,
        'extra' => 4,
        'Minor' => 4,
        'minor' => 4,
        'Crowd' => 5,
        'crowd' => 5,
    ];

    // =========================================================================
    // PHASE 5.1: CHARACTER BIBLE SMART ORDERING
    // =========================================================================

    /**
     * Get sorted characters with display metadata (Phase 5.1)
     *
     * Returns characters sorted by importance with additional display data:
     * - Scene count
     * - Thumbnail (reference image if available)
     * - Status badge (reference status)
     * - Role priority label
     *
     * @param array $characters Array of character data
     * @param string $sortMethod Sort method: 'smart', 'role_first', 'scenes_first', 'alphabetical'
     * @return array Sorted characters with display metadata
     */
    public function getSortedCharactersWithMetadata(array $characters, string $sortMethod = 'smart'): array
    {
        if (empty($characters)) {
            return [];
        }

        // Add metadata to each character
        $withMetadata = [];
        foreach ($characters as $idx => $character) {
            $withMetadata[] = $this->buildCharacterMetadata($character, $idx);
        }

        // Sort based on method
        $sorted = $this->sortCharacters($withMetadata, $sortMethod);

        Log::debug('[BibleOrdering] Characters sorted', [
            'total' => count($sorted),
            'method' => $sortMethod,
            'topCharacter' => $sorted[0]['name'] ?? 'N/A',
        ]);

        return $sorted;
    }

    /**
     * Build display metadata for a character
     */
    protected function buildCharacterMetadata(array $character, int $originalIndex): array
    {
        // Get scene count
        $scenes = $character['scenes'] ?? $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];
        $sceneCount = count($scenes);

        // Get reference status (supports both storage key and legacy base64)
        $refStatus = $character['referenceImageStatus'] ?? 'none';
        $hasReference = $this->hasReferenceImage($character);

        // Determine status badge
        $statusBadge = $this->getCharacterStatusBadge($refStatus, $hasReference, $character);

        // Get role priority
        $role = $character['role'] ?? 'Supporting';
        $rolePriority = self::ROLE_PRIORITY[$role] ?? 3;
        $roleLabel = $this->getRolePriorityLabel($rolePriority);

        // Check DNA completeness
        $dnaCompleteness = $this->calculateDNACompleteness($character);

        return array_merge($character, [
            'originalIndex' => $originalIndex,
            'sceneCount' => $sceneCount,
            'sceneList' => $scenes,
            'hasReference' => $hasReference,
            'referenceStatus' => $refStatus,
            'statusBadge' => $statusBadge,
            'rolePriority' => $rolePriority,
            'roleLabel' => $roleLabel,
            'dnaCompleteness' => $dnaCompleteness,
            'thumbnail' => $hasReference ? 'data:' . ($character['referenceImageMimeType'] ?? 'image/png') . ';base64,' . substr($character['referenceImageBase64'], 0, 100) . '...' : null,
            'hasThumbnail' => $hasReference,
        ]);
    }

    /**
     * Get status badge for character
     */
    protected function getCharacterStatusBadge(string $refStatus, bool $hasReference, array $character): array
    {
        if ($refStatus === 'ready' && $hasReference) {
            return [
                'type' => 'success',
                'label' => 'Ready',
                'icon' => 'check-circle',
                'color' => 'green',
            ];
        }

        if ($refStatus === 'generating') {
            return [
                'type' => 'processing',
                'label' => 'Generating',
                'icon' => 'refresh',
                'color' => 'blue',
            ];
        }

        if ($refStatus === 'error') {
            return [
                'type' => 'error',
                'label' => 'Error',
                'icon' => 'exclamation-circle',
                'color' => 'red',
            ];
        }

        // Check if character has description (can generate reference)
        $hasDescription = !empty($character['description']);
        if ($hasDescription) {
            return [
                'type' => 'pending',
                'label' => 'Needs Reference',
                'icon' => 'camera',
                'color' => 'yellow',
            ];
        }

        return [
            'type' => 'incomplete',
            'label' => 'Incomplete',
            'icon' => 'question-circle',
            'color' => 'gray',
        ];
    }

    /**
     * Get role priority label
     */
    protected function getRolePriorityLabel(int $priority): string
    {
        return match ($priority) {
            1 => 'Protagonist',
            2 => 'Supporting',
            3 => 'Background',
            4 => 'Extra',
            5 => 'Crowd',
            default => 'Unknown',
        };
    }

    /**
     * Calculate DNA completeness percentage
     */
    protected function calculateDNACompleteness(array $character): int
    {
        $totalFields = 0;
        $filledFields = 0;

        // Check hair fields
        $hair = $character['hair'] ?? [];
        foreach (['style', 'color', 'length', 'texture'] as $field) {
            $totalFields++;
            if (!empty($hair[$field])) {
                $filledFields++;
            }
        }

        // Check wardrobe fields
        $wardrobe = $character['wardrobe'] ?? [];
        foreach (['outfit', 'colors', 'style', 'footwear'] as $field) {
            $totalFields++;
            if (!empty($wardrobe[$field])) {
                $filledFields++;
            }
        }

        // Check makeup fields
        $makeup = $character['makeup'] ?? [];
        foreach (['style', 'details'] as $field) {
            $totalFields++;
            if (!empty($makeup[$field])) {
                $filledFields++;
            }
        }

        // Check accessories
        $totalFields++;
        if (!empty($character['accessories'])) {
            $filledFields++;
        }

        // Check description
        $totalFields++;
        if (!empty($character['description'])) {
            $filledFields++;
        }

        return $totalFields > 0 ? (int) round(($filledFields / $totalFields) * 100) : 0;
    }

    /**
     * Sort characters by specified method
     */
    protected function sortCharacters(array $characters, string $method): array
    {
        usort($characters, function ($a, $b) use ($method) {
            switch ($method) {
                case 'alphabetical':
                    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');

                case 'scenes_first':
                    // Sort by scene count descending, then role
                    if ($a['sceneCount'] !== $b['sceneCount']) {
                        return $b['sceneCount'] - $a['sceneCount'];
                    }
                    return $a['rolePriority'] - $b['rolePriority'];

                case 'role_first':
                    // Sort by role priority, then scene count
                    if ($a['rolePriority'] !== $b['rolePriority']) {
                        return $a['rolePriority'] - $b['rolePriority'];
                    }
                    return $b['sceneCount'] - $a['sceneCount'];

                case 'smart':
                default:
                    // Smart ordering: Protagonists first, then by scene count within role
                    // Priority: role -> scene count -> alphabetical
                    if ($a['rolePriority'] !== $b['rolePriority']) {
                        return $a['rolePriority'] - $b['rolePriority'];
                    }
                    if ($a['sceneCount'] !== $b['sceneCount']) {
                        return $b['sceneCount'] - $a['sceneCount'];
                    }
                    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            }
        });

        return $characters;
    }

    // =========================================================================
    // PHASE 5.2: LOCATION BIBLE SMART ORDERING
    // =========================================================================

    /**
     * Get sorted locations with display metadata (Phase 5.2)
     *
     * Returns locations sorted by importance with additional display data:
     * - Scene list
     * - First appearance (scene index)
     * - Frequency (how many scenes use this location)
     * - Interior/Exterior indicator
     * - Time of day
     *
     * @param array $locations Array of location data
     * @param array $scenes Array of scene data (to calculate first appearance)
     * @param string $sortMethod Sort method: 'first_appearance', 'frequency', 'alphabetical'
     * @return array Sorted locations with display metadata
     */
    public function getSortedLocationsWithMetadata(array $locations, array $scenes = [], string $sortMethod = 'first_appearance'): array
    {
        if (empty($locations)) {
            return [];
        }

        // Add metadata to each location
        $withMetadata = [];
        foreach ($locations as $idx => $location) {
            $withMetadata[] = $this->buildLocationMetadata($location, $idx, $scenes);
        }

        // Sort based on method
        $sorted = $this->sortLocations($withMetadata, $sortMethod);

        Log::debug('[BibleOrdering] Locations sorted', [
            'total' => count($sorted),
            'method' => $sortMethod,
            'topLocation' => $sorted[0]['name'] ?? 'N/A',
        ]);

        return $sorted;
    }

    /**
     * Build display metadata for a location
     */
    protected function buildLocationMetadata(array $location, int $originalIndex, array $scenes): array
    {
        // Get assigned scenes
        $assignedScenes = $location['scenes'] ?? [];
        $frequency = count($assignedScenes);

        // Calculate first appearance
        $firstAppearance = !empty($assignedScenes) ? min($assignedScenes) : PHP_INT_MAX;

        // Get reference status (supports both storage key and legacy base64)
        $refStatus = $location['referenceImageStatus'] ?? 'none';
        $hasReference = $this->hasReferenceImage($location);

        // Determine status badge
        $statusBadge = $this->getLocationStatusBadge($refStatus, $hasReference, $location);

        // Get type indicator
        $type = $location['type'] ?? 'exterior';
        $typeLabel = ucfirst($type);
        $typeIcon = $type === 'interior' ? 'home' : 'sun';

        // Get time of day
        $timeOfDay = $location['timeOfDay'] ?? 'day';
        $timeLabel = ucfirst($timeOfDay);

        return array_merge($location, [
            'originalIndex' => $originalIndex,
            'sceneList' => $assignedScenes,
            'frequency' => $frequency,
            'firstAppearance' => $firstAppearance === PHP_INT_MAX ? null : $firstAppearance,
            'hasReference' => $hasReference,
            'referenceStatus' => $refStatus,
            'statusBadge' => $statusBadge,
            'typeLabel' => $typeLabel,
            'typeIcon' => $typeIcon,
            'timeLabel' => $timeLabel,
            'hasThumbnail' => $hasReference,
        ]);
    }

    /**
     * Get status badge for location
     */
    protected function getLocationStatusBadge(string $refStatus, bool $hasReference, array $location): array
    {
        if ($refStatus === 'ready' && $hasReference) {
            return [
                'type' => 'success',
                'label' => 'Ready',
                'icon' => 'check-circle',
                'color' => 'green',
            ];
        }

        if ($refStatus === 'generating') {
            return [
                'type' => 'processing',
                'label' => 'Generating',
                'icon' => 'refresh',
                'color' => 'blue',
            ];
        }

        if ($refStatus === 'error') {
            return [
                'type' => 'error',
                'label' => 'Error',
                'icon' => 'exclamation-circle',
                'color' => 'red',
            ];
        }

        $hasDescription = !empty($location['description']);
        if ($hasDescription) {
            return [
                'type' => 'pending',
                'label' => 'Needs Reference',
                'icon' => 'image',
                'color' => 'yellow',
            ];
        }

        return [
            'type' => 'incomplete',
            'label' => 'Incomplete',
            'icon' => 'question-circle',
            'color' => 'gray',
        ];
    }

    /**
     * Sort locations by specified method
     */
    protected function sortLocations(array $locations, string $method): array
    {
        usort($locations, function ($a, $b) use ($method) {
            switch ($method) {
                case 'alphabetical':
                    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');

                case 'frequency':
                    // Sort by frequency descending, then first appearance
                    if ($a['frequency'] !== $b['frequency']) {
                        return $b['frequency'] - $a['frequency'];
                    }
                    $aFirst = $a['firstAppearance'] ?? PHP_INT_MAX;
                    $bFirst = $b['firstAppearance'] ?? PHP_INT_MAX;
                    return $aFirst - $bFirst;

                case 'first_appearance':
                default:
                    // Sort by first appearance, then frequency
                    $aFirst = $a['firstAppearance'] ?? PHP_INT_MAX;
                    $bFirst = $b['firstAppearance'] ?? PHP_INT_MAX;
                    if ($aFirst !== $bFirst) {
                        return $aFirst - $bFirst;
                    }
                    return $b['frequency'] - $a['frequency'];
            }
        });

        return $locations;
    }

    // =========================================================================
    // PHASE 5.3: VISUAL CONSISTENCY DASHBOARD
    // =========================================================================

    /**
     * Get Visual Consistency Dashboard data (Phase 5.3)
     *
     * Returns comprehensive dashboard data including:
     * - Overall consistency score
     * - Per-character reference status
     * - Per-location reference status
     * - Potential issues flagged
     * - Recommendations
     *
     * @param array $sceneMemory The scene memory with all Bibles
     * @param array $storyboard The storyboard with generated images
     * @return array Dashboard data
     */
    public function getConsistencyDashboard(array $sceneMemory, array $storyboard = []): array
    {
        $characterBible = $sceneMemory['characterBible'] ?? [];
        $locationBible = $sceneMemory['locationBible'] ?? [];
        $styleBible = $sceneMemory['styleBible'] ?? [];

        // Calculate individual scores
        $characterScore = $this->calculateCharacterConsistencyScore($characterBible);
        $locationScore = $this->calculateLocationConsistencyScore($locationBible);
        $styleScore = $this->calculateStyleConsistencyScore($styleBible);

        // Calculate overall score (weighted average)
        $weights = ['character' => 0.5, 'location' => 0.3, 'style' => 0.2];
        $overallScore = (int) round(
            ($characterScore['score'] * $weights['character']) +
            ($locationScore['score'] * $weights['location']) +
            ($styleScore['score'] * $weights['style'])
        );

        // Detect issues
        $issues = $this->detectConsistencyIssues($characterBible, $locationBible, $styleBible, $storyboard);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($characterScore, $locationScore, $styleScore, $issues);

        // Get quick stats
        $stats = [
            'totalCharacters' => count($characterBible['characters'] ?? []),
            'charactersWithReferences' => $characterScore['withReferences'],
            'totalLocations' => count($locationBible['locations'] ?? []),
            'locationsWithReferences' => $locationScore['withReferences'],
            'hasStyleReference' => $styleScore['hasReference'],
            'totalScenes' => count($storyboard['scenes'] ?? []),
            'scenesGenerated' => $this->countGeneratedScenes($storyboard),
        ];

        return [
            'overallScore' => $overallScore,
            'overallGrade' => $this->getScoreGrade($overallScore),
            'characterScore' => $characterScore,
            'locationScore' => $locationScore,
            'styleScore' => $styleScore,
            'issues' => $issues,
            'recommendations' => $recommendations,
            'stats' => $stats,
            'canGenerateAllReferences' => $this->canGenerateAllReferences($characterBible, $locationBible),
        ];
    }

    /**
     * Calculate character consistency score
     */
    protected function calculateCharacterConsistencyScore(array $characterBible): array
    {
        $characters = $characterBible['characters'] ?? [];
        $enabled = $characterBible['enabled'] ?? false;

        if (!$enabled || empty($characters)) {
            return [
                'score' => 0,
                'enabled' => $enabled,
                'total' => 0,
                'withReferences' => 0,
                'withDescriptions' => 0,
                'dnaComplete' => 0,
                'details' => [],
            ];
        }

        $total = count($characters);
        $withReferences = 0;
        $withDescriptions = 0;
        $dnaComplete = 0;
        $details = [];

        foreach ($characters as $idx => $char) {
            $hasRef = $this->hasReferenceImage($char, requireReady: true);
            $hasDesc = !empty($char['description']);
            $dnaCompleteness = $this->calculateDNACompleteness($char);

            if ($hasRef) $withReferences++;
            if ($hasDesc) $withDescriptions++;
            if ($dnaCompleteness >= 70) $dnaComplete++;

            $details[] = [
                'index' => $idx,
                'name' => $char['name'] ?? 'Unknown',
                'hasReference' => $hasRef,
                'hasDescription' => $hasDesc,
                'dnaCompleteness' => $dnaCompleteness,
                'status' => $hasRef ? 'ready' : ($hasDesc ? 'pending' : 'incomplete'),
            ];
        }

        // Score calculation:
        // - 50% weight for reference images
        // - 30% weight for descriptions
        // - 20% weight for DNA completeness
        $refScore = $total > 0 ? ($withReferences / $total) * 50 : 0;
        $descScore = $total > 0 ? ($withDescriptions / $total) * 30 : 0;
        $dnaScore = $total > 0 ? ($dnaComplete / $total) * 20 : 0;

        return [
            'score' => (int) round($refScore + $descScore + $dnaScore),
            'enabled' => $enabled,
            'total' => $total,
            'withReferences' => $withReferences,
            'withDescriptions' => $withDescriptions,
            'dnaComplete' => $dnaComplete,
            'details' => $details,
        ];
    }

    /**
     * Calculate location consistency score
     */
    protected function calculateLocationConsistencyScore(array $locationBible): array
    {
        $locations = $locationBible['locations'] ?? [];
        $enabled = $locationBible['enabled'] ?? false;

        if (!$enabled || empty($locations)) {
            return [
                'score' => 0,
                'enabled' => $enabled,
                'total' => 0,
                'withReferences' => 0,
                'withDescriptions' => 0,
                'details' => [],
            ];
        }

        $total = count($locations);
        $withReferences = 0;
        $withDescriptions = 0;
        $details = [];

        foreach ($locations as $idx => $loc) {
            $hasRef = $this->hasReferenceImage($loc, requireReady: true);
            $hasDesc = !empty($loc['description']);

            if ($hasRef) $withReferences++;
            if ($hasDesc) $withDescriptions++;

            $details[] = [
                'index' => $idx,
                'name' => $loc['name'] ?? 'Unknown',
                'hasReference' => $hasRef,
                'hasDescription' => $hasDesc,
                'type' => $loc['type'] ?? 'exterior',
                'status' => $hasRef ? 'ready' : ($hasDesc ? 'pending' : 'incomplete'),
            ];
        }

        // Score calculation:
        // - 60% weight for reference images
        // - 40% weight for descriptions
        $refScore = $total > 0 ? ($withReferences / $total) * 60 : 0;
        $descScore = $total > 0 ? ($withDescriptions / $total) * 40 : 0;

        return [
            'score' => (int) round($refScore + $descScore),
            'enabled' => $enabled,
            'total' => $total,
            'withReferences' => $withReferences,
            'withDescriptions' => $withDescriptions,
            'details' => $details,
        ];
    }

    /**
     * Calculate style consistency score
     */
    protected function calculateStyleConsistencyScore(array $styleBible): array
    {
        $enabled = $styleBible['enabled'] ?? false;

        if (!$enabled) {
            return [
                'score' => 0,
                'enabled' => false,
                'hasReference' => false,
                'hasStyle' => false,
                'hasColorGrade' => false,
                'hasAtmosphere' => false,
            ];
        }

        $hasReference = $this->hasReferenceImage($styleBible, requireReady: true);
        $hasStyle = !empty($styleBible['style']);
        $hasColorGrade = !empty($styleBible['colorGrade']);
        $hasAtmosphere = !empty($styleBible['atmosphere']);

        // Score calculation
        $score = 0;
        if ($hasReference) $score += 40;
        if ($hasStyle) $score += 25;
        if ($hasColorGrade) $score += 20;
        if ($hasAtmosphere) $score += 15;

        return [
            'score' => $score,
            'enabled' => $enabled,
            'hasReference' => $hasReference,
            'hasStyle' => $hasStyle,
            'hasColorGrade' => $hasColorGrade,
            'hasAtmosphere' => $hasAtmosphere,
        ];
    }

    /**
     * Detect consistency issues
     */
    protected function detectConsistencyIssues(array $characterBible, array $locationBible, array $styleBible, array $storyboard): array
    {
        $issues = [];

        // Check character issues
        $characters = $characterBible['characters'] ?? [];
        foreach ($characters as $idx => $char) {
            // Character without reference but appears in many scenes
            $sceneCount = count($char['scenes'] ?? $char['appliedScenes'] ?? $char['appearsInScenes'] ?? []);
            $hasRef = $this->hasReferenceImage($char, requireReady: true);

            if (!$hasRef && $sceneCount >= 3) {
                $issues[] = [
                    'type' => 'character_no_reference',
                    'severity' => 'high',
                    'message' => "Character \"{$char['name']}\" appears in {$sceneCount} scenes but has no reference image",
                    'target' => ['type' => 'character', 'index' => $idx],
                    'action' => 'generate_reference',
                ];
            }

            // Main character without DNA
            $role = $char['role'] ?? 'Supporting';
            $isMain = in_array(strtolower($role), ['main', 'protagonist', 'lead', 'primary']);
            $dnaCompleteness = $this->calculateDNACompleteness($char);

            if ($isMain && $dnaCompleteness < 50) {
                $issues[] = [
                    'type' => 'protagonist_incomplete_dna',
                    'severity' => 'medium',
                    'message' => "Protagonist \"{$char['name']}\" has incomplete DNA ({$dnaCompleteness}%)",
                    'target' => ['type' => 'character', 'index' => $idx],
                    'action' => 'auto_populate_dna',
                ];
            }
        }

        // Check location issues
        $locations = $locationBible['locations'] ?? [];
        foreach ($locations as $idx => $loc) {
            $sceneCount = count($loc['scenes'] ?? []);
            $hasRef = $this->hasReferenceImage($loc, requireReady: true);

            if (!$hasRef && $sceneCount >= 2) {
                $issues[] = [
                    'type' => 'location_no_reference',
                    'severity' => 'medium',
                    'message' => "Location \"{$loc['name']}\" is used in {$sceneCount} scenes but has no reference",
                    'target' => ['type' => 'location', 'index' => $idx],
                    'action' => 'generate_reference',
                ];
            }
        }

        // Check style issues
        $styleEnabled = $styleBible['enabled'] ?? false;
        if ($styleEnabled) {
            $hasStyleRef = $this->hasReferenceImage($styleBible);
            $hasStyleDef = !empty($styleBible['style']);

            if (!$hasStyleRef && !$hasStyleDef) {
                $issues[] = [
                    'type' => 'style_undefined',
                    'severity' => 'low',
                    'message' => 'Style Bible is enabled but has no style defined or reference image',
                    'target' => ['type' => 'style'],
                    'action' => 'define_style',
                ];
            }
        }

        // Check storyboard issues
        $scenes = $storyboard['scenes'] ?? [];
        $generatedCount = $this->countGeneratedScenes($storyboard);
        $totalScenes = count($scenes);

        if ($totalScenes > 0 && $generatedCount > 0 && $generatedCount < $totalScenes) {
            $pendingCount = $totalScenes - $generatedCount;
            $issues[] = [
                'type' => 'partial_generation',
                'severity' => 'info',
                'message' => "{$pendingCount} of {$totalScenes} scenes still need images generated",
                'target' => ['type' => 'storyboard'],
                'action' => 'generate_remaining',
            ];
        }

        // Sort by severity
        $severityOrder = ['high' => 1, 'medium' => 2, 'low' => 3, 'info' => 4];
        usort($issues, fn($a, $b) => ($severityOrder[$a['severity']] ?? 5) - ($severityOrder[$b['severity']] ?? 5));

        return $issues;
    }

    /**
     * Generate recommendations based on scores and issues
     */
    protected function generateRecommendations(array $charScore, array $locScore, array $styleScore, array $issues): array
    {
        $recommendations = [];

        // Character recommendations
        if ($charScore['enabled'] && $charScore['total'] > 0) {
            if ($charScore['withReferences'] === 0) {
                $recommendations[] = [
                    'priority' => 'high',
                    'category' => 'characters',
                    'message' => 'Generate reference images for characters to ensure face consistency',
                    'action' => 'generate_all_character_references',
                ];
            } elseif ($charScore['withReferences'] < $charScore['total']) {
                $missing = $charScore['total'] - $charScore['withReferences'];
                $recommendations[] = [
                    'priority' => 'medium',
                    'category' => 'characters',
                    'message' => "{$missing} characters still need reference images",
                    'action' => 'generate_missing_references',
                ];
            }

            if ($charScore['dnaComplete'] < $charScore['total']) {
                $recommendations[] = [
                    'priority' => 'low',
                    'category' => 'characters',
                    'message' => 'Auto-populate DNA for all characters to improve prompt quality',
                    'action' => 'batch_auto_populate_dna',
                ];
            }
        }

        // Location recommendations
        if ($locScore['enabled'] && $locScore['total'] > 0 && $locScore['withReferences'] === 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'locations',
                'message' => 'Generate reference images for locations to ensure environment consistency',
                'action' => 'generate_all_location_references',
            ];
        }

        // Style recommendations
        if ($styleScore['enabled'] && $styleScore['score'] < 50) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'style',
                'message' => 'Complete style configuration for visual consistency',
                'action' => 'configure_style',
            ];
        }

        // Sort by priority
        $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
        usort($recommendations, fn($a, $b) => ($priorityOrder[$a['priority']] ?? 4) - ($priorityOrder[$b['priority']] ?? 4));

        return $recommendations;
    }

    /**
     * Check if all references can be generated
     */
    protected function canGenerateAllReferences(array $characterBible, array $locationBible): bool
    {
        // Need at least one character or location with a description
        $characters = $characterBible['characters'] ?? [];
        $locations = $locationBible['locations'] ?? [];

        foreach ($characters as $char) {
            if (!empty($char['description'])) {
                return true;
            }
        }

        foreach ($locations as $loc) {
            if (!empty($loc['description'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count generated scenes
     */
    protected function countGeneratedScenes(array $storyboard): int
    {
        $count = 0;
        $scenes = $storyboard['scenes'] ?? [];

        foreach ($scenes as $scene) {
            if (!empty($scene['imageUrl']) && ($scene['status'] ?? '') === 'ready') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get grade label for score
     */
    protected function getScoreGrade(int $score): array
    {
        if ($score >= 90) {
            return ['grade' => 'A', 'label' => 'Excellent', 'color' => 'green'];
        }
        if ($score >= 80) {
            return ['grade' => 'B', 'label' => 'Good', 'color' => 'blue'];
        }
        if ($score >= 70) {
            return ['grade' => 'C', 'label' => 'Fair', 'color' => 'yellow'];
        }
        if ($score >= 50) {
            return ['grade' => 'D', 'label' => 'Needs Work', 'color' => 'orange'];
        }
        return ['grade' => 'F', 'label' => 'Critical', 'color' => 'red'];
    }

    /**
     * Get characters that need reference images (Phase 5.3 - One-click generation)
     */
    public function getCharactersNeedingReferences(array $characterBible): array
    {
        $characters = $characterBible['characters'] ?? [];
        $needsReferences = [];

        foreach ($characters as $idx => $char) {
            $hasRef = $this->hasReferenceImage($char, requireReady: true);
            $hasDesc = !empty($char['description']);

            if (!$hasRef && $hasDesc) {
                $needsReferences[] = [
                    'index' => $idx,
                    'name' => $char['name'] ?? 'Unknown',
                    'description' => $char['description'],
                    'role' => $char['role'] ?? 'Supporting',
                ];
            }
        }

        return $needsReferences;
    }

    /**
     * Get locations that need reference images (Phase 5.3 - One-click generation)
     */
    public function getLocationsNeedingReferences(array $locationBible): array
    {
        $locations = $locationBible['locations'] ?? [];
        $needsReferences = [];

        foreach ($locations as $idx => $loc) {
            $hasRef = $this->hasReferenceImage($loc, requireReady: true);
            $hasDesc = !empty($loc['description']);

            if (!$hasRef && $hasDesc) {
                $needsReferences[] = [
                    'index' => $idx,
                    'name' => $loc['name'] ?? 'Unknown',
                    'description' => $loc['description'],
                    'type' => $loc['type'] ?? 'exterior',
                ];
            }
        }

        return $needsReferences;
    }
}
