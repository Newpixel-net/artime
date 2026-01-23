# Phase 14: Cinematic Flow & Action Scenes - Research

**Researched:** 2026-01-23
**Domain:** Shot transition validation, action scene decomposition, mixed scene handling
**Confidence:** HIGH

## Summary

Phase 14 addresses three critical gaps in the cinematic shot architecture: preventing jarring cuts (jump cuts), improving non-dialogue action scene decomposition, and handling mixed dialogue+action scenes smoothly. The existing codebase has substantial infrastructure for these features but they are not fully connected to the DialogueSceneDecomposerService flow.

The `ShotContinuityService` already implements Hollywood continuity rules including jump cut detection (`checkJumpCut()`), 30-degree rule validation, shot compatibility matrices, and coverage patterns for action scenes. However, these validations are not integrated into the speech-driven shot creation pipeline established in Phases 11-13. The `SceneTypeDetectorService` can detect action scenes but the `DynamicShotEngine` mentioned in the ROADMAP doesn't exist as a standalone service - action scene handling is scattered across multiple services.

**Primary recommendation:** Create a `TransitionValidator` class that hooks into the post-shot-creation pipeline, leveraging existing `ShotContinuityService.checkJumpCut()` and add scale-change enforcement. For action scenes, enhance the existing fallback paths in `DialogueSceneDecomposerService` to use `ShotContinuityService.getCoveragePattern('action')`.

## Standard Stack

The established libraries/tools for this domain:

### Core (Already in Codebase)
| Service | Location | Purpose | Integration Status |
|---------|----------|---------|-------------------|
| ShotContinuityService | `app/Services/ShotContinuityService.php` | Jump cut detection, 30-degree rule, compatibility | EXISTS - needs integration |
| ShotProgressionService | `app/Services/ShotProgressionService.php` | Energy progression, action validation | EXISTS - partially integrated |
| SceneTypeDetectorService | `app/Services/SceneTypeDetectorService.php` | Scene type classification (dialogue/action) | EXISTS - needs connection |
| DialogueSceneDecomposerService | `app/Services/DialogueSceneDecomposerService.php` | Speech-driven shot creation | TARGET - needs action fallback |

### Supporting (Need Creation)
| Component | Purpose | Why Needed |
|-----------|---------|------------|
| TransitionValidator | Post-processing shot sequence validation | Hooks checkJumpCut + scale enforcement |
| ActionDecomposer | Non-dialogue scene shot creation | Uses action coverage patterns |
| HybridDecomposer | Mixed scene handling | Seamlessly transitions between modes |

## Architecture Patterns

### Current Shot Creation Flow
```
VideoWizard.decomposeScenesIntoShots()
    |
    +-> DialogueSceneDecomposerService.decompose()  // For dialogue scenes
    |       |
    |       +-> createShotsFromSpeechSegments()  // Phase 11: 1:1 mapping
    |       +-> enforceSingleCharacterConstraint()  // Phase 12
    |       +-> validate180DegreeRule()  // Phase 12
    |       +-> validateCharacterAlternation()  // Phase 12
    |
    +-> ShotIntelligenceService.analyzeScene()  // For non-dialogue (LEGACY)
            |
            +-> Uses SceneTypeDetectorService for classification
            +-> Uses ShotContinuityService.getCoveragePattern()
```

### Proposed Phase 14 Enhancement
```
VideoWizard.decomposeScenesIntoShots()
    |
    +-> Classify scene type (dialogue/action/mixed)
    |
    +-> DIALOGUE: DialogueSceneDecomposerService.decompose()
    |       +-> [existing Phase 11-13 flow]
    |       +-> NEW: validateTransitions() -> TransitionValidator
    |
    +-> ACTION: ActionSceneDecomposer.decompose()
    |       +-> Uses ShotContinuityService.getCoveragePattern('action')
    |       +-> Applies action variety rules
    |       +-> validateTransitions() -> TransitionValidator
    |
    +-> MIXED: HybridSceneDecomposer.decompose()
            +-> Segments scene into dialogue/action beats
            +-> Routes each beat to appropriate decomposer
            +-> Smooths transitions between modes
```

