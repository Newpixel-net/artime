# Multi-Shot Decomposition System - Comprehensive Upgrade Plan

## Executive Summary

The current Multi-Shot Decomposition system has **critical architectural flaws** that produce unusable video content:
- Shots with half-bodies, missing heads, or hands-only that cannot be animated
- No validation that generated content matches shot type requirements
- Scene transitions that break narrative continuity
- Detail shots that end scenes with static, non-animatable content

This plan provides a complete architectural overhaul to create **smart, animation-aware shot decomposition**.

---

## Part 1: Critical Problems Identified

### 1.1 Animatability Failures
| Problem | Current Behavior | Impact |
|---------|-----------------|--------|
| No content validation | Images assigned to shots without checking what's in them | Half-body/hands-only shots |
| Detail shots allow objects/hands | Line 17483: "hand gesture or important element" | Scene endings without character |
| No face detection for close-ups | Close-up can be any "detail" | Lip-sync impossible |

### 1.2 Collage-First Architecture Flaws
| Problem | Current Behavior | Impact |
|---------|-----------------|--------|
| Blind region extraction | Regions cropped mechanically | Wrong content for shot types |
| No shot-type matching | Region assigned regardless of content | Medium shot gets hands-only region |
| No quality gate | Images marked 'ready' without verification | Unusable shots go to video |

### 1.3 Scene Continuity Gaps
| Problem | Current Behavior | Impact |
|---------|-----------------|--------|
| Isolated scene decomposition | Each scene analyzed alone | No flow between scenes |
| Transitions ignored | Transition metadata not used in shot planning | Jarring scene changes |
| No character position tracking | Characters can "teleport" between shots | Visual discontinuity |

---

## Part 2: The Smart Shot Decomposition Architecture

### 2.1 New Core Principle: Animation-First Design

Every shot must pass the **Animatability Test**:
```
Is this shot animatable?
├── Does it contain a CHARACTER (not just hands/objects)?
├── Is the CHARACTER's FACE visible (for close-up/medium)?
├── Is the framing appropriate for MOTION (not cropped awkwardly)?
└── Does it connect narratively to adjacent shots?
```

### 2.2 New System Components

```
┌─────────────────────────────────────────────────────────────────┐
│                    SMART DECOMPOSITION PIPELINE                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │   NARRATIVE  │───▶│    SHOT      │───▶│  ANIMATABILITY│      │
│  │   ANALYZER   │    │   PLANNER    │    │    VALIDATOR  │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│         │                   │                    │               │
│         ▼                   ▼                    ▼               │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │ Cross-Scene  │    │ Shot-Type    │    │  Content     │      │
│  │ Continuity   │    │ Requirements │    │  Detection   │      │
│  │ Engine       │    │ Engine       │    │  Engine      │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Part 3: Implementation Plan

### Phase 1: Shot Type Redefinition (Priority: CRITICAL)

#### 1.1 Redefine "Detail" Shots for Animation

**Current (Broken):**
```php
'detail' => 'DETAIL/INSERT SHOT of a specific object, hand gesture, or important element'
```

**New Definition:**
```php
'detail' => [
    'type' => 'detail',
    'description' => 'Detail shot focusing on character expression or key prop interaction',
    'purpose' => 'emphasis',
    'lens' => 'telephoto 85mm',
    'requirements' => [
        'must_include' => 'character_face_or_upper_body',
        'forbidden' => ['hands_only', 'object_only', 'partial_body'],
        'animatable' => true,
    ],
    'alternatives' => [
        'character_reaction' => 'Character face reacting to event',
        'prop_with_character' => 'Character hand WITH face visible holding object',
        'environmental_with_character' => 'Character in frame with environmental focus',
    ],
]
```

#### 1.2 Create Shot Type Requirements Matrix

```php
const SHOT_ANIMATABILITY_REQUIREMENTS = [
    'establishing' => [
        'face_required' => false,
        'body_required' => false,  // Can be environment-only
        'min_character_visibility' => 0,
        'motion_type' => 'camera_pan_or_environmental',
    ],
    'wide' => [
        'face_required' => false,
        'body_required' => true,   // Full body must be visible
        'min_character_visibility' => 0.8,  // 80% of body visible
        'motion_type' => 'full_body_movement',
    ],
    'medium' => [
        'face_required' => true,
        'body_required' => true,   // Upper body minimum
        'min_character_visibility' => 0.5,  // Waist up
        'motion_type' => 'upper_body_gesture',
    ],
    'medium-close' => [
        'face_required' => true,
        'body_required' => false,  // Shoulders up OK
        'min_character_visibility' => 0.3,
        'motion_type' => 'facial_and_gesture',
    ],
    'close-up' => [
        'face_required' => true,   // MUST have face
        'body_required' => false,
        'min_character_visibility' => 0.2,
        'motion_type' => 'facial_expression',
    ],
    'detail' => [
        'face_required' => true,   // Changed from false!
        'body_required' => false,
        'min_character_visibility' => 0.15,
        'motion_type' => 'subtle_movement',
        'fallback_to' => 'close-up',  // If no character, use close-up instead
    ],
];
```

### Phase 2: Animatability Validation System (Priority: CRITICAL)

#### 2.1 Create Content Detection Service

**New File:** `modules/AppVideoWizard/app/Services/ContentAnalysisService.php`

```php
<?php

