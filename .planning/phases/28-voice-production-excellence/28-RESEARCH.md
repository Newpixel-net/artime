# Phase 28: Voice Production Excellence - Research

**Researched:** 2026-01-27
**Domain:** TTS Voice Production, Multi-Speaker Dialogue, Voice Registry Systems
**Confidence:** HIGH

## Summary

Phase 28 addresses critical gaps in voice production capabilities identified in a comprehensive audit. The existing codebase has strong foundations with VoiceRegistryService (Phase 17 VOC-05), VoiceoverService (with multi-speaker shot support), and the Phase 25 voice direction services (VoicePromptBuilderService, VoiceDirectionVocabulary, VoicePacingService). However, these components are not yet fully integrated.

The primary integration gaps are: (1) VoicePromptBuilderService is orphaned and not connected to the TTS generation pipeline, (2) VoiceRegistryService exists but does not persist across wizard sessions, (3) the Character Bible modal has voice settings but they do not flow through to Scene DNA, and (4) multi-speaker dialogue is handled via sequential generation rather than native provider APIs.

**Primary recommendation:** Integrate VoicePromptBuilderService into VoiceoverService.generateSceneVoiceover() and create a VoiceContinuityValidator that runs at scene transition points.

## Standard Stack

The application already has an established voice stack. No new libraries are required.

### Core (Already Implemented)
| Service | Location | Purpose | Status |
|---------|----------|---------|--------|
| VoiceoverService | Services/VoiceoverService.php | TTS generation routing (OpenAI, Kokoro) | Active, handles multi-speaker |
| VoiceRegistryService | Services/VoiceRegistryService.php | Character-voice mapping with first-wins | Active but session-only |
| VoicePromptBuilderService | Services/VoicePromptBuilderService.php | Emotional direction, pacing, ambient cues | Orphaned - needs integration |
| VoiceDirectionVocabulary | Services/VoiceDirectionVocabulary.php | Emotion tags, vocal qualities, non-verbals | Active via VoicePromptBuilder |
| VoicePacingService | Services/VoicePacingService.php | SSML breaks, pause markers | Active via VoicePromptBuilder |
| SpeechSegmentParser | Services/SpeechSegmentParser.php | Parse narration into typed segments | Active |

### TTS Providers Supported
| Provider | Voice Count | Multi-Speaker | Emotion Control | Method |
|----------|-------------|---------------|-----------------|--------|
| OpenAI gpt-4o-mini-tts | 6 voices | No native | Instructions parameter | System prompt |
| Kokoro (local) | 15+ voices | No native | Descriptive text | Prompt engineering |
| ElevenLabs (config-only) | Many | Text-to-Dialogue API | Inline brackets | `[emotion]` tags |

### Data Structures
| Model | Location | Voice Fields |
|-------|----------|--------------|
| characterBible.characters[].voice | sceneMemory | `{id, gender, style, pitch, speed, isNarrator}` |
| SpeechSegment | Services/SpeechSegment.php | `voiceId, emotion, type, speaker, needsLipSync` |

## Architecture Patterns

### Current Voice Flow (Gaps Identified)
```
User selects voice in Character Bible modal
    ↓
sceneMemory.characterBible.characters[].voice = {...}
    ↓
VoiceoverService.getVoiceForSpeaker() looks up voice
    ↓ (GAP: VoicePromptBuilderService not called)
AI::process() generates TTS directly
```

### Recommended Voice Flow (Phase 28)
```
User selects voice in Character Bible modal
    ↓
sceneMemory.characterBible.characters[].voice = {...}
    ↓
Scene DNA includes voice registry snapshot (VOC-07)
    ↓
VoiceRegistryService.initializeFromCharacterBible() (persist)
    ↓
VoiceContinuityValidator.validateBeforeGeneration() (VOC-08)
    ↓
VoicePromptBuilderService.buildEnhancedVoicePrompt() (VOC-09)
    ↓
VoiceoverService.generateSceneVoiceover() with enhanced text
```

### Recommended Project Structure Addition
```
modules/AppVideoWizard/app/Services/
├── Voice/
│   ├── VoiceContinuityValidator.php    # NEW: VOC-08 validation
│   └── MultiSpeakerDialogueBuilder.php # NEW: VOC-10 dialogue assembly
```

