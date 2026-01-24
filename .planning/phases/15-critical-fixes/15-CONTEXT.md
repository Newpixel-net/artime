# Phase 15: Critical Fixes - Context

**Gathered:** 2026-01-24
**Status:** Ready for planning

<domain>
## Phase Boundary

Fix immediate voice assignment and validation gaps that cause TTS failures:
- VOC-01: Narrator voice assigned to shots
- VOC-02: Empty text validation before TTS

</domain>

<decisions>
## Implementation Decisions

### Narrator Voice Source
- Primary: `$this->getNarratorVoice()` method (already exists at line 8470)
- Fallback chain: Character Bible narrator → animation.narrator.voice → animation.voiceover.voice → 'nova'
- Use existing method, don't duplicate logic

### Validation Behavior
- Non-blocking (same pattern as M8 validation)
- Log errors but don't halt generation
- Empty segments: skip with warning, continue processing
- Missing type: log error, default to 'narrator' (same behavior, just with logging)

### Error Logging
- Use `Log::warning()` for recoverable issues (empty text skipped)
- Use `Log::error()` for data integrity issues (missing segment type)
- Include context: scene index, segment index, speaker name

### Default Voice Handling
- Narrator: use `getNarratorVoice()` method
- Character: existing fallback chain works (Character Bible → gender → hash)
- No changes needed to default handling

### Claude's Discretion
- Exact log message format
- Whether to add validation counters/summary
- Performance optimization if any

</decisions>

<specifics>
## Specific Findings from Codebase Analysis

**Fix Location 1 - Narrator Voice (VOC-01):**
- File: `VideoWizard.php`
- Method: `overlayNarratorSegments()` (lines 23662-23719)
- Gap: Line ~23701 sets `narratorText` but NOT `narratorVoiceId`
- Fix: Add `$shots[$shotIdx]['narratorVoiceId'] = $this->getNarratorVoice();`

**Fix Location 2 - Empty Text Validation (VOC-02):**
- File: `VideoWizard.php` lines 23979-24031 (TTS calls)
- File: `VoiceoverService.php` lines 657-696 (TTS calls)
- Gap: No empty text check before `AI::process()` calls
- Fix: Add `if (empty(trim($text))) { Log::warning(...); continue; }`

**Fix Location 3 - Type Validation (VOC-02):**
- File: `VideoWizard.php` lines 23491, 23554, 23670 (type coercion)
- Gap: Silent default to 'narrator' without logging
- Fix: Add `Log::error()` when type is null/missing

**Existing Infrastructure:**
- `SpeechSegment::validate()` exists (lines 301-347) but not called
- `getNarratorVoice()` exists (line 8470) and handles fallback chain
- `enrichWithCharacterBible()` exists for character voice lookup

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.
Audit recommendations for Voice Registry (Phase 17) and Multi-Speaker (Phase 18) already mapped to future phases.

</deferred>

---

*Phase: 15-critical-fixes*
*Context gathered: 2026-01-24*
*Source: Codebase exploration + TTS/Lip-Sync audit*