namespace Modules\AppVideoWizard\Services;

class ContentAnalysisService
{
    /**
     * Analyze generated image for animatability requirements.
     * Uses Gemini Vision to detect what's in the image.
     */
    public function analyzeImageContent(string $imageBase64): array
    {
        $prompt = <<<PROMPT
Analyze this image for video animation suitability. Return JSON:
{
    "has_character": boolean,
    "face_visible": boolean,
    "face_visibility_percent": 0-100,
    "body_visible": boolean,
    "body_visibility_percent": 0-100,
    "body_parts_visible": ["head", "shoulders", "torso", "arms", "hands", "waist", "legs", "feet"],
    "is_hands_only": boolean,
    "is_object_only": boolean,
    "is_partial_body": boolean,
    "character_position": "left" | "center" | "right",
    "character_facing": "camera" | "left" | "right" | "away",
    "framing_quality": "excellent" | "good" | "poor" | "unusable",
    "animation_suitability": "high" | "medium" | "low" | "none",
    "issues": ["list of problems if any"]
}
PROMPT;

        // Call Gemini Vision API
        return $this->geminiService->analyzeImage($imageBase64, $prompt);
    }

    /**
     * Validate if image meets shot type requirements.
     */
    public function validateForShotType(array $analysis, string $shotType): array
    {
        $requirements = self::SHOT_ANIMATABILITY_REQUIREMENTS[$shotType] ?? [];
        $issues = [];
        $passed = true;

        // Check face requirement
        if ($requirements['face_required'] && !$analysis['face_visible']) {
            $issues[] = "Shot type '{$shotType}' requires visible face but none detected";
            $passed = false;
        }

        // Check body requirement
        if ($requirements['body_required'] && !$analysis['body_visible']) {
            $issues[] = "Shot type '{$shotType}' requires visible body but none detected";
            $passed = false;
        }

        // Check for unusable content
        if ($analysis['is_hands_only']) {
            $issues[] = "Image shows only hands - not suitable for video animation";
            $passed = false;
        }

        if ($analysis['is_partial_body'] && $requirements['min_character_visibility'] > 0.3) {
            $issues[] = "Image shows awkwardly cropped body";
            $passed = false;
        }

        return [
            'passed' => $passed,
            'issues' => $issues,
            'suggestion' => $passed ? null : $this->getSuggestion($shotType, $analysis),
        ];
    }
}
```

#### 2.2 Integrate Validation into Collage Extraction

**Modify:** `extractCollageQuadrantsToShots()` in VideoWizard.php

```php
// After extracting region image, VALIDATE before assignment
$analysis = $this->contentAnalysisService->analyzeImageContent($regionBase64);
$validation = $this->contentAnalysisService->validateForShotType($analysis, $shot['type']);

if (!$validation['passed']) {
    // Mark shot as needing regeneration
    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIdx]['imageStatus'] = 'needs_regen';
    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIdx]['validationIssues'] = $validation['issues'];
    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIdx]['suggestion'] = $validation['suggestion'];

    // Log for user visibility
    Log::warning('[ShotValidation] Shot failed animatability check', [
        'scene' => $sceneIndex,
        'shot' => $shotIdx,
        'type' => $shot['type'],
        'issues' => $validation['issues'],
    ]);
} else {
    // Mark as ready
    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIdx]['imageStatus'] = 'ready';
    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIdx]['contentAnalysis'] = $analysis;
}
```

### Phase 3: Cross-Scene Continuity Engine (Priority: HIGH)

#### 3.1 Scene Connection Analysis

**New File:** `modules/AppVideoWizard/app/Services/SceneContinuityEngine.php`

```php
<?php