### Pattern 1: Jump Cut Prevention (Existing)
**What:** ShotContinuityService.checkJumpCut() detects same-shot-type sequences
**When to use:** After shot sequence is created, before committing
**Location:** `ShotContinuityService.php` lines 354-400

```php
// Source: ShotContinuityService.php line 354
public function checkJumpCut(array $prevShot, array $currShot): array
{
    $prevType = $this->normalizeType($prevShot['type'] ?? 'medium');
    $currType = $this->normalizeType($currShot['type'] ?? 'medium');

    $prevSize = $this->getShotSize($prevType);
    $currSize = $this->getShotSize($currType);

    // If sizes differ significantly, not a jump cut
    if (abs($prevSize - $currSize) > 1) {
        return ['isJumpCut' => false];
    }

    // Same type is highest risk
    $isJumpCut = ($prevType === $currType);
    // ...
}
```

### Pattern 2: Shot Scale Hierarchy (Existing)
**What:** getShotSize() maps shot types to numeric scale
**When to use:** For enforcing "at least one step" scale change
**Location:** `ShotContinuityService.php` lines 1163-1177

```php
// Source: ShotContinuityService.php line 1163
protected function getShotSize(string $type): int
{
    $sizes = [
        'extreme-close-up' => 1,
        'close-up' => 1,
        'medium-close-up' => 2,
        'medium' => 2,
        'medium-wide' => 3,
        'wide' => 4,
        'extreme-wide' => 5,
        'establishing' => 5,
    ];
    return $sizes[$type] ?? 2;
}
```

### Pattern 3: Action Coverage Pattern (Existing)
**What:** Coverage patterns define shot sequences for action scenes
**When to use:** When scene is classified as action type
**Location:** `ShotContinuityService.php` lines 44-51

```php
// Source: ShotContinuityService.php line 44
public const COVERAGE_PATTERNS = [
    'action' => [
        'establishing'     => 1,  // Sets location
        'wide'             => 2,  // Full action context
        'medium'           => 3,  // Character action
        'tracking'         => 4,  // Following movement
        'close-up'         => 5,  // Detail/impact
        'insert'           => 6,  // Specific detail
    ],
    // ...
];
```

### Anti-Patterns to Avoid
- **Adding validation in VideoWizard directly:** Keep validation in service classes for testability
- **Creating new shot type enums:** Use existing `VwShotType` model and `getShotSize()` mapping
- **Blocking on validation failures:** Prior decision: "Validation non-blocking - log violations but don't halt"

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Jump cut detection | Custom same-type check | `ShotContinuityService.checkJumpCut()` | Handles edge cases, movement consideration |
| Shot compatibility | Simple type comparison | `ShotContinuityService.SHOT_COMPATIBILITY` matrix | Pre-computed scores from Hollywood conventions |
| Action scene patterns | Manual shot list | `ShotContinuityService.getCoveragePattern('action')` | Professionally designed sequences |
| Scene type detection | Keyword matching | `SceneTypeDetectorService.detectSceneType()` | Sophisticated scoring with fallbacks |
| Scale size mapping | Custom enum | `ShotContinuityService.getShotSize()` | Already handles all shot types |

**Key insight:** The continuity infrastructure is robust but disconnected from the Phase 11-13 speech-driven pipeline.

## Common Pitfalls

### Pitfall 1: Treating DialogueSceneDecomposer as Only for Dialogue
**What goes wrong:** Action scenes bypass DialogueSceneDecomposerService entirely, losing Phase 11-13 benefits
**Why it happens:** Service name implies dialogue-only scope
**How to avoid:** Either rename to `SpeechDrivenDecomposerService` or add action fallback path
**Warning signs:** Non-dialogue scenes producing generic shots without emotion/position awareness

