# Dynamic Speech Segments - Implementation Plan

> **Methodology**: GSD + Ralph Loop
> **Created**: 2026-01-22
> **Status**: PLANNING

---

## The Problem

Currently, each scene has a **single, static Speech Type** dropdown:
- Narrator (no lip-sync)
- Internal (no lip-sync)
- Monologue (lip-sync)
- Dialogue (lip-sync)

**User Pain**: Real movie scenes mix all these types naturally. A scene might have:
1. Narrator setting context
2. Characters talking
3. Internal thoughts
4. Back to narrator

**Current System Forces**: Pick ONE type per entire scene.

---

## The Vision

Transform from **scene-level speech type** to **segment-based speech parsing**:

```
Scene 1: "Neon City Brawl"
├── [NARRATOR] "In a dystopian megacity's underbelly..."     → no lip-sync
├── [DIALOGUE: KAI] "They found me."                          → lip-sync on Kai
├── [DIALOGUE: THUG] "Nowhere to run."                        → lip-sync on Thug
├── [INTERNAL: KAI] "I need to focus... channel the Essence." → V.O., no lip-sync
└── [NARRATOR] "And then everything changed."                 → no lip-sync
```

Each segment:
- Has its own type (narrator/dialogue/internal/monologue)
- Has its own speaker (or null for narrator)
- Has its own `needsLipSync` flag
- Generates its own audio track
- Controls which face to animate (if any)

---

## Research Findings

### Industry Standard (Hollywood Screenplay Format)
From Final Draft, StudioBinder, and academic research:

```screenplay
INT. DARK ALLEY - NIGHT

NARRATOR (V.O.)
The city never sleeps. Neither does Kai.

KAI backs against the wall, breathing heavily.

KAI
(whispering)
They're everywhere...

KAI (V.O.)
I should never have come here.

THUG #1
End of the line, pretty boy.
```

### Key Technical Patterns
1. **Segment-Based Parsing** - Each block is discrete with type
2. **Lip-Sync Per Segment** - Only dialogue gets face animation
3. **Hierarchical Structure**: Scene → Segments[] → Audio tracks

### Existing Codebase Analysis

**Current Structure** (from VoiceoverService exploration):
```php
$scene['voiceover'] = [
    'enabled' => true,
    'text' => '',
    'speechType' => 'narrator',  // Single type for whole scene
    'speakingCharacter' => null
];
```

**Existing Parsing** (`VoiceoverService::parseDialogue()`):
- Already supports `[NARRATOR] text` and `CHARACTER: text` formats
- Already extracts speaker identification
- Already returns `isNarrator` flag
- Already used in `generateDialogueAudio()` for multi-speaker

**Gap**: Parsing exists but isn't used during script generation or UI display.

---

## Architecture Design

### New Data Model: SpeechSegment

```php
// modules/AppVideoWizard/app/Services/SpeechSegment.php

class SpeechSegment
{
    public string $id;           // Unique segment ID
    public string $type;         // 'narrator' | 'dialogue' | 'internal' | 'monologue'
    public string $text;         // The spoken content
    public ?string $speaker;     // Character name (null for narrator)
    public ?string $characterId; // Reference to Character Bible
    public ?string $voiceId;     // TTS voice to use
    public bool $needsLipSync;   // Calculated: dialogue/monologue = true
    public ?float $startTime;    // Calculated after TTS
    public ?float $duration;     // Calculated after TTS
    public ?string $audioUrl;    // Generated audio URL

    public static function fromArray(array $data): self;
    public function toArray(): array;
    public function calculateNeedsLipSync(): bool;
}
```

### New Service: SpeechSegmentParser

```php
// modules/AppVideoWizard/app/Services/SpeechSegmentParser.php

class SpeechSegmentParser
{
    /**
     * Parse raw text into speech segments.
     * Supports formats:
     *   [NARRATOR] text
     *   [INTERNAL: CHARACTER] text
     *   CHARACTER: text
     *   "Quoted dialogue" (attributed to last speaker)
     *   Plain text (defaults to narrator)
     */
    public function parse(string $text, array $characterBible = []): array;

    /**
     * Convert segments back to displayable text with markers.
     */
    public function toDisplayText(array $segments): string;

    /**
     * Validate segments against Character Bible.
     */
    public function validateSpeakers(array $segments, array $characterBible): array;

    /**
     * Auto-detect speech type from text patterns.
     */
    public function detectSpeechType(string $text): string;
}
```