namespace Modules\AppVideoWizard\Services;

class SceneContinuityEngine
{
    /**
     * Build cross-scene context for shot planning.
     */
    public function buildSceneConnectionContext(array $scenes, int $currentSceneIndex): array
    {
        $context = [
            'previous_scene' => null,
            'next_scene' => null,
            'transition_in' => null,
            'transition_out' => null,
            'continuity_requirements' => [],
        ];

        // Previous scene analysis
        if ($currentSceneIndex > 0) {
            $prevScene = $scenes[$currentSceneIndex - 1];
            $context['previous_scene'] = [
                'final_shot_type' => $this->getFinalShotType($prevScene),
                'final_character_position' => $this->getCharacterPosition($prevScene, 'final'),
                'ending_action' => $this->getEndingAction($prevScene),
                'location' => $prevScene['location'] ?? null,
            ];
            $context['transition_in'] = $prevScene['transition'] ?? 'cut';

            // If previous scene ends with character looking at something,
            // current scene should acknowledge that
            if ($this->endsWithLookingAction($prevScene)) {
                $context['continuity_requirements'][] = [
                    'type' => 'looking_continuation',
                    'description' => 'Previous scene ends with character looking - start with what they see or their reaction',
                ];
            }
        }

        // Next scene analysis
        if ($currentSceneIndex < count($scenes) - 1) {
            $nextScene = $scenes[$currentSceneIndex + 1];
            $context['next_scene'] = [
                'opening_shot_type' => $this->getOpeningShotType($nextScene),
                'starting_action' => $this->getStartingAction($nextScene),
                'location' => $nextScene['location'] ?? null,
            ];
            $context['transition_out'] = $scenes[$currentSceneIndex]['transition'] ?? 'cut';

            // Plan final shot to lead into next scene
            if ($this->requiresSmoothTransition($context['transition_out'])) {
                $context['continuity_requirements'][] = [
                    'type' => 'smooth_exit',
                    'description' => 'End with shot that leads naturally to next scene',
                ];
            }
        }

        return $context;
    }

    /**
     * Recommend first shot type based on previous scene.
     */
    public function recommendFirstShot(array $context): string
    {
        if (!$context['previous_scene']) {
            return 'establishing';  // First scene of video
        }

        $prevFinal = $context['previous_scene']['final_shot_type'];
        $transition = $context['transition_in'];

        // Smart recommendations based on cinematic rules
        $recommendations = [
            'close-up' => [
                'cut' => 'wide',           // Cut from close-up: open wide for context
                'dissolve' => 'medium',     // Dissolve: medium maintains flow
                'fade' => 'establishing',   // Fade: new chapter, establish location
            ],
            'wide' => [
                'cut' => 'medium',          // Cut from wide: move closer
                'dissolve' => 'medium',
                'fade' => 'establishing',
            ],
            'detail' => [
                'cut' => 'wide',            // Cut from detail: pull back for context
                'dissolve' => 'close-up',   // Dissolve: match scale
                'fade' => 'establishing',
            ],
        ];

        return $recommendations[$prevFinal][$transition] ?? 'medium';
    }

