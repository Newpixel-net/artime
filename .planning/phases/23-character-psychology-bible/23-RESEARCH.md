# Phase 23: Character Psychology & Bible Integration - Research

**Researched:** 2026-01-27
**Domain:** Facial expression terminology, Hollywood visual subtext, Bible data integration for AI image generation
**Confidence:** MEDIUM (primary from existing codebase HIGH, external research MEDIUM)

## Summary

This research investigates how to add nuanced human behavior (FACS micro-expressions, body language, subtext) and Story Bible integration to image generation prompts. The key findings are:

1. **FACS terminology is NOT effective for current image models** - Image models respond better to physical manifestations ("brow furrowed, jaw clenched") than AU codes ("AU4+AU7"). The FACS system is designed for facial recognition/classification, not generation.

2. **Physical manifestation descriptions are most effective** - Instead of "angry expression," use "brow lowered creating vertical crease, upper lip tensed, nostrils slightly flared." This aligns with how the codebase already handles expression descriptions.

3. **Existing Bible infrastructure is solid** - The `StoryBibleService` and `CharacterLookService` already provide Character DNA (hair, wardrobe, physical traits) and Location DNA. Phase 23 should extend these, not rebuild.

4. **Hollywood subtext is conveyed through mise-en-scene** - Environment (colors, lighting, objects) reflects character emotional state. This can be implemented as a "PsychologyToEnvironment" mapping layer.

**Primary recommendation:** Implement a `CharacterPsychologyService` that translates emotional states into physical manifestations (not FACS codes) and pairs with mise-en-scene environmental cues, integrating with existing Bible structures.

## Standard Stack

The established libraries/tools for this domain:

### Core (Existing - Extend)
| Component | Location | Purpose | Extension Needed |
|-----------|----------|---------|------------------|
| StoryBibleService | `Services/StoryBibleService.php` | Story DNA generation | Add psychology fields to characters |
| CharacterLookService | `Services/CharacterLookService.php` | Character visual DNA | Add expression/body language mappings |
| StructuredPromptBuilderService | `Services/StructuredPromptBuilderService.php` | Prompt assembly | Integrate psychology layer |
| PromptTemplateLibrary | `Services/PromptTemplateLibrary.php` | Shot-type budgets | Already has `micro_expressions` emphasis |
| CinematographyVocabulary | `Services/CinematographyVocabulary.php` | Lens/lighting psychology | Good foundation, extend for emotion |

### New Services
| Service | Purpose | Confidence |
|---------|---------|------------|
| CharacterPsychologyService | Emotional state to physical manifestation mapping | HIGH |
| MiseEnSceneService | Environment-emotion integration | HIGH |
| ContinuityAnchorService | Track persistent visual elements across shots | HIGH |

### No External Libraries Required
This phase extends existing PHP services. No new npm/composer packages needed.

## Architecture Patterns

### Recommended Extension Structure
```
modules/AppVideoWizard/app/Services/
├── CharacterPsychologyService.php    # NEW: Emotion-to-physical mapping
├── MiseEnSceneService.php            # NEW: Environment-emotion integration
├── ContinuityAnchorService.php       # NEW: Cross-shot persistence
├── CharacterLookService.php          # EXTEND: Add expression presets
├── StoryBibleService.php             # EXTEND: Psychology in character profiles
└── StructuredPromptBuilderService.php # EXTEND: Assemble psychology layer
```