### Pattern 1: Voice Registry Persistence
**What:** Persist voice mappings in Scene DNA so they survive wizard reload
**When to use:** On scene generation, on characterBible save
**Example:**
```php
// In buildSceneDNA() method
$sceneDNA['voiceRegistry'] = [
    'characters' => $this->buildVoiceRegistryFromBible($characterBible),
    'narrator' => $this->getNarratorVoice(),
    'lastValidatedAt' => now()->toIso8601String(),
];
```

### Pattern 2: Enhanced Voice Prompt Integration
**What:** Call VoicePromptBuilderService before TTS generation
**When to use:** Every TTS call that has emotion or character context
**Example:**
```php
// In VoiceoverService.generateSceneVoiceover()
$promptBuilder = app(VoicePromptBuilderService::class);

// For segments with emotion
if (!empty($segment->emotion)) {
    $enhanced = $promptBuilder->buildEnhancedVoicePrompt($segment, [
        'provider' => $provider,
        'includeAmbient' => false,
        'arcPosition' => $segment->emotionalArcNote ?? null,
    ]);

    $textToSpeak = $enhanced['text'];
    $instructions = $enhanced['instructions']; // For OpenAI
}
```

### Pattern 3: Voice Continuity Validation
**What:** Check voice assignments match across scenes before generation
**When to use:** Before batch voiceover generation
**Example:**
```php
// VoiceContinuityValidator.php
public function validateSceneTransition(array $previousScene, array $currentScene): array
{
    $issues = [];
    $previousVoices = $this->extractVoiceAssignments($previousScene);
    $currentVoices = $this->extractVoiceAssignments($currentScene);

    foreach ($currentVoices as $character => $voiceId) {
        if (isset($previousVoices[$character]) && $previousVoices[$character] !== $voiceId) {
            $issues[] = [
                'type' => 'voice_drift',
                'character' => $character,
                'expected' => $previousVoices[$character],
                'actual' => $voiceId,
            ];
        }
    }

    return ['valid' => empty($issues), 'issues' => $issues];
}
```

### Pattern 4: Multi-Speaker Dialogue Format
**What:** Structure dialogue for sequential or native multi-speaker generation
**When to use:** Shots with multiple speakers
**Example:**
```php
// For ElevenLabs Text-to-Dialogue (future)
$dialogueTurns = [
    ['voice_id' => 'voice_abc', 'text' => '[excited] This is amazing!'],
    ['voice_id' => 'voice_xyz', 'text' => '[skeptical] Are you sure about that?'],
];

// For sequential generation (current approach)
foreach ($speakers as $speaker) {
    $result = $this->generateSceneVoiceover($project, [
        'narration' => $speaker['text'],
    ], ['voice' => $speaker['voiceId']]);
    $audioSegments[] = $result;
}
```

### Anti-Patterns to Avoid
- **Voice lookup on every generation:** Cache voice assignments in VoiceRegistryService, not in each method
- **Hardcoded emotion tags:** Use VoiceDirectionVocabulary constants, not inline strings
- **Skipping validation:** Always validate continuity before batch generation
- **Provider-specific code in Livewire:** Keep provider logic in Service classes

## Don't Hand-Roll

Problems that have existing solutions in the codebase:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Emotion-to-tag mapping | Custom tag arrays | VoiceDirectionVocabulary::EMOTIONAL_DIRECTION | Provider-specific mappings already exist |
| Pause/break timing | Inline hardcoded times | VoicePacingService::PAUSE_TYPES | Consistent naming, SSML conversion |
| Character voice lookup | Manual array iteration | VoiceRegistryService.getVoiceForCharacter() | First-wins logic, mismatch detection |
| Speech segment parsing | Regex in Livewire | SpeechSegmentParser.parse() | Full segment type detection |
| Arc position calculation | Manual index math | VoicePromptBuilderService.buildEmotionalArc() | Proportional distribution across segments |
| SSML conversion | Provider-specific string building | VoicePacingService.toSSML() | Handles all pause formats |

**Key insight:** Phase 25 built comprehensive voice direction infrastructure that is currently unused. Integration is the priority, not new features.

## Common Pitfalls

### Pitfall 1: Provider-Specific Tag Leakage
**What goes wrong:** ElevenLabs `[whispers]` tags sent to OpenAI, causing literal text output
**Why it happens:** Not checking provider before applying inline tags
**How to avoid:** Use VoiceDirectionVocabulary.wrapWithDirection() which checks provider
**Warning signs:** Text output includes visible bracket tags

