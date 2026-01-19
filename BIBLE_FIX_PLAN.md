# Bible Fix Plan - Final Solution

## Problem Summary

The Character and Location Bibles are extracting **too many entries** because:

1. **Characters**: AI is told to "EXTRACT ALL" including extras, no filtering happens
2. **Locations**: AI receives contradictory instructions, no consolidation for hierarchical locations

---

## Fix 1: Character Extraction Filtering

### File: `CharacterExtractionService.php`

### Change 1A: Update AI Prompt (lines 269-282)

**REPLACE:**
```php
=== CRITICAL RULES ===
1. **EXTRACT ALL CHARACTERS** - If the script shows 5 people, extract 5 separate characters
2. Each character MUST have a DETAILED description field - this is REQUIRED, never leave it empty
3. Each entry MUST be a SINGLE individual person (never groups)
4. Description must include: age, gender, ethnicity/skin tone, build, hair, eyes, clothing
5. If script mentions "a group" - extract EACH individual as their own character entry
```

**WITH:**
```php
=== CRITICAL RULES ===
1. **EXTRACT MAIN AND RECURRING CHARACTERS ONLY** - Focus on characters that appear in 2+ scenes
2. **EXCLUDE EXTRAS** - Do NOT include: unnamed background characters, generic roles like "Guard 1", "Waiter", "Police Officer 2", crowd members, or one-scene generic characters
3. **MERGE AGE VARIANTS** - If "Young Sarah" and "Sarah" are the same character at different ages, create ONE entry with name "Sarah Chen" and note age variations in description
4. Each character MUST have a DETAILED description field - this is REQUIRED, never leave it empty
5. Description must include: age, gender, ethnicity/skin tone, build, hair, eyes, clothing
6. For groups or crowds, only extract NAMED individuals with plot significance

=== WHO TO INCLUDE ===
- Protagonists and main characters (always include)
- Supporting characters with names who appear in multiple scenes
- Antagonists with names (even if 1-2 scenes)

=== WHO TO EXCLUDE ===
- "Guard 1", "Officer 2", "Waiter", "Pedestrian" - generic numbered/role characters
- "Crowd member", "Bystander", "Extra" - background people
- Unnamed characters with no dialogue or plot significance
- Flashback versions as separate entries (merge with main character)
```

### Change 1B: Add Post-Extraction Filter (new method)

**ADD after `parseResponse()` method:**

