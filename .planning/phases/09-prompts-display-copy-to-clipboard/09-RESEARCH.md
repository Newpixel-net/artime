# Phase 9: Prompts Display + Copy-to-Clipboard - Research

**Researched:** 2026-01-23
**Domain:** Prompt display, clipboard API, cinematography metadata
**Confidence:** HIGH

## Summary

Phase 9 adds the Prompts section to the Scene Text Inspector modal, displaying full image and video prompts with one-click copy functionality. Research confirms all required data structures exist (`imagePrompt`, `videoPrompt`, `type`, `cameraMovement`) and the codebase already has a validated clipboard implementation pattern in the timeline component.

The key insight is that prompt data lives in `$this->multiShotMode['decomposedScenes'][$sceneIndex]['shots']`, not in the basic storyboard structure. The current `getInspectorSceneProperty()` computed property needs to be updated to include this shots array for Phase 9.

**Primary recommendation:** Update the computed property to include `decomposedScenes` data, then display prompts per-shot with copy buttons using the validated `navigator.clipboard.writeText()` pattern.

## Standard Stack

No new dependencies required. All technologies already validated in codebase.

### Core (Already Validated)
| Library | Version | Purpose | Where Validated |
|---------|---------|---------|-----------------|
| Alpine.js | 3.x | Copy button state management, inline x-data | _timeline.blade.php line 2143 |
| Native Clipboard API | N/A | `navigator.clipboard.writeText()` | _timeline.blade.php line 2143 |
| Livewire 3 | 3.x | Computed property for prompt data | VideoWizard.php line 1308 |
| Blade | Latest | Template rendering | scene-text-inspector.blade.php |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Optional chaining | ES2020 | `navigator.clipboard?.writeText()` for graceful degradation | All clipboard calls |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Native Clipboard API | alpine-clipboard plugin | Plugin adds dependency, native is already validated |
| Button text change | Toast notification | Toast requires more complex integration, button change is simpler |

**Installation:** No new packages needed.

## Architecture Patterns

### Data Flow Pattern

```
VideoWizard.php
├── multiShotMode['decomposedScenes'][$index]['shots']
│   └── Each shot contains:
│       ├── imagePrompt (string) - Full image generation prompt
│       ├── videoPrompt (string) - Full video/motion prompt
│       ├── type (string) - Shot type ID: 'close-up', 'medium', 'wide', etc.
│       ├── cameraMovement (string) - Movement ID: 'push-in', 'pan-left', etc.
│       └── narrativeBeat['motionDescription'] (string) - Alternative video prompt source
│
├── getInspectorSceneProperty() [NEEDS UPDATE]
│   └── Currently returns:
│       ├── script: $this->script['scenes'][$index]
│       └── storyboard: $this->storyboard[$index]
│       [MUST ADD]:
│       └── shots: $this->multiShotMode['decomposedScenes'][$index]['shots'] ?? []
```

### Recommended Blade Structure for Prompts Section

```blade
{{-- Prompts Section (Phase 9) - Replace placeholder --}}
<div style="margin-bottom: 1.5rem;">
    <h4 style="...">
        Prompts
        @if(!empty($shots))
            <span style="...">({{ count($shots) }} shots)</span>
        @endif
    </h4>

    @if(!empty($shots))
        @foreach($shots as $shotIndex => $shot)
            {{-- Shot Header with Badges --}}
            <div style="...">
                {{-- Shot number --}}
                <span>Shot {{ $shotIndex + 1 }}</span>

                {{-- Shot Type Badge (PRMT-05) --}}
                <span style="...">{{ $shotTypeBadges[$shot['type']] }}</span>

                {{-- Camera Movement Indicator (PRMT-06) --}}
                <span style="...">{{ $cameraIcons[$shot['cameraMovement']] }}</span>
            </div>

            {{-- Image Prompt (PRMT-01, PRMT-03) --}}
            <div style="...">
                <div style="display: flex; justify-content: space-between;">
                    <span>IMAGE PROMPT</span>
                    <button x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText('...')
                                .then(() => { copied = true; setTimeout(() => copied = false, 2000); })">
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied">Copied!</span>
                    </button>
                </div>
                <div style="white-space: pre-wrap;">{{ $shot['imagePrompt'] }}</div>
            </div>

            {{-- Video Prompt (PRMT-02, PRMT-04) --}}
            <div style="...">
                ...
            </div>
        @endforeach
    @else
        <div style="...">No shots decomposed for this scene</div>
    @endif
</div>
```

### Copy Button Pattern (Validated)

Source: `_timeline.blade.php` line 2142-2144