### Pitfall 2: Voice Registry Not Persisted
**What goes wrong:** Voice assignments reset when user reloads wizard
**Why it happens:** VoiceRegistryService is instantiated fresh each request
**How to avoid:** Store registry snapshot in sceneMemory.sceneDNA.voiceRegistry
**Warning signs:** Character voices change unexpectedly across sessions

### Pitfall 3: Emotion Tag Instability
**What goes wrong:** TTS output becomes garbled or cuts off
**Why it happens:** Too many emotion/break tags in single segment
**How to avoid:** Limit to 1-2 emotional tags per segment (documented in VoiceDirectionVocabulary)
**Warning signs:** Audio artifacts, truncated output, inconsistent tone

### Pitfall 4: Silent Validation Failures
**What goes wrong:** Voice mismatches shipped to production
**Why it happens:** Validation runs but results not surfaced to user
**How to avoid:** Display validation results in UI, block generation on critical issues
**Warning signs:** Users report character voice changes mid-video

### Pitfall 5: Multi-Speaker Audio Desync
**What goes wrong:** Dialogue timing feels unnatural, overlaps or gaps
**Why it happens:** Sequential generation without proper gap/transition handling
**How to avoid:** Use processMultiSpeakerShot() which tracks startTime offsets
**Warning signs:** Awkward pauses, cut-off words between speakers

## Code Examples

### Integration Point 1: VoicePromptBuilder in VoiceoverService
```php
// In VoiceoverService.generateSceneVoiceover()
// Source: Pattern derived from existing VoicePromptBuilderService

protected function enhanceTextWithVoiceDirection(
    string $text,
    ?string $emotion,
    string $provider
): array {
    if (empty($emotion)) {
        return ['text' => $text, 'instructions' => ''];
    }

    $segment = new SpeechSegment([
        'text' => $text,
        'emotion' => $emotion,
        'type' => SpeechSegment::TYPE_DIALOGUE,
    ]);

    $promptBuilder = app(VoicePromptBuilderService::class);
    return $promptBuilder->buildEnhancedVoicePrompt($segment, [
        'provider' => $provider,
        'includeAmbient' => false,
    ]);
}
```

### Integration Point 2: Voice Registry in Scene DNA
```php
// In VideoWizard.buildSceneDNA()
// Source: Extends existing sceneDNA structure

protected function buildVoiceRegistryForDNA(): array
{
    $characterBible = $this->sceneMemory['characterBible'] ?? [];
    $registry = [];

    foreach ($characterBible['characters'] ?? [] as $char) {
        $name = $char['name'] ?? '';
        $voice = $char['voice'] ?? [];

        if (!empty($name) && !empty($voice['id'])) {
            $registry[strtoupper($name)] = [
                'voiceId' => $voice['id'],
                'gender' => $voice['gender'] ?? null,
                'style' => $voice['style'] ?? null,
                'pitch' => $voice['pitch'] ?? 'normal',
                'speed' => $voice['speed'] ?? 1.0,
                'isNarrator' => $voice['isNarrator'] ?? false,
            ];
        }
    }

    return $registry;
}
```

### Integration Point 3: Voice Continuity Check UI
```php
// In VideoWizard or Scene DNA modal
// Source: Pattern from existing continuity issues display

public function getVoiceContinuityIssues(): array
{
    $registry = app(VoiceRegistryService::class);
    $registry->initializeFromCharacterBible(
        $this->sceneMemory['characterBible'] ?? [],
        $this->getNarratorVoice()
    );

    return $registry->validateContinuity();
}
```

### Integration Point 4: Enhanced Character Bible Voice UI
```blade
{{-- In character-bible.blade.php, Voice section --}}
{{-- Add emotion preview dropdown --}}
<div>
    <label>{{ __('Emotion Preview') }}</label>
    <select wire:model.live="previewEmotion">
        <option value="">{{ __('Neutral') }}</option>
        @foreach(['trembling', 'whisper', 'cracking', 'grief', 'anxiety', 'fear', 'contempt', 'joy'] as $emotion)
            <option value="{{ $emotion }}">{{ ucfirst($emotion) }}</option>
        @endforeach
    </select>
    <button wire:click="previewVoiceWithEmotion({{ $editIndex }}, $previewEmotion)">
        {{ __('Preview') }}
    </button>
</div>
```

## State of the Art

### Provider Capabilities (2026)

