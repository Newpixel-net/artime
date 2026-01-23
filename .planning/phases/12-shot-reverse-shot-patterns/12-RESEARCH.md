# Phase 12: Shot/Reverse-Shot Patterns - Research

**Researched:** 2026-01-23
**Domain:** Cinematographic dialogue coverage patterns and spatial continuity
**Confidence:** HIGH

## Summary

Shot/reverse-shot is a fundamental filmmaking technique for dialogue scenes where two characters are filmed separately using different camera angles, toggling back and forth between alternating perspectives. This pattern is governed by the 180-degree rule, which maintains spatial consistency by keeping the camera on one side of an imaginary line (axis of action) between characters.

The existing codebase already has extensive infrastructure for this pattern in `DialogueSceneDecomposerService` (Lines 488-578 handle spatial data calculation and shot pairing). Phase 11 created the 1:1 speech-to-shot mapping foundation, and Phase 12 builds on it by activating and validating the shot/reverse-shot patterns that are already scaffolded but not fully utilized.

**Primary recommendation:** Activate existing `pairReverseShots()`, enhance `calculateSpatialData()` to ensure proper character positioning, and validate 180-degree rule enforcement across the full dialogue sequence.

## Standard Stack

The established libraries/tools for this domain:

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP 8.x | 8.1+ | Backend logic | Laravel framework requirement |
| Laravel | 10.x | Application framework | Project standard, existing codebase |
| DialogueSceneDecomposerService | Current | Dialogue pattern engine | Already implements shot/reverse-shot (lines 534-578) |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Illuminate\Support\Facades\Log | Laravel | Debugging spatial data | Validate continuity calculations |
| array functions | PHP native | Shot manipulation | Pairing, filtering, spatial mapping |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Custom pairing logic | Existing pairReverseShots() | Custom = reinventing wheel, existing = proven pattern |
| Manual spatial calculation | Existing calculateSpatialData() | Manual = error-prone, existing = 180-rule aware |

**Installation:**
No new packages required - all functionality exists in current codebase.

## Architecture Patterns

### Recommended Project Structure
```
Services/
├── DialogueSceneDecomposerService.php  # Shot/reverse-shot core logic
├── DynamicShotEngine.php               # Shot type selection
└── (No new files needed for Phase 12)
```

### Pattern 1: Shot/Reverse-Shot with 180-Degree Rule
**What:** Alternating camera angles between characters while maintaining spatial consistency
**When to use:** 2-character dialogue scenes (already detected by existing code)
**Example:**
```php
// Source: DialogueSceneDecomposerService.php lines 488-508
protected function calculateSpatialData(string $speakerName, array $characters, string $shotType): array
{
    // Determine which character is speaking (A or B)
    $isCharacterA = count($characters) >= 1 && strcasecmp($speakerName, $characters[0]) === 0;

    // Camera position follows 180-degree rule
    // When shooting Character A: camera is on left, A is screen-right, looks screen-left
    // When shooting Character B: camera is on left, B is screen-left, looks screen-right

    $spatial = [
        'cameraPosition' => $this->axisLockSide, // Always same side (180-degree rule)
        'cameraAngle' => $this->determineCameraAngle($shotType),
        'subjectPosition' => $isCharacterA ? 'right' : 'left', // A=right, B=left
        'eyeLineDirection' => $isCharacterA ? 'screen-left' : 'screen-right',
        'lookingAt' => $isCharacterA && count($characters) >= 2 ? $characters[1] : ($characters[0] ?? null),
        'reverseOf' => null, // Set later when pairing
        'pairId' => null,    // Set later when pairing
    ];

    return $spatial;
}
```

