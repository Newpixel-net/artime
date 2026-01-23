# Video Wizard - Current State

> Last Updated: 2026-01-23
> Session: Phase 3 - Hollywood Production System (Continued)

---

## Current Position

**Phase:** 3 of ongoing (Hollywood Production System)
**Plan:** 05 of 7 (in phase)
**Status:** In Progress

**Progress:** [######----] 71% of Phase 3 (5/7 plans complete)

---

## Current Focus

**Phase 3: Hollywood Production System** - IN PROGRESS

Enhance the production pipeline with Hollywood-standard moment extraction and shot generation.

Plans:
1. ~~Activate Hollywood Shot Sequence~~ COMPLETE
2. ~~Eliminate Placeholder Moments~~ COMPLETE
3. ~~Enable Hollywood Features by Default~~ COMPLETE
4. (skipped) Scene Type Detection
5. ~~Smart Retry Logic for Batch Generation~~ COMPLETE
6. (pending) Batch Generation Progress UI
7. (pending) Final Integration

---

## Guiding Principle

**"Automatic, effortless, Hollywood-quality output from button clicks."**

The system should be sophisticated and automatically updated based on previous steps in the wizard. Users click buttons and perform complete actions without effort.

---

## Completed This Session

### Plan 03-05: Smart Retry Logic for Batch Generation (COMPLETE)
**Summary:** Automatic retry with exponential backoff for batch image and video generation, with progress tracking

**Tasks:**
1. [x] Add retry tracking properties (generationRetryCount, maxRetryAttempts, generationStatus)
2. [x] Add smart retry method for image generation with exponential backoff
3. [x] Add smart retry method for video generation with exponential backoff
4. [x] Add batch generation status summary method

**Commits:**
- `38983d7` - feat(03-05): add smart retry logic for batch generation

**SUMMARY:** `.planning/phases/03-hollywood-production-system/03-05-SUMMARY.md`

---

## Previous Plans in Phase 3

### Plan 03-01: Activate Hollywood Shot Sequence (COMPLETE)
**Summary:** VideoWizard now calls generateHollywoodShotSequence instead of analyzeScene, activating emotion-driven shot types and dialogue coverage patterns

**Commits:**
- `0bb6542` - feat(03-01): activate Hollywood shot sequence in VideoWizard

**SUMMARY:** `.planning/phases/03-hollywood-production-system/03-01-SUMMARY.md`

### Plan 03-02: Eliminate Placeholder Moments (COMPLETE)
**Summary:** Two-tier fallback system (narration analysis + narrative arc) that NEVER returns useless "continues the scene" placeholders

**Commits:**
- `2d9508b` - feat(03-02): eliminate placeholder moments with meaningful extraction

**SUMMARY:** `.planning/phases/03-hollywood-production-system/03-02-SUMMARY.md`

### Plan 03-03: Enable Hollywood Features by Default (COMPLETE)
**Summary:** Five Hollywood production settings added to VwSettingSeeder with runtime initialization fallback

**Commits:**
- `325efa1` - feat(03-03): add Hollywood production feature settings to seeder
- `9efe55c` - feat(03-03): add runtime Hollywood settings initialization

**SUMMARY:** `.planning/phases/03-hollywood-production-system/03-03-SUMMARY.md`

---

## Previous Sessions (Complete)

### Phase 2: Narrative Intelligence - COMPLETE

All 3 plans successfully executed:
1. Wire NarrativeMomentService into ShotIntelligenceService
2. Enhance buildAnalysisPrompt with narrative moments
3. Map narrative moments to shot recommendations

See: `.planning/phases/02-narrative-intelligence/` for summaries.

### Milestone 1.5: Automatic Speech Flow System - COMPLETE

All 4 plans successfully executed:
1. Automatic Speech Segment Parsing
2. Detection Summary UI
3. characterIntelligence Backward Compatibility
4. Segment Data Flow to Shots

See: `.planning/phases/1.5-automatic-speech-flow/1.5-CONTEXT.md` for implementation decisions.

---

## Decisions Made

| Date | Area | Decision | Context |
|------|------|----------|---------|
| 2026-01-23 | Retry Pattern | Exponential backoff (2s, 4s, 8s) | Standard retry pattern for API reliability |
| 2026-01-23 | Max Retries | 3 attempts per item | Balance between recovery and failure detection |
| 2026-01-23 | Status Tracking | Item keys: scene_{i}, scene_{i}_shot_{j}, video_scene_{i} | Unique identification for mixed batch operations |
| 2026-01-23 | Settings Category | Use 'hollywood' group for new feature settings | Separate from existing 'shot_progression' and 'cinematic_intelligence' categories |
| 2026-01-23 | Runtime Initialization | Create settings on mount if missing | Ensures Hollywood features work in development environments |
| 2026-01-23 | Shot Variety | DynamicShotEngine handles variety through Hollywood patterns | Not ShotProgressionService - different approach |
| 2026-01-23 | Service Creation | Inline creation of NarrativeMomentService | Matches existing VideoWizard pattern |
| 2026-01-23 | Fallback Strategy | Two-tier fallback (narration analysis -> narrative arc) | Ensures meaningful output even when AI and rule-based extraction fail |
| 2026-01-23 | Subject Naming | Use 'the character' or 'the protagonist' instead of 'the subject' | More meaningful and consistent terminology for shot generation |
| 2026-01-23 | Arc Structure | Standard narrative arc (setup->rising->climax->falling->resolution) | Hollywood-standard story structure ensures dramatic progression |
| 2026-01-23 | Deduplication Timing | After interpolation | Interpolation may duplicate moments when expanding |
| 2026-01-23 | Verb Window Size | 2-verb sliding window | Allows same verb after 2+ gap |
| 2026-01-23 | Similarity Detection | Verb + synonym groups | More comprehensive than exact match |
| 2026-01-23 | NarrativeMomentService DI | Optional constructor param + setter | Matches existing service injection pattern |
| 2026-01-23 | Decomposition Timing | After scene type detection, before prompt building | Moments available for AI prompt |
| 2026-01-23 | Error Handling | try/catch with Log::warning | Graceful degradation if decomposition fails |
| 2026-01-23 | Speaker Matching | Use fuzzy matching (exact, partial, Levenshtein<=2) | Tolerates typos and name variations |
| 2026-01-23 | Unknown Speakers | Auto-create Character Bible entry with autoDetected flag | User can configure voice later |
| 2026-01-23 | Parse Timing | Parse after generateScript and on narration blur | Instant, invisible parsing |
| 2026-01-23 | Character Intelligence UI | Remove entirely, replace with Detection Summary | Manual config no longer needed |
| 2026-01-23 | Detection Summary Styling | Use Tailwind utility classes | Consistent with existing design |
| 2026-01-23 | Voice Status Indicators | Green=assigned, Yellow=needs voice | Clear visual feedback |
| 2026-01-23 | Migration Trigger | Trigger on both project load and component hydration | Catch all entry points |
| 2026-01-23 | Deprecation Style | Keep methods functional but log warnings in debug mode | Backward compatibility without noise |
| 2026-01-23 | Segment Inheritance | Shots inherit segments from scene via getShotSpeechSegments | Consistent data flow |
| 2026-01-23 | Diagnostic Method | verifySpeechFlow is public for debugging/admin tools | Pipeline visibility |

---

## Phase 3 Progress - What Was Built

### Plan 03-05: Smart Retry Logic (NEW)
1. **Retry Properties:** generationRetryCount, maxRetryAttempts=3, generationStatus
2. **Image Retry:** generateImageWithRetry() with exponential backoff
3. **Video Retry:** generateVideoWithRetry() with exponential backoff
4. **Status Summary:** getBatchGenerationStatus() for progress tracking
5. **Retry All:** retryAllFailed() for manual retry of failed items

### Plan 03-01: Hollywood Shot Sequence Activation
1. **VideoWizard Integration:** generateHollywoodShotSequence called instead of analyzeScene
2. **Emotional Arc Flow:** NarrativeMomentService extracts intensity values for shot type selection
3. **Character Integration:** Scene characters from characterBible passed for dialogue coverage
4. **Two Locations Fixed:** decomposeSceneWithDynamicEngine and generateCollagePreview

### Plan 03-02: Meaningful Moment Fallback
1. **Two-Tier Fallback:** generateMeaningfulMomentsFromNarration -> generateNarrativeArcMoments
2. **Action Extraction:** Priority-ordered verb extraction from ACTION_EMOTION_MAP
3. **Subject Extraction:** Character context and pronoun pattern matching
4. **Narrative Arc:** Setup->Rising->Climax->Falling->Resolution structure
5. **Intensity Calculation:** Phase-based and emotion-based intensity

### Plan 03-03: Hollywood Settings Enabled by Default
1. **VwSettingSeeder:** Added hollywood_shot_sequences_enabled, emotional_arc_shot_mapping_enabled, dialogue_coverage_patterns_enabled
2. **Runtime Initialization:** ensureHollywoodSettingsExist() creates settings if missing
3. **Verification:** ShotProgressionService connection confirmed in ShotIntelligenceService

### Key Methods Added
**03-05 (VideoWizard.php):**
- `generateImageWithRetry()` - Smart image retry with backoff
- `scheduleImageRetry()` - Schedule image retry event
- `generateVideoWithRetry()` - Smart video retry with backoff
- `scheduleVideoRetry()` - Schedule video retry event
- `getBatchGenerationStatus()` - Progress summary
- `retryAllFailed()` - Retry all failed items

**03-01 (VideoWizard.php):**
- Updated `decomposeSceneWithDynamicEngine()` for Hollywood patterns
- Updated `generateCollagePreview()` for Hollywood patterns

**03-02 (NarrativeMomentService.php):**
- `generateMeaningfulMomentsFromNarration()`
- `extractActionFromText()`
- `extractSubjectFromChunk()`
- `summarizeChunk()`
- `extractFirstActionFromNarration()`
- `generateNarrativeArcMoments()`
- `calculateArcIntensity()`
- `calculateIntensityFromEmotion()`

**03-03 (VideoWizard.php + VwSettingSeeder.php):**
- `ensureHollywoodSettingsExist()` method
- Hollywood settings category in seeder

---

## Hollywood Settings Overview

| Setting | Default | Category | Purpose |
|---------|---------|----------|---------|
| `shot_progression_enabled` | true | shot_progression | Prevents repetitive shots |
| `cinematic_intelligence_enabled` | true | cinematic_intelligence | Character state tracking |
| `hollywood_shot_sequences_enabled` | true | hollywood | Professional shot patterns |
| `emotional_arc_shot_mapping_enabled` | true | hollywood | Emotion-to-shot-type mapping |
| `dialogue_coverage_patterns_enabled` | true | hollywood | Shot/reverse shot for dialogue |

---

## Blockers

None currently

---

## Key Files

| File | Purpose | Status |
|------|---------|--------|
| `.planning/phases/03-hollywood-production-system/03-01-SUMMARY.md` | Plan 01 summary | Created |
| `.planning/phases/03-hollywood-production-system/03-02-SUMMARY.md` | Plan 02 summary | Created |
| `.planning/phases/03-hollywood-production-system/03-03-SUMMARY.md` | Plan 03 summary | Created |
| `.planning/phases/03-hollywood-production-system/03-05-SUMMARY.md` | Plan 05 summary | **Created** |
| `Livewire/VideoWizard.php` | Hollywood patterns + retry logic | **Updated** |
| `Services/NarrativeMomentService.php` | Narrative decomposition | Updated |
| `database/seeders/VwSettingSeeder.php` | Hollywood settings | Updated |

---

## Session Continuity

**Last session:** 2026-01-23
**Stopped at:** Completed 03-05-PLAN.md (Smart Retry Logic for Batch Generation)
**Resume file:** None
**Phase 3 Status:** IN PROGRESS (5/7 plans complete)

---

*Session: Phase 3 - Hollywood Production System*
*Plan 03-05 COMPLETE - Smart retry logic with exponential backoff*
