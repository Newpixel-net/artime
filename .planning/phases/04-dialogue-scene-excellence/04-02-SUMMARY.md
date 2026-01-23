---
phase: 04-dialogue-scene-excellence
plan: 02
subsystem: shot-generation
tags: [ots, depth-of-field, framing, dialogue, hollywood]

dependency-graph:
  requires:
    - 03-01 (Hollywood shot sequence activation)
    - 03-02 (Meaningful moment extraction)
  provides:
    - OTS shot data structure with foreground/background specification
    - OTS-specific visual prompt generation
    - Intelligent OTS detection in dialogue shots
    - Dialogue pattern with OTS specifications
  affects:
    - 04-03 (Reaction shot variety - will benefit from OTS framing)
    - Future visual prompt improvements

tech-stack:
  added: []
  patterns:
    - Foreground blur specification for depth
    - 180-degree rule enforcement
    - Profile angle calculation from speaker position

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php
    - modules/AppVideoWizard/app/Services/DynamicShotEngine.php

decisions:
  - area: OTS shoulder determination
    choice: Left shoulder when speaker is screen-right, right when screen-left
    rationale: Follows 180-degree rule for consistent spatial continuity
  - area: OTS detection
    choice: Alternating pattern for medium shots with 0.3-0.7 intensity
    rationale: Creates natural shot/reverse-shot rhythm without overusing OTS
  - area: Dialogue pattern OTS specs
    choice: Mirrored shoulders between OTS and reverse OTS
    rationale: Maintains visual continuity across cuts

metrics:
  duration: 8 minutes
  completed: 2026-01-23
---

# Phase 04 Plan 02: OTS Shot Depth and Framing Summary

**One-liner:** OTS shots now specify foreground shoulder, blur depth, and profile angle for Hollywood-style depth framing.

## What Was Built

### Task 1: OTS-specific shot data structure
- Added `buildOTSData()` method to DialogueSceneDecomposerService
- Returns structured data with:
  - `foregroundCharacter`: The listener (blurred)
  - `foregroundShoulder`: left/right based on speaker position
  - `foregroundBlur`: true
  - `foregroundVisible`: "shoulder and partial head"
  - `backgroundCharacter`: The speaker (in focus)
  - `backgroundPosition`: Screen position
  - `depthOfField`: shallow
  - `focusOn`: Speaker name
  - `profileAngle`: left-three-quarter or right-three-quarter

### Task 2: OTS-specific visual prompt builder
- Added `buildOTSPrompt()` method for dedicated OTS prompt generation
- Prompts include:
  - Shot type declaration
  - Foreground blur description with shoulder side
  - Background character in sharp focus
  - Profile angle specification
  - Depth of field and cinematic lighting
  - Emotional atmosphere from dialogue

- Added `detectDialogueEmotion()` helper:
  - Detects angry/furious -> "intense confrontational mood"
  - Detects love/tender -> "warm intimate atmosphere"
  - Detects scared/afraid -> "tense fearful atmosphere"
  - Detects sad/grief -> "somber emotional moment"
  - Detects happy/joy -> "uplifting joyful energy"
  - Punctuation-based: ! = "emphatic delivery", ? = "questioning tone"

### Task 3: OTS detection integration
- Added `shouldUseOTS()` method for intelligent OTS detection:
  - Returns true for explicit 'over-the-shoulder' shot type
  - Returns true for medium/medium-close shots with 0.3-0.7 intensity on odd indices
  - Creates alternating shot/reverse-shot rhythm

- Integrated into `createDialogueShot()`:
  - Detects when OTS should be applied
  - Finds listener character for foreground
  - Calculates spatial data
  - Builds OTS data and prompt
  - Updates shot type, description, and charactersInShot

### Task 4: DynamicShotEngine dialogue pattern with OTS specs
- Updated `$dialoguePattern` array with `otsSpecs` for OTS shots:
  - Pattern item 1 (OTS on Character A):
    - foregroundCharacter: charB
    - foregroundShoulder: right
    - profileAngle: left-three-quarter
  - Pattern item 2 (Reverse OTS on Character B):
    - foregroundCharacter: charA
    - foregroundShoulder: left (mirrored)
    - profileAngle: right-three-quarter

- Added code to copy `otsSpecs` to shot data when applying pattern

## Technical Details

### Shoulder Determination Logic
```
If speaker is screen-right:
  - Camera sees over listener's LEFT shoulder
  - Speaker at left-three-quarter angle

If speaker is screen-left:
  - Camera sees over listener's RIGHT shoulder
  - Speaker at right-three-quarter angle
```

### OTS Data Flow
1. `createDialogueShot()` called with exchange data
2. `shouldUseOTS()` checks if OTS framing appropriate
3. If yes, `buildOTSData()` creates structured OTS specification
4. `buildOTSPrompt()` generates OTS-specific visual description
5. Shot data updated with OTS info and new prompt

### Additional Enhancements (from linter)
The linter added complementary spatial continuity methods:
- `calculateSpatialData()`: 180-degree rule enforcement
- `determineCameraAngle()`: Shot type to camera angle mapping
- `pairReverseShots()`: Links shot/reverse-shot pairs
- `buildSpatialAwarePrompt()`: Position-aware prompt generation
- `getDialogueVisualHint()`: Dialogue-based visual hints

## Deviations from Plan

None - plan executed exactly as written.

## Files Changed

| File | Changes |
|------|---------|
| DialogueSceneDecomposerService.php | +buildOTSData(), +buildOTSPrompt(), +detectDialogueEmotion(), +shouldUseOTS(), OTS integration in createDialogueShot() |
| DynamicShotEngine.php | +otsSpecs in dialoguePattern, otsData copy to shots |

## Success Criteria Verification

- [x] OTS shots have foregroundShoulder specified (left/right)
- [x] OTS shots have foregroundBlur: true
- [x] OTS prompts describe "shoulder in soft-focus foreground"
- [x] Profile angles are explicit (left-three-quarter, right-three-quarter)
- [x] Reverse OTS mirrors the original OTS shoulder
- [x] PHP syntax valid (no errors reported)

## Commits

| Hash | Message |
|------|---------|
| 3f14f75 | feat(04-02): add OTS shot depth and framing enhancements |

## Next Phase Readiness

OTS shot enhancements are complete. The system now generates proper Hollywood-style over-the-shoulder shots with:
- Explicit foreground/background specification
- Depth-of-field guidance
- Profile angle direction
- Emotional atmosphere from dialogue

Ready for Plan 04-03: Reaction Shot Variety.