### Pattern 2: Reverse Shot Pairing
**What:** Linking consecutive shots from different characters into pairs
**When to use:** After shot creation, before duration calculation
**Example:**
```php
// Source: DialogueSceneDecomposerService.php lines 534-578
protected function pairReverseShots(array $shots): array
{
    $pairCounter = 0;
    $lastSpeakerShot = [];

    foreach ($shots as $index => &$shot) {
        // Skip non-dialogue shots (establishing, reaction without speaker)
        if (empty($shot['speakingCharacter'])) {
            continue;
        }

        $speaker = $shot['speakingCharacter'];

        // Check if there's a previous shot from different speaker (reverse candidate)
        foreach ($lastSpeakerShot as $prevSpeaker => $prevIndex) {
            if ($prevSpeaker !== $speaker && $prevIndex !== null) {
                // This is a reverse of the previous speaker's shot
                $pairId = 'pair_' . $pairCounter;

                // Link current shot to previous
                $shot['spatial']['reverseOf'] = $prevIndex;
                $shot['spatial']['pairId'] = $pairId;

                // Link previous shot to current
                $shots[$prevIndex]['spatial']['pairId'] = $pairId;

                $pairCounter++;
                break;
            }
        }

        // Track this speaker's latest shot
        $lastSpeakerShot[$speaker] = $index;
    }

    return $shots;
}
```

### Pattern 3: Character Alternation Enforcement
**What:** Ensure shots alternate between characters in dialogue sequences
**When to use:** Phase 11's 1:1 speech-to-shot mapping naturally creates this when speech segments alternate
**Example:**
```php
// Source: Phase 11 implementation - enhanceShotsWithDialoguePatterns() lines 1708-1788
// Shots created from speech segments already alternate if speakers alternate in transcript
// Phase 12 validates this pattern is maintained
$speakers = $this->extractSpeakersFromShots($shots);
// Validate alternation: shots[i].speaker !== shots[i+1].speaker for dialogue
```

### Anti-Patterns to Avoid
- **Breaking 180-degree rule:** Never place camera on opposite side of axis - destroys spatial continuity
- **Same speaker consecutive without reaction:** If speaker has 2+ consecutive shots, insert reaction shot from listener
- **Missing spatial data:** Every dialogue shot MUST have spatial array with eyeLineDirection, subjectPosition
- **Eyeline mismatch:** Character A looking screen-left must face Character B looking screen-right

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| 180-degree spatial calculation | Custom left/right logic | `calculateSpatialData()` (line 488) | Handles character A/B positioning, eyeline direction, camera angle |
| Shot pairing logic | Manual indexing | `pairReverseShots()` (line 534) | Tracks speaker history, creates pair IDs, links spatial data |
| Character lookup from speakers | Name matching loops | `buildCharacterLookup()` (line 336) | Handles fuzzy matching, extracts voice IDs, gender |
| Eyeline direction determination | Custom string logic | `calculateSpatialData()` subjectPosition logic | Automatically derives screen-left/right from character position |
| Coverage validation | Manual counting | `analyzeCoverage()` (line 587) | Checks requirements, detects missing types, validates patterns |

**Key insight:** `DialogueSceneDecomposerService` already implements Hollywood-standard shot/reverse-shot patterns. Phase 12 is about activating/validating these existing patterns, not building new ones.

## Common Pitfalls

### Pitfall 1: Axis Jump (Breaking 180-Degree Rule)
**What goes wrong:** Camera crosses imaginary line between characters, causing them to appear on wrong sides of frame
**Why it happens:** Misunderstanding which side is "left" vs which character is "A"
**How to avoid:**
- Always use `$this->axisLockSide = 'left'` (line 31) - camera never moves
- Character A always screen-right, B always screen-left (enforced in line 500)
- Never modify `cameraPosition` after establishing it
**Warning signs:**
- Both characters looking same direction in consecutive shots
- Character positions flip between cuts
- Log shows `subjectPosition` changing for same speaker

### Pitfall 2: Missing Reverse Pairing
**What goes wrong:** Shots created but not linked as reverse pairs, losing spatial relationship data
**Why it happens:** Forgetting to call `pairReverseShots()` after shot creation
**How to avoid:**
- Always call `pairReverseShots()` after `enhanceShotsWithDialoguePatterns()`
- Verify `spatial.pairId` exists on dialogue shots
- Check `spatial.reverseOf` points to valid previous shot index
**Warning signs:**
- `pairId` is null on dialogue shots
- Log shows "pairCount: 0" when multiple speakers exist
- No spatial continuity between character shots

