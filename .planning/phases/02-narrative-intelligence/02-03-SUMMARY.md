---
phase: 02-narrative-intelligence
plan: 03
subsystem: shot-intelligence
tags: [action-uniqueness, deduplication, validation, hollywood-standard]
dependency-graph:
  requires: ["02-01"]
  provides: ["action-deduplication", "uniqueness-validation"]
  affects: ["shot-recommendations", "video-prompts"]
tech-stack:
  added: []
  patterns: ["automatic-deduplication", "post-analysis-validation"]
key-files:
  created: []
  modified:
    - "modules/AppVideoWizard/app/Services/NarrativeMomentService.php"
    - "modules/AppVideoWizard/app/Services/ShotIntelligenceService.php"
decisions:
  - id: "dedup-timing"
    choice: "After interpolation"
    reason: "Interpolation may duplicate moments when expanding count"
  - id: "verb-window"
    choice: "2-verb sliding window"
    reason: "Allow same verb after 2+ gap for natural language flow"
metrics:
  duration: "~10min"
  completed: "2026-01-23"
---

# Phase 02 Plan 03: Action Uniqueness Validation Summary

**One-liner:** Automatic action deduplication with progression markers plus uniqueness validation scoring for Hollywood-standard shot sequences.

## What Was Built

### Task 1: Action Deduplication in NarrativeMomentService

Added three methods to NarrativeMomentService for ensuring unique actions across moments:

1. **`deduplicateActions(array $moments): array`** (public)
   - Scans consecutive moments for duplicate action verbs
   - Adds progression markers ("begins to", "then", "suddenly", etc.) to deduplicate
   - Uses 2-verb sliding window: same verb allowed after 2+ gap
   - Marks modified moments with `deduplicated: true` flag
   - Called automatically after interpolation in `decomposeNarrationIntoMoments()`

2. **`extractPrimaryVerb(string $action): string`** (protected)
   - Extracts primary action verb using regex pattern
   - Recognizes 40+ common action verbs from ACTION_EMOTION_MAP
   - Falls back to first word if no verb detected

3. **`areActionsSimilar(string $action1, string $action2): bool`** (public)
   - Compares two actions for similarity
   - Checks exact verb match, verb stem variations (look/looks/looking)
   - Recognizes synonym groups (look/watch/observe/gaze/stare/glance)

### Task 2: Action Uniqueness Validation in ShotIntelligenceService

Added validation method and integration:

1. **`validateActionUniqueness(array $moments): array`** (public)
   - Returns uniqueness score (0-100%)
   - Returns issues array with details for each duplicate
   - Uses NarrativeMomentService.areActionsSimilar() when available
   - Falls back to simple word comparison otherwise

2. **Integration in `analyzeScene()`**
   - Calls validateActionUniqueness() after narrative decomposition
   - Logs warning if issues detected
   - Adds actionUniqueness to analysis result

## How It Works

```
Narration Input
      |
      v
decomposeNarrationIntoMoments()
      |
      v
interpolateMoments()  <-- May duplicate moments
      |
      v
deduplicateActions()  <-- Adds progression markers
      |
      v
validateActionUniqueness()  <-- Calculates score
      |
      v
Analysis with actionUniqueness { valid, issues, uniquenessScore }
```

## Key Patterns

### Deduplication with Progression Markers
```php
// Input: ["looks around", "looks at the building", "walks away"]
// Output: ["looks around", "then looks at the building", "walks away"]
// The "then" marker makes the second "looks" unique
```

### Sliding Window Verb Tracking
```php
$usedVerbs = []; // Max 2 recent verbs
// After processing each moment:
$usedVerbs[] = $verb;
if (count($usedVerbs) > 2) array_shift($usedVerbs);
// This allows "looks" at moment 1 and moment 4 (gap of 2+)
```

### Synonym Group Detection
```php
$synonymGroups = [
    ['look', 'watch', 'observe', 'gaze', 'stare', 'glance'],
    ['run', 'sprint', 'dash', 'race', 'rush'],
    // ...
];
// "watches" and "observes" are considered similar
```

## Verification Results

- NarrativeMomentService.php: deduplicateActions() at line 678
- NarrativeMomentService.php: extractPrimaryVerb() at line 724
- NarrativeMomentService.php: areActionsSimilar() at line 745
- ShotIntelligenceService.php: validateActionUniqueness() at line 1852
- decomposeNarrationIntoMoments() calls deduplicateActions() after interpolation
- analyzeScene() includes actionUniqueness in result

## Commits

| Hash | Type | Description |
|------|------|-------------|
| `0c43f1f` | feat | Add action deduplication to NarrativeMomentService |
| `1d5e047` | feat | Add action uniqueness validation to ShotIntelligenceService |

## Decisions Made

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Deduplication timing | After interpolation | Interpolation expands moments, potentially creating duplicates |
| Verb window size | 2 verbs | Allows natural repetition with gap while preventing consecutive duplicates |
| Progression markers | 9 markers with rotation | Provides variety ("begins to", "then", "suddenly", etc.) |
| Similarity detection | Verb + synonyms | More comprehensive than exact match alone |

## Deviations from Plan

None - plan executed exactly as written.

## Files Modified

| File | Lines Added | Changes |
|------|-------------|---------|
| `NarrativeMomentService.php` | +113 | 3 new methods, integration call |
| `ShotIntelligenceService.php` | +93 | validateActionUniqueness(), integration |

## Success Criteria Verification

- [x] Consecutive moments with same action verb get progression markers
- [x] areActionsSimilar() detects synonyms and verb variations
- [x] validateActionUniqueness() returns uniqueness score (0-100%)
- [x] Analysis result includes actionUniqueness validation results
- [x] Deduplication happens after interpolation to catch expanded duplicates

## Next Phase Readiness

Phase 02 Plan 03 complete. The narrative intelligence system now:
1. Decomposes narration into moments (02-01)
2. Enhances AI prompts with moment context (02-02)
3. Automatically deduplicates actions (02-03)
4. Validates and scores action uniqueness (02-03)

Ready for any remaining Phase 02 plans or Phase 03.
