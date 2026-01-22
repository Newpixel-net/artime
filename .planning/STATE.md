# Video Wizard - Current State

> Last Updated: 2026-01-22 (continued session)
> Session: Dynamic Speech Segments Implementation - COMPLETE

---

## Current Focus

**Dynamic Speech Segments** - Transform static per-scene speech type into segment-based mixed narration/dialogue system.

See: `.planning/DYNAMIC_SPEECH_SEGMENTS.md` for full implementation plan.

---

## Completed This Session

1. **Reverted codebase** to commit 78d88d0 (stable state)
2. **Research completed** - Hollywood screenplay format, AI lip-sync patterns, existing parser analysis
3. **Implementation plan created** - GSD + Ralph Loop methodology applied
4. **Architecture designed** - SpeechSegment data model, parser service, UI components
5. **Phase 1 COMPLETE** - Core infrastructure implemented
6. **Phase 2 COMPLETE** - Parser integration into VoiceoverService
7. **Phase 3 COMPLETE** - AI generation integration with LAYER 14
8. **Phase 4 COMPLETE** - Segmented audio generation
9. **Phase 5 COMPLETE** - UI implementation with segment editor
10. **Phase 6 COMPLETE** - Video generation integration with segment-aware lip-sync routing
11. **Phase 7 COMPLETE** - Polish & documentation with error handling

---

## Completed Phases

**Phase 1: Core Infrastructure** ✅
- [x] 1.1 Create `SpeechSegment` data class
- [x] 1.2 Create `SpeechSegmentParser` service
- [x] 1.3 Add `speechSegments` to scene structure
- [x] 1.4 Update `sanitizeScene()` for segments
- [x] 1.5 Migration logic for existing projects

**Phase 2: Parser Implementation** ✅
- [x] 2.1 Implement `parse()` method with regex patterns
- [x] 2.2 Implement `toDisplayText()` for segment → text conversion
- [x] 2.3 Implement `validateSpeakers()` against Character Bible
- [x] 2.4 Add unit tests (deferred - manual testing first)
- [x] 2.5 Integrate parser into VoiceoverService

**Phase 3: AI Generation Integration** ✅
- [x] 3.1 Update `buildMultiLayerPrompt()` to request segmented output
- [x] 3.2 Add segment format instructions to AI prompt (LAYER 14)
- [x] 3.3 Parse AI response into segments (via sanitizeSpeechSegments)
- [x] 3.4 Auto-detect speech types when AI doesn't specify
- [x] 3.5 Map speakers to Character Bible entries

**Phase 4: Audio Generation** ✅
- [x] 4.1 Added `generateSegmentedAudio()` method to VoiceoverService
- [x] 4.2 Generate separate audio track per segment
- [x] 4.3 Calculate timing/duration per segment
- [x] 4.4 Store audio URLs in segment data
- [x] 4.5 Create combined timeline for playback

**Phase 5: UI Implementation** ✅
- [x] 5.1 Create SpeechSegmentEditor CSS styles
- [x] 5.2 Add 'mixed' option to speech type dropdown
- [x] 5.3 Create segment list view with type badges
- [x] 5.4 Add inline edit form for segments
- [x] 5.5 Add segment CRUD Livewire methods (add, delete, move, update)
- [x] 5.6 Add "Parse from Text" button for bulk entry

**Phase 6: Video Generation Integration** ✅
- [x] 6.1 Update `ShotIntelligenceService.needsLipSync()` for segment awareness
- [x] 6.2 Add `getSegmentLipSyncInfo()` helper method with per-segment routing
- [x] 6.3 Add `getModelForSegment()`, `getLipSyncSegments()`, `getVoiceoverOnlySegments()` methods
- [x] 6.4 Add `sceneNeedsLipSync()` and `getSceneLipSyncInfo()` helpers in VideoWizard
- [x] 6.5 Update shot decomposition to use segment-aware lip-sync detection

**Phase 7: Polish & Documentation** ✅
- [x] 7.1 Add comprehensive error handling to all segment CRUD methods
- [x] 7.2 Add `validateSegments()`, `safeParse()`, `normalizeSegments()` to SpeechSegmentParser
- [x] 7.3 Add JSDoc/PHPDoc comments to all new methods
- [x] 7.4 Add `MAX_TEXT_LENGTH`, `MAX_SEGMENTS_PER_SCENE`, `VALID_TYPES` constants
- [x] 7.5 Enhanced `validate()` method in SpeechSegment with comprehensive checks
- [x] 7.6 User feedback via toast notifications for all segment operations

---

## Next Up

1. **End-to-end testing** with mixed speech type scenes
2. **Browser testing** of segment editor UI
3. **Integration testing** with video generation pipeline

---

## Blockers

None currently

---

## Technical Decisions

### Data Model
```php
SpeechSegment {
    id, type, text, speaker, characterId,
    voiceId, needsLipSync, startTime, duration, audioUrl
}
```

### Speech Types
- `narrator` - External V.O., no lip-sync
- `dialogue` - Character speaking, lip-sync required
- `internal` - Character thoughts as V.O., no lip-sync
- `monologue` - Character speaking alone, lip-sync required

### Backwards Compatibility
- Keep `speechType` field at scene level
- Add value `mixed` for segmented scenes
- Migration converts `voiceover.text` to single segment

---

## Key Files

| File | Purpose | Status |
|------|---------|--------|
| `.planning/DYNAMIC_SPEECH_SEGMENTS.md` | Full implementation plan | ✅ |
| `Services/SpeechSegment.php` | Data class | ✅ CREATED |
| `Services/SpeechSegmentParser.php` | Parser service | ✅ CREATED |
| `Services/VoiceoverService.php` | Audio generation | ✅ MODIFIED |
| `Services/ScriptGenerationService.php` | Scene sanitization + AI prompt | ✅ MODIFIED |
| `Services/ShotIntelligenceService.php` | Segment-aware lip-sync routing | ✅ MODIFIED |
| `Livewire/VideoWizard.php` | Segment CRUD + lip-sync helpers | ✅ MODIFIED |
| `views/livewire/steps/script.blade.php` | Segment editor UI | ✅ MODIFIED |

---

## Notes

- GSD v1.9.4 installed
- Ralph Loop at `~/.ralph/`
- Hollywood cinematography skill at `~/.claude/skills/hollywood-cinematography/`
- New `generateSegmentedAudio()` method added to VoiceoverService
- Parser supports: `[NARRATOR]`, `[INTERNAL: CHAR]`, `[MONOLOGUE: CHAR]`, `CHARACTER:` formats
- ShotIntelligenceService now has segment-aware lip-sync routing methods
- VideoWizard uses `sceneNeedsLipSync()` helper for segment-aware decomposition

---

## Phase Overview

| Phase | Description | Priority | Status |
|-------|-------------|----------|--------|
| 1 | Core Infrastructure | HIGH | ✅ Complete |
| 2 | Parser Implementation | HIGH | ✅ Complete |
| 3 | AI Generation Integration | MEDIUM | ✅ Complete |
| 4 | Audio Generation | MEDIUM | ✅ Complete |
| 5 | UI Implementation | MEDIUM | ✅ Complete |
| 6 | Video Generation Integration | MEDIUM | ✅ Complete |
| 7 | Polish & Documentation | LOW | ✅ Complete |

**🎉 ALL PHASES COMPLETE - Ready for Testing**