```php
/**
 * Filter out extras and merge age variants from extracted characters.
 */
protected function filterAndConsolidateCharacters(array $characters): array
{
    $filtered = [];
    $nameMap = []; // Track base names for age variant merging

    foreach ($characters as $char) {
        // Skip extras and background characters
        if ($this->isExtraOrBackground($char)) {
            Log::debug('CharacterExtraction: Filtered out extra/background', [
                'name' => $char['name'],
                'role' => $char['role'] ?? 'unknown',
            ]);
            continue;
        }

        // Check for age variants (e.g., "Young Sarah" should merge with "Sarah")
        $baseName = $this->extractBaseName($char['name']);

        if (isset($nameMap[$baseName])) {
            // Merge with existing character
            $existingIdx = $nameMap[$baseName];
            $filtered[$existingIdx] = $this->mergeAgeVariants($filtered[$existingIdx], $char);
            Log::debug('CharacterExtraction: Merged age variant', [
                'variant' => $char['name'],
                'mergedWith' => $filtered[$existingIdx]['name'],
            ]);
        } else {
            // Add as new character
            $nameMap[$baseName] = count($filtered);
            $filtered[] = $char;
        }
    }

    return $filtered;
}

/**
 * Check if character is an extra or background character that should be filtered.
 */
protected function isExtraOrBackground(array $char): bool
{
    $name = strtolower($char['name'] ?? '');
    $role = strtolower($char['role'] ?? '');
    $sceneCount = count($char['appearsInScenes'] ?? []);

    // Role-based filtering
    if (in_array($role, ['extra', 'background', 'crowd'])) {
        return true;
    }

    // Generic numbered character pattern (Guard 1, Officer 2, etc.)
    if (preg_match('/^(guard|officer|soldier|enforcer|agent|worker|employee|servant|waiter|waitress|bartender|driver|pilot|nurse|doctor|official|protestor|bystander|pedestrian|customer|patron|guest|member|person|man|woman|figure|individual)\s*\d*$/i', $name)) {
        return true;
    }

    // Generic role-only names
    $genericNames = ['the waiter', 'the guard', 'the officer', 'a stranger', 'mysterious figure',
                     'shadowy figure', 'hooded figure', 'masked figure', 'crowd member', 'bystander'];
    if (in_array($name, $genericNames)) {
        return true;
    }

    // Single-scene characters with generic names (Supporting role + 1 scene + no proper name)
    if ($sceneCount <= 1 && $role === 'supporting' && !$this->hasProperName($name)) {
        return true;
    }

    return false;
}

/**
 * Check if name appears to be a proper name vs generic role.
 */
protected function hasProperName(string $name): bool
{
    // Proper names typically start with capital letter and don't have numbers
    // Generic roles are like "Guard 1", "Officer 2", "The Waiter"

    $name = trim($name);

    // Starts with "The" or "A/An" - likely generic
    if (preg_match('/^(the|a|an)\s+/i', $name)) {
        return false;
    }

    // Contains numbers - likely generic (Guard 1, Officer 2)
    if (preg_match('/\d+/', $name)) {
        return false;
    }

    // Has first+last name structure - likely proper
    if (preg_match('/^[A-Z][a-z]+\s+[A-Z][a-z]+/', $name)) {
        return true;
    }

    return true; // Default to proper name
}

/**
 * Extract base name for age variant detection.
 * "Young Sarah Chen" -> "sarah chen"
 * "Old Marcus" -> "marcus"
 */
protected function extractBaseName(string $name): string
{
    $name = strtolower(trim($name));

    // Remove age prefixes
    $agePatterns = [
        '/^young\s+/',
        '/^old\s+/',
        '/^elderly\s+/',
        '/^teenage?\s+/',
        '/^child\s+/',
        '/^baby\s+/',
        '/^adult\s+/',
        '/^middle[- ]aged?\s+/',
    ];

    foreach ($agePatterns as $pattern) {
        $name = preg_replace($pattern, '', $name);
    }

    return trim($name);
}

/**
 * Merge two characters that are age variants of each other.
 */
protected function mergeAgeVariants(array $main, array $variant): array
{
    // Combine scenes
    $allScenes = array_unique(array_merge(
        $main['appearsInScenes'] ?? [],
        $variant['appearsInScenes'] ?? []
    ));
    sort($allScenes);
    $main['appearsInScenes'] = $allScenes;

    // Add age variant note to description if not already there
    $variantName = $variant['name'];
    if (stripos($main['description'], 'age variant') === false) {
        $main['description'] .= " (Also appears as {$variantName} in flashback/memory scenes)";
    }

    // Combine traits
    $main['traits'] = array_unique(array_merge(
        $main['traits'] ?? [],
        $variant['traits'] ?? []
    ));

    // Keep the "more important" role
    $roleHierarchy = ['main' => 1, 'supporting' => 2, 'background' => 3, 'extra' => 4];
    $mainPriority = $roleHierarchy[strtolower($main['role'] ?? 'supporting')] ?? 2;
    $variantPriority = $roleHierarchy[strtolower($variant['role'] ?? 'supporting')] ?? 2;
    if ($variantPriority < $mainPriority) {
        $main['role'] = $variant['role'];
    }

    return $main;
}
```

### Change 1C: Call filter in extractCharacters()

**MODIFY `extractCharacters()` method to call filter:**

After parsing, add:
```php
// Filter and consolidate before returning
$characters = $this->filterAndConsolidateCharacters($result['characters'] ?? []);

Log::info('CharacterExtraction: Filtered characters', [
    'beforeFilter' => count($result['characters'] ?? []),
    'afterFilter' => count($characters),
]);

return [
    'characters' => $characters,
    'hasHumanCharacters' => count($characters) > 0,
    'suggestedStyleNote' => $result['suggestedStyleNote'] ?? '',
];
```