### Pattern 1: Physical Manifestation Mapping (Not FACS)
**What:** Map emotional states to specific physical descriptions image models understand
**When to use:** Every character-focused shot (close-up through medium)
**Example:**
```php
// Source: Research finding - image models respond to physical descriptions, not AU codes
const EMOTION_MANIFESTATIONS = [
    'suppressed_anger' => [
        'face' => 'jaw muscles visibly tensed, brow lowered creating vertical crease between eyebrows, lips pressed into thin line',
        'eyes' => 'narrowed gaze with slight lid tension, focused intensity',
        'body' => 'shoulders rigid, hands gripping armrest or clenched at sides, weight shifted forward',
        'breath' => 'controlled shallow breathing, chest barely moving',
    ],
    'hidden_sadness' => [
        'face' => 'corners of mouth pulled slightly down, chin dimpled from held tension, eyes glistening but not tearful',
        'eyes' => 'gaze lowered or distant, occasional blink interruption, slight redness around lids',
        'body' => 'shoulders curved inward, arms crossed protectively, weight shifted back',
        'breath' => 'occasional deep inhale held longer than natural',
    ],
    'forced_smile' => [
        'face' => 'mouth corners raised without cheek elevation, smile asymmetry, tension around mouth',
        'eyes' => 'eyes not crinkling (missing crow\'s feet), gaze slightly disconnected from smile',
        'body' => 'posture stiff, gestures not matching apparent emotion',
        'breath' => 'normal but controlled',
    ],
    // ... more mappings
];
```

### Pattern 2: Subtext as Contrast Layer
**What:** Visible emotion vs. hidden emotion expressed through body language contradictions
**When to use:** Scenes with emotional complexity
**Example:**
```php
// Source: Hollywood cinematography research on subtext
public function buildSubtextLayer(string $visibleEmotion, string $hiddenEmotion): array
{
    return [
        'surface' => $this->getManifestations($visibleEmotion),
        'leakage' => $this->getLeakageCues($hiddenEmotion), // Micro-expressions that "leak" true emotion
        'body_contradiction' => $this->getContradictions($visibleEmotion, $hiddenEmotion),
    ];
}

// Leakage cues - brief moments that reveal true emotion
const LEAKAGE_CUES = [
    'anger' => 'momentary nostril flare, brief jaw clench, flash of narrowed eyes',
    'fear' => 'quick eye dart, brief shoulder raise, subtle backward lean',
    'sadness' => 'momentary downward gaze, brief lip tremble, eye glisten',
    'disgust' => 'brief nose wrinkle, micro lip curl, subtle head turn',
];
```

### Pattern 3: Mise-en-Scene Emotional Mirroring
**What:** Environment reflects character's emotional state
**When to use:** All shots where environment is visible
**Example:**
```php
// Source: Cinematography research on visual storytelling
const MISE_EN_SCENE_MAPPINGS = [
    'anxiety' => [
        'lighting' => 'harsh overhead creating unflattering shadows, flickering practical lights',
        'colors' => 'desaturated with sickly yellow-green undertone',
        'space' => 'cramped framing, cluttered background, oppressive ceiling',
        'objects' => 'sharp angles, precarious arrangements, reflective surfaces',
    ],
    'hope' => [
        'lighting' => 'warm golden light from side, soft fill, lens flare',
        'colors' => 'warm palette with golden highlights bleeding into shadows',
        'space' => 'open space, visible horizon or window, clean lines',
        'objects' => 'organic shapes, growing plants, open doors',
    ],
    'isolation' => [
        'lighting' => 'single harsh source, deep shadows, minimal fill',
        'colors' => 'cool blues and grays, desaturated',
        'space' => 'vast empty space around character, distant walls',
        'objects' => 'empty chairs, closed doors, barriers',
    ],
];
```

### Pattern 4: Continuity Anchor System
**What:** Track and persist visual details across related shots
**When to use:** Any shot sequence featuring same character/location
**Example:**
```php
// Source: Character consistency research for AI image generation
class ContinuityAnchorService
{
    // Hierarchy of elements by persistence priority
    const ANCHOR_PRIORITY = [
        'primary' => ['face_structure', 'hair_style', 'hair_color', 'distinctive_features'],
        'secondary' => ['wardrobe_main', 'wardrobe_color', 'accessories'],
        'tertiary' => ['posture_base', 'hand_position', 'prop_interaction'],
    ];

    public function buildAnchorsFromFirstShot(array $shotData): array
    {
        // Extract specific details that MUST persist
        return [
            'character_anchors' => [
                'visual_dna' => $this->extractVisualDNA($shotData),
                'wardrobe_specifics' => 'red wool scarf loosely draped over left shoulder, navy peacoat unbuttoned',
                'hair_state' => 'windswept from left, strand across forehead',
                'accessory_positions' => 'silver watch visible on left wrist',
            ],
            'location_anchors' => [
                'lighting_direction' => 'key light from upper right (window)',
                'background_elements' => 'bookshelf with red spine book third from left, wilting plant on windowsill',
            ],
        ];
    }
}
```