| Provider | Multi-Speaker | Emotion Tags | Method | Status |
|----------|---------------|--------------|--------|--------|
| ElevenLabs v3 | Text-to-Dialogue API | `[emotion]`, `[laughs]`, `[interrupting]` | JSON array of turns | Available |
| OpenAI gpt-4o-mini-tts | No native | Via `instructions` param | System prompt | Available |
| Hume Octave | Native emotional context | Natural language | Prompt | New entrant |
| Chatterbox (open) | No | `[laugh]`, `[cough]`, exaggeration slider | Tags | Open source |

### ElevenLabs Text-to-Dialogue API
- **Endpoint:** POST /text-to-dialogue/convert
- **Format:** Array of speaker turns with voice_id per turn
- **Audio tags:** `[sad]`, `[laughing]`, `[whispering]`, `[interrupting]`, `[overlapping]`
- **No speaker limit:** Supports unlimited speakers per dialogue
- **Output:** Single MP3/PCM with natural transitions
- **Latency:** Higher than single-voice TTS, not suitable for real-time

### OpenAI Instructions Parameter
```php
// OpenAI's approach - no SSML, uses instructions
$response = AI::process($text, 'speech', [
    'voice' => 'coral',
    'model' => 'gpt-4o-mini-tts',
], $teamId);

// To control emotion, modify the text prompt or use style prompting
// "Speak in a cheerful and positive tone."
```

| Old Approach | Current Approach | Impact |
|--------------|------------------|--------|
| SSML prosody tags | Natural language instructions | Simpler, more flexible |
| Manual audio stitching | Native multi-speaker APIs | Better transitions |
| Single emotion per segment | Emotion arcs across dialogue | More natural delivery |

**Deprecated/outdated:**
- SSML break tags in OpenAI: Not supported, use natural punctuation
- Fixed emotion vocabularies: Modern TTS understands natural descriptions

## Open Questions

### 1. ElevenLabs Text-to-Dialogue Integration
- **What we know:** API exists, supports multi-speaker with emotion tags
- **What's unclear:** Full request/response format, error handling, cost per dialogue
- **Recommendation:** Add as future enhancement (VOC-10 can use sequential for now), document API structure for later implementation

### 2. Voice Preview Caching
- **What we know:** previewVoice() generates audio on demand
- **What's unclear:** Should previews be cached? How long? Per-session or persistent?
- **Recommendation:** Start without caching, add if performance becomes issue

### 3. Viseme Data for Lip-Sync
- **What we know:** Context mentions viseme extraction at 24fps
- **What's unclear:** Which providers return viseme data? Format compatibility with Multitalk?
- **Recommendation:** Defer to Phase 29+ (P2 in audit), focus on voice continuity first

## Sources

### Primary (HIGH confidence)
- VoiceRegistryService.php - Lines 1-314, full implementation reviewed
- VoiceoverService.php - Lines 1-1168, multi-speaker patterns documented
- VoicePromptBuilderService.php - Lines 1-379, emotional arc and ambient cues
- VoiceDirectionVocabulary.php - Lines 1-305, emotion mappings
- VoicePacingService.php - Lines 1-348, SSML conversion
- character-bible.blade.php - Voice section UI (lines 440-578)

### Secondary (MEDIUM confidence)
- [ElevenLabs Text to Dialogue Documentation](https://elevenlabs.io/docs/overview/capabilities/text-to-dialogue) - API overview
- [ElevenLabs Eleven v3 Audio Tags](https://elevenlabs.io/blog/eleven-v3-audio-tags-bringing-multi-character-dialogue-to-life) - Tag syntax
- [OpenAI TTS Guide](https://platform.openai.com/docs/guides/text-to-speech) - Instructions parameter
- [Speechmatics Best TTS APIs 2026](https://www.speechmatics.com/company/articles-and-news/best-tts-apis-in-2025-top-12-text-to-speech-services-for-developers) - Industry overview

### Tertiary (LOW confidence)
- Hume Octave, Chatterbox mentioned in web searches - not verified with official docs

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All services exist in codebase, reviewed directly
- Architecture patterns: HIGH - Based on existing code patterns
- Provider capabilities: MEDIUM - Web search verified, API details need confirmation
- Pitfalls: HIGH - Documented in code comments and observed in codebase

**Research date:** 2026-01-27
**Valid until:** 2026-02-27 (30 days - stable domain, provider APIs may update)