### Pitfall 2: Validating After Generation Instead of During
**What goes wrong:** Jump cuts detected but expensive to fix (regenerate images)
**Why it happens:** Validation as afterthought rather than integrated constraint
**How to avoid:** Validate shot types BEFORE creating shot objects, adjust proactively
**Warning signs:** High validation failure rates, user seeing "jump cut detected" warnings

### Pitfall 3: Mixed Scene Mode Switching
**What goes wrong:** Abrupt style change when dialogue ends and action begins within scene
**Why it happens:** Treating dialogue and action as binary states
**How to avoid:** Use transition shots (cutaway, reaction) when switching modes
**Warning signs:** Shot types jumping from close-up dialogue to wide action without bridge

### Pitfall 4: Action Scene Monotony
**What goes wrong:** Action scenes get same shot type repeated (medium, medium, medium)
**Why it happens:** No variety enforcement for non-speech-driven shots
**How to avoid:** Apply `COVERAGE_PATTERNS['action']` sequence, track used types
**Warning signs:** All action shots being "medium" or "wide" with no detail/insert shots

## Code Examples

### Example 1: Using Existing Jump Cut Detection
```php
// Source: ShotContinuityService.php
// Called after creating shot sequence:

$continuityService = new ShotContinuityService(new CameraMovementService());
$analysis = $continuityService->analyzeSequence($shots);

foreach ($analysis['issues'] as $issue) {
    if ($issue['type'] === 'jump_cut') {
        Log::warning('Jump cut detected', [
            'position' => $issue['position'],
            'suggestion' => $issue['suggestion'],
        ]);
        // Non-blocking: log but continue (per prior decision)
    }
}
```

### Example 2: Enforcing Scale Change
```php
// Build on existing getShotSize() pattern:

protected function enforceScaleChange(array $shots): array
{
    for ($i = 1; $i < count($shots); $i++) {
        $prevSize = $this->getShotSize($shots[$i - 1]['type']);
        $currSize = $this->getShotSize($shots[$i]['type']);

        // Requirement: "Shot scale changes by at least one step"
        if (abs($currSize - $prevSize) < 1) {
            // Adjust current shot to be at least one step different
            $shots[$i]['type'] = $this->getAlternateScale($shots[$i]['type'], $prevSize);
            $shots[$i]['scaleAdjusted'] = true;
        }
    }
    return $shots;
}
```

### Example 3: Action Scene Decomposition
```php
// Using existing coverage pattern:

protected function decomposeActionScene(array $scene, int $sceneIndex): array
{
    $continuityService = new ShotContinuityService(new CameraMovementService());
    $pattern = $continuityService->getCoveragePattern('action');

    // Pattern: establishing -> wide -> medium -> tracking -> close-up -> insert
    $shots = [];
    $narration = $scene['narration'] ?? '';
    $actionBeats = $this->extractActionBeats($narration);

    foreach ($actionBeats as $index => $beat) {
        $patternIndex = $index % count($pattern);
        $shotType = $pattern[$patternIndex]['type'];

        $shots[] = [
            'type' => $shotType,
            'description' => $beat['description'],
            'purpose' => $pattern[$patternIndex]['name'],
            // ... other shot properties
        ];
    }

    return $shots;
}
```