### Anti-Patterns to Avoid
- **FACS AU codes in prompts:** Image models don't understand "AU4+AU7". Use physical descriptions.
- **Emotion labels:** "She looks angry" doesn't guide image generation. Use "brow furrowed, jaw clenched, shoulders squared."
- **Rebuilding Bible infrastructure:** CharacterLookService already handles DNA. Extend it, don't duplicate.
- **Ignoring shot type:** Close-ups need facial detail, wide shots need body language emphasis.

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Character visual persistence | Custom tracking | Extend CharacterLookService DNA | Already has hair, wardrobe, physical fields |
| Wardrobe continuity | New tracking system | CharacterLookService.wardrobePerScene | Already tracks per-scene wardrobe |
| Shot-type emphasis | Manual prioritization | PromptTemplateLibrary.SHOT_TEMPLATES | Has 'micro_expressions' in close-up emphasis |
| Lighting psychology | New mappings | CinematographyVocabulary.getRatioForMood | Already maps moods to lighting ratios |

**Key insight:** Phase 22 laid excellent groundwork. Psychology layer should integrate with existing vocabulary and template systems, not replace them.

## Common Pitfalls

### Pitfall 1: Using FACS Terminology in Prompts
**What goes wrong:** Prompts include "AU4 brow lowerer" which image models don't understand
**Why it happens:** FACS is scientific standard for facial analysis, seems authoritative
**How to avoid:** Always translate to physical descriptions: "brow lowered creating vertical crease"
**Warning signs:** Generated images don't show expected expressions despite "correct" AU codes

### Pitfall 2: Over-describing Expressions
**What goes wrong:** Too many facial details compete for attention, muddied results
**Why it happens:** Trying to be comprehensive
**How to avoid:** Focus on 2-3 key physical cues per emotional state, prioritized by shot type
**Warning signs:** Generated expressions look unnatural or conflicted when single emotion intended

### Pitfall 3: Inconsistent Bible Data Integration
**What goes wrong:** Some shots have character DNA, others don't
**Why it happens:** Psychology layer bypasses existing Bible system
**How to avoid:** Always pull from CharacterLookService, extend don't replace
**Warning signs:** Character appearance varies between shots despite Bible being set

### Pitfall 4: Subtext Overload
**What goes wrong:** Every shot has complex visible/hidden emotion contrast
**Why it happens:** Subtext seems sophisticated, applied universally
**How to avoid:** Subtext varies by scene tension level (context parameter from discussion)
**Warning signs:** Viewer fatigue, characters seem constantly conflicted

### Pitfall 5: Ignoring Token Budget
**What goes wrong:** Psychology descriptions blow past CLIP 77-token limit
**Why it happens:** Rich descriptions are long
**How to avoid:** Use ModelPromptAdapterService compression, psychology in priority order
**Warning signs:** CLIP-based models ignore expression details (truncated)

## Code Examples

Verified patterns from existing codebase:

### Existing Shot Type Emphasis (Source: PromptTemplateLibrary.php)
```php
// Source: C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\PromptTemplateLibrary.php
'close-up' => [
    'emphasis' => ['facial_detail', 'emotion', 'micro_expressions', 'eye_contact'],
    'default_lens' => '85mm',
    'word_budget' => [
        'subject' => 35,  // 35% of tokens for subject (character + expression)
        'action' => 20,
        'environment' => 10,
        'lighting' => 18,
        'style' => 17,
    ],
],
```

### Existing Emotion-to-Body-Language Mapping (Source: VideoWizard.php)
```php
// Source: C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Livewire\VideoWizard.php
$bodyLanguage = [
    'tense' => 'tight shoulders, hands gripping, jaw set',
    'relieved' => 'shoulders dropping, exhale visible, face softening',
    'angry' => 'rigid posture, clenched fists, narrowed eyes',
    'sad' => 'hunched shoulders, downcast eyes, slow movements',
    'confused' => 'furrowed brow, shifting weight, questioning expression',
];
```

