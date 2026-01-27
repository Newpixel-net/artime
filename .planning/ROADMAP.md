# Video Wizard Development Roadmap

## Milestone 11.1: Voice Production Excellence

**Target:** Complete voice production pipeline with registry, continuity validation, and multi-speaker support
**Status:** In Progress (2026-01-27)
**Total requirements:** 6 (P0-P1 priorities from Phase 28 context)
**Phases:** 28

---

## Overview

Voice Production Excellence completes the Hollywood-Quality Prompt Pipeline by adding voice consistency infrastructure. Phase 25 created VoicePromptBuilderService with emotional direction and pacing, but the service is currently orphaned (not integrated into UI). This milestone adds Voice Registry for character-voice persistence, continuity validation across scenes, and multi-speaker dialogue support.

---

## Phase Overview

| Phase | Name | Goal | Requirements | Success Criteria |
|-------|------|------|--------------|------------------|
| 28 | Voice Production Excellence | Users get consistent character voices across scenes with multi-speaker dialogue support | VOC-07, VOC-08, VOC-09, VOC-10, VOC-11, VOC-12 | 4 |

**Total:** 1 phase | 6 requirements | 4 success criteria

---

## Phase 28: Voice Production Excellence

**Goal:** Users get consistent character voices across scenes with multi-speaker dialogue support

**Status:** Not started

**Plans:** TBD (run /gsd:plan-phase 28 to break down)

**Dependencies:** Phase 25 (VoicePromptBuilderService must exist)

**Requirements:**
- VOC-07: Voice Registry persists character-voice mappings across scenes
- VOC-08: Voice Continuity Validation ensures settings match across scenes
- VOC-09: Enhanced SSML Markup with full emotional direction support
- VOC-10: Multi-Speaker Dialogue handles conversations in single generation
- VOC-11: VoicePromptBuilderService integration into wizard UI
- VOC-12: Voice selection UI in Character Bible modal

**Success Criteria** (what must be TRUE):
1. Character voice selections persist across all scenes — same character always uses same voice without manual re-selection
2. Voice continuity warnings appear when settings drift — user notified if voice parameters change unexpectedly
3. Multi-speaker dialogue generates without manual splitting — conversations with 2+ characters produce unified audio
4. Voice prompts from Phase 25 flow through to generation — emotional direction tags appear in TTS requests

---

## Progress Tracking

| Phase | Status | Requirements | Success Criteria |
|-------|--------|--------------|------------------|
| Phase 28: Voice Production Excellence | Not started | VOC-07 through VOC-12 (6) | 0/4 |

**Overall Progress:**

```
Phase 28: ░░░░░░░░░░ 0%
─────────────────────
M11.1:    ░░░░░░░░░░ 0% (0/6 requirements)
```

**Coverage:** 6/6 requirements mapped (100%)

---

## Dependencies

```
Phase 25 (Voice Prompt Enhancement) [v11 - SHIPPED]
    |
    v
Phase 28 (Voice Production Excellence)
```

Phase 28 builds on Phase 25's VoicePromptBuilderService.

---

*Milestone 11.1 roadmap created: 2026-01-27*
*Phase 28 defined from context document*
*Source: .planning/phases/28-voice-production-excellence/28-CONTEXT.md*
