# Milestone v11: Hollywood-Quality Prompt Pipeline

**Status:** SHIPPED 2026-01-27
**Phases:** 22-27
**Total Plans:** 21

## Overview

Hollywood-Quality Prompt Pipeline transforms the Video Wizard's AI prompts from basic descriptions into professional cinematography-level detail. Current prompts are 50-80 words; target is 600-1000 words with micro-expressions, FACS terminology, camera psychology, temporal beats, and emotional direction. The system builds template-driven expansion with Story Bible integration, model-specific adapters (CLIP 77-token limits, Gemini paragraphs, Runway concise), and optional LLM-powered expansion for complex shots. No new libraries needed — existing Laravel/Livewire architecture with enhanced prompt engineering.

## Phases

### Phase 22: Foundation & Model Adapters

**Goal:** Users get model-appropriate prompts with proper token limits and professional camera/lighting vocabulary
**Depends on:** None (starts new milestone)
**Plans:** 3 plans

Plans:
- [x] 22-01: CinematographyVocabulary and PromptTemplateLibrary (Wave 1)
- [x] 22-02: ModelPromptAdapterService with CLIP tokenization (Wave 2)
- [x] 22-03: Integration into ImageGenerationService (Wave 3)

**Requirements:** INF-01, INF-03, IMG-01, IMG-02, IMG-03

**Success Criteria:**
1. Generated image prompts respect model token limits
2. Template library returns different prompt structures based on shot type
3. Camera specifications appear in prompts with psychological reasoning
4. Framing descriptions include quantified positions
5. Lighting descriptions include specific values

---

### Phase 23: Character Psychology & Bible Integration

**Goal:** Users see prompts that capture nuanced human behavior and maintain Story Bible consistency
**Depends on:** Phase 22
**Plans:** 4 plans

Plans:
- [x] 23-01: CharacterPsychologyService with emotion-to-physical mappings (Wave 1)
- [x] 23-02: MiseEnSceneService for environment-emotion integration (Wave 1)
- [x] 23-03: ContinuityAnchorService and CharacterLookService expression presets (Wave 2)
- [x] 23-04: StructuredPromptBuilderService integration (Wave 3)

**Requirements:** INF-02, IMG-04, IMG-05, IMG-06, IMG-07, IMG-08, IMG-09

**Success Criteria:**
1. Generated prompts include physical manifestations (research: FACS AU codes don't work)
2. Character Bible data appears in prompts
3. Emotional states expressed through physical manifestations
4. Continuity anchors persist across related shots

---

### Phase 24: Video Temporal Expansion

**Goal:** Users see video prompts that choreograph motion, timing, and multi-character dynamics
**Depends on:** Phase 23
**Plans:** 4 plans

Plans:
- [x] 24-01: VideoTemporalService and MicroMovementService (Wave 1)
- [x] 24-02: CharacterDynamicsService and CharacterPathService (Wave 1)
- [x] 24-03: TransitionVocabulary and CameraMovementService temporal extension (Wave 1)
- [x] 24-04: VideoPromptBuilderService integration (Wave 2)

**Requirements:** VID-01, VID-02, VID-03, VID-04, VID-05, VID-06, VID-07

**Success Criteria:**
1. Video prompts contain all image prompt elements plus temporal structure
2. Temporal beats appear with specific timing
3. Camera movement includes duration and psychological framing
4. Multi-character shots describe spatial power dynamics

---

### Phase 25: Voice Prompt Enhancement

**Goal:** Users see voice prompts that direct emotional performance with specific delivery cues
**Depends on:** Phase 22 (template infrastructure)
**Plans:** 3 plans

Plans:
- [x] 25-01: VoiceDirectionVocabulary (Wave 1)
- [x] 25-02: VoicePacingService (Wave 1)
- [x] 25-03: VoicePromptBuilderService integration (Wave 2)

**Requirements:** VOC-01, VOC-02, VOC-03, VOC-04, VOC-05, VOC-06

**Success Criteria:**
1. Voice prompts include bracketed direction tags
2. Pacing markers appear with specific timing
3. Emotional arc direction spans dialogue sequences

---

### Phase 26: LLM-Powered Expansion

**Goal:** Users get AI-enhanced prompts for complex shots that exceed template capability
**Depends on:** Phases 22-25
**Plans:** 4 plans (including gap closure)

Plans:
- [x] 26-01: ComplexityDetectorService (Wave 1)
- [x] 26-02: LLMExpansionService (Wave 2)
- [x] 26-03: StructuredPromptBuilderService integration (Wave 3)
- [x] 26-04: Gap closure - build() delegation (Wave 4)

**Requirements:** INF-04

**Success Criteria:**
1. Complex shots trigger LLM expansion
2. LLM-expanded prompts maintain template structure and vocabulary

---

### Phase 27: UI & Performance Polish

**Goal:** Users can preview, compare, and efficiently use expanded prompts
**Depends on:** Phases 22-26
**Plans:** 3 plans

Plans:
- [x] 27-01: Prompt caching layer and expansion toggle setting (Wave 1)
- [x] 27-02: Prompt comparison UI component for storyboard (Wave 2)
- [x] 27-03: Hollywood expansion toggle in settings sidebar (Wave 2)

**Requirements:** INF-05, INF-06

**Success Criteria:**
1. Identical contexts return cached prompts
2. UI shows before/after prompt comparison
3. Prompt expansion toggle available in settings

---

## Milestone Summary

**Decimal Phases:** None

**Key Decisions:**

- Physical manifestations over FACS AU codes (research showed AU codes don't work for image models)
- Lens psychology includes reasoning, not just specs
- Word budgets sum to exactly 100% for predictable allocation
- BPE tokenizer with word-estimate fallback
- Subject NEVER removed during compression
- Grok as primary LLM (cost-effective at $0.20/1M input)
- Meta-prompting with vocabulary constraints over few-shot examples
- 3+ characters ALWAYS triggers complexity

**Issues Resolved:**

- CLIP 77-token limit handled with intelligent compression
- Model-specific prompt adaptation (CLIP compressed, Gemini full)
- Complex shot detection with multi-dimensional scoring
- Backward compatibility via build() delegation to buildHollywoodPrompt()

**Issues Deferred:**

- Phase 28 (Voice Production Excellence) defined but not planned

**Technical Debt Incurred:**

- VoicePromptBuilderService orphaned (awaiting UI integration)

---

*For current project status, see .planning/ROADMAP.md*
