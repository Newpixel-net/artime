---
phase: 04
plan: 04
subsystem: dialogue-decomposition
tags: [coverage-validation, shot-variety, two-shot-breaks, ots-monotony]
dependency-graph:
  requires: [04-01, 04-02]
  provides: [coverage-validation, auto-correction, shot-variety-enforcement]
  affects: [video-generation, dialogue-scenes]
tech-stack:
  added: []
  patterns: [coverage-checklist, auto-correction, pattern-analysis]
key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Services/DialogueSceneDecomposerService.php
decisions:
  - id: coverage-requirements
    choice: "Establishing 1+, OTS 2+, Close-up 1+ per dialogue scene"
    rationale: "Hollywood standard coverage ensures all angles"
  - id: ots-break-threshold
    choice: "Two-shot break after 4 consecutive OTS shots"
    rationale: "Prevents visual monotony in shot/reverse-shot"
  - id: character-coverage-minimum
    choice: "30% minimum per character"
    rationale: "Ensures both characters have adequate screen time"
metrics:
  duration: ~8 minutes
  completed: 2026-01-23
---

# Phase 4 Plan 04: Coverage Completeness Validation Summary

**One-liner:** Coverage validation with auto-fix ensures Hollywood-standard shot variety and character balance in dialogue scenes.

## What Was Built

### 1. Coverage Requirements Definition
Added `$coverageRequirements` property defining Hollywood minimums:
- **Required types:** establishing (1+), OTS (2+), close-up (1+)
- **Per-character:** at least 1 speaking shot, 30%+ coverage
- **Patterns:** max 4 consecutive OTS, min 3 different shot types

### 2. Shot Type Categories
Added `$shotTypeCategories` for grouping analysis:
- `establishing`: establishing, two-shot, wide
- `ots`: over-the-shoulder, medium
- `closeup`: close-up, extreme-close-up, medium-close
- `reaction`: reaction shots

### 3. Coverage Analysis Method
`analyzeCoverage(array $shots, array $characters): array`
- Counts shots by type and category
- Counts shots by character for balance checking
- Tracks consecutive OTS sequences
- Identifies issues: missing types, character imbalance, OTS monotony, insufficient variety

### 4. Coverage Correction Methods
`fixCoverageIssues()` dispatches to specific fixers:
- `insertMissingShotType()`: Adds establishing shot at start or close-up at 65% position
- `insertTwoShotBreaks()`: Inserts two-shot breathing room after 4 OTS shots

### 5. Shot Builder Helpers
- `buildEstablishingShot()`: Creates two-shot with spatial data
- `buildTwoShotBreak()`: Creates brief visual variety shot
- `buildEmphasisCloseup()`: Creates dramatic close-up at climax

### 6. Main Method Integration
Updated `decomposeDialogueScene()` to:
1. Analyze coverage after pairing reverse shots
2. Fix detected issues automatically
3. Re-analyze to confirm fixes
4. Log final coverage summary with category breakdown

## Key Code Additions

```php
// Coverage requirements
protected array $coverageRequirements = [
    'requiredTypes' => [
        'establishing' => 1,
        'over-the-shoulder' => 2,
        'close-up' => 1,
    ],
    'perCharacter' => [
        'speakingShots' => 1,
        'coverage' => 0.3,
    ],
    'patterns' => [
        'maxConsecutiveOTS' => 4,
        'minVariety' => 3,
    ],
];

// Analysis integration in decomposeDialogueScene()
$coverageAnalysis = $this->analyzeCoverage($shots, $characters);
if (!empty($coverageAnalysis['issues'])) {
    $shots = $this->fixCoverageIssues($shots, $coverageAnalysis, $characters, $characterLookup);
}
```

## Verification

- [x] `$coverageRequirements` property exists (line 81)
- [x] `$shotTypeCategories` property exists (line 105)
- [x] `analyzeCoverage()` method exists (line 568)
- [x] `fixCoverageIssues()` method exists (line 690)
- [x] `insertTwoShotBreaks()` breaks OTS sequences (line 719)
- [x] Missing shot types auto-inserted
- [x] Final coverage logged
- [x] Integration in main decomposition method

## Deviations from Plan

None - plan executed exactly as written.

## Decisions Made

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Coverage thresholds | 1 establishing, 2 OTS, 1 close-up | Hollywood standard coverage |
| OTS break threshold | 4 consecutive max | Prevents visual monotony |
| Character minimum | 30% per character | Ensures balanced screen time |
| Close-up position | 65% through scene | Near climax for emphasis |
| Two-shot duration | 2 seconds | Brief visual break |

## What This Enables

1. **Automatic Quality Assurance:** Every dialogue scene validated for coverage
2. **Hollywood Standards:** Ensures professional shot variety
3. **Character Balance:** Both characters get adequate coverage
4. **Visual Rhythm:** OTS monotony prevented with two-shot breaks
5. **Missing Shot Detection:** Auto-inserts establishing and close-up when missing

## Files Modified

| File | Changes |
|------|---------|
| `DialogueSceneDecomposerService.php` | +354 lines: coverage properties, analysis, correction methods |

## Commit

- `ead2b40` - feat(04-04): add coverage completeness validation for dialogue scenes
