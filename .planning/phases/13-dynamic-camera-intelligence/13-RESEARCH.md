# Phase 13: Dynamic Camera Intelligence - Research

**Researched:** 2026-01-23
**Domain:** Emotion-driven cinematography, shot type selection
**Confidence:** HIGH (based on existing codebase analysis)

## Summary

Phase 13 enhances the existing shot type selection system to make dynamic camera choices that respond to emotional intensity and conversation position. The codebase already has substantial infrastructure for this:

- `DialogueSceneDecomposerService` has `selectShotTypeForIntensity()`, `calculateDialoguePosition()`, and `calculateEmotionalIntensity()` methods
- `DynamicShotEngine` has `selectShotTypeWithClimaxAwareness()` and `selectShotTypeForEmotion()` methods
- Milestone 5 established emotion detection with keyword analysis, climax detection, and intensity-to-shot mapping

The gap is that these systems operate somewhat independently and lack full conversation position awareness for CAM-02/CAM-04. The current `selectShotTypeForIntensity()` uses position but doesn't enforce the Hollywood arc pattern (establishing -> medium -> tight progression).

**Primary recommendation:** Enhance `selectShotTypeForIntensity()` to combine emotional intensity with explicit conversation position rules, ensuring establishing shots at opening and tight framing at climax (CAM-04), while making per-speaker emotion analysis explicit (CAM-03).

## What Already Exists

### Shot Type Selection (CAM-01 Partial)

**Location:** `DialogueSceneDecomposerService.php` lines 1326-1344

```php
protected function selectShotTypeForIntensity(float $intensity, string $position): string
{
    // Climax moments get tighter framing
    if ($position === 'climax' && $intensity >= 0.8) {
        return 'extreme-close-up';
    }

    if ($intensity >= 0.75) {
        return 'close-up';
    } elseif ($intensity >= 0.55) {
        return 'medium-close';
    } elseif ($intensity >= 0.4) {
        return 'over-the-shoulder';
    } elseif ($intensity >= 0.25) {
        return 'medium';
    } else {
        return 'wide';
    }
}
```

**What it does:**
- Maps intensity thresholds to shot types
- Has special case for climax position
- Called by `createDialogueShot()` and `enhanceShotsWithDialoguePatterns()`

**Gap for CAM-01:** Method exists but doesn't dynamically vary thresholds based on conversation arc or speaker emotion.

### Conversation Position Detection (CAM-02 Partial)

**Location:** `DialogueSceneDecomposerService.php` lines 426-439

```php
protected function calculateDialoguePosition(int $index, int $total): string
{
    $progress = $total > 1 ? $index / ($total - 1) : 0;

    if ($progress < 0.2) {
        return 'opening';
    } elseif ($progress < 0.5) {
        return 'building';
    } elseif ($progress < 0.8) {
        return 'climax';
    } else {
        return 'resolution';
    }
}
```

**What it does:**
- Returns position labels: 'opening', 'building', 'climax', 'resolution'
- Based purely on index position in dialogue sequence

**Gap for CAM-02:** Position is detected but not strongly enforced in shot type selection. The 'opening' position doesn't guarantee wide/establishing shots, and 'climax' doesn't guarantee tight framing.

### Emotional Intensity Calculation (CAM-03 Partial)

**Location:** `DialogueSceneDecomposerService.php` lines 444-477

```php
protected function calculateEmotionalIntensity(array $exchange, string $position, array $scene): float
{
    // Base intensity from position
    $positionIntensity = match($position) {
        'opening' => 0.3,
        'building' => 0.5,
        'climax' => 0.85,
        'resolution' => 0.5,
        default => 0.5,
    };

    // Adjust based on scene mood
    $mood = strtolower($scene['mood'] ?? 'neutral');
    $moodIntensity = $this->emotionIntensityMap[$mood] ?? 0.5;

    // Adjust based on dialogue content (exclamations, questions)
    $text = $exchange['text'] ?? '';
    $textModifier = 0;
    if (str_contains($text, '!')) $textModifier += 0.15;
    if (str_contains($text, '?')) $textModifier += 0.05;
    if (str_contains($text, '...')) $textModifier -= 0.1;

    // Combine: 50% position + 30% mood + text modifier
    $intensity = ($positionIntensity * 0.5) + ($moodIntensity * 0.3) + $textModifier;
    return max(0.1, min(1.0, $intensity));
}
```

