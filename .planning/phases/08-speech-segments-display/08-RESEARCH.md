# Phase 8: Speech Segments Display - Research

**Researched:** 2026-01-23
**Domain:** Livewire/Blade UI - Speech Segment Display in Scene Text Inspector Modal
**Confidence:** HIGH

## Summary

Phase 8 implements the full speech segments display section in the Scene Text Inspector modal created in Phase 7. The modal shell exists with a placeholder div at lines 175-183 of `scene-text-inspector.blade.php`.

The speech segment data structure is well-established with the `SpeechSegment` class and `SpeechSegmentParser` service. Each segment contains: `id`, `type`, `text`, `speaker`, `characterId`, `voiceId`, `needsLipSync`, `duration`, `order`, and `emotion`. The storyboard already displays truncated speech segments with type icons and lip-sync indicators, providing an established pattern to follow and extend.

**Primary recommendation:** Implement full segment list display using existing `$this->inspectorScene['script']['speechSegments']` data path, with scrollable container supporting 10+ segments, showing all segment properties (type icon, type label, speaker name with Character Bible matching, lip-sync indicator, and estimated duration).

## Standard Stack

### Core Data Structure - SpeechSegment Class

**Location:** `modules/AppVideoWizard/app/Services/SpeechSegment.php`

| Property | Type | Purpose |
|----------|------|---------|
| `id` | string | Unique segment identifier (e.g., `seg-abc123`) |
| `type` | string | Speech type: `narrator`, `dialogue`, `internal`, `monologue` |
| `text` | string | The spoken text content |
| `speaker` | ?string | Speaker name (null for narrator) |
| `characterId` | ?string | Reference to Character Bible entry ID |
| `voiceId` | ?string | TTS voice ID for this segment |
| `needsLipSync` | bool | Whether segment requires lip-sync animation |
| `duration` | ?float | Duration in seconds (set after audio generation) |
| `order` | int | Position within scene's segments array |
| `emotion` | ?string | Emotion/tone hint for TTS |

### Speech Type Constants

```php
SpeechSegment::TYPE_NARRATOR = 'narrator';
SpeechSegment::TYPE_DIALOGUE = 'dialogue';
SpeechSegment::TYPE_INTERNAL = 'internal';
SpeechSegment::TYPE_MONOLOGUE = 'monologue';

// Types requiring lip-sync
SpeechSegment::LIP_SYNC_TYPES = ['dialogue', 'monologue'];

// Voiceover-only types (no lip movement)
SpeechSegment::VOICEOVER_ONLY_TYPES = ['narrator', 'internal'];
```

### Type Icons and Colors (from storyboard.blade.php lines 2549-2555)

| Type | Icon | Color | Label | Lip-Sync |
|------|------|-------|-------|----------|
| `narrator` | `🎙️` | `rgba(14, 165, 233, 0.4)` | NARRATOR | NO |
| `dialogue` | `💬` | `rgba(16, 185, 129, 0.4)` | DIALOGUE | YES |
| `internal` | `💭` | `rgba(168, 85, 247, 0.4)` | INTERNAL | NO |
| `monologue` | `🗣️` | `rgba(251, 191, 36, 0.4)` | MONOLOGUE | YES |

## Architecture Patterns

### Data Access Path

```php
// In scene-text-inspector.blade.php
$scene = $this->inspectorScene['script'] ?? null;  // Already set up in Phase 7
$speechSegments = $scene['speechSegments'] ?? [];
```

The `inspectorScene` computed property is defined in `VideoWizard.php` (lines 1305-1318):

```php
public function getInspectorSceneProperty(): ?array
{
    if ($this->inspectorSceneIndex === null) {
        return null;
    }

    return [
        'script' => $this->script['scenes'][$this->inspectorSceneIndex] ?? null,
        'storyboard' => $this->storyboard[$this->inspectorSceneIndex] ?? null,
    ];
}
```

### Character Bible Access for Speaker Matching

```php
// Access Character Bible from $sceneMemory (available in all blade templates)
$characterBible = $sceneMemory['characterBible']['characters'] ?? [];

// Match speaker to character (case-insensitive)
$speakerUpper = strtoupper($segment['speaker'] ?? '');
$matchedCharacter = null;
foreach ($characterBible as $char) {
    $charName = strtoupper($char['name'] ?? '');
    if ($charName === $speakerUpper ||
        str_contains($charName, $speakerUpper) ||
        str_contains($speakerUpper, $charName)) {
        $matchedCharacter = $char;
        break;
    }
}
```

### Duration Estimation (from SpeechSegment class)

```php
// SpeechSegment::estimateDuration() at 150 words per minute
$wordCount = str_word_count($segment['text']);
$durationSeconds = round(($wordCount / 150) * 60, 2);

// Format as MM:SS or just seconds
if ($durationSeconds >= 60) {
    $durationFormatted = sprintf('%d:%02d', floor($durationSeconds / 60), $durationSeconds % 60);
} else {
    $durationFormatted = round($durationSeconds, 1) . 's';
}
```

