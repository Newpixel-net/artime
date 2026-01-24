# Video Wizard

## What This Is

AI-powered video creation platform built with Laravel and Livewire. Users input a concept and the system automatically generates scripts, storyboards, images, and videos with Hollywood-quality cinematography. The wizard guides users through 7 steps: Concept → Characters → Script → Storyboard → Animation → Audio → Export.

## Core Value

**"Automatic, effortless, Hollywood-quality output from button clicks."**

The system should be sophisticated and automatically updated based on previous steps in the wizard. Users click buttons and perform complete actions without effort.

## Current Milestone: v9 Voice Production Excellence

**Goal:** Professional-grade voice continuity and TTS production pipeline aligned with modern industry standards (Dia, VibeVoice, Gemini 2.5 TTS).

**Target features:**
- Narrator Voice Assignment — Narrator voiceId flows to shots for TTS generation
- Segment Validation — Empty/invalid segments caught before reaching TTS
- Unified Distribution — Narrator and internal thoughts use consistent word-split distribution
- Voice Continuity — Same character maintains same voice across all scenes
- Voice Registry — Centralized source of truth for character voice assignments
- Multi-Speaker Support — Track multiple speakers per shot for complex dialogue

## Requirements

### Validated

<!-- Shipped and confirmed valuable. -->

- ✓ **M1**: Stability & Bug Fixes — dialogue parsing, needsLipSync, error handling
- ✓ **M1.5**: Automatic Speech Flow — auto-parse scripts, Detection Summary UI, segment data flow
- ✓ **M2**: Narrative Intelligence — NarrativeMomentService integration, unique moments per shot
- ✓ **M3**: Hollywood Production System — Hollywood shot sequences, auto-proceed, smart retry, character consistency
- ✓ **M4**: Dialogue Scene Excellence — 180-degree rule, OTS depth, reaction shots, coverage validation
- ✓ **M5**: Emotional Arc System — climax detection, intensity smoothing, arc templates
- ✓ **M6**: UI/UX Polish — dialogue display, shot badges, progress indicators, visual consistency
- ✓ **M7**: Scene Text Inspector — full transparency modal, speech segments, prompts, copy-to-clipboard
- ✓ **M8**: Cinematic Shot Architecture — speech-driven shots, shot/reverse-shot, dynamic camera, action scenes

### Active

<!-- Current scope. Building toward these. -->

- [ ] **VOC-01**: Narrator voice assigned — overlayNarratorSegments() sets narratorVoiceId on shots
- [ ] **VOC-02**: Empty text validation — empty/invalid segments caught before TTS generation
- [ ] **VOC-03**: Unified distribution — narrator and internal thoughts use same word-split approach
- [ ] **VOC-04**: Voice continuity validation — same character keeps same voice across scenes
- [ ] **VOC-05**: Voice Registry centralization — single source of truth for character voices
- [ ] **VOC-06**: Multi-speaker shot support — multiple speakers tracked per shot for dialogue

### Out of Scope

<!-- Explicit boundaries. Includes reasoning to prevent re-adding. -->

- Real-time collaboration — complexity, not core to video creation
- Mobile app — web-first approach
- Video editing timeline — use external tools for post-production
- Multi-character in single shot — model limitation, embrace as creative constraint

## Context

**Technical environment:**
- Laravel 10 + Livewire 3
- Main component: VideoWizard.php (~18k lines)
- Services: SpeechSegmentParser, SpeechSegment, NarrativeMomentService, ShotIntelligenceService
- Image generation: HiDream, NanoBanana Pro, NanoBanana
- Video generation: Runway, Multitalk (single character lip-sync)

**M8 Foundation (complete):**
- DialogueSceneDecomposerService — speech-driven shots, shot/reverse-shot, emotion analysis
- SceneTypeDetectorService — routes dialogue/action/mixed scenes
- ShotContinuityService — jump cut prevention, coverage patterns
- Transition validation — scale changes enforced between consecutive shots

**Current issues (from audit):**
- Narrator voice not assigned — overlayNarratorSegments() sets narratorText but NOT narratorVoiceId
- Single speaker per shot — only first speaker's voice used: array_keys($speakers)[0]
- No voice continuity — same character could get different voices across scenes
- Internal thought asymmetry — narrator uses word-split, internal uses segment-split
- Silent type coercion — missing segment type defaults to 'narrator' without error
- Empty text validation — empty segments can reach TTS generation

**Industry standards (2025):**
- Dia 1.6B TTS — speaker tags [S1], [S2] for consistent multi-voice dialogue
- Microsoft VibeVoice — 90 min speech with 4 distinct speakers
- Google Gemini 2.5 TTS — seamless dialogue with consistent character voices
- MultiTalk (MeiGen-AI) — audio-driven multi-person conversational video

## Constraints

- **Tech stack**: Laravel + Livewire (existing architecture)
- **File structure**: Must follow existing module pattern in `modules/AppVideoWizard/`
- **UI consistency**: Must match existing vw-* CSS class naming
- **Video model**: Multitalk supports single character per shot — design around this

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| SpeechSegment types: narrator, dialogue, internal, monologue | Cover all Hollywood speech patterns | ✓ Good |
| Lip-sync only for dialogue/monologue | Narrator and internal are voiceover only | ✓ Good |
| Purple for speaker names | Consistent with app color scheme | ✓ Good |
| Type icons: 🎙️💬💭🗣️ | Immediate visual recognition | ✓ Good |
| M4 DialogueSceneDecomposerService | Foundation for shot/reverse-shot | ✓ Good - will extend |
| Speech-to-shot 1:1 mapping | Each speech segment drives its own shot | ✓ Good (M8) |
| Narrator overlay pattern | Narrator spans shots, not dedicated | ✓ Good (M8) |
| Jump cut prevention | Validate transitions, enforce scale changes | ✓ Good (M8) |
| Action coverage pattern | Use ShotContinuityService for action scenes | ✓ Good (M8) |
| Voice Registry pattern | Centralized voice assignment (from audit) | — Pending (M9) |
| Multi-speaker tracking | Multiple speakers per shot for dialogue | — Pending (M9) |

---
*Last updated: 2026-01-24 after Milestone 9 start*
