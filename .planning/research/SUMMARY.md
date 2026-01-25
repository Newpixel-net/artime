# Project Research Summary

**Project:** Video Wizard M11 - Hollywood-Quality Prompt Pipeline
**Domain:** AI-generated cinematography (multi-model video production)
**Researched:** 2026-01-25
**Confidence:** HIGH

## Executive Summary

Hollywood-quality prompts require expanding user inputs from 50-80 words to 600-1000 words with professional cinematography vocabulary, but this expansion must be strategically designed to avoid fatal pitfalls. The research reveals that **no new libraries are needed** — the existing Laravel/Livewire architecture with enhanced LLM prompting is fully capable. The core challenge is building a template-driven expansion system that integrates Story Bible context while respecting model token limits and avoiding prompt bloat.

The recommended approach is a **preprocessing layer architecture** that slots between existing prompt building (VideoWizard, StructuredPromptBuilderService) and image generation (ImageGenerationService). This expansion uses LLM-powered intelligence (Grok 4/GPT-4o) combined with rule-based templates organized by shot type, delivering detailed prompts with specific camera specs, lighting ratios, micro-expressions, and temporal choreography. The expansion must be **model-aware** — CLIP-based models like HiDream have a strict 77-token limit requiring compression, while Gemini-based models handle longer prompts gracefully.

Critical risks center on three pitfalls: (1) **Token truncation** — expanded prompts silently cut at 77 tokens on CLIP models, wasting expansion effort; (2) **Prompt bloat** — excessive detail confuses models and reduces output quality; (3) **Shot-to-shot inconsistency** — characters morph between shots without reference images and style anchors. Mitigation requires model-specific formatters from Phase 1, complexity scoring in Phase 2, and a continuity anchor system integrated throughout.

## Key Findings

### Recommended Stack

The existing stack is fully sufficient — this is an architecture and prompt engineering problem, not a technology acquisition problem. No new external libraries, databases, or microservices are required.

**Core technologies:**
- **PromptExpanderService (enhanced)** — Already exists, needs word count targeting for 600-1000 words instead of current 50-100 target
- **PHP associative arrays for templates** — Composable prompt sections (emotion, lighting, camera) without external templating overhead
- **Grok 4 / GPT-4o (existing AI facade)** — Intelligent expansion via existing integration, no new API connections needed
- **Custom PromptValidationService (new)** — Word count and structure validation to ensure targets met, simple PHP class

**Critical additions identified:**
- Template library system using PHP constants/arrays (no framework needed)
- Context injection from existing StoryBibleService
- Model-specific prompt formatters (adapter pattern for CLIP vs Gemini vs Runway)
- Caching layer for expanded prompts to avoid repeated AI calls

**Explicitly avoid:**
- External templating libraries (Twig, Blade components) — overkill for string composition
- NLP libraries (spaCy, NLTK) — word counting is trivial, emotion detection via LLM
- Separate microservice — adds latency without benefit
- Fine-tuned LLM — expensive and unnecessary, prompt engineering achieves same result

### Expected Features

Hollywood-quality prompts differ from amateur prompts through **specificity, technical precision, and emotional depth**. Professional cinematographers specify exact lens choices, lighting ratios, actor micro-expressions, and temporal progression — not just "what is seen" but "why it looks that way."

**Must have (table stakes):**
1. **Specific camera specs** — "85mm f/1.4" not "close-up"; professionals specify lens psychology
2. **Quantified framing** — "Face fills 80% of frame" not "extreme close-up"
3. **Lighting setup descriptions** — "2:1 key-to-fill ratio, 3200K warmth" not "dramatic lighting"
4. **Camera angle psychology** — Low angle = power, high angle = vulnerability (film language)
5. **Micro-expression details** — FACS-informed facial descriptions ("AU1+AU2, hope/uncertainty")
6. **Body language specifics** — Posture, weight distribution, tension points
7. **Emotional state in physicality** — "Tears pooling but not falling" not "sad"
8. **Temporal structure (video)** — Beat-by-beat timing: [0.0-1.0s: X, 1.0-2.0s: Y, 2.0-3.0s: Z]
9. **Voice delivery tags** — Emotional markers separate from spoken text