### Modified Scene Structure

```php
$scene['speechSegments'] = [
    [
        'id' => 'seg-1',
        'type' => 'narrator',
        'text' => 'In a dystopian megacity...',
        'speaker' => null,
        'needsLipSync' => false
    ],
    [
        'id' => 'seg-2',
        'type' => 'dialogue',
        'text' => 'They found me.',
        'speaker' => 'KAI',
        'characterId' => 'char-kai-123',
        'needsLipSync' => true
    ],
    [
        'id' => 'seg-3',
        'type' => 'dialogue',
        'text' => 'Nowhere to run.',
        'speaker' => 'THUG',
        'characterId' => 'char-thug-456',
        'needsLipSync' => true
    ],
    [
        'id' => 'seg-4',
        'type' => 'internal',
        'text' => 'I need to focus...',
        'speaker' => 'KAI',
        'characterId' => 'char-kai-123',
        'needsLipSync' => false  // Internal = V.O., no lip movement
    ]
];

// Backwards compatibility - keep single speechType as "mixed"
$scene['speechType'] = 'mixed';
$scene['voiceover']['speechType'] = 'mixed';
```

### UI Changes

**Remove**: Single "Speech Type" dropdown per scene

**Add**: Segment Editor Component
```
┌─────────────────────────────────────────────────────────────┐
│ Scene 1: Neon City Brawl                                    │
├─────────────────────────────────────────────────────────────┤
│ 🎙️ Voiceover Segments                          [+ Add]     │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 🎙️ NARRATOR                              [Edit] [Delete]│ │
│ │ "In a dystopian megacity's underbelly..."               │ │
│ │ ⏱️ ~3s  |  No lip-sync                                  │ │
│ └─────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 💬 KAI                                   [Edit] [Delete]│ │
│ │ "They found me."                                        │ │
│ │ ⏱️ ~1.5s  |  Lip-sync: Kai                              │ │
│ └─────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 💬 THUG                                  [Edit] [Delete]│ │
│ │ "Nowhere to run."                                       │ │
│ │ ⏱️ ~1s  |  Lip-sync: Thug                               │ │
│ └─────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 💭 KAI (Internal)                        [Edit] [Delete]│ │
│ │ "I need to focus... channel the Essence."               │ │
│ │ ⏱️ ~2s  |  V.O. over Kai (no lip-sync)                  │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Total: ~7.5s  |  2 lip-sync tracks  |  [Generate All Audio] │
└─────────────────────────────────────────────────────────────┘
```

---

## Fix Plan (Ralph Loop Format)

### Phase 1: Core Infrastructure (HIGH PRIORITY)

- [ ] **1.1** Create `SpeechSegment` data class with type/speaker/needsLipSync
- [ ] **1.2** Create `SpeechSegmentParser` service with parse/toDisplayText methods
- [ ] **1.3** Add `speechSegments` array to scene data structure
- [ ] **1.4** Update `sanitizeScene()` to initialize empty segments array
- [ ] **1.5** Add migration logic: convert existing `voiceover.text` to segments

### Phase 2: Parser Implementation (HIGH PRIORITY)

- [ ] **2.1** Implement `parse()` method with regex patterns:
  - `[NARRATOR]` tags
  - `[INTERNAL: CHARACTER]` tags
  - `CHARACTER:` prefix
  - `"Quoted dialogue"` detection
  - Plain text fallback
- [ ] **2.2** Implement `toDisplayText()` for segment → text conversion
- [ ] **2.3** Implement `validateSpeakers()` against Character Bible
- [ ] **2.4** Add unit tests for parser edge cases
- [ ] **2.5** Integrate parser into `VoiceoverService`

### Phase 3: AI Generation Integration (MEDIUM PRIORITY)

- [ ] **3.1** Update `buildMultiLayerPrompt()` to request segmented output
- [ ] **3.2** Add segment format instructions to AI prompt
- [ ] **3.3** Parse AI response into segments
- [ ] **3.4** Auto-detect speech types when AI doesn't specify
- [ ] **3.5** Map speakers to Character Bible entries

### Phase 4: Audio Generation (MEDIUM PRIORITY)

- [ ] **4.1** Update `generateDialogueAudio()` to use segments
- [ ] **4.2** Generate separate audio track per segment
- [ ] **4.3** Calculate timing/duration per segment
- [ ] **4.4** Store audio URLs in segment data
- [ ] **4.5** Create combined timeline for playback

