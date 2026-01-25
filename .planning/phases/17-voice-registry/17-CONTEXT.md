# Phase 17: Voice Registry - Context

**Gathered:** 2026-01-25
**Phase Goal:** Centralize voice assignment as single source of truth

## Decisions Made

### 1. Registry Scope: Voice IDs Only

**Decision:** VoiceRegistry stores only character→voiceId mappings, not full voice settings.

**Rationale:**
- Keeps registry simple and focused on its core purpose: preventing voice inconsistency
- Voice settings (speed, pitch, style) remain in Character Bible where they belong
- Avoids data duplication between registry and Character Bible
- Aligns with VOC-04's tracking approach (already uses just voiceId)

**Implementation notes:**
- Registry properties: `narratorVoiceId`, `internalVoiceId`, `characterVoices[]`
- Each `characterVoices` entry: `['voiceId' => string, 'source' => string]`
- Source tracks where voice was first assigned (for debugging)

### 2. Initialization: Pre-seed from Character Bible

**Decision:** Initialize VoiceRegistry with voices from Character Bible at decomposition start.

**Rationale:**
- Character Bible is the canonical source for character data
- Pre-seeding catches missing voice assignments before runtime
- Enables validation: any runtime assignment that differs from Bible is a mismatch
- Provides complete picture for validateContinuity() checks

**Implementation notes:**
- Loop through `$this->sceneMemory['characterBible']['characters']`
- Register each character's voice (using existing voice extraction logic)
- Set narrator voice from `getNarratorVoice()` fallback chain
- Log characters registered at initialization for debugging

### 3. Migration: Wrapper Pattern

**Decision:** VoiceRegistry wraps existing lookup methods rather than replacing them.

**Rationale:**
- Minimal disruption to working code
- Existing fallback chains in `getNarratorVoice()` and `getVoiceForCharacterName()` are well-tested
- Registry adds consistency layer on top without rewriting voice resolution
- Gradual migration: callers can switch to registry as convenient

**Implementation notes:**
- `VoiceRegistry::getVoice(string $characterName)` checks registry first
- If not in registry, calls existing `getVoiceForCharacterName()` and registers result
- First-occurrence-wins: once registered, voice doesn't change
- Existing methods remain callable for backward compatibility

## Phase-Specific Constraints

### Must Preserve
- `getNarratorVoice()` fallback chain (Character Bible → animation.narrator.voice → 'nova')
- `getVoiceForCharacterName()` fallback chain (Bible → gender → hash)
- Non-blocking validation pattern from M8/VOC-04

### Integration Points
- Called during `decomposeAllScenes()` initialization
- Used by `overlayNarratorSegments()` for narrator voice
- Used by `markInternalThoughtAsVoiceover()` for internal voice
- Used by shot decomposition for dialogue voices

### Expected Outcomes
1. Single source of truth for voice assignments
2. Simplified debugging (one place to check)
3. Validation catches mismatches early
4. Character Bible voices flow consistently to all speech types

## Open Questions

None - all decisions made.

## References

- VOC-04 implementation: `validateVoiceContinuity()` in VideoWizard.php (line 8625)
- Character Bible structure: `sceneMemory['characterBible']['characters'][]`
- Narrator voice: `getNarratorVoice()` at line 8598
- Character voice: `getVoiceForCharacterName()` at line 23349
