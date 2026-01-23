---
phase: 12-shot-reverse-shot-patterns
plan: 01
subsystem: video-processing
tags: [dialogue, shot-reverse-shot, 180-degree-rule, cinematography, validation]

# Dependency graph
requires:
  - phase: 11-speech-driven-shot-creation
    provides: 1:1 speech-to-shot mapping, enhanceShotsWithDialoguePatterns()
provides:
  - 180-degree rule validation (SCNE-04)
  - Single-character constraint enforcement (FLOW-02)
  - Character alternation validation (FLOW-04)
  - Shot/reverse-shot quality assurance
affects: [12-02, scene-generation, image-generation]

# Tech tracking
tech-stack:
  added: []
  patterns: [validation-after-transform, logging-for-violations]

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php

key-decisions:
  - "Validation methods are non-blocking - log violations but don't halt processing"
  - "Single-character constraint converts two-shots to wide shots rather than failing"
  - "Character alternation threshold is 3+ consecutive shots from same speaker"

patterns-established:
  - "Phase 12 validation pattern: validate after pairReverseShots(), before calculateShotDurations()"
  - "Constraint enforcement returns modified array; validation returns issues array"

# Metrics
duration: 8min
completed: 2026-01-23
---

# Phase 12 Plan 01: Shot/Reverse-Shot Validation Summary

**180-degree rule validation, single-character constraint enforcement, and character alternation checking for Hollywood-standard dialogue shot coverage**

## Performance

- **Duration:** 8 min
- **Started:** 2026-01-23T09:15:00Z
- **Completed:** 2026-01-23T09:23:00Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments

- Added `validate180DegreeRule()` method checking camera axis lock and eyeline opposition
- Added `enforceSingleCharacterConstraint()` method converting multi-character shots (FLOW-02)
- Added `validateCharacterAlternation()` method flagging 3+ consecutive same-speaker shots (FLOW-04)
- Integrated all validators into `enhanceShotsWithDialoguePatterns()` pipeline

## Task Commits

All tasks committed as single atomic unit (same file, interdependent methods):

1. **Tasks 1-3: Add validation methods and integrate** - `95018c9` (feat)

## Files Created/Modified

- `modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php` - Added 3 validation methods and integration

### Methods Added

**validate180DegreeRule(array $shots): array** (line 587)
- Filters to dialogue shots only (those with speakingCharacter)
- Checks camera position matches axisLockSide (always 'left')
- Validates eyelines oppose between different speakers
- Returns array of violations for debugging

**enforceSingleCharacterConstraint(array $shots): array** (line 668)
- Converts two-shot/establishing to wide shot with single character
- Ensures OTS shots focus on speaking character only
- Adds otsData with foregroundBlur and foregroundVisible
- Returns modified shots array

**validateCharacterAlternation(array $shots): array** (line 767)
- Tracks consecutive shots from same speaker
- Flags 3+ consecutive as potential coverage issue
- Non-speaking shots (reaction, narrator) reset counter
- Suggests reaction shot insertion for variety

### Integration Point (line 2044-2060)

After `pairReverseShots()`, before `calculateShotDurations()`:
```php
$shots = $this->enforceSingleCharacterConstraint($shots);
$axisViolations = $this->validate180DegreeRule($shots);
$alternationIssues = $this->validateCharacterAlternation($shots);
```

## Decisions Made

1. **Validation is non-blocking** - Log violations/issues but continue processing. Rationale: Missing validation shouldn't break video generation; issues are for debugging and future improvement.

2. **Constraint enforcement modifies shots** - enforceSingleCharacterConstraint() returns modified array rather than just reporting. Rationale: FLOW-02 model constraint MUST be enforced for image generation to work.

3. **Threshold of 3 for alternation** - Same speaker for 3+ shots triggers warning. Rationale: 2 consecutive is common (monologue, continued thought), but 3+ likely indicates missing coverage variety.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - all methods implemented as specified.

## Next Phase Readiness

- Validation infrastructure complete
- Ready for Plan 12-02 (any remaining shot/reverse-shot enhancements)
- All three validation hooks firing in the dialogue pipeline
- No blockers

---
*Phase: 12-shot-reverse-shot-patterns*
*Plan: 01*
*Completed: 2026-01-23*
