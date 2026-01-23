# Technology Stack: Scene Text Inspector Modal

**Project:** Video Wizard - Scene Text Inspector Modal
**Researched:** 2026-01-23
**Confidence:** HIGH

## Executive Summary

The Scene Text Inspector modal requires minimal new stack additions since Laravel 10 + Livewire 3 + Alpine.js are already validated in the existing app. The focus is on established patterns for copy-to-clipboard, scrollable content, and text display within the existing modal framework.

## Core Framework (Validated)

| Technology | Version | Status | Notes |
|------------|---------|--------|-------|
| Laravel | 10.x | ✅ In Use | Backend framework |
| Livewire | 3.x | ✅ In Use | Component framework |
| Alpine.js | 3.x | ✅ In Use | Shipped with Livewire 3 |
| Blade | Latest | ✅ In Use | Template engine |

**Rationale:** All core technologies already validated. No new dependencies required.

---

## Modal Patterns

### Existing Modal Pattern (Validated)

The app uses a consistent modal pattern across character-bible, location-bible, and stock-browser modals:

```blade
@if($showSceneTextInspectorModal ?? false)
<div class="vw-modal-overlay"
     wire:key="scene-text-inspector-modal-{{ $sceneIndex }}"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000; padding: 0.5rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99));
                border: 1px solid rgba(139,92,246,0.3);
                border-radius: 0.75rem;
                width: 100%; max-width: 800px; max-height: 96vh;
                display: flex; flex-direction: column; overflow: hidden;">
```

**Key characteristics:**
- **Fixed positioning:** `position: fixed; inset: 0` for overlay
- **Flexbox centering:** `display: flex; align-items: center; justify-content: center`
- **Z-index:** `1000` for overlay layer
- **Max constraints:** `max-width: 800px; max-height: 96vh`
- **Flex column:** `flex-direction: column` for header/content/footer sections
- **Overflow control:** `overflow: hidden` on container to manage scrolling

### Modal Structure Pattern

**Three-section layout:**

```blade
{{-- Header (Fixed) --}}
<div style="padding: 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex; justify-content: space-between; align-items: center;
            flex-shrink: 0;">
    <div>
        <h3>Modal Title</h3>
        <p>Subtitle</p>
    </div>
    <button type="button" wire:click="closeModal">×</button>
</div>

{{-- Scrollable Content --}}
<div style="flex: 1; overflow-y: auto; padding: 0.75rem;">
    <!-- Content here scrolls independently -->
</div>

{{-- Footer (Fixed) --}}
<div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1);
            display: flex; justify-content: space-between; flex-shrink: 0;">
    <!-- Actions -->
</div>
```

**Pattern benefits:**
- Header and footer remain visible during scroll
- `flex: 1` on content area takes remaining space
- `overflow-y: auto` enables vertical scrolling only
- `flex-shrink: 0` prevents header/footer from shrinking

**Source:** Validated in character-bible.blade.php, location-bible.blade.php, edit-prompt.blade.php

---

## Scrollable Content Handling

### Pattern: Flex-Based Scrollable Container

For long text content (scene speeches, prompts), use the established pattern:

```blade
<div style="flex: 1; overflow-y: auto; padding: 0.75rem;">
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        @foreach($speeches as $speech)
            <div style="background: rgba(255,255,255,0.05);
                       border-radius: 0.5rem; padding: 1rem;">
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.6);
                           margin-bottom: 0.5rem;">
                    {{ $speech['type'] }}
                </div>
                <div style="white-space: pre-wrap; line-height: 1.6;">
                    {{ $speech['text'] }}
                </div>
            </div>
        @endforeach
    </div>
</div>
```

**Key CSS properties:**
- **`flex: 1`** - Content area takes all available vertical space
- **`overflow-y: auto`** - Enables vertical scrolling when content exceeds height
- **`white-space: pre-wrap`** - Preserves line breaks while allowing text wrapping
- **`line-height: 1.6`** - Improves readability for long text blocks

