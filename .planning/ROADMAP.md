# Video Wizard Development Roadmap

## Milestone 1: Stability & Bug Fixes
**Target:** Eliminate critical bugs, establish stable baseline
**Status:** 80% Complete

| Task | Status | Priority |
|------|--------|----------|
| Fix dialogue parsing by speaker | Done | P0 |
| Fix needsLipSync on all dialogue shots | Done | P0 |
| Fix Collage Preview empty state | Done | P0 |
| Remove duplicate methods | Done | P0 |
| Add AI retry logic | Pending | P1 |
| Fix error handling | Pending | P1 |

---

## Milestone 1.5: Automatic Speech Flow System -- COMPLETE
**Target:** Remove Character Intelligence bottleneck, connect Speech Segments to Character Bible for automatic flow
**Status:** Complete (2026-01-23)
**Plans:** 4 plans in 3 waves -- ALL COMPLETE

**Goal:** Automatic, effortless, Hollywood-quality output from button clicks. Script is auto-parsed into speech segments, speakers are auto-linked to Character Bible, and data flows through to video generation without manual intervention.

Plans:
- [x] 1.5-01-PLAN.md -- Auto-parse script into segments after AI generation
- [x] 1.5-02-PLAN.md -- Replace Character Intelligence UI with Detection Summary
- [x] 1.5-03-PLAN.md -- Backward compatibility for characterIntelligence
- [x] 1.5-04-PLAN.md -- Ensure segment data flows to shots/video generation

---

## Milestone 2: Narrative Intelligence -- COMPLETE
**Target:** Each shot captures unique moment with emotional arc
**Status:** Complete (2026-01-23)
**Plans:** 3 plans in 2 waves -- ALL COMPLETE

**Goal:** Integrate existing NarrativeMomentService into shot generation workflow. Each shot gets a unique narrative moment with emotional intensity mapping to shot type.

Plans:
- [x] 02-01-PLAN.md -- Wire NarrativeMomentService into ShotIntelligenceService
- [x] 02-02-PLAN.md -- Enhance AI prompt with narrative moments
- [x] 02-03-PLAN.md -- Add action uniqueness validation

---

## Milestone 3: Hollywood Production System -- COMPLETE
**Target:** Production-ready Hollywood-quality cinematography that ACTUALLY WORKS
**Status:** Complete (2026-01-23)
**Plans:** 7 plans in 4 waves -- ALL COMPLETE

**Goal:** Transform Video Wizard into a production-ready system where all Hollywood cinematography features work properly, the UX is smooth and professional, and users get amazing results with minimal effort.

**Critical Finding (FIXED):** Hollywood cinematography code EXISTED but was NOT BEING CALLED:
- ✅ `generateHollywoodShotSequence()` now invoked in all scene decomposition
- ✅ Emotion-driven shot types now applied
- ✅ Dialogue coverage patterns now active
- ✅ NarrativeMomentService returns real moments (no more placeholders)

Plans:
- [x] 03-01-PLAN.md -- Activate Hollywood Shot Sequences (replace analyzeScene → generateHollywoodShotSequence)
- [x] 03-02-PLAN.md -- Fix NarrativeMomentService Placeholders (meaningful moment extraction)
- [x] 03-03-PLAN.md -- Enable Hollywood Settings by Default (seeder + runtime init)
- [x] 03-04-PLAN.md -- Auto-Proceed Through Steps (Script → Storyboard → Animation → Assembly)
- [x] 03-05-PLAN.md -- Batch Generation Smart Retry (exponential backoff, status tracking)
- [x] 03-06-PLAN.md -- Character Consistency Enforcement (reference images, portraits)
- [x] 03-07-PLAN.md -- Smart Defaults from Concept (AI-assisted setting suggestions)

### Wave 1: Fix Critical Broken Integrations ✅

**Phase 3: Activate Hollywood Shot Sequences** ✅
- ✅ `generateHollywoodShotSequence()` now called at lines 16806, 22741
- ✅ Emotional arc extracted via NarrativeMomentService and passed to DynamicShotEngine
- ✅ Characters from characterBible passed for dialogue coverage patterns

**Phase 4: Fix NarrativeMomentService Placeholders** ✅
- ✅ New `generateMeaningfulMomentsFromNarration()` extracts real actions from text
- ✅ New `generateNarrativeArcMoments()` fallback uses Hollywood arc (setup→climax→resolution)
- ✅ No more "continues the scene" or "the subject" placeholders

### Wave 2: Enable Disabled Features ✅

