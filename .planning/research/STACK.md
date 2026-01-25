# Technology Stack for Hollywood-Quality Prompt Pipeline

**Project:** Video Wizard v11 - Hollywood-Quality Prompt Pipeline
**Researched:** 2026-01-25
**Confidence:** HIGH (based on existing codebase analysis + 2026 industry standards)

## Executive Summary

The 600-1000 word Hollywood-quality prompt expansion does NOT require new libraries or frameworks. The existing stack (Laravel 10, Livewire 3, existing services) is fully capable. What's needed is:

1. **Enhanced LLM prompting strategy** - Structured expansion prompts for AI
2. **Template layering system** - Composable template sections
3. **Validation pipelines** - Word count and quality gates

This is an **architecture and prompt engineering problem**, not a technology stack problem.

---

## Recommended Stack Additions

### Core Approach: LLM-Powered Expansion

| Component | Technology | Version | Purpose | Why |
|-----------|------------|---------|---------|-----|
| Expansion Engine | PromptExpanderService (enhanced) | existing | 10-15x prompt expansion | Already exists, needs word count targets |
| Template System | PHP associative arrays | N/A | Composable prompt sections | Simple, no dependencies, Laravel-native |
| LLM Backend | Grok 4 / GPT-4o (existing) | current | Intelligent expansion | Already integrated via AI facade |
| Validation | Custom ValidatorService | new | Word count + structure validation | Ensures 600-1000 word targets met |

### DO NOT Add

| Technology | Why Not |
|------------|---------|
| External templating library (Twig, Blade components) | Overkill - PHP arrays + string concatenation work fine |
| NLP libraries (spaCy, NLTK) | Word counting is trivial, emotion detection via LLM |
| Separate microservice | Latency overhead, existing architecture handles this |
| Vector database for prompt retrieval | Not needed - templates are static, context from Bibles |
| Fine-tuned LLM | Expensive, prompt engineering achieves same result |

---

## Expansion Strategy

### The Industry Standard: "Shot Grammar" Framework (2026)