---

## Fix 2: Location Extraction Consolidation

### File: `LocationExtractionService.php`

### Change 2A: Update AI Prompt (lines 206-210, 286-290)

**REPLACE contradictory instructions:**

```php
=== CRITICAL RULES ===
1. **CONSOLIDATE RELATED LOCATIONS** - Different rooms/areas of the same building = ONE location
   - "Living Room", "Kitchen", "Bedroom" in same house = "The House" or "Home"
   - "Office Lobby", "Office Meeting Room", "Office Rooftop" = "Corporate Office"
2. **MERGE OUTDOOR AREAS** - Related outdoor spaces = ONE location
   - "Forest Path", "Forest Clearing", "Dense Woods" = "Forest"
   - "Beach Shore", "Beach Dunes" = "Beach"
3. **TIME IS NOT A LOCATION** - Same place at different times = ONE entry
   - "Dock at Dawn" and "Dock at Night" = "Dock" (note time variations)
4. Focus on DISTINCT VISUAL ENVIRONMENTS that need consistency
5. A 5-minute film typically has 3-6 distinct locations, not 10+

=== CONSOLIDATION EXAMPLES ===
WRONG (fragmented):
- "John's House Exterior"
- "John's House Living Room"
- "John's House Kitchen"
- "John's House Bedroom"

CORRECT (consolidated):
- "John's House" (interior/exterior, includes all rooms)

WRONG (fragmented):
- "Forest Path"
- "Forest Clearing"
- "Dark Forest"

CORRECT (consolidated):
- "Forest" (various areas within)
```

### Change 2B: Add Post-Extraction Consolidation (new method)

**ADD after `parseResponse()` method:**

```php
/**
 * Consolidate fragmented locations into unified entries.
 */
protected function consolidateLocations(array $locations): array
{
    $consolidated = [];
    $hierarchyMap = []; // Track "Building" -> index for merging "Building Room"

    foreach ($locations as $loc) {
        $name = $loc['name'] ?? '';
        $baseName = $this->extractLocationBaseName($name);

        // Check if this is a sub-location of an existing entry
        if (isset($hierarchyMap[$baseName])) {
            // Merge into existing location
            $existingIdx = $hierarchyMap[$baseName];
            $consolidated[$existingIdx] = $this->mergeLocations($consolidated[$existingIdx], $loc);
            Log::debug('LocationExtraction: Merged sub-location', [
                'subLocation' => $name,
                'mergedWith' => $consolidated[$existingIdx]['name'],
            ]);
            continue;
        }

        // Check if any existing location is a sub-location of this one
        $foundParent = false;
        foreach ($consolidated as $idx => $existing) {
            $existingBase = $this->extractLocationBaseName($existing['name']);
            if ($this->isSubLocationOf($name, $existing['name']) || $this->isSubLocationOf($existing['name'], $name)) {
                // Merge with existing
                $consolidated[$idx] = $this->mergeLocations($existing, $loc);
                $hierarchyMap[$baseName] = $idx;
                $foundParent = true;
                break;
            }
        }

        if (!$foundParent) {
            // Add as new location
            $hierarchyMap[$baseName] = count($consolidated);
            $consolidated[] = $loc;
        }
    }

    return $consolidated;
}

/**
 * Extract base name for location hierarchy detection.
 * "Elias's Home Living Room" -> "elias's home"
 * "Corporate Office Lobby" -> "corporate office"
 */
protected function extractLocationBaseName(string $name): string
{
    $name = strtolower(trim($name));

    // Common room/area suffixes to remove
    $suffixes = [
        '/\s+(room|lobby|hallway|corridor|entrance|foyer|kitchen|bedroom|bathroom|living\s*room|dining\s*room|study|office|basement|attic|garage|garden|yard|patio|balcony|rooftop|exterior|interior)$/i',
        '/\s+(path|clearing|trail|meadow|shore|dunes|cliff|peak|base|edge)$/i',
        '/\s+at\s+(dawn|dusk|night|day|noon|morning|evening|sunset|sunrise)$/i',
    ];

    foreach ($suffixes as $pattern) {
        $name = preg_replace($pattern, '', $name);
    }

    return trim($name);
}

/**
 * Check if location A is a sub-location of location B.
 */
protected function isSubLocationOf(string $a, string $b): bool
{
    $a = strtolower(trim($a));
    $b = strtolower(trim($b));

    // A contains B as prefix: "Elias's Home Living Room" contains "Elias's Home"
    if (strpos($a, $b) === 0 && strlen($a) > strlen($b)) {
        return true;
    }

    // B contains A as prefix: "Elias's Home" is prefix of "Elias's Home Living Room"
    if (strpos($b, $a) === 0 && strlen($b) > strlen($a)) {
        return true;
    }

    // Check for possessive variations
    $aBase = $this->extractLocationBaseName($a);
    $bBase = $this->extractLocationBaseName($b);

    return $aBase === $bBase && $aBase !== $a && $aBase !== $b;
}

/**
 * Merge two locations that are related (same building/area).
 */
protected function mergeLocations(array $main, array $sub): array
{
    // Use shorter/simpler name (the parent location)
    if (strlen($sub['name']) < strlen($main['name'])) {
        $main['name'] = $sub['name'];
    }

    // Combine scenes
    $allScenes = array_unique(array_merge(
        $main['appearsInScenes'] ?? [],
        $sub['appearsInScenes'] ?? []
    ));
    sort($allScenes);
    $main['appearsInScenes'] = $allScenes;

    // Merge descriptions (keep the more detailed one, note variations)
    if (strlen($sub['description'] ?? '') > strlen($main['description'] ?? '')) {
        $main['description'] = $sub['description'];
    }

    // Add note about sub-areas
    $subName = $sub['name'];
    if (stripos($main['description'], $subName) === false && $subName !== $main['name']) {
        $main['description'] .= " (Includes various areas: {$subName})";
    }

    // Track time of day variations
    if (!isset($main['timeVariations'])) {
        $main['timeVariations'] = [];
    }
    if (!empty($sub['timeOfDay']) && !in_array($sub['timeOfDay'], $main['timeVariations'])) {
        $main['timeVariations'][] = $sub['timeOfDay'];
    }

    return $main;
}
```