    /**
     * Recommend final shot type based on next scene.
     */
    public function recommendFinalShot(array $context, string $currentFinalType): string
    {
        if (!$context['next_scene']) {
            return 'close-up';  // Last scene: end on character emotion
        }

        $nextOpening = $context['next_scene']['opening_shot_type'];
        $transition = $context['transition_out'];

        // Avoid matching scales across cuts (creates jump cut feel)
        if ($transition === 'cut' && $currentFinalType === $nextOpening) {
            return $this->getAlternativeShot($currentFinalType);
        }

        // For story continuation, end on character (not detail/object)
        if ($currentFinalType === 'detail') {
            return 'close-up';  // Change detail to close-up for better continuation
        }

        return $currentFinalType;
    }
}
```

#### 3.2 Integrate into Decomposition

**Modify:** `decomposeSceneWithDynamicEngine()` in VideoWizard.php

```php
public function decomposeSceneWithDynamicEngine($scene, $sceneIndex, $visualDescription)
{
    // NEW: Build cross-scene context
    $continuityEngine = new SceneContinuityEngine();
    $sceneContext = $continuityEngine->buildSceneConnectionContext(
        $this->script['scenes'],
        $sceneIndex
    );

    // NEW: Get recommended first/last shot types
    $recommendedFirst = $continuityEngine->recommendFirstShot($sceneContext);
    $recommendedLast = $continuityEngine->recommendFinalShot($sceneContext, 'close-up');

    // Build decomposition context WITH continuity info
    $context = $this->buildDecompositionContext($sceneIndex, $scene);
    $context['continuity'] = $sceneContext;
    $context['recommended_first_shot'] = $recommendedFirst;
    $context['recommended_final_shot'] = $recommendedLast;

    // Pass to engine
    $engine = new DynamicShotEngine();
    $analysis = $engine->analyzeScene($scene, $context);

    // Ensure first shot matches recommendation
    if ($analysis['shots'][0]['type'] !== $recommendedFirst) {
        $analysis['shots'][0]['type'] = $recommendedFirst;
        $analysis['shots'][0]['continuity_adjusted'] = true;
    }

    // Ensure final shot is animatable (not detail/hands)
    $lastIdx = count($analysis['shots']) - 1;
    if ($analysis['shots'][$lastIdx]['type'] === 'detail') {
        $analysis['shots'][$lastIdx]['type'] = $recommendedLast;
        $analysis['shots'][$lastIdx]['continuity_adjusted'] = true;
    }

    return $analysis;
}
```

### Phase 4: Smart Collage Generation (Priority: HIGH)

#### 4.1 Content-Aware Region Planning

**New approach:** Instead of generating 4 random images and assigning them to shots, pre-plan what each region should contain.

```php
/**
 * Plan collage regions based on shot requirements BEFORE generation.
 */
public function planCollageRegions(array $shots): array
{
    $regions = [];

    foreach ($shots as $idx => $shot) {
        $requirements = self::SHOT_ANIMATABILITY_REQUIREMENTS[$shot['type']] ?? [];

        $regions[$idx] = [
            'shot_index' => $idx,
            'shot_type' => $shot['type'],
            'content_requirements' => [
                'must_show_face' => $requirements['face_required'] ?? false,
                'must_show_body' => $requirements['body_required'] ?? false,
                'min_visibility' => $requirements['min_character_visibility'] ?? 0,
                'forbidden' => ['hands_only', 'object_only'],
            ],
            'prompt_modifier' => $this->getPromptModifierForRequirements($requirements),
        ];
    }

    return $regions;
}

/**
 * Generate modified prompt that ensures animatable content.
 */
protected function getPromptModifierForRequirements(array $requirements): string
{
    $modifiers = [];

    if ($requirements['face_required']) {
        $modifiers[] = "CHARACTER'S FACE MUST BE CLEARLY VISIBLE";
    }

    if ($requirements['body_required']) {
        $modifiers[] = "Show character from " . $this->getFramingDescription($requirements['min_character_visibility']);
    }

    $modifiers[] = "DO NOT show only hands or objects without character face visible";
    $modifiers[] = "ENSURE the shot is suitable for video animation with character movement";

    return implode(". ", $modifiers);
}
```

#### 4.2 Post-Generation Validation Loop

```php
/**
 * Generate collage with validation and auto-regeneration.
 */