### Mobile Responsiveness

The existing pattern handles mobile well:
- `max-width: 800px` constrains desktop size
- `width: 100%` allows mobile expansion
- `padding: 0.5rem` on overlay provides breathing room
- `max-height: 96vh` prevents full-screen overflow

**Source:** Pattern validated across multiple modals in app

---

## Copy-to-Clipboard Implementation

### Recommended Pattern: Native Navigator API with Alpine.js

The app already uses `navigator.clipboard.writeText()` in `_timeline.blade.php` line 2142-2144:

```javascript
// Existing validated pattern
navigator.clipboard?.writeText(chapters.trim());
this.showNotification('{{ __("Chapters copied to clipboard!") }}');
```

### Implementation for Scene Text Inspector

**Pattern 1: Simple Copy Button (Recommended)**

```blade
<button type="button"
        x-data="{ copied: false }"
        @click="
            navigator.clipboard.writeText('{{ addslashes($prompt) }}')
                .then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                })
        "
        style="padding: 0.3rem 0.6rem; background: rgba(139,92,246,0.2);
               border: 1px solid rgba(139,92,246,0.4); border-radius: 0.35rem;
               color: #c4b5fd; font-size: 0.65rem; cursor: pointer;">
    <span x-show="!copied">📋 Copy</span>
    <span x-show="copied" style="color: #10b981;">✓ Copied!</span>
</button>
```

**Pattern 2: Copy with Error Handling**

For production robustness:

```blade
<button type="button"
        x-data="{ copied: false, failed: false }"
        @click="
            navigator.clipboard.writeText('{{ addslashes($text) }}')
                .then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                })
                .catch(() => {
                    failed = true;
                    setTimeout(() => failed = false, 2000);
                })
        "
        style="...">
    <span x-show="!copied && !failed">📋 Copy</span>
    <span x-show="copied">✓ Copied!</span>
    <span x-show="failed">⚠️ Failed</span>
</button>
```

**Pattern 3: Copy with Livewire Notification**

For consistency with app's notification system:

```blade
<button type="button"
        @click="
            navigator.clipboard.writeText('{{ addslashes($prompt) }}')
                .then(() => $wire.dispatch('notify', {
                    message: 'Prompt copied to clipboard',
                    type: 'success'
                }))
        "
        style="...">
    📋 Copy Prompt
</button>
```

### Why Native API vs Plugin

**Decision:** Use native `navigator.clipboard.writeText()` instead of alpine-clipboard plugin.

**Rationale:**
1. **Already validated** - Timeline component uses native API successfully
2. **No new dependencies** - Keeps bundle size minimal
3. **Browser support** - 96%+ support for navigator.clipboard in 2026
4. **Simplicity** - One-liner vs plugin setup
5. **Security** - Requires HTTPS (already in place for production)

**Fallback:** Optional `?.` operator provides graceful degradation