### Pitfall 3: Single Character Constraint Violation
**What goes wrong:** Attempting to show multiple characters in one shot despite model limitation
**Why it happens:** Two-shot/establishing shot types default to showing both characters
**How to avoid:**
- **CRITICAL:** Phase 11 decision: "Single character visible per shot (model constraint enforced)"
- Skip establishing two-shots that show both characters
- Convert "two-shot" type to single-character wide shot
- OTS shots: show listener's SHOULDER only (blurred, partial), not full character
**Warning signs:**
- Shot type is "two-shot" or "establishing" with 2+ characters in charactersInShot
- OTS foregroundCharacter marked as full character instead of partial
- Generation fails due to "multiple characters" error

### Pitfall 4: Reaction Shot Insertion Breaking Pairing
**What goes wrong:** Reaction shots inserted between speaker alternations, breaking A→B→A pattern
**Why it happens:** `shouldInsertReaction()` (line 1478) doesn't check if it breaks pairing
**How to avoid:**
- Insert reactions AFTER completing reverse pair (after B responds to A)
- Reaction shots should not have `speakingCharacter` (line 541 skips them in pairing)
- Never insert reaction between A's shot and B's reverse shot
**Warning signs:**
- Pattern is A→reaction→B instead of A→B→reaction
- Reaction shot has `pairId` (should not be paired)
- Consecutive reactions without dialogue between them

### Pitfall 5: Emotional Intensity Overriding Shot Type
**What goes wrong:** High intensity forces extreme-close-up, losing OTS coverage needed for dialogue
**Why it happens:** `selectShotTypeForIntensity()` (line 1061) prioritizes emotion over coverage
**How to avoid:**
- Dialogue scenes should favor medium/OTS even at high intensity
- Climax can use close-up but not extreme-close-up (lose spatial context)
- Reserve ECU for single dramatic moments, not entire conversations
**Warning signs:**
- All shots in dialogue are extreme-close-up
- No OTS or medium shots in conversation
- Lost sense of spatial relationship between characters

## Code Examples

Verified patterns from official sources:

### Activating Shot/Reverse-Shot in Phase 11 Flow
```php
// Source: VideoWizard.php line 18016 (existing Phase 11 code)
// Enhance with DialogueSceneDecomposerService patterns (shot types, camera positions)
$enhancedShots = $dialogueDecomposer->enhanceShotsWithDialoguePatterns(
    $shotsFromSpeech,  // Created by createShotsFromSpeechSegments (1:1 mapping)
    $scene,
    $characterBible
);

// Phase 12 addition: Validate shot/reverse-shot patterns are applied
$this->validateShotReversePattern($enhancedShots, $sceneIndex);
```

### Validating 180-Degree Rule Compliance
```php
// New validation method for Phase 12
protected function validateShotReversePattern(array $shots, int $sceneIndex): void
{
    $dialogueShots = array_filter($shots, fn($s) => !empty($s['speakingCharacter']));

    if (count($dialogueShots) < 2) {
        return; // Not a dialogue scene
    }

    $axisViolations = [];
    $cameraPosition = null;

    foreach ($dialogueShots as $index => $shot) {
        $spatial = $shot['spatial'] ?? [];

        // Check 180-degree rule: camera position must be consistent
        if ($cameraPosition === null) {
            $cameraPosition = $spatial['cameraPosition'] ?? null;
        } elseif (isset($spatial['cameraPosition']) && $spatial['cameraPosition'] !== $cameraPosition) {
            $axisViolations[] = "Shot {$index}: Camera jumped to {$spatial['cameraPosition']} (expected {$cameraPosition})";
        }

        // Check eyeline match: consecutive shots must have opposite eyelines
        if ($index > 0) {
            $prevShot = $dialogueShots[$index - 1];
            $prevEyeline = $prevShot['spatial']['eyeLineDirection'] ?? null;
            $currentEyeline = $spatial['eyeLineDirection'] ?? null;

            if ($prevEyeline === $currentEyeline && $shot['speakingCharacter'] !== $prevShot['speakingCharacter']) {
                $axisViolations[] = "Shot {$index}: Eyeline mismatch - both characters looking {$currentEyeline}";
            }
        }
    }

    if (!empty($axisViolations)) {
        Log::warning('Shot/Reverse-Shot: 180-degree rule violations detected', [
            'scene' => $sceneIndex,
            'violations' => $axisViolations,
        ]);
    }
}
```