### Recommended Section Structure

Replace the placeholder div (lines 175-183 in scene-text-inspector.blade.php) with:

```blade
{{-- Speech Segments Section (Phase 8) --}}
<div style="margin-bottom: 1.5rem;">
    <h4 style="margin: 0 0 0.75rem 0; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
        Speech Segments
        @if(!empty($scene['speechSegments']))
            <span style="opacity: 0.6; font-weight: normal; font-size: 0.7rem; text-transform: none;">({{ count($scene['speechSegments']) }} segments)</span>
        @endif
    </h4>

    @php
        $speechSegments = $scene['speechSegments'] ?? [];
        $characterBible = $sceneMemory['characterBible']['characters'] ?? [];

        // Type configuration
        $typeConfig = [
            'narrator' => ['icon' => '🎙️', 'color' => 'rgba(14, 165, 233, 0.4)', 'label' => 'NARRATOR', 'lipSync' => false],
            'dialogue' => ['icon' => '💬', 'color' => 'rgba(16, 185, 129, 0.4)', 'label' => 'DIALOGUE', 'lipSync' => true],
            'internal' => ['icon' => '💭', 'color' => 'rgba(168, 85, 247, 0.4)', 'label' => 'INTERNAL', 'lipSync' => false],
            'monologue' => ['icon' => '🗣️', 'color' => 'rgba(251, 191, 36, 0.4)', 'label' => 'MONOLOGUE', 'lipSync' => true],
        ];
    @endphp

    @if(!empty($speechSegments))
        <div style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach($speechSegments as $index => $segment)
                {{-- Segment card with full details --}}
            @endforeach
        </div>
    @else
        <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; text-align: center; color: rgba(255,255,255,0.6); font-size: 0.75rem;">
            {{ __('No speech segments for this scene') }}
        </div>
    @endif
</div>
```

### Segment Card Pattern (inspired by storyboard display)

```blade
@php
    $segType = $segment['type'] ?? 'narrator';
    $typeData = $typeConfig[$segType] ?? $typeConfig['narrator'];
    $needsLipSync = $typeData['lipSync'];

    // Estimate duration (150 wpm)
    $wordCount = str_word_count($segment['text'] ?? '');
    $estDuration = $segment['duration'] ?? round(($wordCount / 150) * 60, 1);
    $durationDisplay = $estDuration >= 60
        ? sprintf('%d:%02d', floor($estDuration / 60), $estDuration % 60)
        : round($estDuration, 1) . 's';

    // Character Bible matching
    $speaker = $segment['speaker'] ?? null;
    $matchedChar = null;
    if ($speaker) {
        $speakerUpper = strtoupper($speaker);
        foreach ($characterBible as $char) {
            $charName = strtoupper($char['name'] ?? '');
            if ($charName === $speakerUpper || str_contains($charName, $speakerUpper) || str_contains($speakerUpper, $charName)) {
                $matchedChar = $char;
                break;
            }
        }
    }
@endphp

<div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border-left: 3px solid {{ $typeData['color'] }}; border-radius: 0.375rem;">
    {{-- Header row: Type, Speaker, Badges --}}
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
        <span style="font-size: 1rem;">{{ $typeData['icon'] }}</span>
        <span style="font-size: 0.7rem; font-weight: 600; color: rgba(255,255,255,0.9); padding: 0.15rem 0.4rem; background: {{ $typeData['color'] }}; border-radius: 0.25rem;">{{ $typeData['label'] }}</span>

        @if($speaker)
            <span style="color: #c4b5fd; font-size: 0.75rem; font-weight: 600;">{{ $speaker }}</span>
            @if($matchedChar)
                <span title="{{ __('Character in Bible') }}" style="font-size: 0.7rem;">👤</span>
            @endif
        @endif

        <span style="margin-left: auto; font-size: 0.65rem; padding: 0.1rem 0.35rem; border-radius: 0.2rem; {{ $needsLipSync ? 'background: rgba(16,185,129,0.2); color: #6ee7b7;' : 'background: rgba(100,116,139,0.2); color: rgba(255,255,255,0.5);' }}">
            LIP-SYNC: {{ $needsLipSync ? 'YES' : 'NO' }}
        </span>

        <span style="font-size: 0.65rem; color: rgba(255,255,255,0.5);" title="{{ __('Estimated duration') }}">
            ⏱️ {{ $durationDisplay }}
        </span>
    </div>

    {{-- Full text content --}}
    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.85); line-height: 1.5; white-space: pre-wrap;">{{ $segment['text'] ?? '' }}</div>
</div>
```

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Type icons/colors | Custom mapping | `$typeConfig` array pattern from storyboard | Consistency with existing display |
| Duration calculation | Manual math | `str_word_count() / 150 * 60` formula | Established 150 WPM standard |
| Character matching | Complex fuzzy match | Simple `strtoupper()` + `str_contains()` | Pattern from SpeechSegmentParser |
| Scrollable list | Custom JS scroll | `overflow-y: auto; max-height: 400px` | Native CSS, no JS needed |