**What it does:**
- Combines position, scene mood, and dialogue text analysis
- Uses `$emotionIntensityMap` for mood keywords (calm=0.2 to climax=0.95)

**Gap for CAM-03:** This calculates intensity from the dialogue text but doesn't analyze the *speaker's* emotional state specifically. The method is per-exchange, but the same speaker's evolving emotional state across multiple exchanges isn't tracked.

### Establishing Shot Logic (CAM-04 Partial)

**Location:** `DialogueSceneDecomposerService.php` lines 225-227

```php
// SHOT 1: Establishing two-shot (if more than 2 exchanges)
if ($totalExchanges > 2 && ($options['includeEstablishing'] ?? true)) {
    $shots[] = $this->createEstablishingShot($scene, $speakers, $characterLookup);
}
```

**What it does:**
- Adds establishing shot at the START of dialogue scenes
- Only if scene has more than 2 exchanges

**Gap for CAM-04:** Opening establishing shot exists, but there's no explicit "tight framing at climax" enforcement beyond the intensity-based selection.

### DynamicShotEngine Alternative Methods

**Location:** `DynamicShotEngine.php` lines 897-920, 1047-1078

These provide additional shot type selection with climax awareness:

```php
public function selectShotTypeWithClimaxAwareness(float $intensity, bool $isClimax, string $template): string
{
    if ($isClimax) {
        return $intensity >= 0.9 ? 'extreme-close-up' : 'close-up';
    }
    // Template-adjusted thresholds...
}

public function selectShotTypeForEmotion(float $intensity, int $index, int $totalShots, ?string $sceneType): string
{
    // First shot always establishing (unless very short)
    if ($index === 0 && $totalShots > 2) {
        return 'establishing';
    }
    // Last shot character-centric for animation
    if ($index === $totalShots - 1) {
        return $intensity > 0.6 ? 'close-up' : 'medium';
    }
    // Intensity-based selection...
}
```

**Note:** These are parallel implementations that exist in DynamicShotEngine, but the primary speech-driven path uses `DialogueSceneDecomposerService.selectShotTypeForIntensity()`.

## Standard Stack

### Core Components (Existing)

| Component | Location | Purpose |
|-----------|----------|---------|
| DialogueSceneDecomposerService | app/Services/ | Shot type selection, emotional intensity, dialogue patterns |
| DynamicShotEngine | app/Services/ | Climax-aware shot selection, arc templates |
| VideoWizard | app/Livewire/ | Orchestration, speech-to-shot flow |

### Data Structures (Existing)

```php
// emotionIntensityMap - Maps mood keywords to intensity values
$emotionIntensityMap = [
    'calm' => 0.2, 'neutral' => 0.25, 'curious' => 0.3,
    'concerned' => 0.45, 'questioning' => 0.5, 'determined' => 0.55,
    'urgent' => 0.75, 'angry' => 0.8, 'fearful' => 0.75,
    'revelation' => 0.9, 'climax' => 0.95, 'shock' => 0.9,
];

// Shot types by intensity (dialogueShotTypes)
$dialogueShotTypes = [
    'establishing' => ['intensity' => 0.1],
    'two-shot' => ['intensity' => 0.2],
    'wide' => ['intensity' => 0.25],
    'medium' => ['intensity' => 0.4],
    'over-the-shoulder' => ['intensity' => 0.5],
    'medium-close' => ['intensity' => 0.6],
    'close-up' => ['intensity' => 0.8],
    'extreme-close-up' => ['intensity' => 0.95],
];
```

## Architecture Patterns

### Current Flow (Phase 11/12)

```
Speech Segments
      │
      ▼
createShotsFromSpeechSegments() [1:1 mapping]
      │
      ▼
enhanceShotsWithDialoguePatterns()
      │
      ├─► calculateDialoguePosition() → 'opening'/'building'/'climax'/'resolution'
      │
      ├─► calculateEmotionalIntensity() → 0.0-1.0 float
      │
      └─► selectShotTypeForIntensity() → 'close-up'/'medium'/etc.
```

### Proposed Enhancement Pattern

