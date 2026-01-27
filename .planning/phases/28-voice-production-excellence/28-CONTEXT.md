# Phase 28: Voice Production Excellence - Context

> Source: User-provided audit document (2026-01-27)

## TTS/Voice Production Audit Summary

This phase addresses gaps identified in a comprehensive audit of the Video Wizard's voice production capabilities.

### Current Implementation Strengths

1. **VoiceGenerationService** - Unified voice routing to multiple providers
2. **Multi-provider support** - ElevenLabs, Azure, Google TTS
3. **Voice settings persistence** - Stored in scene data
4. **Audio stitching** - Combines multiple voice segments

### Identified Gaps

1. **No Voice Registry** - Voice selections not persisted across scenes
2. **Basic SSML support** - Limited markup capabilities
3. **No multi-speaker dialogue handling** - Single voice per generation
4. **No voice continuity validation** - Settings can drift between scenes
5. **Limited emotional direction** - Basic emotion tags only
6. **No lip-sync production pipeline** - Missing viseme data extraction

### Industry Standards Reference

**Modern TTS Capabilities (2026):**
- Dia (Nari Labs) - Multi-speaker with emotion control, 8Hz viseme output
- VibeVoice - 2-second cloning, SSML emotion tags
- Gemini 2.5 TTS - Native dialogue understanding, prosody control
- ElevenLabs Turbo v3 - Real-time streaming, voice mixing

**Lip-Sync Standards:**
- Viseme extraction at minimum 24fps
- Phoneme-to-viseme mapping
- Blend shape weight outputs for 3D avatar compatibility

### Recommended Improvements (Priority Order)

**P0 - Critical:**
1. Voice Registry - Persist voice selections per character
2. Voice Continuity Validation - Verify settings match across scenes

**P1 - High:**
3. Enhanced SSML Markup - Full emotional direction support
4. Multi-Speaker Dialogue - Handle conversations in single generation

**P2 - Medium:**
5. Viseme Extraction Pipeline - For future lip-sync support
6. Voice Performance Caching - Avoid re-generating identical lines

**P3 - Enhancement:**
7. Voice A/B Testing - Compare voice options
8. Emotional Arc Planning - Voice evolution across scenes

### Integration Points

- **CharacterBible** - Voice Registry should integrate with character definitions
- **VoiceGenerationService** - Core routing logic needs enhancement
- **Scene DNA** - Voice settings should flow through DNA system
- **Prompt Pipeline** - Voice prompts (Phase 25) should consume registry data

### Success Metrics

- Voice consistency across 5+ scene videos: 100%
- Multi-speaker dialogue without manual splitting: Yes
- Emotional direction in prompts: [trembling], [whisper], [cracking] etc.
- Voice Registry persistence: Character → Voice mapping maintained

---

*Context gathered: 2026-01-27*
*Source: User audit document*