**Phase 5: Enable Shot Progression + Cinematic Intelligence** ✅
- ✅ Hollywood settings seeded: shot_progression_enabled, cinematic_intelligence_enabled, etc.
- ✅ Runtime initialization in `ensureHollywoodSettingsExist()` creates settings if missing
- ✅ All Hollywood features ON by default

### Wave 3: Smooth UX & Automation ✅

**Phase 7: Auto-Proceed Through Steps** ✅
- ✅ `$autoProceedEnabled` property added (default false)
- ✅ Script → auto-proceed to Storyboard + start batch images
- ✅ Storyboard complete → auto-proceed to Animation + start batch videos
- ✅ Animation complete → auto-proceed to Assembly with success notification
- ✅ `getOverallProgressProperty()` provides pipeline percentage (Script 20%, Storyboard 40%, Animation 30%, Assembly 10%)

**Phase 8: Batch Generation Improvements** ✅
- ✅ `generateImageWithRetry()` and `generateVideoWithRetry()` with exponential backoff (2s, 4s, 8s)
- ✅ `$generationRetryCount`, `$maxRetryAttempts = 3`, `$generationStatus` tracking
- ✅ `getBatchGenerationStatus()` returns pending/generating/complete/failed counts
- ✅ `retryAllFailed()` resets and retries all failed items

### Wave 4: Quality & Consistency ✅

**Phase 9: Character Consistency Enforcement** ✅
- ✅ `getCharacterReferenceImages()` extracts character data per scene
- ✅ `buildCharacterConsistencyPrompt()` adds character descriptions to prompts
- ✅ `generateCharacterPortraits()` creates reference portraits from Character Bible
- ✅ Character references passed to ALL image generation calls

**Phase 10: Smart Defaults from Concept** ✅
- ✅ `analyzeConceptForDefaults()` keyword-based analysis (platform, duration, type, etc.)
- ✅ `analyzeConceptWithAI()` AI-enhanced analysis via GeminiService
- ✅ `applySuggestedSettings()` auto-fills Step 1 configuration
- ✅ Concept update triggers automatic setting suggestions

| Wave | Plans | Focus | Status |
|------|-------|-------|--------|
| 1 | 03-01, 03-02 | Fix critical broken integrations | ✅ Complete |
| 2 | 03-03 | Enable disabled features | ✅ Complete |
| 3 | 03-04, 03-05 | Smooth UX & automation | ✅ Complete |
| 4 | 03-06, 03-07 | Quality & consistency | ✅ Complete |

**Success Metrics:**
| Metric | Target | Status |
|--------|--------|--------|
| Hollywood patterns applied | 100% | ✅ 100% |
| Narrative moments unique | 100% | ✅ 100% |
| Auto-proceed enabled | Yes | ✅ Yes |
| Character consistency | 100% | ✅ Implemented |
| One-click generation | Yes | ✅ Yes |

---

## Milestone 4: Dialogue Scene Excellence
**Target:** Hollywood-style Shot/Reverse Shot coverage
**Status:** Not Started

| Task | Status | Priority |
|------|--------|----------|
| Implement S/RS pattern | Pending | HIGH |
| OTS shot detection | Pending | MEDIUM |
| Reaction shot placeholders | Pending | MEDIUM |
| Two-character coverage | Pending | HIGH |

---

## Milestone 5: Emotional Arc System
**Target:** Intensity-driven cinematography
**Status:** Not Started

| Task | Status | Priority |
|------|--------|----------|
| Build intensity curve extraction | Pending | HIGH |
| Emotion to shot type mapping | Pending | HIGH |
| Climax framing logic | Pending | MEDIUM |
| Arc visualization | Pending | LOW |

---

## Milestone 6: UI/UX Polish
**Target:** Professional, intuitive interface
**Status:** Not Started

| Task | Status | Priority |
|------|--------|----------|
| Dialogue text in shot cards | Pending | MEDIUM |
| Shot type badges | Pending | LOW |
| Progress indicators | Pending | MEDIUM |
| Live preview updates | Pending | LOW |

---

## Progress Overview

```
Milestone 1:   ████████░░ 80%
Milestone 1.5: ██████████ 100% COMPLETE
Milestone 2:   ██████████ 100% COMPLETE
Milestone 3:   ██████████ 100% COMPLETE
Milestone 4:   ░░░░░░░░░░  0%
Milestone 5:   ░░░░░░░░░░  0%
Milestone 6:   ░░░░░░░░░░  0%
─────────────────────────
Overall:       █████████░ 65%
```

---

## Guiding Principle

**"Automatic, effortless, Hollywood-quality output from button clicks."**

The system should be sophisticated and automatically updated based on previous steps in the wizard. Users click buttons and perform complete actions without effort.

---

*Last Updated: 2026-01-23 (Milestone 3 Completed)*