**Should have (competitive differentiators):**
1. **Subtext layer** — What the visual implies vs what it shows (emotional context for AI)
2. **Mise-en-scene integration** — Environment reflects character psychology
3. **Temporal choreography (video)** — "0.0s-1.0s: braced fear → 1.0s-2.0s: confused hope → 2.0s-3.0s: disbelief"
4. **Camera movement psychology** — Why camera moves, not just how ("dolly in creating intimacy")
5. **Catchlight specification** — Eye reflections for "alive" look
6. **Breath and micro-movements** — Chest rise, finger twitch, swallow
7. **Continuity anchors** — Wardrobe details, prop positions, injury progression across shots
8. **Color grading intent** — Specific LUT references or teal-and-orange ratios
9. **Anamorphic/lens character** — Oval bokeh, lens flare quality
10. **Spatial relationship power dynamics** — Character positioning reveals relationships

**Defer (v2+):**
- Multi-character spatial choreography (complex blocking)
- Advanced physics descriptions for video
- Complex camera rig simulations (Steadicam, gimbal specifics)
- Film stock emulation references
- Professional colorist terminology

**Anti-features (avoid completely):**
- Vague adjectives ("beautiful," "dramatic," "nice") — AI can't act on these
- Emotional labels without physical manifestation ("she looks sad")
- Conflicting instructions ("harsh sunlight" + "soft dreamy look")
- Static video descriptions (describing video like still images)
- Overloading single prompt with 10+ elements
- Conversational filler ("Please create an image of...")

### Architecture Approach

The Hollywood-quality prompt pipeline integrates as a **preprocessing layer** that transforms 50-80 word prompts into 600-1000 word Hollywood-quality prompts before reaching the image generation service. This slots cleanly into the existing well-structured service architecture without disrupting current flows.

**Current flow (50-80 words):**
```
VideoWizard::buildShotPrompt() → ImageGenerationService::buildImagePrompt() →
StructuredPromptBuilder::build() → API Call (Gemini/HiDream)
```

**Proposed flow (600-1000 words):**
```
VideoWizard::buildShotPrompt() →
HollywoodPromptExpanderService::expand() [NEW] →
ImageGenerationService::generateSceneImage() → API Call
```

**Major components:**
1. **HollywoodPromptExpanderService (new)** — Central orchestrator; expand() method targets 600-1000 words with caching support
2. **PromptTemplateLibrary (new)** — Template management by shot type (close-up, wide, establishing) with word budget allocation per section
3. **ContextInjectorService (new)** — Pulls Bible data (characters, location, style) and injects into prompt sections
4. **PromptValidationService (new)** — Word count validation, section presence checking, contradiction detection
5. **Model-specific adapters** — Format prompts for target model (CLIP compression for HiDream, paragraph style for Gemini, concise for Runway)

**Key integration points:**
- VideoWizard.php line ~21988: Add optional expansion flag, call expander service
- ImageGenerationService.php line ~218: Accept pre-expanded prompts, skip redundant processing
- Collage generation line ~26244: Use cached expanded prompts for rapid multi-shot generation

**Anti-patterns avoided:**
- Prompt bloat without purpose (adding words just to hit count)
- Breaking Bible integration (expansion must preserve character/location/style anchors)
- Synchronous expansion in collage loop (pre-expand in parallel before generation)
- Hardcoded shot type logic (use template-driven configuration)

### Critical Pitfalls

**1. Token Truncation (CRITICAL — Phase 1)**
CLIP-based models (HiDream, Stable Diffusion) have a hard 77-token limit. Expanded 800-word prompts get silently truncated; only first 75 tokens influence image. Critical visual details at prompt end are ignored.

**Prevention:** Build model-specific formatters from Phase 1. Front-load critical information (subject + action first). Use CLIP tokenizer to count actual tokens. For CLIP models, compress to essentials; for Gemini, use full expansion.

