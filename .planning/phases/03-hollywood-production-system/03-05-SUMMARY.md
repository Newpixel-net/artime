---
phase: 03-hollywood-production-system
plan: 05
subsystem: batch-generation
tags: [retry, exponential-backoff, batch-processing, progress-tracking, livewire]

# Dependency graph
requires:
  - phase: 03-01
    provides: Hollywood shot sequence activation
  - phase: 03-02
    provides: Meaningful moment extraction
  - phase: 03-03
    provides: Hollywood settings enabled by default
provides:
  - Smart retry logic for failed image generations
  - Smart retry logic for failed video generations
  - Batch generation status summary
  - Retry all failed generations capability
affects: [03-06, 03-07, batch-operations, generation-reliability]

# Tech tracking
tech-stack:
  added: []
  patterns: [exponential-backoff, retry-tracking, status-aggregation]

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php

key-decisions:
  - "Exponential backoff: 2s, 4s, 8s delay pattern"
  - "Max 3 retry attempts per item"
  - "Separate tracking for images and videos using prefixes"
  - "Status values: pending, generating, complete, failed, failed_permanent"

patterns-established:
  - "Retry tracking with item keys: scene_{i}, scene_{i}_shot_{j}, video_scene_{i}"
  - "usleep for microsecond-precision backoff delays"
  - "Livewire dispatch for scheduling retries with delay"

# Metrics
duration: 15min
completed: 2026-01-23
---

# Phase 03 Plan 05: Smart Retry Logic Summary

**Automatic retry with exponential backoff for batch image and video generation, with progress tracking and manual retry-all capability**

## Performance

- **Duration:** 15 min
- **Started:** 2026-01-23T01:39:25Z
- **Completed:** 2026-01-23T01:54:00Z
- **Tasks:** 4
- **Files modified:** 1

## Accomplishments
- Failed image generations now retry automatically up to 3 times
- Failed video generations now retry automatically up to 3 times
- Progress tracking shows per-scene/shot status with retry counts
- Users can retry all failed items with single action

## Task Commits

All tasks committed in single atomic commit:

1. **Task 1: Add retry tracking properties** - `38983d7`
2. **Task 2: Add smart retry method for image generation** - `38983d7`
3. **Task 3: Add smart retry method for video generation** - `38983d7`
4. **Task 4: Add batch generation status summary** - `38983d7`

## Files Created/Modified
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Added 6 new methods and 3 properties for smart retry logic

## Key Methods Added

### Properties (lines 1173-1184)
- `$generationRetryCount` - Track retry attempts per scene/shot
- `$maxRetryAttempts = 3` - Maximum retry attempts
- `$generationStatus` - Track generation status per item

### Methods (lines 6745-7011)
- `generateImageWithRetry()` - Wrapper for image generation with exponential backoff
- `scheduleImageRetry()` - Schedule retry event for failed image
- `generateVideoWithRetry()` - Wrapper for video generation with exponential backoff
- `scheduleVideoRetry()` - Schedule retry event for failed video
- `getBatchGenerationStatus()` - Get summary of batch progress
- `retryAllFailed()` - Reset and retry all failed items

## Decisions Made
- **Exponential backoff pattern:** 2^n seconds (2s, 4s, 8s) for increasing delays
- **Max retries:** 3 attempts per item (configurable via $maxRetryAttempts)
- **Item key format:** `scene_{i}` for single-shot, `scene_{i}_shot_{j}` for multi-shot, `video_scene_{i}` for videos
- **Status tracking:** Public `$generationStatus` for UI binding, protected `$generationRetryCount` for internal tracking
- **Retry scheduling:** Livewire dispatch events (`retry-image-generation`, `retry-video-generation`) with delay parameter

## Deviations from Plan
None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Smart retry infrastructure ready for UI integration
- Events dispatched but listeners not yet implemented (future plan)
- Ready for 03-06: Batch generation progress UI

---
*Phase: 03-hollywood-production-system*
*Completed: 2026-01-23*