Based on [fal.ai Kling 2.6 Pro Guide](https://fal.ai/learn/devs/kling-2-6-pro-prompt-guide) and [OpenAI Sora 2 Cookbook](https://cookbook.openai.com/examples/sora/sora2_prompting_guide):

```
[Scene Setting]          - Environment, lighting, atmosphere
[Subject Description]    - Character DNA, wardrobe, physical details
[Emotional State]        - Micro-expressions, body language
[Action/Motion]          - Temporal progression, what happens
[Camera/Cinematography]  - Shot type, movement, lens characteristics
[Technical Quality]      - Resolution, color grade, film look
```

This maps directly to existing services:
- Scene Setting -> `StoryBibleService::getMasterStyleGuide()`
- Subject Description -> `StructuredPromptBuilderService::buildCharacterDNA()`
- Camera/Cinematography -> `VideoPromptBuilderService::buildCameraMovementComponent()`

### Expansion Multiplier Strategy

Current prompts: 50-100 words
Target prompts: 600-1000 words
Expansion factor: **10-15x**

**Expansion happens at these layers:**

| Section | Current Words | Target Words | Expansion Source |
|---------|---------------|--------------|------------------|
| Subject/Character | 10-20 | 100-150 | Character Bible DNA + LLM micro-detail |
| Emotion/Expression | 5-10 | 80-120 | LLM expansion with emotion vocabulary |
| Environment | 10-20 | 80-120 | Location Bible + atmospheric details |
| Action/Motion | 10-20 | 100-150 | Temporal beats + body language |
| Camera/Technical | 15-30 | 80-120 | Existing VideoPromptBuilder + lens specs |
| Continuity/Context | 0-10 | 60-100 | Physical continuity tracking |
| Quality Markers | 10-20 | 40-60 | Existing quality markers + negatives |
| **TOTAL** | **60-130** | **540-820** | |

Add buffer for connecting prose: +80-180 words = **620-1000 words target**

---

## Implementation Architecture

### Layer 1: Section Templates (PHP Arrays)

```php
// No new library needed - extend existing constants pattern
const EMOTION_EXPANSION_TEMPLATES = [
    'happy' => [
        'micro_expressions' => 'corners of mouth naturally lifted, crow\'s feet appearing at eyes, cheeks raised with genuine warmth',
        'body_language' => 'shoulders relaxed and open, slight forward lean, animated hand gestures',
        'breathing' => 'easy rhythmic breathing, occasional deep satisfied breath',
        'gaze' => 'eyes bright and engaged, frequent blinking, pupils slightly dilated',
    ],
    'tense' => [
        'micro_expressions' => 'jaw slightly clenched, forehead with subtle furrow, lips pressed together',
        'body_language' => 'shoulders raised, muscles coiled with potential energy, weight shifted forward',
        'breathing' => 'shallow controlled breathing, visible tension in neck',
        'gaze' => 'eyes darting, hyperalert scanning, reduced blink rate',
    ],
    // ... etc
];
```

### Layer 2: LLM Expansion Service (Enhanced PromptExpanderService)

```php
// Enhance existing service with word count targeting
public function expandToHollywoodQuality(
    string $basicPrompt,
    array $options = []
): array {
    $targetWordCount = $options['targetWords'] ?? 800;
    $sections = $options['sections'] ?? self::DEFAULT_SECTIONS;

    // Use existing AI facade with expansion-focused system prompt
    $systemPrompt = $this->buildHollywoodExpansionPrompt($targetWordCount, $sections);

    // Existing Grok/OpenAI integration
    return $this->callAI($systemPrompt, $basicPrompt);
}
```

### Layer 3: Validation Pipeline

```php
// New: PromptValidationService
class PromptValidationService
{
    public function validate(string $prompt, array $requirements): array
    {
        $wordCount = str_word_count($prompt);
        $sections = $this->detectSections($prompt);

        return [
            'valid' => $wordCount >= $requirements['minWords']
                    && $wordCount <= $requirements['maxWords'],
            'wordCount' => $wordCount,
            'sectionsPresent' => $sections,
            'missingElements' => $this->findMissingElements($prompt, $requirements),
        ];
    }
}
```

---

## LLM Prompt Engineering Strategy

### System Prompt for Hollywood Expansion

Based on [IBM 2026 Prompt Engineering Guide](https://www.ibm.com/think/prompt-engineering) and [Palantir Best Practices](https://www.palantir.com/docs/foundry/aip/best-practices-prompt-engineering):

```text
You are a Hollywood cinematographer and screenplay writer expanding brief scene descriptions into detailed visual prompts for AI video generation.

TARGET OUTPUT: {targetWords} words (strictly enforced)

REQUIRED SECTIONS (include ALL):
1. SUBJECT (100-150 words): Character appearance from DNA, specific clothing textures, skin details with pores/imperfections
2. MICRO-EXPRESSIONS (80-120 words): Specific facial muscle movements, eye behavior, breathing patterns
3. BODY LANGUAGE (60-80 words): Posture, weight distribution, hand positions, subtle movements
4. ENVIRONMENT (80-120 words): Location details, textures, atmospheric elements, background activity
5. TEMPORAL PROGRESSION (60-100 words): What changes from beginning to end of shot, motion beats
6. CAMERA/CINEMATOGRAPHY (80-120 words): Shot type, lens, movement with timing, depth of field
7. LIGHTING (40-60 words): Key/fill/rim sources, color temperature, shadows
8. CONTINUITY ANCHORS (40-60 words): Details that must match previous/next shots

RULES:
- Use concrete visual language, not abstract adjectives
- Replace "beautiful" with specific textures, colors, light qualities
- Include at least 3 specific micro-expressions
- Describe at least one subtle body movement
- Specify exact color values when possible (hex or descriptive)
- Write as flowing prose, not bullet points
```

### Few-Shot Examples

Provide 2-3 expansion examples in the system prompt:

```text
EXAMPLE INPUT: "Sarah enters the cafe, nervous about the meeting"

EXAMPLE OUTPUT (782 words):
A woman in her late twenties with fair skin showing subtle freckles across the bridge of her nose steps through the weathered wooden door of a sunlit corner cafe. Sarah wears a forest green wool coat over a cream cashmere sweater, the fabric showing natural texture and gentle pilling at the cuffs from frequent wear. Her chestnut brown hair is pulled back in a low ponytail, a few loose strands framing her face, catching the warm afternoon light streaming through large plate-glass windows...

[Continue with full expansion demonstrating all sections]
```

---

## Integration Points with Existing Services

### StructuredPromptBuilderService Enhancements

Current `build()` method returns ~60-100 word prompts. Enhancement path:

```php
// Add new method alongside existing
public function buildHollywoodPrompt(array $options): array
{
    // 1. Build base structured prompt (existing)
    $base = $this->build($options);

    // 2. Expand via LLM with word count target
    $expanded = $this->promptExpander->expandToHollywoodQuality(
        $this->toPromptString($base),
        [
            'targetWords' => $options['targetWords'] ?? 800,
            'characterDNA' => $base['creative_prompt']['character_dna'],
            'locationDNA' => $base['creative_prompt']['location_dna'],
            'styleDNA' => $base['creative_prompt']['style_dna'],
        ]
    );

    // 3. Validate meets requirements
    $validation = $this->validator->validate($expanded['prompt'], [
        'minWords' => 600,
        'maxWords' => 1000,
    ]);

    // 4. Retry if validation fails
    if (!$validation['valid']) {
        return $this->expandWithRetry($base, $validation);
    }

    return $expanded;
}
```

### VideoPromptBuilderService Enhancements

Similar pattern for video prompts, focusing on temporal progression:

```php
public function buildHollywoodVideoPrompt(array $shot, array $context): array
{
    $base = $this->buildHollywoodPrompt($shot, $context);

    // Add temporal progression layer (unique to video)
    $temporalBeats = $this->buildTemporalProgression($shot);

    // Expand to target with temporal emphasis
    return $this->promptExpander->expandForVideo(
        $base['prompt'],
        [
            'targetWords' => 800,
            'temporalBeats' => $temporalBeats,
            'duration' => $shot['duration'] ?? 6,
        ]
    );
}
```

---

## Alternatives Considered

| Approach | Recommended | Alternative | Why Not Alternative |
|----------|-------------|-------------|---------------------|
| LLM expansion | Yes - Grok 4/GPT-4o | Fine-tuned model | Cost, maintenance, existing models work well |
| Template system | PHP arrays | Twig/Blade | Unnecessary complexity, arrays are sufficient |
| Validation | Custom PHP | External NLP | Word counting is trivial, LLM handles semantics |
| Prompt storage | In-service constants | Database/file | No dynamic prompts needed, constants faster |

---

## Performance Considerations

### LLM Call Optimization

Each Hollywood-quality prompt requires 1 LLM expansion call. Mitigate latency:

1. **Parallel expansion** - Expand all shots in scene simultaneously
2. **Caching** - Cache expanded prompts by hash of inputs
3. **Streaming** - Use streaming API for progressive display
4. **Fallback** - Rule-based expansion if LLM fails (existing PromptExpanderService.expandWithRules)

### Token Budget

- Input: ~200-300 tokens (basic prompt + Bible context)
- Output: ~800-1000 tokens (expanded prompt)
- Total per shot: ~1,100-1,300 tokens
- Model: Grok 4 Fast (economy tier) = cost-effective

### Batch Processing

For full storyboard generation (10-20 shots):
- Sequential: 10-20 LLM calls @ ~2s each = 20-40s
- Parallel: 3-5 concurrent calls = 8-12s total
- Recommendation: Use job queue for background processing

---

## Configuration

### Environment Variables (Existing)

No new env vars needed. Uses existing:
```
GROK_API_KEY=xxx
OPENAI_API_KEY=xxx
AI_DEFAULT_PROVIDER=grok
```

### New Service Configuration

```php
// config/video-wizard.php additions
'hollywood_prompts' => [
    'image_target_words' => 800,
    'video_target_words' => 800,
    'voice_target_words' => 200,
    'min_words' => 600,
    'max_words' => 1000,
    'max_expansion_retries' => 2,
    'expansion_model_tier' => 'economy', // grok-4-fast
],
```

---

## Voice Prompt Specifications

Voice prompts have different requirements than image/video prompts:

| Dimension | Image/Video Prompts | Voice Prompts |
|-----------|---------------------|---------------|
| Target words | 600-1000 | 150-300 |
| Focus | Visual detail | Emotional direction |
| Key elements | Micro-expressions, body language | Pacing, vocal quality, breath |

### Voice Prompt Structure

```text
VOICE PROMPT SECTIONS:
1. EMOTIONAL FOUNDATION (40-60 words): Core emotion, intensity, subtext
2. VOCAL QUALITY (30-50 words): Tone, pitch range, resonance
3. PACING MARKERS (30-50 words): Rhythm, pauses, emphasis words
4. BREATH AND TEXTURE (20-40 words): Breathiness, catch, tremor
5. AMBIENT CONTEXT (20-40 words): Room tone, environmental cues
```

---

## Implementation Checklist

- [ ] Enhance `PromptExpanderService` with `expandToHollywoodQuality()` method
- [ ] Add Hollywood expansion system prompt with word count targeting
- [ ] Create emotion/body language expansion template arrays
- [ ] Add temporal progression templates for video prompts
- [ ] Create `PromptValidationService` for word count validation
- [ ] Update `StructuredPromptBuilderService` with Hollywood mode
- [ ] Update `VideoPromptBuilderService` with Hollywood mode
- [ ] Add configuration for target word counts
- [ ] Add retry logic for under-length prompts
- [ ] Add caching layer for expanded prompts

---

## Sources

### Official Documentation (HIGH confidence)
- Existing codebase: `StructuredPromptBuilderService.php`, `VideoPromptBuilderService.php`, `PromptExpanderService.php`

### Industry Standards (MEDIUM confidence)
- [OpenAI Sora 2 Prompting Guide](https://cookbook.openai.com/examples/sora/sora2_prompting_guide) - Prompt structure, temporal beats, character consistency
- [fal.ai Kling 2.6 Pro Prompt Guide](https://fal.ai/learn/devs/kling-2-6-pro-prompt-guide) - Scene/Camera/Lighting/Atmosphere structure
- [IBM 2026 Prompt Engineering Guide](https://www.ibm.com/think/prompt-engineering) - LLM prompting best practices
- [Palantir Prompt Engineering Best Practices](https://www.palantir.com/docs/foundry/aip/best-practices-prompt-engineering) - Production prompt strategies

### Research Context (MEDIUM confidence)
- [AI Fire - 7 Prompts to Master Any AI Video Tool](https://www.aifire.co/p/7-prompts-to-master-any-ai-video-tool-veo-kling-runway-guide) - Cross-platform prompt patterns
- [Crafting Cinematic Sora Video Prompts GitHub Gist](https://gist.github.com/ruvnet/e20537eb50866b2d837d4d13b066bd88) - 250+ example prompts
- [TrueFan.ai Cinematic AI Video Prompts](https://www.truefan.ai/blogs/cinematic-ai-video-prompts-2026) - 2026 prompt structure framework