```blade
<button type="button"
        x-data="{ copied: false }"
        @click="
            navigator.clipboard.writeText($el.closest('[data-prompt]').dataset.prompt)
                .then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                })
                .catch(() => {
                    // Fallback for iOS Safari pre-16.4
                    const ta = document.createElement('textarea');
                    ta.value = $el.closest('[data-prompt]').dataset.prompt;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                })
        "
        style="padding: 0.3rem 0.6rem; background: rgba(139,92,246,0.2);
               border: 1px solid rgba(139,92,246,0.4); border-radius: 0.35rem;
               color: #c4b5fd; font-size: 0.65rem; cursor: pointer;">
    <span x-show="!copied">Copy</span>
    <span x-show="copied" style="color: #10b981;">Copied!</span>
</button>
```

### Anti-Patterns to Avoid
- **Inline prompt text in @click:** Long prompts break HTML. Use data attributes or escape properly with `addslashes()` and JSON encoding.
- **Modifying computed property:** The computed property is read-only. Don't try to update shots from the modal.
- **Missing null checks:** Always use `?? []` for shots array, prompts may not exist for all shots.

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Clipboard access | Custom textarea copy | `navigator.clipboard.writeText()` | Native API is simpler, already validated |
| Copy feedback | Custom notification system | Alpine.js `x-data="{ copied: false }"` | Timeline component pattern works |
| Shot type labels | Custom mapping | `getShotTypeLabel()` helper | Already exists at storyboard.blade.php line 1127 |
| Camera movement icons | Custom icon set | Existing icon mapping in storyboard.blade.php line 1160 | Already defined |

**Key insight:** The storyboard.blade.php already has helper functions for shot type labels and camera movement icons that should be reused.

## Common Pitfalls

### Pitfall 1: Prompt Data Not Available in Inspector
**What goes wrong:** `$this->inspectorScene['storyboard']` doesn't contain shots with prompts
**Why it happens:** The computed property currently only returns `storyboard[$index]` which is scene-level data
**How to avoid:** Update `getInspectorSceneProperty()` to include:
```php
'shots' => $this->multiShotMode['decomposedScenes'][$this->inspectorSceneIndex]['shots'] ?? [],
```
**Warning signs:** Prompts section shows "No shots" when shots exist in multi-shot mode

### Pitfall 2: Long Prompts Breaking HTML
**What goes wrong:** Prompts containing quotes or special characters break the @click handler
**Why it happens:** Prompts are directly interpolated into JavaScript
**How to avoid:** Use data attributes with proper escaping:
```blade
<div data-prompt="{{ json_encode($shot['imagePrompt']) }}">
    <button @click="navigator.clipboard.writeText(JSON.parse($el.closest('[data-prompt]').dataset.prompt))">
```
Or use addslashes() for simple escaping:
```blade
navigator.clipboard.writeText('{{ addslashes($prompt) }}')
```
**Warning signs:** Copy button doesn't work, JavaScript console errors

### Pitfall 3: iOS Safari Clipboard Restrictions
**What goes wrong:** Copy fails silently on older iOS Safari versions
**Why it happens:** Clipboard API requires secure context and user gesture; some iOS versions need execCommand fallback
**How to avoid:** Include execCommand fallback in catch block (see code pattern above)
**Warning signs:** Copy works on desktop but fails on iPhone

### Pitfall 4: Missing Shot Type/Camera Movement Data
**What goes wrong:** Badges show "Unknown" or empty
**Why it happens:** Not all shots have type/cameraMovement set
**How to avoid:** Use fallback values:
```php
$shotType = $shot['type'] ?? 'medium';
$cameraMovement = $shot['cameraMovement'] ?? 'static';
```
**Warning signs:** Empty badges or "undefined" text

## Code Examples

### Example 1: Updated Computed Property (VideoWizard.php)

```php
/**
 * Get scene data for inspector (computed to avoid serialization).
 * Updated for Phase 9 to include shots with prompts.
 */
public function getInspectorSceneProperty(): ?array
{
    if ($this->inspectorSceneIndex === null) {
        return null;
    }

    return [
        'script' => $this->script['scenes'][$this->inspectorSceneIndex] ?? null,
        'storyboard' => $this->storyboard[$this->inspectorSceneIndex] ?? null,
        // Phase 9: Include shots for prompt display
        'shots' => $this->multiShotMode['decomposedScenes'][$this->inspectorSceneIndex]['shots'] ?? [],
    ];
}
```

### Example 2: Shot Type Badge Configuration

From `modules/AppVideoWizard/config/config.php` lines 1598-1680:

