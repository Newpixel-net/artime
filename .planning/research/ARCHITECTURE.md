# Architecture: Hollywood-Quality Prompt Pipeline

**Domain:** AI Video Generation - Prompt Expansion System
**Researched:** 2026-01-25
**Confidence:** HIGH (based on direct codebase analysis)

## Executive Summary

The Hollywood-quality prompt pipeline integrates into an existing, well-structured service architecture. The current flow uses a clean separation between prompt building (VideoWizard, StructuredPromptBuilderService) and image generation (ImageGenerationService). The expansion system should slot in as a **preprocessing layer** that transforms 50-80 word prompts into 600-1000 word Hollywood-quality prompts before they reach the image generation service.

## Current Architecture Overview

```
+-----------------------------------------------------------------------------+
|                           VideoWizard.php                                    |
|                         (Main Livewire Component)                            |
|                                                                              |
|  +---------------------------------------------------------------------+    |
|  |                      buildShotPrompt()                               |    |
|  |  - Story visual content (what's happening)                           |    |
|  |  - Dialogue integration                                              |    |
|  |  - Camera/shot type (brief)                                          |    |
|  |  - Technical specs (lens, lighting, style)                           |    |
|  |  OUTPUT: 50-80 word "compact prompt"                                 |    |
|  +---------------------------------------------------------------------+    |
+-----------------------------------------------------------------------------+
                                     |
                                     v
+-----------------------------------------------------------------------------+
|                    ImageGenerationService.php                                |
|                                                                              |
|  +---------------------------------------------------------------------+    |
|  |                      buildImagePrompt()                              |    |
|  |  Calls StructuredPromptBuilderService.build()                        |    |
|  |  - Visual mode configuration                                         |    |
|  |  - Bible integrations (character, location, style)                   |    |
|  |  - Scene DNA injection                                               |    |
|  |  - Negative prompt handling                                          |    |
|  +---------------------------------------------------------------------+    |
|                                                                              |
|  +---------------------------------------------------------------------+    |
|  |               generateSceneImage() / generateWithGemini()            |    |
|  |  - Reference cascade (characters, location, style)                   |    |
|  |  - API call to Gemini/HiDream                                        |    |
|  +---------------------------------------------------------------------+    |
+-----------------------------------------------------------------------------+
```

## Existing Service Ecosystem

### Core Services (Direct Integration Points)

| Service | File | Responsibility | Integration Priority |
|---------|------|----------------|---------------------|
| StructuredPromptBuilderService | `app/Services/StructuredPromptBuilderService.php` | JSON-based image prompts, visual modes | HIGH - Extend or wrap |
| VideoPromptBuilderService | `app/Services/VideoPromptBuilderService.php` | Hollywood formula video prompts | HIGH - Extend for expansion |
| PromptExpanderService | `app/Services/PromptExpanderService.php` | AI-powered prompt enhancement (existing) | HIGH - Replace/Enhance |
| ImageGenerationService | `app/Services/ImageGenerationService.php` | API calls to Gemini/HiDream | MEDIUM - Call site changes |
| EnhancedPromptService | `app/Services/EnhancedPromptService.php` | Unified facade for intelligence | LOW - Can wrap new system |

### Supporting Services (Context Providers)

| Service | Purpose |
|---------|---------|
| CameraMovementService | Motion intelligence, movement prompts |
| ShotIntelligenceService | AI-driven shot decomposition |
| ShotContinuityService | 30-degree rule, sequence validation |
| SceneTypeDetectorService | Auto scene classification |
| StoryBibleService | Character/location/style consistency |

## Recommended Architecture: HollywoodPromptExpander

### Component Boundaries

```
+-----------------------------------------------------------------------------+
|                    NEW: HollywoodPromptExpanderService                       |
|                                                                              |
|  +----------------------+  +----------------------+  +------------------+   |
|  |  Template Library    |  |  Expansion Engine    |  |  Context Injector|   |
|  |  - Shot type rules   |  |  - AI expansion      |  |  - Bible data    |   |
|  |  - Component blocks  |  |  - Rule fallback     |  |  - Scene state   |   |
|  |  - Quality markers   |  |  - Word count mgmt   |  |  - Continuity    |   |
|  +----------------------+  +----------------------+  +------------------+   |
|                                                                              |
|  INPUT: Compact prompt (50-80 words) + context                               |
|  OUTPUT: Hollywood prompt (600-1000 words)                                   |
+-----------------------------------------------------------------------------+
```

### Data Flow: Before vs After

**CURRENT Flow (50-80 words):**
```
Scene Data
    |
    v
VideoWizard::buildShotPrompt()
    | (50-80 words)
    v
ImageGenerationService::buildImagePrompt()
    | (adds Bible context)
    v
StructuredPromptBuilder::build()
    | (structured JSON prompt)
    v
API Call (Gemini/HiDream)
```

