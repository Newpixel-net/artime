# Phase 23: Scene-Level Shot Continuity - Research

**Researched:** 2026-01-28
**Domain:** Hollywood continuity editing, scene-level shot orchestration, AI video prompt systems
**Confidence:** HIGH (verified against existing codebase + authoritative film sources)

## Summary

This research addresses why sequential shots lack logical connection despite individual shots looking cinematic. The investigation revealed a **critical discovery**: comprehensive infrastructure for scene-level continuity already exists in the codebase but is either (1) not being called at the right points, (2) missing required metadata to function, or (3) not integrated into the main shot generation flow.

**The core gap:** The system captures eyeline data from AI but doesn't use it to enforce spatial relationships. The 180-degree rule validation exists but `screenDirection` and `lookDirection` fields needed for it to work are not being populated during shot generation.

**Primary recommendation:** Connect existing ShotContinuityService methods (especially `analyzeHollywoodContinuity()`) to the shot generation pipeline, ensuring shots are enriched with spatial metadata (screenDirection, lookDirection, exitAction/entryAction) that enables the existing validation logic to function.

---

## CRITICAL FINDING: Admin Panel Settings Investigation

### Settings Discovered (8 Shot Continuity Settings)

The Video Wizard has a complete **shot_continuity** settings category with the following admin-configurable options:

| Setting Slug | Name | Current Value | Purpose |
|-------------|------|---------------|---------|
| `shot_continuity_enabled` | Enable Shot Continuity System | `true` | Master toggle for continuity analysis |
| `shot_continuity_min_score` | Minimum Continuity Score | `60` | Threshold before showing warnings (0-100) |
| `shot_continuity_30_degree_rule` | Enforce 30-Degree Rule | `true` | Warns when consecutive same-size shots lack angle change |
| `shot_continuity_auto_optimize` | Auto-Optimize Shot Sequences | `false` | Auto-insert transition shots when incompatible |
| `shot_continuity_coverage_patterns` | Enable Coverage Pattern Suggestions | `true` | Suggest professional patterns by scene type |
| `shot_continuity_movement_flow` | Check Movement Flow | `true` | Analyze camera movement continuity |
| `shot_continuity_jump_cut_detection` | Detect Jump Cuts | `true` | Flag potential jump cuts |
| `shot_continuity_default_scene_type` | Default Scene Type | `dialogue` | Default for coverage patterns |

**Source:** `VwSettingSeeder.php` lines 1194-1310

### Existing Services Architecture (Already Built!)

| Service | Purpose | Status |
|---------|---------|--------|
| `ShotContinuityService` | 180-degree rule, eyeline matching, shot compatibility, coverage patterns | **EXISTS but `analyzeHollywoodContinuity()` NOT CALLED** |
| `ShotProgressionService` | Story beats, energy curves, action continuity | **EXISTS and partially integrated** |
| `ShotIntelligenceService` | Main orchestrator, calls continuity analysis | **Uses basic `analyzeSequence()` not Hollywood rules** |
| `DialogueSceneDecomposerService` | Dialogue-specific 180-degree rule validation | **EXISTS, has its own validation** |

### The Integration Gap

**What's Being Called:**
```php
// In ShotIntelligenceService::addContinuityAnalysis()
$continuityResult = $this->continuityService->analyzeSequence($shots);
```

**What's NOT Being Called (but exists!):**
```php
// These comprehensive Hollywood methods are NOT used:
$this->continuityService->analyzeHollywoodContinuity($shots, $options);
$this->continuityService->check180DegreeRule($prevShot, $currShot);
$this->continuityService->checkEyelineMatch($prevShot, $currShot);
$this->continuityService->checkMatchOnAction($prevShot, $currShot);
```

**Why They Don't Work:**
The `check180DegreeRule()` method looks for:
- `$shot['screenDirection']` or `$shot['spatial_direction']`
- These fields are **NOT being set** during shot generation

The `checkEyelineMatch()` method looks for:
- `$shot['lookDirection']` or `$shot['gaze_direction']`
- The `eyeline` field IS captured from AI but NOT in the format expected

---

## Current System Analysis

### How Shots Are Currently Generated