### Existing Character DNA Template (Source: CharacterLookService.php)
```php
// Source: C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\CharacterLookService.php
const CHARACTER_DNA_TEMPLATES = [
    'action_hero' => [
        'physical' => [
            'build' => 'athletic muscular',
            'age_range' => '30-45',
            'distinctive_features' => 'strong jawline, intense gaze',
        ],
        'hair' => [
            'style' => 'short military cut',
            'color' => 'dark brown',
        ],
        // ... wardrobe, accessories
    ],
];
```

### Existing Mood-to-Lighting Integration (Source: CinematographyVocabulary.php)
```php
// Source: C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\CinematographyVocabulary.php
// Maps moods to lighting ratios
$moodMapping = [
    'dramatic' => '4:1',   // 2 stops difference
    'noir' => '8:1',       // 3 stops difference
    'natural' => '2:1',    // 1 stop difference
    'friendly' => '1:1',   // flat lighting
];
```

## Research Answers to CONTEXT.md Questions

### FACS Terminology Level (RESEARCH NEEDED resolved)
**Recommendation:** Use physical manifestations, NOT AU codes

Image models (Stable Diffusion, Midjourney, Flux) respond to natural language descriptions of physical states, not scientific terminology. FACS is designed for analysis/classification, not generation.

**Use this:**
```
brow lowered creating vertical crease between eyebrows, jaw muscles visibly clenched,
lips pressed into thin line, eyes narrowed with slight lid tension
```

**Not this:**
```
AU4 brow lowerer with AU7 lid tightener, AU24 lip presser
```

**Confidence:** HIGH (verified against multiple AI image generation resources)

### Intensity Conveying (RESEARCH NEEDED resolved)
**Recommendation:** Use physical gradients, not percentages

Instead of "70% angry," describe physical intensity:
- **Subtle:** "hint of tension at jaw, slight brow furrow"
- **Moderate:** "visible jaw clench, pronounced brow furrow, nostrils slightly flared"
- **Intense:** "jaw locked, veins visible at temple, deep vertical crease between brows"

**Confidence:** MEDIUM (derived from research, not explicitly tested)

### Emotional Conflict Expression (RESEARCH NEEDED resolved)
**Recommendation:** Hollywood standard is "body betrays face"

Express conflicting emotions through contradiction between:
1. **Face:** Shows socially expected emotion (forced smile)
2. **Body:** Reveals true emotion (rigid posture, clenched hands)
3. **Eyes:** Often the "leak" point (not crinkling with smile, gaze disconnected)

**Example prompt addition:**
```
face showing polite smile with raised mouth corners but eyes not crinkling,
shoulders held rigid, hands gripping coffee cup tightly betraying inner tension
```

**Confidence:** HIGH (consistent across cinematography sources)

### Subtext Structure (RESEARCH NEEDED resolved)
**Recommendation:** Three-layer structure

1. **Surface Layer:** What character wants to project (visible emotion)
2. **Leakage Layer:** Micro-cues that slip through (brief expressions)
3. **Body Layer:** Posture/gesture revealing true state

Implementation should allow scene-dependent intensity (from CONTEXT.md "vary based on tension level").

**Confidence:** MEDIUM (synthesized from multiple sources)

### Bible Data Format in Prompts (RESEARCH NEEDED resolved)
**Recommendation:** Inline descriptive, building on existing JSON structure

The existing StructuredPromptBuilderService uses inline descriptive format for character DNA. Extend this pattern:

```php
// Existing pattern from StructuredPromptBuilderService
'character_dna' => [
    'visual_dna' => 'Marcus Chen, early 30s, athletic build, sharp jawline...',
    'wardrobe' => 'navy peacoat, red wool scarf...',
],

// Extended with psychology
'character_dna' => [
    'visual_dna' => '...existing...',
    'wardrobe' => '...existing...',
    'expression_state' => 'brow slightly furrowed, jaw held tense, gaze focused and unwavering',
    'body_state' => 'shoulders squared, weight forward on balls of feet, hands loose but ready',
    'subtext_cues' => 'occasional jaw clench betraying controlled anger beneath calm exterior',
],
```

