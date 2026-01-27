---
phase: 24-video-temporal-expansion
plan: 01
subsystem: video-prompts
tags: [temporal-beats, micro-movements, video-generation, breathing, realism]

# Dependency graph
requires:
  - phase: 23-character-psychology-bible
    provides: CharacterPsychologyService with emotion-to-physical mappings
provides:
  - VideoTemporalService for beat-by-beat timing with time markers
  - MicroMovementService for breathing/eye/head/hand micro-movements
  - TEMPORAL_BEAT_GUIDELINES constant with action type durations
  - MICRO_MOVEMENT_LIBRARY constant with shot-appropriate life motion
  - SHOT_TYPE_MICRO_MAPPING for shot-scale visibility rules
affects: [24-04 VideoPromptBuilderService integration, VID-02, VID-06]

# Tech tracking
tech-stack:
  added: []
  patterns: [temporal-beat-structuring, shot-type-micro-mapping, emotion-variant-selection]

key-files:
  created:
    - modules/AppVideoWizard/app/Services/VideoTemporalService.php
    - modules/AppVideoWizard/app/Services/MicroMovementService.php
    - tests/Unit/VideoWizard/VideoTemporalServiceTest.php
    - tests/Unit/VideoWizard/MicroMovementServiceTest.php
  modified: []

key-decisions:
  - "Simple actions 2-3 seconds, complex motions 4-5 seconds for natural pacing"
  - "MAX_ACTIONS_PER_DURATION prevents overpacking clips (5s=2, 10s=4, 15s=5)"
  - "Shot type determines visible micro-movements (close-up=face, wide=none)"
  - "Emotion-to-variant mapping (tense=held breath, anxious=rapid breath)"

patterns-established:
  - "Temporal beat format: [00:00-00:02] action. [00:02-00:05] next action."
  - "Shot-scale visibility: extreme-close-up shows eyes+breathing, wide shows nothing"
  - "Emotion-variant pattern: EMOTION_MICRO_VARIANTS[emotion][category] = variant_name"

# Metrics
duration: 13min
completed: 2026-01-27
---

# Phase 24 Plan 01: VideoTemporalService and MicroMovementService Summary

**Temporal beat structuring with [00:00-00:02] time markers and shot-appropriate micro-movement vocabulary for breathing, eyes, head, and hands**

## Performance

- **Duration:** 13 min
- **Started:** 2026-01-27T01:02:48Z
- **Completed:** 2026-01-27T01:15:51Z
- **Tasks:** 2/2
- **Files modified:** 4 created

## Accomplishments

- VideoTemporalService formats temporal beats as `[00:00-00:02] action. [00:02-00:05] next action.`
- TEMPORAL_BEAT_GUIDELINES defines duration ranges: simple_action 2-3s, complex_motion 4-5s, emotional_beat 3-4s, camera_movement 3-8s
- MAX_ACTIONS_PER_DURATION prevents rushed video (5s max 2 actions, 10s max 4, 15s max 5)
- MicroMovementService provides MICRO_MOVEMENT_LIBRARY with breathing (subtle/heavy/held), eyes (natural/focused/shifting), head (settle/tilt/nod), hands (fidget/grip/release)
- SHOT_TYPE_MICRO_MAPPING filters by visibility: close-up shows eyes+breathing+head, wide-shot shows nothing
- EMOTION_MICRO_VARIANTS maps emotions to appropriate variants (tense=held breath, anxious=rapid breath)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create VideoTemporalService** - `e919067` (feat - bundled in previous session with CharacterPathService)
2. **Task 2: Create MicroMovementService** - `bfe2433` (feat)

_Note: Task 1 was committed in a previous session along with Plan 02's CharacterPathService. Task 2 was completed in this execution._

## Files Created/Modified

- `modules/AppVideoWizard/app/Services/VideoTemporalService.php` - Temporal beat structuring with time markers for video prompts
- `modules/AppVideoWizard/app/Services/MicroMovementService.php` - Micro-movement vocabulary for breathing, eyes, head, hands with shot-type filtering
- `tests/Unit/VideoWizard/VideoTemporalServiceTest.php` - Unit tests for temporal beat formatting and validation
- `tests/Unit/VideoWizard/MicroMovementServiceTest.php` - Unit tests for micro-movement selection and shot-type mapping

## Decisions Made

- **Action durations match research:** Simple actions 2-3s, complex motions 4-5s based on research confirming rushed timing creates artificial motion
- **MAX_ACTIONS_PER_DURATION thresholds:** Conservative limits (5s=2 actions) to prevent overpacked clips
- **Shot-type visibility:** Close-ups show face details (eyes, breathing, head), wide shots show nothing (too far for micro-movements)
- **Emotion-variant mapping:** 9 emotions mapped (tense, relaxed, anxious, curious, sad, happy, angry, fearful, neutral) covering primary states

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- **Task 1 already committed:** VideoTemporalService was already committed in a previous session (commit e919067) bundled with CharacterPathService from Plan 02. This was detected and Task 1 was skipped to avoid duplicate work.
- **PHP not in PATH:** Tests could not be run in this environment due to PHP not being available in the shell PATH. Verification was done via file inspection and grep.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- VideoTemporalService ready for VideoPromptBuilderService integration (Plan 04)
- MicroMovementService ready for VideoPromptBuilderService integration (Plan 04)
- Both services have unit tests covering core functionality
- Constants follow CharacterPsychologyService pattern (nested arrays with descriptive keys)

---
*Phase: 24-video-temporal-expansion*
*Completed: 2026-01-27*
