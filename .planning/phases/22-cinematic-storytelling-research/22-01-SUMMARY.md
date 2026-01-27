---
phase: 22-cinematic-storytelling-research
plan: 01
subsystem: ai-generation
tags: [negative-prompts, anti-portrait, image-generation, prompt-engineering]

# Dependency graph
requires:
  - phase: none
    provides: existing image generation infrastructure
provides:
  - getAntiPortraitNegativePrompts() method with 14 anti-portrait terms
  - buildNegativePrompt() helper that combines user + anti-portrait prompts
  - All 5 image generation call sites using centralized negative prompt building
affects: [22-02, 22-03, future image generation changes]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Centralized negative prompt building via buildNegativePrompt()
    - Anti-portrait terms appended (not replacing) user negative prompts

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "Anti-portrait prompts always appended, never replace user prompts"
  - "14 anti-portrait terms from research document included"
  - "Centralized buildNegativePrompt() for DRY principle"

patterns-established:
  - "buildNegativePrompt(): centralized negative prompt construction"
  - "Anti-portrait terms: looking at camera, eye contact, posed, static, etc."

# Metrics
duration: 4min
completed: 2026-01-28
---

# Phase 22 Plan 01: Anti-Portrait Negative Prompts Summary

**Added anti-portrait negative prompts to all 5 image generation call sites via centralized buildNegativePrompt() method, preventing AI models from defaulting to portrait-style images with characters looking at camera**

## Performance

- **Duration:** 4 min
- **Started:** 2026-01-27T23:39:47Z
- **Completed:** 2026-01-27T23:43:50Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments

- Created `getAntiPortraitNegativePrompts()` method with 14 anti-portrait terms from research
- Created `buildNegativePrompt()` helper that combines user prompts with anti-portrait prompts
- Updated all 5 image generation call sites to use centralized negative prompt building
- Preserved user's existing negative prompts (appended to, not replaced)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create getAntiPortraitNegativePrompts() method** - `2680983` (feat)
2. **Task 2: Create buildNegativePrompt() helper method** - `bad2a1e` (feat)
3. **Task 3: Update all 5 image generation call sites** - `448fc14` (feat)

## Files Created/Modified

- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Added getAntiPortraitNegativePrompts() (lines 21406-21412), buildNegativePrompt() (lines 21421-21435), updated 5 call sites

## Decisions Made

- **Anti-portrait terms always appended:** User's negative prompts come first, then anti-portrait terms are appended. This preserves user intent while ensuring cinematic output.
- **14 terms from research:** Used complete list from 22-RESEARCH.md: looking at camera, looking at viewer, staring at viewer, eye contact, direct gaze, front-facing portrait, posed, static, stock photo, headshot, passport photo, selfie, studio portrait, formal portrait, ID photo
- **Centralized via helper:** Rather than duplicating logic at 5 call sites, buildNegativePrompt() encapsulates the combining logic for maintainability

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- PHP syntax validation could not run (php not in PATH on Windows), but code was verified manually through file inspection

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Anti-portrait foundation complete
- Ready for Plan 02: Environmental storytelling (direction-based camera angles)
- Ready for Plan 03: Dynamic action poses (verb-based action terms)

---
*Phase: 22-cinematic-storytelling-research*
*Completed: 2026-01-28*