### Change 2C: Call consolidation in extractLocations()

**MODIFY `extractLocations()` method:**

After parsing, add:
```php
// Consolidate fragmented locations
$locations = $this->consolidateLocations($result['locations'] ?? []);

Log::info('LocationExtraction: Consolidated locations', [
    'beforeConsolidation' => count($result['locations'] ?? []),
    'afterConsolidation' => count($locations),
]);

return [
    'locations' => $locations,
    'hasDistinctLocations' => count($locations) > 0,
    'suggestedStyleNote' => $result['suggestedStyleNote'] ?? '',
];
```

---

## Fix 3: Add Validation Warnings

### File: `VideoWizard.php`

### Add Bible Health Check Method

```php
/**
 * Check if Bible entries are within expected ranges and warn if not.
 */
protected function validateBibleHealth(): array
{
    $warnings = [];
    $duration = $this->duration ?? 300; // seconds
    $sceneCount = count($this->script['scenes'] ?? []);

    // Expected ranges based on duration
    $maxCharacters = ceil($duration / 60) + 3; // ~1 char per minute + 3
    $maxLocations = ceil($sceneCount / 1.5); // ~1 location per 1.5 scenes

    $characterCount = count($this->sceneMemory['characterBible']['characters'] ?? []);
    $locationCount = count($this->sceneMemory['locationBible']['locations'] ?? []);

    if ($characterCount > $maxCharacters) {
        $warnings[] = [
            'type' => 'character_overflow',
            'message' => "Character Bible has {$characterCount} entries (expected max {$maxCharacters} for {$duration}s video). Review for extras that should be removed.",
            'severity' => 'high',
        ];
    }

    if ($locationCount > $maxLocations) {
        $warnings[] = [
            'type' => 'location_overflow',
            'message' => "Location Bible has {$locationCount} entries (expected max {$maxLocations} for {$sceneCount} scenes). Review for locations that should be consolidated.",
            'severity' => 'high',
        ];
    }

    // Check for fragmented locations (same base name appearing multiple times)
    $locationBases = [];
    foreach ($this->sceneMemory['locationBible']['locations'] ?? [] as $loc) {
        $base = $this->extractLocationBaseName($loc['name'] ?? '');
        $locationBases[$base] = ($locationBases[$base] ?? 0) + 1;
    }

    foreach ($locationBases as $base => $count) {
        if ($count > 1) {
            $warnings[] = [
                'type' => 'fragmented_location',
                'message' => "Location '{$base}' appears fragmented into {$count} entries. Consider consolidating.",
                'severity' => 'medium',
            ];
        }
    }

    // Check for one-scene characters
    $oneSceneCharacters = [];
    foreach ($this->sceneMemory['characterBible']['characters'] ?? [] as $char) {
        if (count($char['appearsInScenes'] ?? []) <= 1) {
            $oneSceneCharacters[] = $char['name'];
        }
    }

    if (count($oneSceneCharacters) > 1) {
        $warnings[] = [
            'type' => 'single_scene_characters',
            'message' => "Characters appearing in only 1 scene: " . implode(', ', $oneSceneCharacters) . ". Consider removing from Bible.",
            'severity' => 'medium',
        ];
    }

    return $warnings;
}
```