**PROPOSED Flow (600-1000 words):**
```
Scene Data
    |
    v
VideoWizard::buildShotPrompt()
    | (50-80 words "seed prompt")
    v
+-----------------------------------------+
| HollywoodPromptExpanderService::expand() |  <-- NEW INTEGRATION POINT
|                                          |
| 1. Parse seed prompt                     |
| 2. Detect shot type, emotion, genre      |
| 3. Load Bible context (characters, etc)  |
| 4. Apply expansion template              |
| 5. AI enhancement (Grok/GPT)             |
| 6. Validate word count (600-1000)        |
| 7. Inject quality markers                |
+-----------------------------------------+
    | (600-1000 words)
    v
ImageGenerationService::generateSceneImage()
    | (API call with expanded prompt)
    v
API Call (Gemini/HiDream)
```

## Component Design

### 1. HollywoodPromptExpanderService (New)

**Purpose:** Central orchestrator for prompt expansion.

```php
<?php

namespace Modules\AppVideoWizard\Services;

class HollywoodPromptExpanderService
{
    // Configuration
    const TARGET_WORD_COUNT_MIN = 600;
    const TARGET_WORD_COUNT_MAX = 1000;

    // Dependencies
    protected PromptTemplateLibrary $templates;
    protected ContextInjectorService $contextInjector;
    protected GrokService $aiService;

    /**
     * Expand a compact prompt to Hollywood quality.
     *
     * @param string $compactPrompt 50-80 word seed prompt
     * @param array $context Shot/scene context
     * @return array ['prompt' => string, 'metadata' => array]
     */
    public function expand(string $compactPrompt, array $context = []): array;

    /**
     * Expand with caching (for regeneration scenarios).
     */
    public function expandWithCache(string $compactPrompt, array $context): array;
}
```

### 2. PromptTemplateLibrary (New)

**Purpose:** Manage expansion templates by shot type and scene category.

```php
class PromptTemplateLibrary
{
    // Template structure for each shot type
    const SHOT_TEMPLATES = [
        'close-up' => [
            'sections' => [
                'subject_face_detail' => 150,    // words
                'emotion_micro_expression' => 100,
                'lighting_skin' => 80,
                'camera_lens' => 60,
                'environment_blur' => 80,
                'cinematic_style' => 80,
                'quality_markers' => 50,
            ],
            'priority_order' => ['subject_face_detail', 'emotion_micro_expression', ...],
        ],
        'wide' => [...],
        'establishing' => [...],
    ];

    public function getTemplate(string $shotType): array;
    public function getSectionContent(string $section, array $context): string;
}
```

### 3. ContextInjectorService (New)

**Purpose:** Pull Bible data and inject into prompt sections.

```php
class ContextInjectorService
{
    /**
     * Build context block from all available sources.
     */
    public function buildFullContext(array $sceneMemory, int $sceneIndex): array
    {
        return [
            'characters' => $this->getCharacterContext(...),
            'location' => $this->getLocationContext(...),
            'style' => $this->getStyleContext(...),
            'continuity' => $this->getContinuityAnchors(...),
            'emotional_arc' => $this->getEmotionalArcContext(...),
        ];
    }
}
```

## Integration Points

### Integration Point 1: VideoWizard.php (~line 21988)

**Current:** `buildShotPrompt()` returns 50-80 word prompt directly.

**Change:** Add optional expansion flag and call expander service.

```php
protected function buildShotPrompt(...): string
{
    // ... existing code ...

    $prompt = implode('. ', array_filter($promptParts));

    // NEW: Check if Hollywood expansion is enabled
    $expandPrompts = $this->storyboard['hollywoodExpansion']['enabled'] ?? false;

    if ($expandPrompts) {
        $expander = app(HollywoodPromptExpanderService::class);
        $expanded = $expander->expand($prompt, [
            'shotType' => $shotType,
            'sceneIndex' => $shotContext['sceneIndex'] ?? null,
            'sceneMemory' => $this->sceneMemory,
            'genre' => $this->storyboard['genre'] ?? 'cinematic',
            'mood' => $shotContext['mood'] ?? 'neutral',
        ]);
        $prompt = $expanded['prompt'];
    }

    return trim($prompt);
}
```

### Integration Point 2: ImageGenerationService.php (~line 218)

**Current:** Takes `visualDescription` and builds prompt with Bible integrations.

**Change:** Accept pre-expanded prompts, skip redundant processing.

```php
public function generateSceneImage(WizardProject $project, array $scene, array $options = []): array
{
    // NEW: Check if prompt is already Hollywood-expanded
    $isHollywoodExpanded = $options['isHollywoodExpanded'] ?? false;

    if ($isHollywoodExpanded) {
        // Use prompt directly, skip buildImagePrompt
        $prompt = $visualDescription;
    } else {
        // Existing flow
        $prompt = $this->buildImagePrompt($visualDescription, ...);
    }

    // ... rest of generation ...
}
```

### Integration Point 3: Collage Generation (~line 26244)

**Current:** Generates multiple shots rapidly with compact prompts.

**Change:** Option to use cached expanded prompts or expand on-demand.

```php
// Per-shot in collage loop
if ($useHollywoodExpansion) {
    $expander = app(HollywoodPromptExpanderService::class);
    $shot['prompt'] = $expander->expandWithCache($shot['prompt'], [
        'shotIndex' => $i,
        'cacheKey' => "collage_{$sceneIndex}_{$i}",
    ]);
}
```

