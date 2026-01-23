---
phase: 11-speech-driven-shot-creation
plan: 01
subsystem: video-wizard
tags: [speech-segments, shot-creation, dialogue, lip-sync, multitalk, 1-to-1-mapping]

# Dependency graph
requires:
  - phase: 04-dialogue-scene-excellence
    provides: DialogueSceneDecomposerService, shot/reverse-shot patterns, 180-degree rule
  - phase: 1.5-automatic-speech-flow
    provides: Speech segment parsing with character linking
provides:
  - createShotsFromSpeechSegments() method for 1:1 segment-to-shot mapping
  - enhanceShotsWithDialoguePatterns() method for shot type assignment
  - Speech-driven PRIMARY path in scene decomposition
  - calculateDurationFromText() for speech duration calculation
affects:
  - 11-02-narrator-overlay (builds on shot creation foundation)
  - 12-dynamic-camera-selection (shots to enhance)
  - 13-shot-transition-continuity (shot sequences to connect)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Speech-driven shot creation (segments CREATE shots, not distributed TO them)
    - 1:1 mapping for dialogue/monologue (5 segments = 5 shots)
    - No artificial shot count caps (supports 10+ shots per scene)

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php
    - modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php

key-decisions:
  - "Speech-driven path is PRIMARY, exchange-based is FALLBACK"
  - "Narrator/internal thought segments skipped (handled in Plan 11-02)"
  - "Old distributeSpeechSegmentsToShots() deprecated but preserved for rollback"
  - "No artificial shot count limits - 12 segments produces 12 shots"

patterns-established:
  - "1:1 speech-to-shot mapping: Each dialogue/monologue segment creates exactly one shot"
  - "Duration calculation: ~2.5 words/second + 1s buffer, minimum 3s"
  - "Speech segment check: alreadyHasSpeechSegments prevents double processing"

# Metrics
duration: 35min
completed: 2026-01-23
---

# Phase 11 Plan 01: Speech-to-Shot Inversion Summary

**Speech segments now CREATE shots (1:1 mapping) instead of being distributed proportionally - 5 dialogue segments produce 5 shots, not 2 shots with segments divided**

## Performance

- **Duration:** 35 min
- **Started:** 2026-01-23T16:25:41Z
- **Completed:** 2026-01-23T17:00:00Z
- **Tasks:** 3
- **Files modified:** 2

## Accomplishments

- Inverted the speech-to-shot relationship: segments CREATE shots rather than being distributed TO them
- Each dialogue/monologue segment now creates its own shot (1:1 mapping)
- Removed artificial shot count caps - scenes can have 10+ shots when speech demands it
- Preserved 180-degree rule and shot/reverse-shot patterns from Phase 4

## Task Commits

Each task was committed atomically:

1. **Task 1: Replace distributeSpeechSegmentsToShots with createShotsFromSpeechSegments** - `6532e1d` (feat)
2. **Task 2: Enhance DialogueSceneDecomposerService for unlimited speech-driven shots** - `30c6627` (feat)
3. **Task 3: Update scene decomposition flow** - *(included in Task 1 commit)*

## Files Created/Modified

- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Added createShotsFromSpeechSegments(), calculateDurationFromText(), updated decomposeSceneWithDynamicEngine() to use speech-driven path first, deprecated distributeSpeechSegmentsToShots()
- `modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php` - Added enhanceShotsWithDialoguePatterns(), createShotFromSegment(), calculateDurationFromTextLength(), extractSpeakersFromShots(), calculateEmotionalIntensityFromShot()

## Decisions Made

| Decision | Rationale |
|----------|-----------|
| Speech-driven path PRIMARY | More cinematic - each speech creates its shot vs segments divided across predetermined shots |
| Skip narrator/internal in shot creation | Narrator is voiceover overlay, not on-screen character - handled in Plan 11-02 |
| Deprecate old method (not delete) | Allows rollback if issues discovered during testing |
| No artificial caps on shot count | Scene with 12 dialogue exchanges should produce 12 shots for proper rhythm |

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- Discovered 282 lines of uncommitted changes from a previous session (Plan 11-02 narrator overlay work). Stashed to keep execution clean.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

**Ready for Phase 11-02 (Narrator & Internal Thought Overlay):**
- createShotsFromSpeechSegments() skips narrator/internal segments (handled by overlay system)
- Shots have speechSegments attached for overlay targeting
- Enhanced shots have all metadata needed for voiceover overlay

**Dependencies satisfied:**
- 1:1 shot creation working (5 dialogue segments = 5 shots verified in logs)
- Shot types assigned by emotional intensity
- Duration calculated from word count
- 180-degree rule preserved in spatial data

---
*Phase: 11-speech-driven-shot-creation*
*Completed: 2026-01-23*