**Sources:**
- [Alpine.js Copy to Clipboard Tutorial](https://codecourse.com/articles/easily-copy-text-to-the-clipboard-with-alpinejs)
- [Web.dev Clipboard Patterns](https://web.dev/patterns/clipboard/copy-text)
- [Alpine.js Extending Guide](https://alpinejs.dev/advanced/extending)

---

## Long Text Display Patterns

### Pattern: Pre-formatted Text with Wrapping

For displaying scene speeches and prompts:

```blade
<div style="white-space: pre-wrap;
           word-wrap: break-word;
           overflow-wrap: break-word;
           line-height: 1.6;
           color: rgba(255,255,255,0.9);
           font-size: 0.8rem;">
    {{ $sceneText }}
</div>
```

**CSS Properties:**
- **`white-space: pre-wrap`** - Preserves line breaks and spaces, allows wrapping
- **`word-wrap: break-word`** - Breaks long words if needed
- **`overflow-wrap: break-word`** - Modern equivalent for better browser support
- **`line-height: 1.6`** - Increases readability for paragraphs

### Pattern: Syntax Highlighting for Prompts

For visual prompts, optionally highlight structure:

```blade
<div style="background: rgba(0,0,0,0.3);
           border-left: 3px solid #8b5cf6;
           padding: 0.75rem;
           border-radius: 0.35rem;">
    <div style="font-family: 'Courier New', monospace;
               font-size: 0.75rem;
               line-height: 1.7;
               color: rgba(255,255,255,0.95);">
        {{ $imagePrompt }}
    </div>
</div>
```

**Visual treatment:**
- Monospace font for prompt text clarity
- Darker background to distinguish from UI
- Left border accent for visual hierarchy
- Increased line-height for readability

### Pattern: Collapsible Long Text (Optional)

For very long prompts, use Alpine.js collapse:

```blade
<div x-data="{ expanded: false }">
    <div style="max-height: 100px; overflow: hidden;"
         :style="expanded && 'max-height: none'">
        {{ $longPrompt }}
    </div>
    <button type="button"
            @click="expanded = !expanded"
            x-show="true"
            style="margin-top: 0.5rem; color: #8b5cf6; font-size: 0.7rem;">
        <span x-show="!expanded">▼ Show More</span>
        <span x-show="expanded">▲ Show Less</span>
    </button>
</div>
```

**Source:** Pattern adapted from existing expandable sections in character-bible DNA section

---

## Component State Management

### Pattern: Livewire Wire:click for Open/Close

Consistent with existing modals:

```php
// VideoWizard.php component
public bool $showSceneTextInspectorModal = false;
public int $inspectedSceneIndex = 0;

public function openSceneTextInspector(int $sceneIndex)
{
    $this->inspectedSceneIndex = $sceneIndex;
    $this->showSceneTextInspectorModal = true;
}

public function closeSceneTextInspectorModal()
{
    $this->showSceneTextInspectorModal = false;
}
```

```blade
{{-- Trigger button in scene card --}}
<button type="button"
        wire:click="openSceneTextInspector({{ $sceneIndex }})"
        style="...">
    📄 Inspect Text
</button>

{{-- Modal --}}
@if($showSceneTextInspectorModal)
<div class="vw-modal-overlay">
    <!-- Modal content -->
    <button type="button"
            wire:click="closeSceneTextInspectorModal">
        Close
    </button>
</div>
@endif
```

**Why wire:click instead of Alpine:**
- Maintains component state on Livewire side
- Consistent with other modal patterns in app
- Simplifies testing and debugging
- No state sync issues between Alpine/Livewire

**Source:** Validated pattern from character-bible, location-bible, stock-browser modals

---

## CSS Conventions

### Existing vw-* Prefix Pattern

The app uses `vw-*` prefixed classes for Video Wizard components:

```css
.vw-modal-overlay { /* Fixed overlay */ }
.vw-modal { /* Modal container */ }
.vw-modal-close { /* Close button */ }
```

**For Scene Text Inspector:**

```css
.vw-text-inspector-section { /* Text section container */ }
.vw-text-inspector-speech { /* Individual speech block */ }
.vw-text-inspector-prompt { /* Prompt display */ }
.vw-copy-btn { /* Reusable copy button style */ }
```

**Inline vs. Class decision:**
- **Inline styles:** Preferred for one-off modal styles (matches existing pattern)
- **Classes:** Use for frequently repeated elements or complex hover states
- **Hybrid approach:** Most existing modals use primarily inline with minimal classes

**Source:** Pattern observed across all existing modal Blade templates

---

## Accessibility Considerations

### Keyboard Navigation

```blade
{{-- Close on Escape key --}}
<div @keydown.escape.window="$wire.closeSceneTextInspectorModal()">

{{-- Focus trap in modal --}}
<button type="button"
        x-ref="firstElement"
        @keydown.tab.shift.prevent="$refs.lastElement.focus()">
    First element
</button>

<button type="button"
        x-ref="lastElement"
        @keydown.tab.prevent="$refs.firstElement.focus()">
    Last element
</button>
```

### ARIA Attributes

```blade
<div role="dialog"
     aria-modal="true"
     aria-labelledby="modal-title"
     aria-describedby="modal-description">
    <h3 id="modal-title">Scene Text Inspector</h3>
    <p id="modal-description">View all text content for this scene</p>
</div>
```

**Note:** Existing modals have minimal accessibility attributes. This is optional enhancement.

---

## Performance Considerations

### Text Rendering Optimization

For scenes with very long text content:

```blade
{{-- Use wire:key to prevent unnecessary re-renders --}}
@foreach($speeches as $index => $speech)
    <div wire:key="speech-{{ $sceneIndex }}-{{ $index }}">
        {{ $speech['text'] }}
    </div>
@endforeach

{{-- Lazy load prompts if not initially visible --}}
<div x-data="{ show: false }" x-intersect="show = true">
    <div x-show="show" x-transition>
        {{ $imagePrompt }}
    </div>
</div>
```

**Optimization strategies:**
1. **wire:key** prevents Livewire DOM diffing issues with lists
2. **x-intersect** lazy loads content below the fold
3. **x-transition** smooths appearance for better UX

**Source:** Livewire 3 performance best practices

---

## Installation & Setup

### No New Dependencies Required

All technologies already in place:

```bash
# Verify existing versions (informational only)
composer show laravel/framework  # Should show 10.x
composer show livewire/livewire   # Should show 3.x
```

### Alpine.js Availability

Alpine.js is bundled with Livewire 3, no separate installation needed:

```blade
{{-- Alpine works out of the box in Livewire 3 components --}}
<div x-data="{ open: false }">
    <!-- Alpine functionality available -->
</div>
```

**Source:** [Livewire 3 Alpine Integration Docs](https://livewire.laravel.com/docs/3.x/alpine)

---

## Implementation Checklist

**For Scene Text Inspector Modal:**

- [ ] Create `@if($showSceneTextInspectorModal)` block in Blade
- [ ] Add Livewire properties: `$showSceneTextInspectorModal`, `$inspectedSceneIndex`
- [ ] Add Livewire methods: `openSceneTextInspector()`, `closeSceneTextInspectorModal()`
- [ ] Implement three-section modal structure (header, scrollable content, footer)
- [ ] Add copy-to-clipboard buttons using `navigator.clipboard.writeText()`
- [ ] Style text display with `white-space: pre-wrap` and appropriate line-height
- [ ] Test scrolling behavior with long content
- [ ] Test copy functionality across different text types
- [ ] Add keyboard escape handler: `@keydown.escape.window`
- [ ] Test mobile responsiveness (max-height, padding)

---

## Alternative Patterns Considered

### Pattern: Alpine Clipboard Plugin

**Considered:** [alpine-clipboard plugin](https://github.com/ryangjchandler/alpine-clipboard)

**Rejected because:**
- Adds unnecessary dependency
- Native API already validated in timeline component
- Plugin overkill for simple copy operation
- Increases bundle size

**When to reconsider:** If need bulk copy operations or copy from non-text sources (canvas, images)

### Pattern: Wire Elements Modal Package

**Considered:** [Wire Elements Modal](https://wire-elements.dev/docs/getting-started/modal-component)

**Rejected because:**
- App uses custom modal pattern consistently
- Package adds complexity for simple use case
- Existing pattern works well and is understood by team
- Migration would require refactoring all modals

**When to reconsider:** If building complex multi-step modals or nested modal flows

**Source:** [Livewire 3 Modals Discussion](https://laracasts.com/discuss/channels/livewire/livewire-v3-modals-slideovers)

---

## Browser Compatibility

### Clipboard API Support

| Feature | Chrome | Firefox | Safari | Edge |
|---------|--------|---------|--------|------|
| navigator.clipboard | 66+ | 63+ | 13.1+ | 79+ |
| writeText() | 66+ | 63+ | 13.1+ | 79+ |

**Coverage:** 96%+ of users globally in 2026

**Fallback strategy:** Optional chaining `?.` prevents errors in unsupported browsers

**Source:** [Can I Use - Async Clipboard API](https://caniuse.com/async-clipboard)

---

## Testing Recommendations

### Manual Testing Scenarios

1. **Copy functionality:**
   - Click copy button, paste in text editor
   - Test with special characters, line breaks
   - Test with very long prompts (>5000 chars)

2. **Scrolling:**
   - Scene with 1 speech vs. 10+ speeches
   - Verify header/footer stay fixed
   - Test smooth scrolling on mobile

3. **Modal lifecycle:**
   - Open modal from scene card
   - Close with X button
   - Close with Escape key
   - Close by clicking overlay (if implemented)

4. **Responsive behavior:**
   - Test on mobile viewport (375px width)
   - Test on tablet (768px width)
   - Test on desktop (1920px width)

### Automated Testing (Optional)

```php
// Livewire component test
public function test_can_open_scene_text_inspector()
{
    Livewire::test(VideoWizard::class)
        ->call('openSceneTextInspector', 0)
        ->assertSet('showSceneTextInspectorModal', true)
        ->assertSet('inspectedSceneIndex', 0);
}

public function test_can_close_scene_text_inspector()
{
    Livewire::test(VideoWizard::class)
        ->set('showSceneTextInspectorModal', true)
        ->call('closeSceneTextInspectorModal')
        ->assertSet('showSceneTextInspectorModal', false);
}
```

---

## Summary

**Stack Decision:** Use existing Laravel 10 + Livewire 3 + Alpine.js stack with native browser APIs.

**Key Patterns:**
1. **Modal structure:** Three-section flex layout with fixed header/footer, scrollable content
2. **Copy-to-clipboard:** Native `navigator.clipboard.writeText()` with Alpine.js state
3. **Long text display:** `white-space: pre-wrap` with `line-height: 1.6`
4. **State management:** Livewire `wire:click` for modal open/close
5. **Styling:** Inline styles following existing vw-* modal conventions

**No new dependencies required.** All patterns validated in existing codebase.

---

## Sources

### Documentation
- [Livewire 3 Alpine Integration](https://livewire.laravel.com/docs/3.x/alpine)
- [Alpine.js Official Documentation](https://alpinejs.dev/)
- [Web.dev Clipboard API Guide](https://web.dev/patterns/clipboard/copy-text)

### Tutorials & Patterns
- [Copy to Clipboard with Alpine.js](https://codecourse.com/articles/easily-copy-text-to-the-clipboard-with-alpinejs)
- [Reusable Copy Button in Alpine.js](https://rezaulhreza.co.uk/blog/how-to-make-a-reusable-click-to-copy-button-in-alpinejs-in-laravel)
- [Creating Dynamic Modals in Livewire 3.0](https://wontonee.com/creating-dynamic-modals-in-livewire-3-0/)
- [Livewire 3 CRUD with Form Objects and Modal](https://laraveldaily.com/post/livewire-3-crud-form-objects-modal-wire-elements)

### Community Resources
- [Alpine.js Copy to Clipboard Examples](https://devdojo.com/question/alpinejs-copy-to-clipboard)
- [Wire Elements Modal Package](https://wire-elements.dev/docs/getting-started/modal-component)
- [Laracasts Livewire V3 Modals Discussion](https://laracasts.com/discuss/channels/livewire/livewire-v3-modals-slideovers)

### Browser Compatibility
- [Can I Use - Async Clipboard API](https://caniuse.com/async-clipboard)
