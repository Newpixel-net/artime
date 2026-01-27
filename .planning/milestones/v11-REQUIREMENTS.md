# Requirements Archive: v11 Hollywood-Quality Prompt Pipeline

**Archived:** 2026-01-27
**Status:** SHIPPED

This is the archived requirements specification for v11.
For current requirements, see `.planning/REQUIREMENTS.md` (created for next milestone).

---

## v11 Requirements

Requirements for Milestone 11. Each maps to roadmap phases.

### Image Prompts (IMG)

**Table Stakes:**
- [x] **IMG-01**: Image prompts include camera specs with psychological reasoning (lens choice affects viewer perception)
- [x] **IMG-02**: Image prompts include quantified framing (percentage of frame, compositional geometry)
- [x] **IMG-03**: Image prompts include lighting with specific ratios (key/fill/back, color temperatures in Kelvin)
- [x] **IMG-04**: Image prompts include micro-expressions using physical manifestations (research: FACS AU codes don't work for image models)
- [x] **IMG-05**: Image prompts include body language with specific posture/gesture descriptions
- [x] **IMG-06**: Image prompts include emotional state visible in physicality (not labels like "sad" but physical manifestations)

**Differentiators:**
- [x] **IMG-07**: Image prompts include subtext layer (what character hides vs reveals through body language)
- [x] **IMG-08**: Image prompts include mise-en-scene integration (environment reflects/contrasts emotional state)
- [x] **IMG-09**: Image prompts include continuity anchors (exact details that must persist across shots)

### Video Prompts (VID)

**Table Stakes:**
- [x] **VID-01**: Video prompts include all image prompt features
- [x] **VID-02**: Video prompts include temporal progression with beat-by-beat timing (0-2s: action, 2-4s: reaction)
- [x] **VID-03**: Video prompts include camera movement with duration and psychological purpose
- [x] **VID-04**: Video prompts include character movement paths within frame

**Differentiators:**
- [x] **VID-05**: Video prompts include inter-character dynamics (mirroring, spatial power relationships)
- [x] **VID-06**: Video prompts include breath and micro-movements for realism
- [x] **VID-07**: Video prompts include transition suggestions to next shot

### Voice Prompts (VOC)

**Table Stakes:**
- [x] **VOC-01**: Voice prompts include emotional direction tags (trembling, whisper, cracking)
- [x] **VOC-02**: Voice prompts include pacing markers with timing ([PAUSE 2.5s])
- [x] **VOC-03**: Voice prompts include vocal quality descriptions (gravelly, exhausted, breathless)

**Differentiators:**
- [x] **VOC-04**: Voice prompts include ambient audio cues for scene atmosphere
- [x] **VOC-05**: Voice prompts include breath and non-verbal sounds
- [x] **VOC-06**: Voice prompts include emotional arc direction across dialogue sequence

### Infrastructure (INF)

**Table Stakes:**
- [x] **INF-01**: Model adapters handle token limits (77-token CLIP limit for image models)
- [x] **INF-02**: Bible integration preserves character/location/style data in expanded prompts
- [x] **INF-03**: Template library organized by shot type (close-up needs face detail, wide needs environment)

**Differentiators:**
- [x] **INF-04**: LLM-powered expansion for complex shots that exceed template capability
- [x] **INF-05**: Prompt caching for performance (avoid re-expanding identical contexts)
- [x] **INF-06**: Prompt comparison view in UI (before/after expansion, word count)

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| INF-01 | Phase 22 | Complete |
| INF-02 | Phase 23 | Complete |
| INF-03 | Phase 22 | Complete |
| INF-04 | Phase 26 | Complete |
| INF-05 | Phase 27 | Complete |
| INF-06 | Phase 27 | Complete |
| IMG-01 | Phase 22 | Complete |
| IMG-02 | Phase 22 | Complete |
| IMG-03 | Phase 22 | Complete |
| IMG-04 | Phase 23 | Complete |
| IMG-05 | Phase 23 | Complete |
| IMG-06 | Phase 23 | Complete |
| IMG-07 | Phase 23 | Complete |
| IMG-08 | Phase 23 | Complete |
| IMG-09 | Phase 23 | Complete |
| VID-01 | Phase 24 | Complete |
| VID-02 | Phase 24 | Complete |
| VID-03 | Phase 24 | Complete |
| VID-04 | Phase 24 | Complete |
| VID-05 | Phase 24 | Complete |
| VID-06 | Phase 24 | Complete |
| VID-07 | Phase 24 | Complete |
| VOC-01 | Phase 25 | Complete |
| VOC-02 | Phase 25 | Complete |
| VOC-03 | Phase 25 | Complete |
| VOC-04 | Phase 25 | Complete |
| VOC-05 | Phase 25 | Complete |
| VOC-06 | Phase 25 | Complete |

---

## Milestone Summary

**Shipped:** 25 of 25 v11 requirements
**Adjusted:** None
**Dropped:** None

---
*Archived: 2026-01-27 as part of v11 milestone completion*