## Common Pitfalls

### Pitfall 1: Empty State Handling
**What goes wrong:** Not checking for empty `speechSegments` array causes `@foreach` to render nothing with no feedback.
**Why it happens:** Scenes may have legacy `narration` field instead of `speechSegments`.
**How to avoid:** Check both `speechSegments` and `narration` fields, show appropriate message for empty state.
**Warning signs:** Blank space in modal where segments should appear.

### Pitfall 2: Missing Character Bible Access
**What goes wrong:** `$sceneMemory` is not available in modal, causing undefined variable errors.
**Why it happens:** Modal template might not have access to parent component's public properties.
**How to avoid:** Verify `$sceneMemory` is available by checking other modals (e.g., character-bible.blade.php line 65 confirms it works).
**Warning signs:** PHP undefined variable error for `$sceneMemory`.

### Pitfall 3: Long Text Overflow
**What goes wrong:** Speech segments with long text (500+ chars) break layout or cause horizontal scroll.
**Why it happens:** Not applying proper text wrapping styles.
**How to avoid:** Use `white-space: pre-wrap; word-break: break-word;` on text containers.
**Warning signs:** Horizontal scrollbar appears, text extends beyond container.

### Pitfall 4: Scrollable Container Performance
**What goes wrong:** 50+ segments (max allowed per scene) cause sluggish scrolling.
**Why it happens:** Too many DOM elements with complex styles.
**How to avoid:** Keep individual segment cards simple; max-height container with overflow handles pagination naturally.
**Warning signs:** Visible lag when scrolling through many segments.

### Pitfall 5: Inconsistent Type Label Casing
**What goes wrong:** Displaying type as lowercase from data (`narrator`) instead of uppercase label (`NARRATOR`).
**Why it happens:** Using `$segment['type']` directly instead of `$typeData['label']`.
**How to avoid:** Always use the `$typeConfig` lookup for display values.
**Warning signs:** Labels show "narrator" instead of "NARRATOR".

## Code Examples

### Complete Segment Display Loop

```blade
@if(!empty($speechSegments))
    <div style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; padding-right: 0.25rem;">
        @foreach($speechSegments as $index => $segment)
            @php
                $segType = $segment['type'] ?? 'narrator';
                $typeData = $typeConfig[$segType] ?? $typeConfig['narrator'];
                $needsLipSync = $typeData['lipSync'];

                // Duration estimation
                $wordCount = str_word_count($segment['text'] ?? '');
                $estDuration = $segment['duration'] ?? round(($wordCount / 150) * 60, 1);
                $durationDisplay = $estDuration >= 60
                    ? sprintf('%d:%02d', floor($estDuration / 60), $estDuration % 60)
                    : round($estDuration, 1) . 's';

                // Character Bible matching
                $speaker = $segment['speaker'] ?? null;
                $matchedChar = null;
                if ($speaker && !empty($characterBible)) {
                    $speakerUpper = strtoupper($speaker);
                    foreach ($characterBible as $char) {
                        $charName = strtoupper($char['name'] ?? '');
                        if ($charName === $speakerUpper || str_contains($charName, $speakerUpper) || str_contains($speakerUpper, $charName)) {
                            $matchedChar = $char;
                            break;
                        }
                    }
                }
            @endphp

            <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border-left: 3px solid {{ $typeData['color'] }}; border-radius: 0 0.375rem 0.375rem 0;">
                {{-- Header: Type badge, Speaker, Lip-sync, Duration --}}
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                    {{-- Type icon and label --}}
                    <span style="font-size: 1rem;">{{ $typeData['icon'] }}</span>
                    <span style="font-size: 0.65rem; font-weight: 600; color: white; padding: 0.15rem 0.4rem; background: {{ $typeData['color'] }}; border-radius: 0.25rem;">
                        {{ $typeData['label'] }}
                    </span>

                    {{-- Speaker name (purple, with character indicator) --}}
                    @if($speaker)
                        <span style="color: #c4b5fd; font-size: 0.75rem; font-weight: 600;">{{ $speaker }}</span>
                        @if($matchedChar)
                            <span title="{{ __('Character exists in Bible') }}" style="font-size: 0.65rem; color: #10b981;">👤</span>
                        @endif
                    @endif

                    {{-- Spacer --}}
                    <span style="flex: 1;"></span>

                    {{-- Lip-sync indicator --}}
                    <span style="font-size: 0.6rem; padding: 0.1rem 0.35rem; border-radius: 0.2rem; font-weight: 500;
                        {{ $needsLipSync
                            ? 'background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.3);'
                            : 'background: rgba(100,116,139,0.15); color: rgba(255,255,255,0.5); border: 1px solid rgba(100,116,139,0.2);'
                        }}">
                        LIP-SYNC: {{ $needsLipSync ? 'YES' : 'NO' }}
                    </span>

                    {{-- Duration --}}
                    <span style="font-size: 0.6rem; color: rgba(255,255,255,0.5);" title="{{ __('Estimated duration at 150 WPM') }}">
                        ⏱️ {{ $durationDisplay }}
                    </span>
                </div>

                {{-- Full text content (no truncation) --}}
                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.85); line-height: 1.6; white-space: pre-wrap; word-break: break-word;">
                    {{ $segment['text'] ?? '' }}
                </div>
            </div>
        @endforeach
    </div>
@elseif(!empty($scene['narration']))
    {{-- Legacy narration fallback --}}
    <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border-left: 3px solid rgba(14, 165, 233, 0.4); border-radius: 0 0.375rem 0.375rem 0;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <span style="font-size: 1rem;">🎙️</span>
            <span style="font-size: 0.65rem; font-weight: 600; color: white; padding: 0.15rem 0.4rem; background: rgba(14, 165, 233, 0.4); border-radius: 0.25rem;">NARRATOR</span>
            <span style="flex: 1;"></span>
            <span style="font-size: 0.6rem; padding: 0.1rem 0.35rem; border-radius: 0.2rem; font-weight: 500; background: rgba(100,116,139,0.15); color: rgba(255,255,255,0.5); border: 1px solid rgba(100,116,139,0.2);">
                LIP-SYNC: NO
            </span>
        </div>
        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.85); line-height: 1.6; white-space: pre-wrap; word-break: break-word;">
            {{ $scene['narration'] }}
        </div>
    </div>
@else
    <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; text-align: center; color: rgba(255,255,255,0.4); font-size: 0.75rem;">
        {{ __('No speech segments for this scene') }}
    </div>
@endif
```