```
Speech Segments
      │
      ▼
createShotsFromSpeechSegments() [unchanged]
      │
      ▼
enhanceShotsWithDialoguePatterns()
      │
      ├─► calculateDialoguePosition() [unchanged]
      │
      ├─► calculateSpeakerEmotionalState() [NEW - CAM-03]
      │         Analyzes speaker's emotion from their dialogue text
      │
      ├─► calculateEmotionalIntensity() [enhanced]
      │         Now includes speaker emotion + position + scene mood
      │
      └─► selectShotTypeForIntensity() [enhanced - CAM-01, CAM-02, CAM-04]
             Position-enforced rules:
             - 'opening' → wide or establishing (never close-up)
             - 'climax' + high intensity → extreme-close-up
             - Speaker emotion adjusts thresholds
```

### Shot Progression Arc Pattern

Hollywood standard for dialogue scenes:

| Position | Progress % | Shot Type Range | Emotional Context |
|----------|-----------|-----------------|-------------------|
| Opening | 0-20% | Establishing, Wide, Medium | Scene setup |
| Building | 20-50% | Medium, OTS, Medium-Close | Increasing engagement |
| Climax | 50-80% | Close-up, Extreme Close-up | Peak emotion |
| Resolution | 80-100% | Medium, Wide | Denouement |

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Emotion detection from text | Custom NLP | Existing `emotionIntensityMap` + punctuation analysis | Already has 20+ emotion keywords mapped |
| Climax detection | Custom algorithm | M5's `detectClimaxFromContent()` in NarrativeMomentService | Already exists with keyword + peak detection |
| Shot type thresholds | New threshold system | Existing `$dialogueShotTypes` array | Already calibrated to Hollywood standards |
| Camera position calculation | New spatial system | Existing `calculateSpatialData()` | Already has 180-degree rule |

## What's Missing (Gaps to Fill)

### Gap 1: Position-Enforced Shot Type Rules (CAM-02, CAM-04)

**Current:** `selectShotTypeForIntensity()` primarily uses intensity thresholds with only a minor climax override.

**Needed:** Explicit position rules that guarantee:
- Opening position NEVER returns close-up (max: medium)
- Climax position with high intensity ALWAYS returns tight framing

**Solution:**
```php
protected function selectShotTypeForIntensity(float $intensity, string $position): string
{
    // CAM-04: Position-enforced rules FIRST
    switch ($position) {
        case 'opening':
            // Opening shots are always wide/establishing
            if ($intensity >= 0.4) return 'medium';
            if ($intensity >= 0.25) return 'wide';
            return 'establishing';

        case 'climax':
            // Climax shots are always tight
            if ($intensity >= 0.8) return 'extreme-close-up';
            return 'close-up';

        case 'resolution':
            // Resolution can ease back
            if ($intensity >= 0.6) return 'medium-close';
            return 'medium';

        case 'building':
        default:
            // Building uses full intensity range
            // (existing threshold logic)
    }
}
```

### Gap 2: Per-Speaker Emotion Analysis (CAM-03)

**Current:** Emotional intensity is calculated per-exchange but doesn't track speaker-specific emotional state.

**Needed:** Analyze each speaker's dialogue text to determine THEIR emotional state, not just the scene's intensity.

**Solution:** New method `analyzeSpeakerEmotion()`:
```php
protected function analyzeSpeakerEmotion(string $dialogueText): array
{
    $text = strtolower($dialogueText);

    // Detect emotion from dialogue content
    $emotions = [
        'angry' => ['yell', 'hate', 'damn', 'furious', '!'],
        'fearful' => ['afraid', 'scared', 'help', 'no!', 'please'],
        'sad' => ['sorry', 'miss', 'lost', 'gone', '...'],
        'excited' => ['amazing', 'incredible', 'yes!', 'finally'],
        'pleading' => ['please', 'beg', 'need', 'must'],
    ];

    foreach ($emotions as $emotion => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return [
                    'emotion' => $emotion,
                    'intensity' => $this->emotionIntensityMap[$emotion] ?? 0.5,
                ];
            }
        }
    }

    return ['emotion' => 'neutral', 'intensity' => 0.5];
}
```

### Gap 3: Shot Variety in Opening Phase (CAM-02)

**Current:** Opening phase may not have enough variety (could all be same shot type).

**Needed:** Ensure camera variety even within opening phase.

**Solution:** Add alternation logic for medium vs wide shots in opening phase based on speaker changes.

## Common Pitfalls