**Confidence:** HIGH (builds on verified existing codebase pattern)

### Wardrobe Specificity (RESEARCH NEEDED resolved)
**Recommendation:** Extend existing wardrobePerScene tracking

CharacterLookService already has `wardrobePerScene` tracking. Extend with:
1. **Anchor specifics:** "red wool scarf loosely draped over left shoulder" (not just "red scarf")
2. **State tracking:** "top button undone, sleeves rolled to elbow"
3. **Change markers:** Intentional vs. continuity-error detection (already exists)

**Confidence:** HIGH (extends verified existing infrastructure)

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| FACS AU codes | Physical manifestations | N/A (FACS never worked for generation) | Image models understand natural language |
| Emotion labels | Body-mind-breath descriptions | 2024-2025 prompt evolution | More realistic expressions |
| Character refs only | Character DNA + Expression DNA | Current best practice | Full-body consistency |
| Manual continuity | Automated anchor extraction | 2025 AI tools | Cross-shot persistence |

**Deprecated/outdated:**
- Using FACS AU codes in image prompts (never effective for generation)
- Single-word emotion labels ("angry", "sad") without physical description
- Separate character/expression systems (should be unified)

## Open Questions

Things that couldn't be fully resolved:

1. **Model-specific expression response**
   - What we know: Different models (HiDream, NanoBanana, Flux) may respond differently to expression prompts
   - What's unclear: Optimal phrasing per model
   - Recommendation: Start with physical manifestations, A/B test with ModelPromptAdapterService

2. **Token budget for psychology layer**
   - What we know: CLIP limit is 77 tokens; subject is priority 1
   - What's unclear: How much of subject budget should go to expression vs. appearance
   - Recommendation: Use shot-type budgets, expression is part of 'subject' allocation

3. **Subtext inference accuracy**
   - What we know: CONTEXT.md says "ALWAYS add subtext"
   - What's unclear: How reliably can system infer appropriate subtext from scene context
   - Recommendation: Implement with manual override option, monitor quality

## Sources

### Primary (HIGH confidence)
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\CharacterLookService.php - Character DNA structure
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\StoryBibleService.php - Bible generation
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\StructuredPromptBuilderService.php - Prompt assembly
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\PromptTemplateLibrary.php - Shot-type budgets
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Services\CinematographyVocabulary.php - Lens/lighting psychology
- C:\Users\VoltaPsy\Documents\GitHub\artime\modules\AppVideoWizard\app\Livewire\VideoWizard.php - Existing body language mappings

### Secondary (MEDIUM confidence)
- [iMotions FACS Guide](https://imotions.com/blog/learning/research-fundamentals/facial-action-coding-system/) - FACS AU to emotion mappings
- [FACS Cheat Sheet](https://melindaozel.com/facs-cheat-sheet/) - AU muscle descriptions
- [Subtext in Film - Medium](https://medium.com/@JohnWritesMed/subtext-and-symbolism-in-film-unlocking-the-hidden-language-of-visual-storytelling-5e1d72fc5d71) - Visual subtext techniques
- [Character Consistency 2025](https://skywork.ai/blog/how-to-consistent-characters-ai-scenes-prompt-patterns-2025/) - AI character consistency patterns
- [MasterClass Cinematography](https://www.masterclass.com/articles/film-101-what-is-cinematography-and-what-does-a-cinematographer-do) - Camera as narrator

### Tertiary (LOW confidence)
- [OpenArt Facial Expression Prompts](https://openart.ai/blog/post/stable-diffusion-prompts-for-facial-expression) - SD expression prompting (needs validation)
- [PirateDiffusion Expressions](https://piratediffusion.com/prompting-a-wide-range-of-facial-expressions-in-stable-diffusion/) - Expression range (needs validation)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Built on verified codebase analysis
- Architecture: HIGH - Extends existing patterns
- Pitfalls: MEDIUM - Derived from research synthesis
- FACS recommendation: HIGH - Multiple sources confirm physical descriptions work
- Subtext structure: MEDIUM - Synthesized from cinematography sources

**Research date:** 2026-01-27
**Valid until:** 60 days (stable domain, no rapid changes expected)