### Phase 5: UI Implementation (MEDIUM PRIORITY)

- [ ] **5.1** Create `SpeechSegmentEditor` Blade component
- [ ] **5.2** Add segment CRUD operations (add/edit/delete/reorder)
- [ ] **5.3** Add segment type dropdown (narrator/dialogue/internal/monologue)
- [ ] **5.4** Add speaker selection from Character Bible
- [ ] **5.5** Show lip-sync indicator per segment
- [ ] **5.6** Add "Parse from Text" button for bulk entry

### Phase 6: Video Generation Integration (LOW PRIORITY)

- [ ] **6.1** Update shot decomposition to use segment speakers
- [ ] **6.2** Route lip-sync segments to Multitalk model
- [ ] **6.3** Route non-lip-sync segments to Minimax model
- [ ] **6.4** Sync audio timeline with video shots
- [ ] **6.5** Handle multi-character dialogue in same shot

### Phase 7: Polish & Documentation (LOW PRIORITY)

- [ ] **7.1** Add backwards compatibility for old projects
- [ ] **7.2** Create user documentation for segment editor
- [ ] **7.3** Add "Mixed" option to legacy speechType dropdown
- [ ] **7.4** Performance optimization for large segment counts
- [ ] **7.5** Error handling and validation messages

---

## File Modifications Summary

| File | Change | Priority |
|------|--------|----------|
| `NEW: Services/SpeechSegment.php` | Data class | HIGH |
| `NEW: Services/SpeechSegmentParser.php` | Parser service | HIGH |
| `WizardProject.php` | Add speechSegments to schema | HIGH |
| `ScriptGenerationService.php` | Update sanitizeScene() | HIGH |
| `VoiceoverService.php` | Use segments for audio gen | MEDIUM |
| `ScriptGenerationService.php` | Update AI prompt | MEDIUM |
| `NEW: Components/SpeechSegmentEditor.blade.php` | UI component | MEDIUM |
| `VideoWizard.php` | Livewire methods for segments | MEDIUM |
| `ShotIntelligenceService.php` | Route by segment type | LOW |
| `DialogueSceneDecomposerService.php` | Use segments for shots | LOW |

---

## Testing Strategy

### Unit Tests (Phase 2)
```php
// tests/Unit/SpeechSegmentParserTest.php

public function test_parses_narrator_tags()
public function test_parses_character_dialogue()
public function test_parses_internal_thoughts()
public function test_parses_mixed_content()
public function test_handles_empty_input()
public function test_validates_against_character_bible()
public function test_calculates_needs_lip_sync()
```

### Integration Tests (Phase 4-5)
```php
// tests/Feature/SpeechSegmentIntegrationTest.php

public function test_generates_audio_per_segment()
public function test_creates_combined_timeline()
public function test_ui_crud_operations()
public function test_migration_from_legacy_format()
```

---

## Verification Steps

### After Phase 2 (Parser Complete)
1. Input mixed text with narrator + dialogue + internal
2. Verify correct segment extraction
3. Verify speaker identification
4. Verify needsLipSync flags

### After Phase 5 (UI Complete)
1. Open scene in Script stage
2. See segment editor instead of dropdown
3. Add/edit/delete segments
4. Verify persistence to database

### After Phase 6 (Full Integration)
1. Generate script with AI
2. See segments auto-parsed
3. Generate audio for all segments
4. Generate video with correct lip-sync per segment

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Breaking existing projects | Migration logic + backwards compatibility |
| AI not outputting segments | Fallback to auto-detection from text |
| Performance with many segments | Batch audio generation, lazy loading |
| Complex UI overwhelming users | Progressive disclosure, "Simple mode" toggle |

---

## Success Criteria

1. **Functional**: Scenes can have mixed narrator + dialogue + internal
2. **Automatic**: AI generates segmented scripts
3. **Editable**: Users can manually adjust segments
4. **Accurate**: Lip-sync only on dialogue/monologue segments
5. **Compatible**: Old projects still work

---

## Next Action

**Start with Phase 1.1**: Create `SpeechSegment` data class

```
---RALPH_STATUS---
STATUS: PLANNING
TASKS_COMPLETED_THIS_LOOP: 0
FILES_MODIFIED: 1
TESTS_STATUS: NOT_RUN
WORK_TYPE: DOCUMENTATION
EXIT_SIGNAL: false
RECOMMENDATION: Begin Phase 1.1 - Create SpeechSegment data class
---END_RALPH_STATUS---
```