```php
// Available shot types with abbreviations
$shotTypes = [
    'extreme-wide' => ['abbrev' => 'EWS', 'name' => 'Extreme Wide Shot'],
    'wide' => ['abbrev' => 'WS', 'name' => 'Wide Shot'],
    'medium-wide' => ['abbrev' => 'MWS', 'name' => 'Medium Wide Shot'],
    'medium' => ['abbrev' => 'MS', 'name' => 'Medium Shot'],
    'medium-close' => ['abbrev' => 'MCU', 'name' => 'Medium Close-Up'],
    'close-up' => ['abbrev' => 'CU', 'name' => 'Close-Up'],
    'extreme-close' => ['abbrev' => 'ECU', 'name' => 'Extreme Close-Up'],
    'over-shoulder' => ['abbrev' => 'OTS', 'name' => 'Over-the-Shoulder'],
    'pov' => ['abbrev' => 'POV', 'name' => 'Point of View'],
    'aerial' => ['abbrev' => 'AERIAL', 'name' => 'Aerial/Drone Shot'],
];
```

### Example 3: Camera Movement Icons

From `storyboard.blade.php` lines 1160-1170:

```php
// Camera movement icon mapping
$cameraIcons = [
    'static' => '',  // No icon for static
    'push-in' => '🔍',
    'pull-out' => '🔭',
    'pan-left' => '⬅️',
    'pan-right' => '➡️',
    'tilt-up' => '⬆️',
    'tilt-down' => '⬇️',
    'tracking' => '🎯',
    'zoom-in' => '🔎',
    'zoom-out' => '🔍',
];
```

### Example 4: Prompt Text Escaping for JavaScript

```blade
{{-- Safe pattern for long prompts --}}
@php
    $imagePromptJson = json_encode($shot['imagePrompt'] ?? '');
@endphp
<div data-prompt="{{ $imagePromptJson }}">
    <button type="button"
            x-data="{ copied: false }"
            @click="
                const prompt = JSON.parse($el.closest('[data-prompt]').dataset.prompt);
                navigator.clipboard.writeText(prompt)
                    .then(() => { copied = true; setTimeout(() => copied = false, 2000); })
            ">
        <span x-show="!copied">Copy</span>
        <span x-show="copied">Copied!</span>
    </button>
</div>
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| execCommand('copy') | navigator.clipboard.writeText() | 2020+ | Modern API with Promise support |
| Plugin libraries | Native browser API | 2020+ | No dependencies needed |
| Toast notifications | Inline button state change | App standard | Simpler, faster feedback |

**Deprecated/outdated:**
- `document.execCommand('copy')` - Deprecated but still needed as fallback for iOS Safari <16.4
- `clipboard.js` library - Unnecessary with native API support

## Open Questions

Things that couldn't be fully resolved:

1. **Video Prompt Fallback Source**
   - What we know: `videoPrompt` exists, also `narrativeBeat.motionDescription` contains similar content
   - What's unclear: Which should be displayed if both exist? Are they always identical?
   - Recommendation: Prefer `videoPrompt`, fallback to `narrativeBeat.motionDescription`

2. **Shots Without Prompts**
   - What we know: Not all shots may have imagePrompt/videoPrompt populated
   - What's unclear: Under what conditions are prompts missing?
   - Recommendation: Show "Prompt not generated" placeholder, don't show copy button

## Sources

### Primary (HIGH confidence)
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` - Computed property structure (lines 1308-1318), shot data structure (lines 18125-18166)
- `modules/AppVideoWizard/resources/views/livewire/steps/partials/_timeline.blade.php` - Validated clipboard pattern (line 2143)
- `modules/AppVideoWizard/resources/views/livewire/modals/shot-preview.blade.php` - Existing prompt display pattern (lines 200-215)
- `modules/AppVideoWizard/config/config.php` - Shot types and camera movements (lines 1598-1754)
- `.planning/research/STACK.md` - Copy-to-clipboard implementation patterns (lines 134-210)

### Secondary (MEDIUM confidence)
- `storyboard.blade.php` - Shot type helper functions and display patterns (lines 1127-1170)
- `scene-text-inspector.blade.php` - Current modal structure with Phase 9 placeholder (lines 293-301)

### Tertiary (LOW confidence)
- None - all findings verified with codebase

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All patterns validated in existing codebase
- Architecture: HIGH - Data structures confirmed via grep/read
- Pitfalls: HIGH - Based on actual codebase patterns and existing research

**Research date:** 2026-01-23
**Valid until:** 2026-02-23 (stable patterns, no external dependencies)

---

## Implementation Checklist for Planner

1. [ ] Update `getInspectorSceneProperty()` to include shots array
2. [ ] Replace Phase 9 placeholder in scene-text-inspector.blade.php
3. [ ] Add shot type badge display (use existing helper or config)
4. [ ] Add camera movement indicator (use existing icon mapping)
5. [ ] Add image prompt section with copy button
6. [ ] Add video prompt section with copy button
7. [ ] Handle empty states (no shots, no prompts)
8. [ ] Test copy on desktop browsers
9. [ ] Test copy on iOS Safari (verify fallback works)