### Character Portrait Thumbnail (Optional Enhancement - SPCH-07)

If showing character portrait for matched speakers:

```blade
@if($matchedChar && !empty($matchedChar['referenceImage']))
    <div style="width: 24px; height: 30px; border-radius: 0.2rem; overflow: hidden; background: rgba(0,0,0,0.3); flex-shrink: 0;">
        <img src="{{ $matchedChar['referenceImage'] }}"
             alt="{{ $matchedChar['name'] }}"
             style="width: 100%; height: 100%; object-fit: cover;">
    </div>
@endif
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Single `narration` field | `speechSegments` array | Phase 1.5 | Enables mixed speech types per scene |
| Simple voiceover toggle | Per-segment `needsLipSync` | Phase 1.5 | Granular lip-sync control |
| Manual speaker entry | Character Bible integration | Phase 4 | Consistent voice/appearance matching |

**Deprecated/outdated:**
- `$scene['narration']` - Still supported for legacy, but `speechSegments` is primary
- `$scene['voiceover']['speechType']` - Replaced by per-segment type in speechSegments array

## Open Questions

1. **Portrait Thumbnail Size for SPCH-07**
   - What we know: Character Bible shows 35x45px thumbnails in sidebar
   - What's unclear: Optimal size for inline display next to speaker name
   - Recommendation: Use 24x30px for inline, or just 👤 indicator without image

2. **Word Count Accuracy**
   - What we know: `str_word_count()` is PHP's standard function
   - What's unclear: Accuracy for non-English text with different word boundaries
   - Recommendation: Use 150 WPM as reasonable estimate, show as "estimated"

## Sources

### Primary (HIGH confidence)
- `modules/AppVideoWizard/app/Services/SpeechSegment.php` - Complete class definition with constants
- `modules/AppVideoWizard/app/Services/SpeechSegmentParser.php` - Parser with Character Bible enrichment
- `modules/AppVideoWizard/resources/views/livewire/modals/scene-text-inspector.blade.php` - Phase 7 modal shell
- `modules/AppVideoWizard/resources/views/livewire/steps/storyboard.blade.php` lines 2498-2650 - Existing segment display pattern
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` lines 1305-1318 - `getInspectorSceneProperty()`

### Secondary (MEDIUM confidence)
- `modules/AppVideoWizard/resources/views/livewire/modals/character-bible.blade.php` - Character Bible access pattern

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Direct code review of SpeechSegment class and existing blade templates
- Architecture: HIGH - Computed property and data access paths verified in source
- Pitfalls: HIGH - Based on actual patterns observed in codebase

**Research date:** 2026-01-23
**Valid until:** 2026-02-23 (stable internal codebase)