**Flow:**
1. `VideoWizard::decomposeScene()` - Entry point
2. `decomposeSceneWithDynamicEngine()` - Main decomposition method
3. `decomposeSceneIntoStoryBeats()` - AI generates story beats with eyeline
4. Shots built from beats, eyeline captured as `$shot['eyeline']`
5. `ShotIntelligenceService::analyzeScene()` - Adds continuity analysis
6. `addContinuityAnalysis()` - Calls `analyzeSequence()` (basic only)

**Data Captured But Not Used Properly:**
```php
// In VideoWizard.php, shots capture eyeline:
$engineShot['eyeline'] = $beatShot['eyeline'] ?? null;
// Values: 'screen-left', 'screen-right', 'camera'

// But ShotContinuityService expects:
$shot['lookDirection'] // or 'gaze_direction'
$shot['screenDirection'] // or 'spatial_direction'
```

### GlobalRules Already Defined in VideoWizard

```php
'globalRules' => [
    'maxSimilarityThreshold' => 0.7,
    'enforceEyeline' => true,          // EXISTS but not enforced!
    'enforce180Rule' => true,          // EXISTS but not enforced!
    'enforceMatchCuts' => true,        // EXISTS but not enforced!
    'minShotVariety' => 3,
],
'dialogueSettings' => [
    'defaultPattern' => 'shot_reverse_shot',
    'insertReactions' => true,
    'matchEyelines' => true,           // EXISTS but not enforced!
],
```

**These flags are set but never read by the shot generation pipeline!**

---

## Hollywood Film Grammar Requirements

### 180-Degree Rule (Spatial Geography)

**Definition:** Camera must stay on one side of an imaginary axis between two subjects. Crossing the line disorients viewers by reversing screen direction.

**Implementation Requirements:**
1. Establish axis at scene start (typically between two main characters)
2. Track `cameraPosition` relative to axis (`left_side` / `right_side`)
3. All shots must maintain same side unless:
   - Neutral shot (wide/establishing) breaks the axis
   - Camera crosses during continuous shot (not between cuts)
   - Deliberate violation for disorientation effect

**Data Fields Needed Per Shot:**
```php
[
    'screenDirection' => 'left_to_right' | 'right_to_left' | 'neutral',
    'axisPosition' => 'left_side' | 'right_side' | 'on_axis',
]
```