### Pitfall 1: Losing Existing Validation

**What goes wrong:** Phase 12 added validation (180-degree rule, single-character constraint). Modifying shot selection could break validation.

**Prevention:** Enhancement must preserve all calls to:
- `validate180DegreeRule()`
- `enforceSingleCharacterConstraint()`
- `validateCharacterAlternation()`

### Pitfall 2: Conflicting Shot Selection Methods

**What goes wrong:** `DynamicShotEngine` and `DialogueSceneDecomposerService` both have shot selection methods. Enhancing one without aligning the other creates inconsistency.

**Prevention:** The PRIMARY path is `DialogueSceneDecomposerService.selectShotTypeForIntensity()` via `enhanceShotsWithDialoguePatterns()`. Focus changes there. DynamicShotEngine methods are for non-speech scenes.

### Pitfall 3: Over-Engineering Emotion Detection

**What goes wrong:** Building complex NLP when simple keyword matching works.

**Prevention:** Use existing `emotionIntensityMap` and add simple keyword patterns. The existing punctuation analysis (!, ?, ...) is sufficient.

### Pitfall 4: Breaking the 1:1 Speech-to-Shot Mapping

**What goes wrong:** Phase 11 established that each speech segment creates exactly one shot. Adding logic that creates/removes shots breaks this.

**Prevention:** Phase 13 ONLY modifies shot TYPE, not shot COUNT. Never add/remove shots in the enhancement methods.

## Code Examples

### Enhanced selectShotTypeForIntensity()

```php
/**
 * PHASE 13: Select shot type with position enforcement and speaker emotion.
 *
 * CAM-01: Dynamic CU/MS/OTS selection based on emotional intensity
 * CAM-02: Camera variety based on position in conversation
 * CAM-04: Establishing at start, tight framing at climax
 *
 * @param float $intensity Emotional intensity 0-1
 * @param string $position Dialogue position (opening/building/climax/resolution)
 * @param string|null $speakerEmotion Speaker's detected emotion
 * @return string Shot type
 */
protected function selectShotTypeForIntensity(
    float $intensity,
    string $position,
    ?string $speakerEmotion = null
): string {
    // PHASE 13 CAM-04: Position-enforced rules take priority
    switch ($position) {
        case 'opening':
            // Opening ALWAYS uses wide framing (CAM-04)
            // Never close-up at conversation start
            if ($intensity >= 0.5) return 'medium';
            if ($intensity >= 0.3) return 'wide';
            return 'establishing';

        case 'climax':
            // Climax ALWAYS uses tight framing (CAM-04)
            if ($intensity >= 0.8 || $speakerEmotion === 'angry' || $speakerEmotion === 'fearful') {
                return 'extreme-close-up';
            }
            return 'close-up';

        case 'resolution':
            // Resolution eases back to medium framing
            if ($intensity >= 0.65) return 'medium-close';
            if ($intensity >= 0.4) return 'medium';
            return 'wide';
    }

    // 'building' phase uses full intensity range (CAM-01)
    // Speaker emotion can adjust intensity threshold (CAM-03)
    $adjustedIntensity = $intensity;
    if ($speakerEmotion === 'angry' || $speakerEmotion === 'fearful') {
        $adjustedIntensity = min(1.0, $intensity + 0.15);
    }

    if ($adjustedIntensity >= 0.75) {
        return 'close-up';
    } elseif ($adjustedIntensity >= 0.55) {
        return 'medium-close';
    } elseif ($adjustedIntensity >= 0.4) {
        return 'over-the-shoulder';
    } elseif ($adjustedIntensity >= 0.25) {
        return 'medium';
    }

    return 'wide';
}
```

### Speaker Emotion Extraction