**2. Prompt Bloat (CRITICAL — Phase 2)**
Overly complex prompts with contradictory instructions confuse models. Runway reports users complain models "ignore prompt instructions" when prompts have multiple scene changes or conflicting style descriptors.

**Prevention:** Follow "single scene" rule (one action, one style per prompt). Limit style descriptors to 3-5 key elements. Avoid negative phrasing (Runway Gen-4 doesn't support it). Implement complexity scoring with "simplify" recommendations when score > 0.7.

**3. Shot-to-Shot Inconsistency (CRITICAL — Phase 2)**
Characters look different across shots ("identity drift") when each prompt is generated independently without shared visual anchors. Destroys narrative coherence.

**Prevention:** Use reference images from first shot for subsequent shots. Create and maintain style anchors (color grading, lighting style, film look). Store canonical character descriptions in Story Bible, reference by ID. Build visual continuity pipeline: Scene 1 extracts anchors → Scene 2+ injects anchors + uses reference image.

**4. TTS Emotion Mismatch (MODERATE — Phase 3)**
TTS models speak stage directions aloud ("She said angrily, Hello") or ignore emotional context, sounding robotic.

**Prevention:** Separate clean text from emotional direction. Use model-appropriate tags (ElevenLabs `<excited>`, Hume natural language). Match text sentiment to desired emotion. Plan for post-production removal if model speaks tags.

**5. Ignoring Performance/Caching (MODERATE — Phase 2)**
Every shot generates fresh expanded prompt via LLM (500ms-2s latency per shot). 20-shot scene takes 10-40 seconds just for prompts.

**Prevention:** Cache expanded prompts by semantic similarity. Batch similar prompts in single API call. Use rule-based expansion for simple shots (5ms vs 800ms). Leverage LLM prompt caching for static prefixes.

## Implications for Roadmap

Based on combined research, the recommended phase structure addresses dependencies, avoids critical pitfalls, and delivers incremental value:

### Phase 1: Foundation & Model Adapters
**Rationale:** Must establish model-aware formatters BEFORE any expansion work to avoid token truncation pitfall. This phase sets architectural patterns that all subsequent phases depend on.

**Delivers:**
- Model-specific prompt formatters (CLIP compression, Gemini paragraph, Runway concise)
- Token counting and validation infrastructure
- Basic prompt template structure
- Rule-based expansion fallback

**Addresses features:**
- Camera specs vocabulary (table stakes)
- Lighting terminology (table stakes)
- Quantified framing (table stakes)

**Avoids pitfall:**
- #1 Token Truncation — formatters prevent silent truncation
- #7 Model Mismatch — adapter pattern handles cross-model differences

**Research flag:** Standard patterns from research. Skip `/gsd:research-phase`.

---

### Phase 2: Template-Driven Expansion + Bible Integration
**Rationale:** With formatters in place, build the core expansion engine. Must include Bible integration and continuity anchors to avoid consistency pitfall. Caching prevents performance issues.

**Delivers:**
- HollywoodPromptExpanderService with expand() method
- PromptTemplateLibrary organized by shot type
- ContextInjectorService pulling Bible data
- Style anchor extraction and injection system
- Prompt caching layer
- Complexity scoring and validation

**Addresses features:**
- Micro-expression details (table stakes)
- Body language specifics (table stakes)
- Continuity anchors (differentiator)
- Subtext layer (differentiator)
- Mise-en-scene integration (differentiator)

**Avoids pitfalls:**
- #2 Prompt Bloat — complexity scoring limits over-detail
- #3 Shot Consistency — style anchors + Bible integration prevent drift
- #6 Performance — caching reduces repeated AI calls
- #8 Subject Placement — model-aware ordering ensures subject priority

**Research flag:** Needs research for emotion vocabulary expansion (FACS library). Consider `/gsd:research-phase` for "Micro-expression template library."

---

### Phase 3: Video Temporal Expansion
**Rationale:** With image prompt expansion working, extend to video-specific features (temporal progression). Depends on Phase 2 architecture.

**Delivers:**
- Temporal beat structure templates
- Video-specific prompt expansion (motion descriptions)
- Camera movement psychology integration
- Temporal choreography system ([0.0-1.0s: X] format)

**Addresses features:**
- Temporal structure (table stakes)
- Temporal choreography (differentiator)
- Camera movement psychology (differentiator)
- Breath and micro-movements (differentiator)

**Avoids pitfall:**
- #2 Prompt Bloat — single scene rule enforcement for video

**Research flag:** Standard patterns (Runway Gen-4 docs comprehensive). Skip `/gsd:research-phase`.

---

### Phase 4: Voice/TTS Prompt Enhancement
**Rationale:** Separate from image/video pipeline. Can be developed in parallel with Phase 3 or after.

**Delivers:**
- Voice-specific prompt structure (150-300 words, not 600-1000)
- Emotional delivery tag system
- Clean text separation from direction cues
- Model-specific TTS adapters (ElevenLabs, Hume)

**Addresses features:**
- Voice delivery tags (table stakes)

**Avoids pitfall:**
- #4 TTS Emotion Mismatch — separate clean text from direction

**Research flag:** Needs research for emotion tag mappings per TTS model. Consider `/gsd:research-phase` for "TTS model emotion API research."

---

### Phase 5: AI-Powered Expansion (Intelligence Layer)
**Rationale:** With template system proven, add LLM enhancement for complex/emotional shots. Optional — templates may be sufficient.

**Delivers:**
- Grok/GPT-4o expansion via existing AI facade
- Hollywood expansion system prompts
- Few-shot examples for expansion quality
- AI fallback to rule-based when API fails

**Addresses features:**
- All differentiators benefit from AI intelligence
- Subtext layer especially (requires inference)

**Research flag:** Standard patterns (IBM/Palantir prompt engineering guides). Skip `/gsd:research-phase`.

---

### Phase 6: UI/UX & Polish
**Rationale:** After core functionality proven, add user-facing features.

**Delivers:**
- Prompt preview before generation
- Expanded vs compact prompt comparison
- Settings UI for expansion toggle
- Prompt inspector with metrics
- Manual prompt editing capability

**Avoids pitfall:**
- #12 Testing Only at Generation — preview catches issues early

**Research flag:** Standard UI patterns. Skip `/gsd:research-phase`.

---

### Phase Ordering Rationale

1. **Foundation first (Phase 1)** — Model adapters are foundational; token truncation pitfall is fatal and affects all subsequent work. Without formatters, expansion effort is wasted.

2. **Template + Bible integration together (Phase 2)** — Consistency pitfall requires Bible integration from the start. Caching prevents performance issues that would require later refactoring.

3. **Video after image (Phase 3)** — Video expansion builds on image prompt architecture. Temporal beats are additive to existing structure.

4. **Voice parallel or after video (Phase 4)** — Independent pipeline, can be developed separately. Different word count targets (150-300 vs 600-1000).

5. **AI enhancement later (Phase 5)** — Templates must work first to establish baseline. AI adds intelligence but isn't required for MVP. Allows A/B testing AI vs templates.

6. **UI last (Phase 6)** — Core functionality must work before polishing UX. Preview requires working expansion system.

### Research Flags

**Phases needing deeper research during planning:**
- **Phase 2 (Micro-expression library)** — FACS vocabulary database needs detailed emotion-to-AU mappings. Research: Paul Ekman FACS resources.
- **Phase 4 (TTS emotion APIs)** — Each TTS model (ElevenLabs, Hume, generic) has different emotion APIs. Research: Model-specific documentation for tag formats.

**Phases with standard patterns (skip research-phase):**
- **Phase 1 (Model adapters)** — Adapter pattern well-documented, token limits known from research.
- **Phase 3 (Video temporal)** — Runway Gen-4 documentation comprehensive, beat structure pattern clear.
- **Phase 5 (AI expansion)** — Prompt engineering guides (IBM, Palantir) provide clear patterns.
- **Phase 6 (UI/UX)** — Standard Livewire UI patterns, no domain research needed.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | **HIGH** | Based on direct codebase analysis; all required services exist or are simple PHP classes |
| Features | **HIGH** | Verified with professional cinematography sources (StudioBinder, MasterClass), FACS official docs, AI platform guides |
| Architecture | **HIGH** | Existing codebase provides clear integration points; preprocessing layer pattern is clean |
| Pitfalls | **HIGH** | Token limits verified with HuggingFace discussions; Runway best practices from official docs; performance patterns from OpenAI docs |

**Overall confidence:** HIGH

### Gaps to Address

**Emotion vocabulary depth:** Research identified FACS as the standard for micro-expressions but didn't build complete AU-to-emotion mapping database. **Handle during Phase 2 planning** — allocate task for FACS library creation with Paul Ekman resources.

**Model-specific performance characteristics:** Research provides general optimization strategies but doesn't include actual latency measurements per model (HiDream vs NanoBanana vs Runway). **Handle during Phase 1 implementation** — benchmark each model to tune caching strategy.

**TTS model emotion tag formats:** Research confirmed tag separation is critical but didn't document exact API formats for each TTS provider. **Handle during Phase 4 planning** — deep-dive research on ElevenLabs vs Hume vs generic TTS emotion APIs.

**Continuity anchor storage format:** Research recommends style anchors but doesn't specify storage schema (database, cache, session). **Handle during Phase 2 planning** — design storage pattern based on scene duration and collage requirements.

## Sources

### Primary (HIGH confidence)
- **Existing codebase** (direct analysis):
  - `modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php`
  - `modules/AppVideoWizard/app/Services/VideoPromptBuilderService.php`
  - `modules/AppVideoWizard/app/Services/PromptExpanderService.php`
  - `modules/AppVideoWizard/app/Services/ImageGenerationService.php`
  - `modules/AppVideoWizard/app/Livewire/VideoWizard.php`

- **Model documentation** (official sources):
  - [Runway Gen-4 Prompting Guide](https://help.runwayml.com/hc/en-us/articles/39789879462419-Gen-4-Video-Prompting-Guide)
  - [CLIP Token Limits](https://github.com/huggingface/diffusers/issues/2136)
  - [OpenAI Prompt Caching](https://platform.openai.com/docs/guides/prompt-caching)
  - [ElevenLabs TTS Best Practices](https://elevenlabs.io/docs/overview/capabilities/text-to-speech/best-practices)

### Secondary (MEDIUM confidence)
- **Cinematography education** (professional standards):
  - [StudioBinder Camera Shots Guide](https://www.studiobinder.com/blog/ultimate-guide-to-camera-shots/)
  - [Paul Ekman FACS](https://www.paulekman.com/facial-action-coding-system/)
  - [No Film School Lighting Techniques](https://nofilmschool.com/lighting-techniques-in-film)
  - [MasterClass Camera Moves](https://www.masterclass.com/articles/guide-to-camera-moves)

- **AI prompt engineering** (2026 industry standards):
  - [OpenAI Sora 2 Prompting Guide](https://cookbook.openai.com/examples/sora/sora2_prompting_guide)
  - [fal.ai Kling 2.6 Pro Guide](https://fal.ai/learn/devs/kling-2-6-pro-prompt-guide)
  - [IBM Prompt Engineering Guide](https://www.ibm.com/think/prompt-engineering)
  - [Palantir Best Practices](https://www.palantir.com/docs/foundry/aip/best-practices-prompt-engineering)

### Tertiary (context validation)
- [Google Veo 3.1 Character Consistency](https://www.financialcontent.com/article/tokenring-2026-1-21-google-launches-veo-31-a-paradigm-shift-in-cinematic-ai-video-and-character-consistency) — emerging 2026 techniques
- [Multi-Shot Character Consistency Research](https://arxiv.org/html/2412.07750v1) — academic validation
- [Hume Octave TTS Prompting](https://www.hume.ai/blog/octave-tts-prompting-guide) — TTS emotion handling

---

*Research completed: 2026-01-25*
*Ready for roadmap: YES*
*Recommended next step: Create roadmap using phase structure above*