---

## Implementation Order

1. **Phase A** (Critical - Fix Extraction):
   - [ ] Update CharacterExtractionService.php prompts
   - [ ] Add `filterAndConsolidateCharacters()` method
   - [ ] Update LocationExtractionService.php prompts
   - [ ] Add `consolidateLocations()` method

2. **Phase B** (Validation):
   - [ ] Add `validateBibleHealth()` to VideoWizard.php
   - [ ] Display warnings in UI when Bible has issues
   - [ ] Add "Clean Up Bible" button for manual consolidation

3. **Phase C** (Testing):
   - [ ] Test with 5-minute drama script
   - [ ] Verify characters: expect 3-5, not 12
   - [ ] Verify locations: expect 4-6, not 12
   - [ ] Verify no "Guard 1", "Young Sarah" as separate entries
   - [ ] Verify "Elias's Home *" consolidated to single entry

---

## Expected Results After Fix

### Character Bible (5-min film)
| Before | After |
|--------|-------|
| Sarah Chen | Sarah Chen |
| Elias Chen | Elias Chen |
| Young Sarah | *(merged with Sarah)* |
| Marcus Hale | Marcus Hale |
| Amelia Hawthorne | Amelia Hawthorne |
| Corrupt Logging Heir | Corrupt Logging Heir *(maybe)* |
| Shadowy Enforcer 1 | *(filtered)* |
| Shadowy Enforcer 2 | *(filtered)* |
| Police Officer 1 | *(filtered)* |
| Female Official 1 | *(filtered)* |
| Female Official 2 | *(filtered)* |
| **Total: 12** | **Total: 4-5** |

### Location Bible (5-min film)
| Before | After |
|--------|-------|
| Eldridge Bay Coastal Town Dock | Eldridge Bay Dock |
| Sarah's City Apartment | Sarah's Apartment |
| Coastal Highway | *(maybe keep)* |
| Eldridge Bay Police Station | *(maybe keep)* |
| Elias's Home Exterior | *(merged)* |
| Elias's Home Living Room | *(merged)* |
| Elias's Home Study | *(merged)* |
| Elias's Home Basement | *(merged)* |
| Eldridge Bay Diner | *(maybe keep)* |
| Foggy Forest Paths | Forest |
| Abandoned Logging Mill | Logging Mill |
| Eldridge Bay Storm Shelter | *(maybe keep)* |
| **Total: 12** | **Total: 5-7** |

---

## Success Criteria

- [ ] No extras (Guard 1, Waiter, etc.) in Character Bible
- [ ] Age variants merged (no Young X + X as separate)
- [ ] Building rooms consolidated (no Building Room + Building Kitchen)
- [ ] 5-min film has ≤6 characters in Bible
- [ ] 5-min film has ≤7 locations in Bible
- [ ] UI shows warning if Bible exceeds expected size