```php
/**
 * PHASE 13 CAM-03: Analyze speaker's emotional state from their dialogue.
 *
 * @param string $dialogueText The speaker's dialogue
 * @return array ['emotion' => string, 'intensity' => float]
 */
protected function analyzeSpeakerEmotion(string $dialogueText): array
{
    $text = strtolower($dialogueText);

    // High-intensity emotions (0.75+)
    if (preg_match('/\b(yell|scream|hate|kill|furious|rage)\b/', $text) ||
        substr_count($dialogueText, '!') >= 2) {
        return ['emotion' => 'angry', 'intensity' => 0.8];
    }

    if (preg_match('/\b(afraid|scared|terrified|help me|no!|please don\'t)\b/', $text)) {
        return ['emotion' => 'fearful', 'intensity' => 0.75];
    }

    if (preg_match('/\b(love|adore|marry|forever)\b/', $text)) {
        return ['emotion' => 'loving', 'intensity' => 0.7];
    }

    // Medium-intensity emotions (0.5-0.7)
    if (preg_match('/\b(sorry|regret|forgive|apologize)\b/', $text)) {
        return ['emotion' => 'remorseful', 'intensity' => 0.6];
    }

    if (preg_match('/\b(think|consider|wonder|perhaps)\b/', $text)) {
        return ['emotion' => 'contemplative', 'intensity' => 0.4];
    }

    // Default neutral
    return ['emotion' => 'neutral', 'intensity' => 0.5];
}
```

## Integration Points

### Primary Integration (in enhanceShotsWithDialoguePatterns)

```php
// Current code (line ~1991-2000):
foreach ($shots as $index => &$shot) {
    $position = $this->calculateDialoguePosition($index, $totalShots);
    $emotionalIntensity = $this->calculateEmotionalIntensityFromShot($shot, $position, $scene);
    $shotType = $this->selectShotTypeForIntensity($emotionalIntensity, $position);
    // ...
}

// PHASE 13 enhancement:
foreach ($shots as $index => &$shot) {
    $position = $this->calculateDialoguePosition($index, $totalShots);

    // CAM-03: Analyze speaker's emotion from their dialogue
    $speakerEmotion = $this->analyzeSpeakerEmotion($shot['dialogue'] ?? '');

    // Calculate intensity including speaker emotion
    $emotionalIntensity = $this->calculateEmotionalIntensityFromShot(
        $shot, $position, $scene, $speakerEmotion
    );

    // CAM-01, CAM-02, CAM-04: Position-enforced shot selection
    $shotType = $this->selectShotTypeForIntensity(
        $emotionalIntensity,
        $position,
        $speakerEmotion['emotion'] ?? null
    );

    // Store speaker emotion for downstream use
    $shot['speakerEmotion'] = $speakerEmotion;
    // ...
}
```

## State of the Art

| Existing Feature | Current State | Phase 13 Enhancement |
|------------------|---------------|----------------------|
| Intensity-to-shot mapping | Works via thresholds | Add position enforcement |
| Position detection | Returns 4 labels | Position RULES shot type |
| Emotion keywords | 20+ in emotionIntensityMap | Add speaker-specific analysis |
| Establishing shots | Optional at start | Required at opening position |
| Climax framing | Intensity-based | Guaranteed tight framing |

## Open Questions

### Resolved via Codebase Analysis

1. **Where is shot type selection?** - `DialogueSceneDecomposerService.selectShotTypeForIntensity()` (primary)
2. **How is position calculated?** - `calculateDialoguePosition()` using index/total ratio
3. **What emotion data exists?** - `emotionIntensityMap` with 20+ keywords mapped to 0.1-0.95

### Items for Planning Phase

1. **Signature change:** Should `selectShotTypeForIntensity()` get a third parameter for speaker emotion, or should emotion be folded into intensity before calling?
   - Recommendation: Add optional third parameter to keep backward compatibility

2. **Test coverage:** Should we add unit tests for position enforcement rules?
   - Recommendation: Not required for M8, but good future improvement

## Sources

### Primary (HIGH confidence)

- `DialogueSceneDecomposerService.php` - Full codebase analysis (2233 lines)
- `DynamicShotEngine.php` - Full codebase analysis (1342 lines)
- `VideoWizard.php` - Integration points analysis (lines 17900-23600)
- `.planning/phases/05-emotional-arc-system/05-01-PLAN.md` - M5 climax detection design

### Secondary (MEDIUM confidence)

- STATE.md - Phase 11/12 completion context
- REQUIREMENTS.md - CAM-01 through CAM-04 specifications

## Metadata

**Confidence breakdown:**
- What exists: HIGH - Direct codebase analysis
- Gap analysis: HIGH - Compared requirements to implementation
- Solution approach: HIGH - Minimal changes to existing patterns
- Code examples: HIGH - Based on existing code patterns

**Research date:** 2026-01-23
**Valid until:** N/A (codebase-specific research)