### Example 4: Mixed Scene Detection and Routing
```php
// Hybrid handling based on scene analysis:

public function decompose(array $scene, int $sceneIndex): array
{
    $sceneType = $this->sceneTypeDetector->detectSceneType($scene);

    switch ($sceneType['sceneType']) {
        case 'dialogue':
            return $this->dialogueDecomposer->decompose($scene, $sceneIndex);

        case 'action':
            return $this->actionDecomposer->decompose($scene, $sceneIndex);

        case 'mixed':
        default:
            // Segment into beats, route appropriately
            $beats = $this->segmentMixedScene($scene);
            $shots = [];
            foreach ($beats as $beat) {
                if ($beat['hasDialogue']) {
                    $shots = array_merge($shots,
                        $this->dialogueDecomposer->decompose($beat, $sceneIndex));
                } else {
                    $shots = array_merge($shots,
                        $this->actionDecomposer->decompose($beat, $sceneIndex));
                }
            }
            return $this->smoothTransitions($shots);
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Proportional segment distribution | Speech-driven 1:1 shot creation | Phase 11 | Each speech = one shot |
| Exchange-based shot counting | Speech segment counting | Phase 11 | More reliable shot boundaries |
| Fixed shot types | Position + emotion driven selection | Phase 13 | Dynamic camera intelligence |
| Post-hoc validation only | Integrated validation | Phase 14 (planned) | Proactive jump cut prevention |

**Current gaps to address:**
- Jump cut prevention: Infrastructure exists, not connected
- Action scenes: Coverage patterns exist, not used by decomposer
- Mixed scenes: No hybrid handling

## Integration Points

### 1. Where TransitionValidator Hooks In
**File:** `DialogueSceneDecomposerService.php`
**Location:** After `createShotsFromSpeechSegments()`, before returning shots
**Why here:** Catches all shots from speech-driven pipeline

```php
// In decompose() method, after line ~270:
$shots = $this->createShotsFromSpeechSegments($speechSegments, $characters, $context);
$shots = $this->enforceSingleCharacterConstraint($shots);

// NEW: Validate and fix transitions
$shots = $this->validateAndFixTransitions($shots);

return $shots;
```

### 2. Where Action Decomposer Routes From
**File:** `VideoWizard.php`
**Location:** `decomposeScenesIntoShots()` method
**Why here:** Central dispatch point for scene decomposition

### 3. Where Scene Type Detection Occurs
**File:** `SceneTypeDetectorService.php`
**Method:** `detectSceneType()`
**Already returns:** `['sceneType' => 'dialogue'|'action'|'emotional'|...]`

## Open Questions

Things that couldn't be fully resolved:

1. **DynamicShotEngine Reference**
   - What we know: ROADMAP mentions "Improve `DynamicShotEngine` for action-only scenes"
   - What's unclear: No class named DynamicShotEngine exists in codebase
   - Recommendation: Likely refers to creating ActionDecomposer OR enhancing existing services

2. **Visual Prompt Continuity Verification**
   - What we know: Success criteria includes "Visual prompt continuity verified across shot sequence"
   - What's unclear: What constitutes "continuity" in image prompts
   - Recommendation: Likely means consistent character descriptions, settings, props across consecutive shots

3. **Reaction Shot Insertion for Jump Cut Prevention**
   - What we know: `checkJumpCut()` suggests "Insert a cutaway, reaction shot"
   - What's unclear: Should Phase 14 auto-insert reaction shots or just flag violations?
   - Recommendation: Start with flagging (non-blocking per prior decision), consider auto-fix later

## Sources

### Primary (HIGH confidence)
- `ShotContinuityService.php` - Full analysis of jump cut, compatibility, coverage patterns
- `DialogueSceneDecomposerService.php` - Phase 11-13 implementation, integration points
- `SceneTypeDetectorService.php` - Scene classification logic
- `ShotProgressionService.php` - Action validation infrastructure

### Secondary (MEDIUM confidence)
- `ROADMAP.md` - Phase 14 requirements and success criteria
- `SHOT_CONTINUITY_IMPROVEMENT_PLAN.md` - Historical design decisions

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All services directly examined in codebase
- Architecture: HIGH - Clear integration patterns visible
- Pitfalls: HIGH - Based on code analysis and prior phase implementations
- Action handling: MEDIUM - Coverage patterns exist but DynamicShotEngine unclear

**Research date:** 2026-01-23
**Valid until:** 2026-02-23 (stable domain, internal codebase)