**Source:** [MasterClass - 180-Degree Rule](https://www.masterclass.com/articles/understanding-the-180-degree-rule-in-cinematography)

### Eyeline Matching (Gaze Continuity)

**Definition:** When Character A looks screen-right, Character B (who A is looking at) must look screen-left in the next shot. Matching eyelines creates the illusion characters are looking at each other.

**Implementation Requirements:**
1. Character A looking `screen-right` -> cut to Character B looking `screen-left`
2. Character looking `camera` (direct address) can cut to any direction
3. POV shots: character looks off-screen -> cut shows what they see
4. Object of gaze should appear in direction character was looking

**Data Fields Needed Per Shot:**
```php
[
    'lookDirection' => 'screen-left' | 'screen-right' | 'center' | 'camera',
    'gazeTarget' => 'character_name' | 'object' | 'off-screen' | null,
]
```

**Source:** [MasterClass - Eyeline Match](https://www.masterclass.com/articles/film-101-what-are-eyelines-how-to-use-eyeline-match-to-tell-a-story-and-drive-a-narrative)

### Match on Action (Action Continuity)

**Definition:** Cutting during movement masks the edit. Action started in one shot continues in next.

**Implementation Requirements:**
1. Track `exitAction` - what motion character is doing as shot ends
2. Track `entryAction` - what motion character does as next shot begins
3. These should match or be compatible (reaching -> grabbing)
4. Cut point should be mid-action, not at rest

**Data Fields Needed Per Shot:**
```php
[
    'exitAction' => 'reaching toward door',      // How this shot ends
    'entryAction' => 'hand touching doorknob',   // How next shot begins (or null for first)
]
```

**Source:** [MasterClass - Continuity Editing](https://www.masterclass.com/articles/continuity-editing-in-film-explained)

### Shot Progression (Emotional Arc)

**Definition:** Shot sizes should serve story rhythm. Wide shots establish, close-ups emphasize emotion.

**Standard Progressions:**

| Progression Type | Pattern | Use Case |
|-----------------|---------|----------|
| Tension Build | Wide -> Medium -> Close-up -> Extreme Close-up | Building to emotional climax |
| Reveal | Close-up (mystery) -> Wide (context) | Surprise reveals |
| Dialogue Standard | Master -> OTS A -> OTS B -> CU A -> CU B | Two-person conversation |
| Action | Wide (geography) -> Medium (action) -> Close-up (impact) | Action sequences |
| Calm to Chaos | Controlled progression -> Rapid cutting | Escalation |

**Source:** [StudioBinder - Camera Shots Guide](https://www.studiobinder.com/blog/ultimate-guide-to-camera-shots/)

---

## Standard Stack

The established services for this domain:

### Core (Already Exist - Need Integration)
| Service | File | Purpose | Integration Status |
|---------|------|---------|-------------------|
| ShotContinuityService | `Services/ShotContinuityService.php` | 180-degree rule, eyeline, compatibility | **Exists, under-utilized** |
| ShotProgressionService | `Services/ShotProgressionService.php` | Story beats, energy, action continuity | **Exists, partially used** |
| DialogueSceneDecomposerService | `Services/DialogueSceneDecomposerService.php` | Dialogue-specific 180 rule | **Exists, scene-type specific** |
| ShotIntelligenceService | `Services/ShotIntelligenceService.php` | Orchestration layer | **Exists, needs to call Hollywood methods** |

### Supporting (Already Exist)
| Service | Purpose | When Used |
|---------|---------|-----------|
| CameraMovementService | Movement continuity | Movement transitions |
| SceneTypeDetectorService | Auto-detect scene type | Choose coverage pattern |
| VwSetting Model | Admin settings access | All continuity toggles |

### No New Libraries Needed

All required functionality exists in the codebase. The gap is **integration and data flow**, not missing capabilities.

---

## Architecture Patterns

### Recommended Shot Data Structure Enhancement

Current shot structure lacks spatial metadata. Enhance to:

```php
$shot = [
    // Existing fields...
    'type' => 'over-shoulder',
    'eyeline' => 'screen-left',  // Already captured from AI

    // NEW: Spatial continuity fields (map from eyeline)
    'lookDirection' => 'screen-left',    // Same as eyeline
    'screenDirection' => 'left_to_right', // Derived from scene context
    'axisPosition' => 'left_side',        // Camera position relative to 180-line

    // NEW: Action continuity fields
    'exitAction' => 'reaching toward door',    // How this shot ends
    'entryAction' => 'hand on doorknob',       // How this shot begins

    // NEW: Spatial linking for dialogue
    'reverseOf' => null,          // Index of shot this reverses
    'pairId' => 'dialogue_pair_1', // Links A/B shots in dialogue
];
```

### Integration Point Pattern

```php
// In shot generation flow, AFTER shots are built:
class ShotContinuityEnricher {
    public function enrichSequence(array $shots, string $sceneType): array {
        // 1. Normalize eyeline -> lookDirection mapping
        $shots = $this->normalizeEyelineFields($shots);

        // 2. Derive screenDirection from scene context + shot index
        $shots = $this->inferScreenDirections($shots, $sceneType);

        // 3. Establish axis position for dialogue
        if ($sceneType === 'dialogue') {
            $shots = $this->establishDialogueAxis($shots);
        }

        // 4. Generate exit/entry action pairs
        $shots = $this->generateActionContinuity($shots);

        // 5. NOW the Hollywood continuity check can work
        $analysis = $this->continuityService->analyzeHollywoodContinuity($shots);

        // 6. Auto-fix if enabled and issues found
        if ($analysis['overall'] < 70 && $this->isAutoFixEnabled()) {
            $shots = $this->applyFixes($shots, $analysis['issues']);
        }

        return $shots;
    }
}
```

### Anti-Patterns to Avoid

- **Calling continuity check without spatial data:** The methods return "valid" by default when fields are missing - this hides problems
- **Checking continuity after prompts are built:** Must check BEFORE prompt generation to fix issues
- **Ignoring scene type:** Dialogue scenes need different rules than action scenes
- **Hard-coding axis position:** Should be established per-scene, not globally

---

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Shot compatibility scoring | Custom scoring matrix | `ShotContinuityService::SHOT_COMPATIBILITY` | 18x18 matrix already defined |
| Coverage patterns | Hardcoded patterns | `ShotContinuityService::COVERAGE_PATTERNS` | 5 scene types already mapped |
| Energy progression | Linear interpolation | `ShotContinuityService::ENERGY_PROGRESSION` | Curves for building/steady/climactic |
| Eyeline rules | Simple left/right check | `ShotContinuityService::EYELINE_RULES` | Handles center, compatible pairs |
| Jump cut detection | Frame similarity | `checkJumpCut()` method | Already considers shot size + movement |
| Story beat assignment | Position-only | `ShotProgressionService::BEAT_TYPES` | Position ranges + shot type matching |

**Key insight:** Over 1,400 lines of continuity logic already exists. The work is connecting data, not writing algorithms.

---

## Common Pitfalls

### Pitfall 1: Missing Spatial Metadata

**What goes wrong:** `analyzeHollywoodContinuity()` returns perfect scores because all checks early-exit when fields are null.

**Why it happens:** Shot generation captures `eyeline` but not `lookDirection`, `screenDirection`, or `spatial_direction`.

**How to avoid:** Map eyeline to expected field names immediately after AI response, before any continuity analysis.

**Warning signs:** Continuity score is always 100 even for obviously broken sequences.

### Pitfall 2: Checking After Prompt Generation

**What goes wrong:** Issues detected but prompts already built, causing expensive re-generation or ignored warnings.

**Why it happens:** Continuity analysis is called in `addContinuityAnalysis()` which runs after shot array is finalized.

**How to avoid:** Run continuity enrichment and validation as shots are being assembled, fix before prompt building.

**Warning signs:** Warnings appear in logs but output videos still have continuity issues.

### Pitfall 3: Not Preserving Axis Across Dialogue

**What goes wrong:** Camera "jumps" the 180-degree line between dialogue shots, causing disorientation.

**Why it happens:** Each shot is generated independently without awareness of established axis.

**How to avoid:** Dialogue scenes need axis lock established in first two-person shot, maintained throughout.

**Warning signs:** Characters appear to switch positions or both look the same direction.

### Pitfall 4: Treating All Scene Types the Same

**What goes wrong:** Action scenes flagged with dialogue continuity errors; dialogue scenes lack proper shot/reverse pattern.

**Why it happens:** Generic continuity rules applied without scene type awareness.

**How to avoid:** Use `SceneTypeDetectorService` result to select appropriate continuity rules and coverage patterns.

**Warning signs:** Coverage pattern suggestions don't match scene content.

---

## Code Examples

### Example 1: Eyeline Normalization (Required Fix)

```php
// In new ShotContinuityEnricher or existing service
protected function normalizeEyelineFields(array $shots): array
{
    foreach ($shots as &$shot) {
        // Map eyeline -> lookDirection (what ShotContinuityService expects)
        if (isset($shot['eyeline']) && !isset($shot['lookDirection'])) {
            $shot['lookDirection'] = $shot['eyeline'];
            $shot['gaze_direction'] = $shot['eyeline']; // Alternative field name
        }

        // Normalize values
        $mapping = [
            'screen-left' => 'left_to_right',
            'screen-right' => 'right_to_left',
            'camera' => 'center',
        ];

        if (isset($shot['eyeline'])) {
            $shot['screenDirection'] = $mapping[$shot['eyeline']] ?? 'center';
        }
    }
    return $shots;
}
```

### Example 2: Using analyzeHollywoodContinuity (Existing Method)

```php
// In ShotIntelligenceService or new integration layer
public function analyzeWithHollywoodRules(array $shots, array $context): array
{
    $sceneType = $context['sceneType'] ?? 'dialogue';
    $progressionType = $context['progressionType'] ?? 'building';

    // Call the comprehensive Hollywood analysis (CURRENTLY NOT CALLED!)
    $analysis = $this->continuityService->analyzeHollywoodContinuity($shots, [
        'sceneType' => $sceneType,
        'progressionType' => $progressionType,
    ]);

    // $analysis contains:
    // - scores['shotCompatibility']
    // - scores['30DegreeRule']
    // - scores['180DegreeRule']  <-- Now works with spatial data
    // - scores['matchOnAction']  <-- Now works with action data
    // - scores['eyelineMatch']   <-- Now works with lookDirection
    // - scores['energyProgression']
    // - issues[] - specific problems with suggestions
    // - overall - weighted combined score

    return $analysis;
}
```

### Example 3: Using GlobalRules (Already Defined, Not Read)

```php
// These flags exist in VideoWizard but are never read:
$globalRules = [
    'enforceEyeline' => true,
    'enforce180Rule' => true,
    'enforceMatchCuts' => true,
];

// Should be used like:
if ($globalRules['enforce180Rule']) {
    $check180 = $this->continuityService->check180DegreeRule($prevShot, $currShot);
    if (!$check180['valid']) {
        // Fix or warn
    }
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Post-generation continuity check | Pre-generation enrichment + check | Should be now | Can fix issues before expensive generation |
| Shot-by-shot generation | Scene-aware shot orchestration | Should be now | Shots aware of siblings |
| Generic continuity rules | Scene-type-specific rules | Already in code | Dialogue/action/emotional each have patterns |
| AI determines everything | AI proposes, rules constrain | Should be now | AI output validated against film grammar |

**Deprecated/outdated:**
- Calling `analyzeSequence()` without spatial data - returns false positives
- Ignoring `globalRules` flags that exist but aren't read

---

## Open Questions

Things that couldn't be fully resolved:

1. **Where should enrichment occur?**
   - What we know: Must be after AI generates beats, before prompts built
   - What's unclear: New service or extend existing?
   - Recommendation: Create `ShotContinuityEnricher` as thin wrapper that calls existing services

2. **How to infer screenDirection for non-dialogue?**
   - What we know: Dialogue has clear A/B character axis
   - What's unclear: Action scenes, montages - what determines screen direction?
   - Recommendation: For non-dialogue, use movement direction as proxy; establish per-scene

3. **Should auto-fix be enabled by default?**
   - What we know: Setting exists (`shot_continuity_auto_optimize`), defaults to `false`
   - What's unclear: User preference for manual vs automatic
   - Recommendation: Keep default `false`, document clearly in admin panel

---

## Success Criteria Mapping

| Success Criterion | How To Achieve | Service/Method |
|------------------|----------------|----------------|
| Sequential shots maintain spatial consistency | Map eyeline -> screenDirection, call `check180DegreeRule()` | ShotContinuityService |
| Eyelines match across cuts in dialogue | Enrich with `lookDirection`, call `checkEyelineMatch()` | ShotContinuityService |
| Shot progression follows rhythm | Use `getEnergyForPosition()`, enforce patterns | ShotContinuityService |
| Action continues logically | Track exit/entry actions, call `checkMatchOnAction()` | ShotContinuityService |

---

## Sources

### Primary (HIGH confidence)
- `ShotContinuityService.php` - 1,400+ lines of existing continuity logic
- `ShotProgressionService.php` - Story beat and energy progression
- `VwSettingSeeder.php` - All admin settings definitions
- `VideoWizard.php` - Current shot generation flow

### Secondary (MEDIUM confidence)
- [MasterClass - 180-Degree Rule](https://www.masterclass.com/articles/understanding-the-180-degree-rule-in-cinematography)
- [MasterClass - Eyeline Match](https://www.masterclass.com/articles/film-101-what-are-eyelines-how-to-use-eyeline-match-to-tell-a-story-and-drive-a-narrative)
- [MasterClass - Continuity Editing](https://www.masterclass.com/articles/continuity-editing-in-film-explained)
- [StudioBinder - Match on Action](https://www.studiobinder.com/blog/what-is-a-match-on-action-cut/)
- [StudioBinder - Camera Shots](https://www.studiobinder.com/blog/ultimate-guide-to-camera-shots/)

### Tertiary (LOW confidence)
- WebSearch for 2026 cinematography trends (general patterns, not specific APIs)

---

## Metadata

**Confidence breakdown:**
- Admin Settings Investigation: HIGH - directly read from VwSettingSeeder.php
- Existing Services Analysis: HIGH - read actual source code
- Integration Gap Analysis: HIGH - grepped for usage, confirmed methods not called
- Hollywood Film Grammar: MEDIUM - verified with authoritative sources (MasterClass, StudioBinder)
- Implementation Approach: MEDIUM - based on codebase patterns but untested

**Research date:** 2026-01-28
**Valid until:** 2026-02-28 (codebase may evolve)

---

## Key Takeaways for Planning

1. **No new algorithms needed** - ShotContinuityService has everything
2. **Data enrichment is the gap** - Shots lack fields the methods expect
3. **Integration point exists** - `addContinuityAnalysis()` calls wrong method
4. **Admin settings work** - 8 toggles already control behavior
5. **GlobalRules are orphaned** - Defined but never read
6. **DialogueSceneDecomposer has parallel logic** - Consider consolidating

The solution is **connecting existing pieces**, not building new ones.
