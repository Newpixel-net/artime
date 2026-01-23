---
phase: 14-cinematic-flow-action-scenes
plan: 01
subsystem: cinematography
tags: [transition-validation, jump-cut, shot-scale, FLOW-03]
dependency-graph:
  requires: [13-01]
  provides: [transition-validation, scale-change-enforcement]
  affects: [14-02]
tech-stack:
  added: []
  patterns: [scale-mapping, shot-adjustment, non-blocking-validation]
key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php
decisions:
  - "Non-blocking validation: Log violations but don't halt video generation"
  - "Prefer stepping OUT (wider) over stepping IN for scale adjustments"
  - "Use local getShotSizeForType to avoid cross-service dependency"
metrics:
  duration: "5 minutes"
  completed: "2026-01-23"
---

# Phase 14 Plan 01: Transition Validation Summary

**One-liner:** Jump cut detection with automatic scale adjustment using local shot-size mapping (FLOW-03)

## What Was Built

Implemented transition validation to prevent jarring cuts between consecutive shots. The system detects potential jump cuts (same shot type or same scale) and automatically adjusts shot types to ensure at least one-step scale change.

### Key Components

1. **getShotSizeForType()** - Maps shot types to numeric scale values (1-5)
   - Scale 1 (tightest): extreme-close-up, close-up
   - Scale 2: medium-close-up, medium-close, medium
   - Scale 3: over-the-shoulder, medium-wide
   - Scale 4: wide, two-shot
   - Scale 5 (widest): extreme-wide, establishing

2. **validateAndFixTransitions()** - Core validation method
   - Iterates through shots comparing consecutive pairs
   - Detects same-type jump cuts and logs warnings
   - Detects same-scale transitions and applies fixes
   - Sets `scaleAdjusted=true` flag on modified shots
   - Logs summary: total shots, jump cuts detected, scale adjustments made

3. **Helper Methods**
   - `getWiderShotType()` - Steps out one level (e.g., medium -> over-the-shoulder)
   - `getTighterShotType()` - Steps in one level (e.g., wide -> medium-wide)

4. **Integration** - Added to enhanceShotsWithDialoguePatterns()
   - Runs LAST after all Phase 12/13 processing
   - Ensures minimal adjustments to finalized shot types

## Commits

| Hash | Description |
|------|-------------|
| 8b4817c | feat(14-01): add getShotSizeForType method for jump cut detection |
| 0e6eaf4 | feat(14-01): add validateAndFixTransitions for jump cut detection (FLOW-03) |
| 3b1d1b9 | feat(14-01): integrate transition validation into enhanceShotsWithDialoguePatterns |

## Files Modified

- `modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php` (+162 lines)

## Decisions Made

1. **Non-blocking validation**: Jump cuts are logged as warnings but do not halt video generation (per prior accumulated decision)

2. **Prefer stepping OUT**: When same scale detected, prefer wider shot (e.g., medium -> over-the-shoulder) over tighter shot, unless already at widest scale

3. **Local implementation**: Use local `getShotSizeForType()` rather than calling ShotContinuityService to avoid cross-service dependency in shot generation flow

4. **Adjustment tracking**: Modified shots have `scaleAdjusted=true` and `adjustmentReason` for debugging

## Deviations from Plan

None - plan executed exactly as written.

## Verification Results

All verification criteria passed:

- [x] All methods exist: getShotSizeForType, validateAndFixTransitions, getWiderShotType, getTighterShotType
- [x] validateAndFixTransitions is called in enhanceShotsWithDialoguePatterns
- [x] Log messages exist for jump cut detection (grep for "jump cut" case-insensitive)
- [x] Scale adjustment flag exists (grep for "scaleAdjusted")

## Success Criteria Met

- [x] Consecutive shots with same type are detected as potential jump cuts and logged
- [x] Shot types are adjusted to ensure at least one-step scale change
- [x] Video generation continues (non-blocking validation per prior decision)
- [x] Modified shots have scaleAdjusted=true flag for debugging

## Next Phase Readiness

Phase 14-02 (Action Scene Pacing) can proceed. The transition validation system provides:
- Scale mapping infrastructure that can be reused
- Pattern for non-blocking validation with logging
- Foundation for more sophisticated transition rules