### Ensuring Character Alternation
```php
// Validate alternating character shots
protected function validateCharacterAlternation(array $shots, int $sceneIndex): array
{
    $issues = [];
    $lastSpeaker = null;
    $consecutiveCount = 0;

    foreach ($shots as $index => $shot) {
        $speaker = $shot['speakingCharacter'] ?? null;

        if ($speaker === null) {
            // Reaction shot, narrator, or establishing - resets alternation
            $lastSpeaker = null;
            $consecutiveCount = 0;
            continue;
        }

        if ($speaker === $lastSpeaker) {
            $consecutiveCount++;

            // FLOW-04 requirement: Alternating character shots in dialogue sequences
            if ($consecutiveCount > 1) {
                $issues[] = [
                    'type' => 'consecutive_speaker',
                    'shot' => $index,
                    'speaker' => $speaker,
                    'count' => $consecutiveCount + 1,
                    'fix' => 'Insert reaction shot from listener or split into separate beats',
                ];
            }
        } else {
            $consecutiveCount = 0;
        }

        $lastSpeaker = $speaker;
    }

    return $issues;
}
```

### Single Character Per Shot Enforcement
```php
// Enforce FLOW-02: Single character visible per shot (model constraint)
protected function enforceSingleCharacterConstraint(array $shots): array
{
    foreach ($shots as &$shot) {
        $charactersInShot = $shot['charactersInShot'] ?? [];

        // Two-shot and establishing shots violate single-character constraint
        if (in_array($shot['type'] ?? '', ['two-shot', 'establishing']) && count($charactersInShot) > 1) {
            // Convert to single-character wide shot
            $shot['type'] = 'wide';
            $shot['purpose'] = 'context';
            $shot['charactersInShot'] = [$charactersInShot[0]]; // Keep only first character

            Log::debug('Shot/Reverse-Shot: Converted multi-character shot to single-character', [
                'original_type' => $shot['type'],
                'new_type' => 'wide',
                'character' => $charactersInShot[0],
            ]);
        }

        // OTS shots: foreground character is partial/blurred, not a full character
        if (($shot['type'] ?? '') === 'over-the-shoulder') {
            $otsData = $shot['otsData'] ?? [];
            $focusCharacter = $otsData['focusCharacter'] ?? $otsData['backgroundCharacter'] ?? null;

            if ($focusCharacter) {
                // Only the focused character counts as "in shot"
                $shot['charactersInShot'] = [$focusCharacter];
                $shot['otsData']['foregroundVisible'] = 'shoulder and partial head'; // Clarify it's partial
                $shot['otsData']['foregroundBlur'] = true; // Ensure blur
            }
        }
    }

    return $shots;
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Proportional segment distribution | 1:1 speech-to-shot mapping | Phase 11 (2026-01-23) | Each speech segment creates own shot, enabling proper shot/reverse-shot |
| Manual shot type selection | Intensity-driven shot types | Phase 5 (Emotional Arc) | Emotional intensity determines close-up vs medium vs wide |
| Static shot counts (min/max) | Content-driven shot counts | Phase 3 (DynamicShotEngine) | No artificial cap, 10+ shots if dialogue demands |
| Multi-character shots | Single character per shot | Phase 12 (model constraint) | Embrace limitation, use shot/reverse-shot instead of two-shots |

**Deprecated/outdated:**
- **distributeSpeechSegmentsToShots()**: Deprecated in Phase 11 (line 23254), replaced by `createShotsFromSpeechSegments()`
- **Two-shot establishing**: Violates FLOW-02 single-character constraint, use wide shot of Character A instead
- **Manual reverse pairing**: Use `pairReverseShots()` (line 534) instead of custom indexing

## Open Questions

Things that couldn't be fully resolved:

1. **Over-the-shoulder foreground blur implementation**
   - What we know: Code specifies `foregroundBlur: true` and `foregroundVisible: 'shoulder and partial head'` (line 1309)
   - What's unclear: Does image generation model respect blur instructions in prompt?
   - Recommendation: Test OTS prompts with explicit "blurred shoulder in foreground" and validate results. If model doesn't blur, may need to adjust to "partial silhouette" or "out of focus shoulder"

2. **Reaction shot frequency in alternating dialogue**
   - What we know: `shouldInsertReaction()` (line 1478) triggers on intensity, questions, and every 3 exchanges
   - What's unclear: Does this create too many reactions in rapid back-and-forth dialogue?
   - Recommendation: Test with rapid dialogue (8+ exchanges). May need to increase interval to every 4-5 exchanges for faster conversations

3. **Coverage validation auto-fixing**
   - What we know: `analyzeCoverage()` detects issues, `fixCoverageIssues()` attempts auto-fixes (line 709)
   - What's unclear: Do fixes work with 1:1 speech-to-shot constraint? (Can't add establishing if it requires deleting a speech-driven shot)
   - Recommendation: Validate fixes respect 1:1 mapping. May need to disable auto-fixes for speech-driven scenes, only validate/warn

4. **Three-character dialogue handling**
   - What we know: Current pattern is optimized for 2 characters (A/B positioning)
   - What's unclear: How should `calculateSpatialData()` handle 3rd character? (Not in scope for Phase 12 but future concern)
   - Recommendation: Phase 12 focuses on 2-character. For 3+ characters, may need to track "active pair" and position 3rd character as listener/observer

## Sources

### Primary (HIGH confidence)
- [Shot/Reverse Shot Explained - MasterClass (2026)](https://www.masterclass.com/articles/shot-reverse-shot) - Official filmmaking technique
- [Understanding the 180-Degree Rule - MasterClass (2026)](https://www.masterclass.com/articles/understanding-the-180-degree-rule-in-cinematography) - Spatial continuity fundamentals
- [Over the Shoulder Shot Guide - StudioBinder](https://www.studiobinder.com/blog/over-the-shoulder-shot/) - OTS framing best practices
- DialogueSceneDecomposerService.php (lines 488-578, 1708-1788) - Existing implementation verified

### Secondary (MEDIUM confidence)
- [Eyeline Match - MasterClass (2026)](https://www.masterclass.com/articles/film-101-what-are-eyelines-how-to-use-eyeline-match-to-tell-a-story-and-drive-a-narrative) - Eyeline continuity principles
- [Screen Direction - StudioBinder](https://www.studiobinder.com/blog/what-is-screen-direction-in-film/) - Left/right positioning
- [Continuity in Dialogue Scenes - Fiveable](https://library.fiveable.me/motion-picture-editing/unit-3/continuity-dialogue-scenes/study-guide/W8nDPcMwrGnm30EX) - Coverage patterns

### Tertiary (LOW confidence)
- [Dialogue Sequence Detection in Movies - ResearchGate](https://www.researchgate.net/publication/225672892_Dialogue_Sequence_Detection_in_Movies) - Academic research, not directly applicable to implementation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Verified in existing codebase, no new dependencies needed
- Architecture: HIGH - Patterns already implemented in DialogueSceneDecomposerService lines 488-578, 1708-1788
- Pitfalls: HIGH - Documented from existing code comments and Hollywood cinematography sources
- Single-character constraint: HIGH - Explicit Phase 11 decision documented in REQUIREMENTS.md

**Research date:** 2026-01-23
**Valid until:** 60 days (stable cinematographic principles, mature codebase)

**Critical finding:** Phase 12 is NOT about building new shot/reverse-shot logic. The infrastructure exists (lines 488-578 in DialogueSceneDecomposerService). Phase 12 is about:
1. **Activating** the existing pairing logic (`pairReverseShots()`)
2. **Validating** 180-degree rule compliance
3. **Enforcing** single-character constraint (FLOW-02)
4. **Testing** that Phase 11's 1:1 mapping produces proper alternating patterns