public function generateValidatedCollage(int $sceneIndex, array $shots): array
{
    $maxAttempts = 3;
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        $attempt++;

        // Plan regions with requirements
        $regionPlans = $this->planCollageRegions($shots);

        // Generate collage with modified prompts
        $collage = $this->generateCollageWithRequirements($sceneIndex, $regionPlans);

        // Validate each region
        $allValid = true;
        foreach ($collage['regions'] as $idx => $region) {
            $analysis = $this->contentAnalysisService->analyzeImageContent($region['base64']);
            $validation = $this->contentAnalysisService->validateForShotType(
                $analysis,
                $shots[$idx]['type']
            );

            if (!$validation['passed']) {
                $allValid = false;
                Log::info("[CollageValidation] Region {$idx} failed validation", [
                    'attempt' => $attempt,
                    'issues' => $validation['issues'],
                ]);
            }
        }

        if ($allValid) {
            return $collage;
        }

        // Modify prompts based on failures and retry
        Log::warning("[CollageValidation] Attempt {$attempt} failed, retrying with adjusted prompts");
    }

    // After max attempts, flag for manual intervention
    return [
        'success' => false,
        'needs_manual_review' => true,
        'collage' => $collage,  // Return last attempt
        'validation_failures' => $this->getValidationFailures(),
    ];
}
```

### Phase 5: UI/UX Improvements (Priority: MEDIUM)

#### 5.1 Show Validation Status on Shot Cards

```blade
{{-- Shot Card with Validation Status --}}
<div class="shot-card {{ $shot['validationStatus'] ?? 'pending' }}">
    <div class="shot-image">
        <img src="{{ $shot['imageUrl'] }}">

        @if(($shot['validationStatus'] ?? '') === 'failed')
        <div class="validation-warning">
            <span class="icon">⚠️</span>
            <span class="message">Not suitable for animation</span>
            <ul class="issues">
                @foreach($shot['validationIssues'] ?? [] as $issue)
                <li>{{ $issue }}</li>
                @endforeach
            </ul>
            <button wire:click="regenerateShot({{ $sceneIndex }}, {{ $shotIndex }})">
                🔄 Regenerate
            </button>
        </div>
        @endif
    </div>
</div>
```

#### 5.2 Scene Continuity Indicator

```blade
{{-- Scene Connection Indicator --}}
@if($sceneIndex > 0)
<div class="scene-connection">
    <div class="connection-line"></div>
    <div class="connection-info">
        <span class="transition">{{ $scene['transition'] ?? 'cut' }}</span>
        @if($continuityIssues[$sceneIndex] ?? false)
        <span class="continuity-warning" title="{{ $continuityIssues[$sceneIndex] }}">
            ⚠️ Continuity issue
        </span>
        @else
        <span class="continuity-ok">✓ Flow OK</span>
        @endif
    </div>
</div>
@endif
```

---

## Part 4: Implementation Priority & Timeline

### Immediate Fixes (Week 1)
1. **Redefine Detail shots** - Remove "hands only" option, require character face
2. **Add basic validation** - Flag shots that appear to be hands/partial bodies
3. **Fix final shot assignment** - Don't auto-assign "detail" to last shot

### Short-term (Weeks 2-3)
4. **Implement ContentAnalysisService** - Vision-based content detection
5. **Add validation to collage extraction** - Reject unusable regions
6. **Build SceneContinuityEngine** - Cross-scene shot planning

### Medium-term (Weeks 4-6)
7. **Smart collage generation** - Pre-planned regions with requirements
8. **Auto-regeneration loop** - Retry failed shots automatically
9. **UI validation indicators** - Show issues to users

### Long-term (Weeks 7+)
10. **Character position tracking** - Maintain continuity within scenes
11. **Advanced motion planning** - Shot-to-shot movement continuity
12. **ML-based quality scoring** - Automated animation suitability scoring

---

## Part 5: Success Metrics

### Quality Gates
- [ ] 0% of shots are "hands only"
- [ ] 0% of shots have bodies cut in half awkwardly
- [ ] 100% of close-up shots have visible faces
- [ ] 100% of final shots are character-centric (not objects)

### Continuity Metrics
- [ ] Scene transitions follow cinematic rules
- [ ] First shot of scene N connects to last shot of scene N-1
- [ ] Character positions are consistent within shot sequences

### Animation Suitability
- [ ] All shots can generate meaningful video motion
- [ ] Lip-sync possible on close-up/medium shots
- [ ] Body movement visible on wide/medium shots

---

## Appendix: Key File Locations

| Component | File | Lines |
|-----------|------|-------|
| Shot type definitions | VideoWizard.php | 15261-15322 |
| Detail shot framing | VideoWizard.php | 17483 |
| Collage extraction | VideoWizard.php | 18257-18400 |
| Scene decomposition | VideoWizard.php | 14815-14918 |
| Dynamic shot engine | DynamicShotEngine.php | 63-96 |
| Motion description | VideoWizard.php | 15798-15853 |
| Continuity validation | VideoWizard.php | 13992-14109 |

---

*Document created: January 19, 2026*
*Author: Claude (AI Assistant)*
*Version: 1.0*
