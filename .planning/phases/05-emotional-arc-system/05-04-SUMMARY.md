---
phase: 05-emotional-arc-system
plan: 04
subsystem: shot-composition
tags: [shot-types, climax-awareness, intensity-mapping, camera-movement]

dependency-graph:
  requires: ["05-01", "05-02"]
  provides: ["arc-aware shot composition", "climax-specific framing", "camera movement suggestions"]
  affects: ["video generation", "shot planning"]

tech-stack:
  added: []
  patterns: ["intensity-to-shot mapping", "template-based thresholds", "climax-aware selection"]

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/app/Services/DynamicShotEngine.php

decisions:
  - id: threshold-ordering
    choice: "Descending threshold order for shot selection"
    reason: "First match wins - tighter shots have higher thresholds"
  - id: climax-framing
    choice: "Always close-up or XCU for climax shots"
    reason: "Maximum emotional impact at narrative peak"
  - id: special-types
    choice: "Preserve establishing, two-shot, reaction types"
    reason: "These serve specific narrative purposes regardless of intensity"

metrics:
  duration: "~15 minutes"
  completed: "2026-01-23"
---

# Phase 5 Plan 04: Arc-Aware Shot Composition Summary

Configurable intensity thresholds with template-specific adjustments and climax-aware shot selection.

## Objective

Enhance emotion-to-shot mapping with smoothed intensities and climax awareness. Shot types now follow the smoothed emotional arc with climax getting tighter framing automatically.

## Tasks Completed

| Task | Name | Status | Verification |
|------|------|--------|--------------|
| 1 | Add configurable intensity thresholds | Done | Properties and method exist |
| 2 | Add climax-aware shot type selection | Done | Methods exist, climax gets tight framing |
| 3 | Add smoothed intensity application method | Done | Public method with full metadata |
| 4 | Integrate into generateHollywoodShotSequence | Done | Uses enhanced arc processing |

## Commit

- `eabe4c8` - feat(05-04): add arc-aware shot composition with climax awareness

## Features Added

### 1. Configurable Intensity Thresholds

```php
protected array $intensityThresholds = [
    'extreme-close-up' => 0.85,  // XCU for peak moments
    'close-up' => 0.70,          // CU for high emotion
    'medium-close' => 0.55,      // MCU for engagement
    'medium' => 0.40,            // Standard shots
    'wide' => 0.25,              // Context shots
    'establishing' => 0.0,       // Opening/scale shots
];
```

### 2. Template-Specific Adjustments

| Template | Adjustments | Effect |
|----------|-------------|--------|
| action | close-up -0.05, XCU -0.05 | More close-ups |
| drama | close-up -0.10, medium -0.05 | Even more close-ups |
| comedy | wide -0.10, close-up +0.10 | More wide shots |
| documentary | medium -0.15, close-up +0.15 | More medium shots |

### 3. Climax-Aware Shot Selection

- `selectShotTypeWithClimaxAwareness()` - Always returns tight framing for climax
- Climax shots get close-up (intensity < 0.9) or extreme-close-up (intensity >= 0.9)
- Non-climax shots use template-adjusted thresholds

### 4. Camera Movement Suggestions

| Intensity | Climax | Movement |
|-----------|--------|----------|
| Any | Yes | push-in |
| >= 0.75 | No | slow-push |
| <= 0.30 | No | static |
| 0.30-0.75 | No | slight-drift or static |

### 5. Smoothed Intensity Application

`applySmoothedIntensityToShots()` updates shot array with:
- `emotionalIntensity` - smoothed value
- `rawIntensity` - preserved original
- `isClimax` - boolean flag
- `suggestedMovement` - camera movement
- `intensityMeta` - template, smoothed flag, climax proximity

### 6. Shot Type Tightness Ranking

```php
'establishing' => 0,
'wide' => 1,
'two-shot' => 2,
'medium' => 3,
'medium-close' => 4,
'close-up' => 5,
'extreme-close-up' => 6,
```

Used to ensure shots never downgrade during climax approach.

### 7. Enhanced generateHollywoodShotSequence

Now returns:
```php
[
    'shots' => [...],
    'hollywoodPatterns' => [
        'emotionalArc' => true/false,
        'smoothedArc' => true/false,
        'dialogueCoverage' => true/false,
    ],
    'emotionalArc' => [
        'applied' => true/false,
        'template' => 'hollywood',
        'climaxIndex' => 3,
    ],
]
```

## Deviations from Plan

None - plan executed exactly as written.

## Files Modified

- `modules/AppVideoWizard/app/Services/DynamicShotEngine.php`
  - Added: `$intensityThresholds` property
  - Added: `$templateThresholdAdjustments` property
  - Added: `getAdjustedThresholds()` method
  - Added: `selectShotTypeWithClimaxAwareness()` method
  - Added: `getCameraMovementForIntensity()` method
  - Added: `applySmoothedIntensityToShots()` method
  - Added: `getShotTypeTightness()` method
  - Modified: `generateHollywoodShotSequence()` method

## Integration Points

1. **From NarrativeMomentService:** Receives smoothed intensity curves and climax data
2. **To Shot Generation:** Provides arc-aware shot types with camera movement suggestions
3. **To Video Generation:** Shot metadata includes intensity and climax proximity

## Phase 5 Status

| Plan | Name | Status |
|------|------|--------|
| 05-01 | Climax Detection | Complete |
| 05-02 | Intensity Curve Smoothing | Complete |
| 05-03 | Shot-to-Beat Mapping | Pending |
| 05-04 | Arc-Aware Shot Composition | **Complete** |

## Next Steps

Plan 05-03 (Shot-to-Beat Mapping) should wire emotional arc data to VideoWizard UI for visualization and user control.