## File Structure

```
modules/AppVideoWizard/app/Services/
|-- HollywoodPromptExpanderService.php    # Main orchestrator (NEW)
|-- PromptTemplateLibrary.php             # Template management (NEW)
|-- ContextInjectorService.php            # Bible/context injection (NEW)
|-- PromptExpanderService.php             # EXISTING (refactor to use new system)
|-- VideoPromptBuilderService.php         # EXISTING (minimal changes)
|-- StructuredPromptBuilderService.php    # EXISTING (no changes)
+-- ImageGenerationService.php            # EXISTING (integration point)

modules/AppVideoWizard/config/
+-- hollywood_templates.php               # Template configuration (NEW)
```

## Anti-Patterns to Avoid

### Anti-Pattern 1: Prompt Bloat Without Purpose
**What:** Adding words just to hit word count targets.
**Why bad:** Dilutes signal, confuses AI model, wastes tokens.
**Instead:** Each section must add specific visual information. If a section has nothing meaningful, skip it entirely.

### Anti-Pattern 2: Breaking Bible Integration
**What:** Overwriting or ignoring character/location/style Bible data.
**Why bad:** Destroys visual consistency the Bible system provides.
**Instead:** Bible data MUST flow through to final prompt. Expansion adds detail, doesn't replace anchors.

### Anti-Pattern 3: Synchronous Expansion in Collage Loop
**What:** Calling AI expansion for each of 4+ shots sequentially.
**Why bad:** Adds 2-4 seconds per shot, making collage generation painfully slow.
**Instead:** Pre-expand all shots in parallel before collage generation, or use cached templates.

### Anti-Pattern 4: Hardcoded Shot Type Logic
**What:** Giant switch statements for each shot type embedded in expander.
**Why bad:** Impossible to tune, extend, or A/B test different templates.
**Instead:** Template-driven approach with configurable JSON/PHP configs.

## Scalability Considerations

| Concern | Current (50-80 words) | With Expansion (600-1000 words) | Mitigation |
|---------|----------------------|--------------------------------|------------|
| API token cost | ~100 tokens/prompt | ~600-1200 tokens/prompt | Cache expanded prompts, batch expansion |
| Latency (expansion) | 0ms | 500-2000ms (AI call) | Pre-expand during shot decomposition, not generation |
| Memory | Minimal | ~1KB per expanded prompt | Store only active scene prompts |
| Collage speed | ~8s for 4 shots | +2-8s if sync expansion | Parallel pre-expansion, template caching |

## Suggested Build Order

Based on dependencies and integration complexity:

### Phase 1: Core Expansion Engine (2-3 tasks)
1. **PromptTemplateLibrary** - Template data structure, shot type configs
2. **HollywoodPromptExpanderService** - Main expand() method with rule-based fallback
3. **Unit tests** - Validate expansion for each shot type

### Phase 2: Context Integration (2-3 tasks)
1. **ContextInjectorService** - Pull Bible data, build context blocks
2. **Integration with SceneMemory** - Wire up character/location/style
3. **Continuity anchors** - Extract style from previous scenes

### Phase 3: AI Enhancement (2 tasks)
1. **AI expansion via Grok/GPT** - Intelligent prompt expansion
2. **Word count validation** - Ensure 600-1000 range

### Phase 4: Integration Points (2-3 tasks)
1. **VideoWizard integration** - Add expansion toggle, call expander
2. **ImageGenerationService** - Accept pre-expanded prompts
3. **Collage optimization** - Caching, parallel expansion

### Phase 5: UI/UX and Polish (1-2 tasks)
1. **Settings UI** - Enable/disable expansion, preview expanded prompts
2. **Prompt inspector** - Show expanded vs compact side-by-side

## Existing PromptExpanderService Analysis

The current `PromptExpanderService.php` already implements:

**Strengths (to preserve):**
- Hollywood formula constants (`HOLLYWOOD_FORMULA`)
- Shot type detection (`detectShotType()`)
- Camera movement inference (`inferCameraMovement()`)
- Lighting inference (`inferLighting()`)
- AI expansion via Grok/GPT (`expandWithAI()`)
- Rule-based fallback (`expandWithRules()`)

**Gaps (to address with new system):**
- Target word count is 50-100, not 600-1000
- No Bible context integration
- No template-driven expansion
- No caching layer
- No collage optimization

**Recommendation:** Extend `PromptExpanderService` rather than replace it. Add `expandToHollywood()` method that uses templates and targets 600-1000 words.

## Sources

All findings based on direct codebase analysis:
- `modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php`
- `modules/AppVideoWizard/app/Services/VideoPromptBuilderService.php`
- `modules/AppVideoWizard/app/Services/PromptExpanderService.php`
- `modules/AppVideoWizard/app/Services/ImageGenerationService.php`
- `modules/AppVideoWizard/app/Services/EnhancedPromptService.php`
- `modules/AppVideoWizard/app/Services/ShotIntelligenceService.php`
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php`
