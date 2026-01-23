# Phase 10: Mobile Responsiveness + Polish - Research

**Researched:** 2026-01-23
**Domain:** Mobile responsiveness, modal adaptation, iOS Safari scroll locking, touch interactions
**Confidence:** HIGH

## Summary

Phase 10 transforms the modals (Character Bible, Location Bible, and the modal containing them) to provide an excellent mobile experience. Research confirms that:

1. **768px breakpoint is the established standard** for mobile/tablet distinction (used in existing project-manager.blade.php)
2. **iOS Safari scroll locking requires specific CSS patterns** - body scroll prevention differs from desktop due to address bar behavior
3. **Touch interactions require 48x48px minimum hit areas** with bottom-positioned interactive elements for thumb accessibility
4. **Existing codebase has patterns** for responsive modals (project-manager, shot-face-correction already implement 768px)
5. **Modal overlay and close buttons** must position in thumb zones (bottom-right for one-handed operation)

The implementation strategy: Use existing breakpoint pattern from project-manager.blade.php, add iOS scroll locking via position:fixed + overflow control, ensure all buttons meet 48px minimum hit area, position close/action buttons in thumb zones.

**Primary recommendation:** Implement responsive breakpoint at 768px (fullscreen below, centered box above), add iOS scroll lock via CSS + JavaScript, position close button in bottom-right thumb zone, ensure all interactive elements have 48px+ hit areas.

## Standard Stack

All technologies already validated in codebase. No new dependencies needed.

### Core (Already in Use)
| Library | Version | Purpose | Where Validated |
|---------|---------|---------|-----------------|
| CSS Media Queries | CSS3 | 768px breakpoint responsive design | project-manager.blade.php lines 490-520 |
| Alpine.js | 3.x | Touch interaction state management | multi-shot.blade.php, character-bible.blade.php |
| CSS overflow: hidden | Native | Body scroll prevention (base) | character-bible.blade.php line 5 |
| position: fixed | Native | Modal overlay positioning | character-bible.blade.php line 3 |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| CSS overscroll-behavior | CSS UI Extension | Prevent scroll chaining (iOS Safari) | Modal overlay, when overflow:hidden insufficient |
| -webkit-overflow-scrolling | WebKit vendor prefix | Momentum scrolling on iOS (legacy) | Modal content on iPad/iPhone (deprecated but supported) |
| touch-action | CSS UI | Fine-grained touch control | Copy buttons, close buttons |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| CSS-only overflow solution | body-scroll-lock JavaScript library | Library adds 2KB dependency; CSS-only is simpler for this use case |
| 768px breakpoint | Custom breakpoints (640px, 1024px) | 768px is industry standard, reduces cognitive load |
| Fixed positioning for modals | Absolute positioning + JavaScript scroll tracking | Fixed is simpler, already used in existing modals |

**Installation:** No new packages needed. Use CSS properties already supported in all target browsers.

## Architecture Patterns

### 1. Responsive Modal Breakpoint Pattern

Use 768px as standard breakpoint for mobile/tablet distinction (matches existing codebase):

```css
/* Desktop: max-width centered box */
@media (min-width: 769px) {
    .vw-modal {
        max-width: 880px;
        width: calc(100% - 1rem);
        max-height: 96vh;
    }
}

/* Mobile: fullscreen with safe padding */
@media (max-width: 768px) {
    .vw-modal {
        width: 100%;
        height: 100%;
        max-width: none;
        max-height: 100vh;
        border-radius: 0;
    }

    .vw-modal-overlay {
        padding: 0;
    }
}
```

**Source:** Existing pattern in project-manager.blade.php lines 490-520

### 2. iOS Safari Scroll Lock Implementation

The challenge: iOS Safari doesn't respect overflow:hidden on body during modal open due to address bar expansion affecting viewport height. Solution combines CSS and JavaScript:

```javascript
// In Livewire component or Alpine.js
function openModal() {
    // 1. Get current scroll position before locking
    const scrollY = window.scrollY;

    // 2. Set body to fixed position
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollY}px`;
    document.body.style.width = '100%';

    // 3. Add CSS classes for additional safety
    document.body.classList.add('modal-open');
}

function closeModal() {
    // 1. Remove fixed positioning
    const scrollY = Math.abs(parseInt(document.body.style.top, 10));
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';

    // 2. Remove CSS class
    document.body.classList.remove('modal-open');

    // 3. Restore scroll position
    window.scrollTo(0, scrollY);
}
```

```css
body.modal-open {
    position: fixed;
    width: 100%;
    overflow: hidden;
    overscroll-behavior: none;
    /* Prevent scroll chaining */
}

/* Additional safety for modal content scrolling */
.vw-modal-content {
    -webkit-overflow-scrolling: touch;
    overflow-y: auto;
}
```

**Why this works:**
- `position: fixed` + `top: -scrollY` prevents the browser from scrolling on the fixed body
- `overscroll-behavior: none` prevents rubber-band scrolling in Safari
- `-webkit-overflow-scrolling: touch` enables momentum scrolling within the modal itself
- Storing and restoring `scrollY` preserves user's scroll position when modal closes

**Sources:**
- [I fixed a decade-long iOS Safari problem](https://stripearmy.medium.com/i-fixed-a-decade-long-ios-safari-problem-0d85f76caec0)
- [Locking body scroll for modals on iOS](https://www.jayfreestone.com/writing/locking-body-scroll-ios/)
- [How To Prevent Scrolling The Page On iOS Safari 15](https://pqina.nl/blog/how-to-prevent-scrolling-the-page-on-ios-safari)

### 3. Thumb Zone Positioning Pattern

Mobile users hold phones with thumb reaching bottom ~40% of screen naturally. Position critical buttons there:

```blade
{{-- Mobile: Close button in bottom-right thumb zone --}}
@if(is_mobile)
    {{-- Footer positioning - easier thumb access --}}
    <div style="position: sticky; bottom: 0; right: 0; padding: 1rem; display: flex; justify-content: flex-end;">
        <button type="button"
                wire:click="closeModal"
                style="min-width: 48px; min-height: 48px; padding: 0.5rem 0.75rem; background: linear-gradient(...); border: none; border-radius: 0.35rem; color: white; cursor: pointer;">
            ✕ Close
        </button>
    </div>
@endif
```

**Positioning rules:**
- **Natural zone:** Bottom 20-40% of screen (most comfortable one-handed reach)
- **Reach zone:** Middle 40-60% of screen (requires slight hand stretch)
- **Hard zone:** Top 20% (requires both hands or finger stretch)

For modals:
- Close button: bottom-right (natural reach)
- Primary action (Save/Done): bottom-right or center-bottom
- Destructive buttons (Delete): top-right or clearly separated
- Secondary navigation: bottom-left or top-bar

**Source:** [Mobile-First UX: Designing for Thumbs](https://dev.to/prateekshaweb/mobile-first-ux-designing-for-thumbs-not-just-screens-339m)

### 4. Touch Button Hit Area Pattern

All interactive elements must be ≥48x48px for safe touch targeting:

```css
/* Enforce minimum hit area */
button, [role="button"], input[type="button"], a.button {
    min-width: 44px;
    min-height: 44px;
    padding: 0.5rem 0.75rem;
    /* Actual visual size may be smaller, but padding expands touch target */
}

/* Safe spacing between buttons */
.button-group {
    display: flex;
    gap: 0.5rem;
    /* 0.5rem = 8px spacing is minimum; 1rem preferred for touch */
}

@media (max-width: 768px) {
    button, [role="button"] {
        min-width: 48px;
        min-height: 48px;
        padding: 0.75rem 1rem;
        /* Increase hit area on mobile */
    }

    .button-group {
        gap: 0.75rem;
        /* Wider spacing on mobile to prevent mis-taps */
    }
}
```

**Source:** [Touch-action - CSS](https://developer.mozilla.org/en-US/docs/Web/CSS/touch-action)

### 5. Copy Button Touch Optimization

Copy buttons require special handling on touch devices - avoid :hover state:

```blade
<button type="button"
        x-data="{ copied: false, isTouching: false }"
        @touchstart="isTouching = true"
        @touchend="isTouching = false"
        @click="
            navigator.clipboard.writeText('{{ addslashes($prompt) }}')
                .then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                })
        "
        style="min-width: 48px; min-height: 48px;
               padding: 0.5rem 0.75rem;
               background: {{ !isTouching ? 'rgba(139,92,246,0.2)' : 'rgba(139,92,246,0.4)' }};
               border: 1px solid rgba(139,92,246,0.4);
               border-radius: 0.35rem;
               color: #c4b5fd;
               font-size: 0.65rem;
               cursor: pointer;
               transition: all 0.15s ease;">
    <span x-show="!copied">Copy</span>
    <span x-show="copied" style="color: #10b981;">Copied!</span>
</button>
```

This uses `@touchstart`/`@touchend` to provide visual feedback on touch without relying on :hover.

### 6. Collapsible Sections on Mobile

Scrollable content (character list, location list) needs better sizing on mobile:

```css
@media (max-width: 768px) {
    /* Character list sidebar - full height with scroll */
    .character-list {
        max-height: calc(100vh - 300px);
        /* Leave room for header, footer, margins */
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        /* Momentum scrolling on iOS */
    }

    /* Editor panel - stacked below list on mobile */
    .character-editor {
        margin-top: 1rem;
        border-top: 1px solid rgba(255,255,255,0.1);
        padding-top: 1rem;
    }
}

@media (min-width: 769px) {
    /* Side-by-side layout on desktop */
    .modal-content {
        display: flex;
        gap: 0.75rem;
    }

    .character-list {
        width: 190px;
        flex-shrink: 0;
        border-right: 1px solid rgba(255,255,255,0.1);
    }

    .character-editor {
        flex: 1;
        border-top: none;
        margin-top: 0;
    }
}
```

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Scroll locking on mobile | Custom position:absolute tracking | CSS position:fixed + JavaScript scroll restoration | Existing codebase patterns, handles iOS quirks |
| Hit area enforcement | Larger visual buttons | CSS padding + min-width/height | Separates visual from touchable size, cleaner code |
| Responsive breakpoints | Multiple custom breakpoints | 768px industry standard (like Bootstrap) | Reduces maintenance, matches existing code |
| Touch feedback | :hover states | Alpine.js x-data with @touch events | :hover doesn't work on touch devices |
| Modal viewport height | 100vh hardcode | calc(100vh - fixed elements) | 100vh includes address bar on mobile, causes overflow |

**Key insight:** The codebase already uses 768px breakpoint (project-manager.blade.php). Consistency across all modals reduces technical debt.

## Common Pitfalls

### Pitfall 1: Using `100vh` on Mobile
**What goes wrong:** Modal becomes taller than screen on mobile, doesn't account for address bar
**Why it happens:** iOS Safari includes address bar in 100vh calculation; it expands/contracts with scroll
**How to avoid:**
```css
@media (max-width: 768px) {
    .vw-modal {
        /* Use max-height with fallback */
        max-height: 100vh;
        max-height: 100dvh; /* Dynamic viewport height - excludes address bar */
        overflow: auto;
    }
}
```
Use `100dvh` (dynamic viewport height) instead of `100vh` - supported in modern iOS Safari 15+
**Warning signs:** Modal gets cut off on iPhone when user scrolls and address bar retracts

### Pitfall 2: Overflow:Hidden on Body Not Working on iOS
**What goes wrong:** Background page scrolls when modal is open on iPhone
**Why it happens:** iOS Safari treats fixed body differently - address bar expansion causes special behavior
**How to avoid:** Combine CSS + JavaScript approach shown in Architecture section above
**Warning signs:** User scrolls background while modal is open on mobile

### Pitfall 3: Close Button Too Small or In Wrong Zone
**What goes wrong:** Users tap close button repeatedly, fat-finger the wrong element
**Why it happens:** 44px buttons are minimum; less than 48px causes mis-taps. Top-right is hard to reach one-handed
**How to avoid:**
```css
/* Minimum 48x48px */
button {
    min-width: 48px;
    min-height: 48px;
}

/* Position in bottom-right or bottom-center for mobile */
@media (max-width: 768px) {
    .close-button {
        position: sticky;
        bottom: 1rem;
        right: 1rem;
    }
}
```
**Warning signs:** Users take multiple attempts to close modal on mobile

### Pitfall 4: Hover States on Touch Devices
**What goes wrong:** Copy button shows "Copied!" state initially because :hover persists on touch
**Why it happens:** Touch triggers :hover state on older implementations; no unambiguous "unhover" event
**How to avoid:** Use `@touchstart`/`@touchend` in Alpine.js, avoid :hover for touch-interactive elements
```javascript
x-data="{ touched: false }"
@touchstart="touched = true"
@touchend="touched = false"
```
**Warning signs:** Copy button shows wrong text on first touch

### Pitfall 5: Left-Right Swipe Gestures Breaking Modals
**What goes wrong:** Safari's back-swipe gesture interferes with horizontal scrolling in modal
**Why it happens:** iOS Safari reserves edge pan gestures for navigation
**How to avoid:** Use `touch-action: manipulation` on swipeable content, or lock horizontal touch on modal
```css
.vw-modal {
    touch-action: manipulation;
    /* Disables double-tap zoom but allows pinch, swipe is disabled */
}
```
**Warning signs:** Left-edge swipe in modal goes back to previous page instead of functioning normally

### Pitfall 6: Font Size Too Small on Mobile
**What goes wrong:** Text becomes unreadable on small screens, modal title/labels are tiny
**Why it happens:** Desktop CSS has `font-size: 0.65rem` which is too small for mobile
**How to avoid:**
```css
@media (max-width: 768px) {
    .vw-modal {
        font-size: 0.875rem;
        /* Slightly larger base font on mobile */
    }

    /* Specific size increases */
    h3 { font-size: 1.125rem; } /* 18px instead of 16px */
    label { font-size: 0.75rem; } /* 12px instead of 10px */
    button { font-size: 0.875rem; } /* 14px instead of 10px */
}
```
**Warning signs:** Users zoom in to read modal text on mobile

### Pitfall 7: Input Fields Too Narrow
**What goes wrong:** Mobile keyboard appears and covers half the modal on small screens
**Why it happens:** Input fields inherit fixed width, keyboard has no space
**How to avoid:**
```css
@media (max-width: 768px) {
    input, textarea, select {
        width: 100%;
        /* Full width on mobile */
    }

    .modal-content {
        padding-bottom: 1rem;
        /* Extra space at bottom for keyboard */
    }
}
```
**Warning signs:** Keyboard obscures inputs or submit buttons

## Code Examples

### Example 1: Character Bible Modal - Mobile Responsive Structure

Source: character-bible.blade.php (existing) with mobile adaptations:

```blade
{{-- Character Bible Modal --}}
@if($showCharacterBibleModal ?? false)
<div class="vw-modal-overlay"
     x-data="{
         modalOpen: true,
         scrollY: 0,
         isMobile: window.innerWidth <= 768
     }"
     @if(true) {{-- This opens modal --}}
     x-init="
         scrollY = window.scrollY;
         document.body.style.position = 'fixed';
         document.body.style.top = `-${scrollY}px`;
         document.body.style.width = '100%';
         document.body.classList.add('modal-open');
     "
     @close-modal.window="
         document.body.style.position = '';
         document.body.style.top = '';
         document.body.style.width = '';
         document.body.classList.remove('modal-open');
         window.scrollTo(0, scrollY);
     "
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">

    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.75rem; display: flex; flex-direction: column; overflow: hidden;">

        {{-- Header: Sticky on mobile --}}
        <div style="padding: 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; position: sticky; top: 0; z-index: 10;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1rem; font-weight: 600;">👤 {{ __('Character Bible') }}</h3>
                <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('Define consistent character appearances') }}</p>
            </div>
            <button type="button"
                    wire:click="closeCharacterBibleModal"
                    style="min-width: 48px; min-height: 48px; padding: 0.25rem; background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                ×
            </button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 0.75rem; display: flex; gap: 0.75rem;">
            {{-- Character List --}}
            <div style="width: 190px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 0.75rem; max-height: calc(100vh - 200px); overflow-y: auto; -webkit-overflow-scrolling: touch;">
                {{-- Character items --}}
            </div>

            {{-- Character Editor --}}
            <div style="flex: 1; display: flex; flex-direction: column; overflow-y: auto;">
                {{-- Editor content --}}
            </div>
        </div>

        {{-- Footer: Sticky on mobile, full-width buttons --}}
        <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; position: sticky; bottom: 0; background: rgba(0,0,0,0.2); flex-wrap: wrap; gap: 0.5rem;">
            <label style="display: flex; align-items: center; gap: 0.4rem; color: rgba(255,255,255,0.7); font-size: 0.7rem; cursor: pointer;">
                <input type="checkbox" wire:model.live="sceneMemory.characterBible.enabled" style="accent-color: #8b5cf6; min-width: 18px; min-height: 18px;">
                {{ __('Enable') }}
            </label>
            <button type="button"
                    wire:click="closeCharacterBibleModal"
                    style="min-width: 100px; min-height: 48px; padding: 0.4rem 0.9rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.35rem; color: white; font-weight: 600; cursor: pointer; font-size: 0.75rem; flex: 1;">
                {{ __('Save & Close') }}
            </button>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .vw-modal-overlay {
        padding: 0 !important;
        align-items: flex-end !important;
        /* Push modal to bottom on mobile */
    }

    .vw-modal {
        width: 100% !important;
        max-width: none !important;
        height: 100% !important;
        max-height: 100dvh !important;
        border-radius: 0 !important;
        /* Full-screen modal on mobile */
    }

    {{-- Stack left panel and right panel vertically --}}
    .vw-modal > div:nth-child(3) {
        flex-direction: column !important;
    }

    {{-- Character list sidebar becomes full-width section --}}
    .vw-modal > div:nth-child(3) > div:first-child {
        width: 100% !important;
        border-right: none !important;
        border-bottom: 1px solid rgba(255,255,255,0.1) !important;
        padding-right: 0.75rem !important;
        max-height: 150px !important;
    }

    {{-- Editor becomes full-width section below --}}
    .vw-modal > div:nth-child(3) > div:last-child {
        flex: 1 !important;
    }
}

{{-- Prevent body scroll when modal open --}}
body.modal-open {
    position: fixed;
    width: 100%;
    overflow: hidden;
    overscroll-behavior: none;
}

{{-- Enable momentum scrolling in modal content --}}
.vw-modal {
    -webkit-overflow-scrolling: touch;
}
</style>
```

### Example 2: Copy Button with Touch Optimization

```blade
<button type="button"
        x-data="{
            copied: false,
            isTouching: false
        }"
        @touchstart="isTouching = true"
        @touchend="isTouching = false"
        @click="
            const prompt = '{{ addslashes($shot['imagePrompt'] ?? '') }}';
            navigator.clipboard.writeText(prompt)
                .then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                })
                .catch(() => {
                    // Fallback for older iOS
                    const ta = document.createElement('textarea');
                    ta.value = prompt;
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
        style="min-width: 48px;
               min-height: 48px;
               padding: 0.5rem 0.75rem;
               background: {{ !isTouching ? 'rgba(139,92,246,0.2)' : 'rgba(139,92,246,0.35)' }};
               border: 1px solid rgba(139,92,246,0.4);
               border-radius: 0.35rem;
               color: #c4b5fd;
               font-size: 0.65rem;
               cursor: pointer;
               touch-action: manipulation;
               transition: all 0.15s ease;">
    <span x-show="!copied">📋 Copy</span>
    <span x-show="copied" style="color: #10b981;">✓ Copied!</span>
</button>
```

### Example 3: Responsive Button Layout

```css
/* Desktop: side-by-side */
@media (min-width: 769px) {
    .modal-footer {
        display: flex;
        gap: 0.5rem;
        justify-content: space-between;
        align-items: center;
    }

    .button-group {
        display: flex;
        gap: 0.5rem;
    }

    button {
        padding: 0.4rem 0.9rem;
        font-size: 0.75rem;
        min-width: 44px;
        min-height: 44px;
    }
}

/* Mobile: full-width stacked */
@media (max-width: 768px) {
    .modal-footer {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .button-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    button {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        min-width: 48px;
        min-height: 48px;
    }

    .primary-button {
        order: -1; /* Place primary button last visually */
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Fixed breakpoints (480px, 1024px) | Industry standard 768px | 2015+ | Bootstrap/Tailwind standardization |
| overflow:hidden on body | position:fixed + scroll restoration | 2020+ | iOS Safari workarounds |
| :hover for all interactions | Touch-aware @touchstart/@touchend | 2015+ | Mobile-first UX |
| 44px button hit area | 48px minimum on mobile | 2020+ | WCAG accessibility standards |
| 100vh for modals | 100dvh (dynamic viewport height) | 2023+ | iOS Safari 15+ support |

**Deprecated/outdated:**
- `document.body.style.overflow = 'hidden'` alone - Insufficient on iOS Safari
- 44x44px hit area - Modern recommendation is 48x48px
- Fixed 100vh layouts - Doesn't account for address bar on mobile

## Open Questions

1. **Breakpoint for landscape tablets**
   - What we know: 768px is portrait tablet breakpoint
   - What's unclear: Should landscape tablet (>1000px) use desktop or mobile layout?
   - Recommendation: Use desktop layout at 769px+, but test iPad landscape (1024px) to ensure buttons aren't too crowded

2. **Scroll position restoration edge case**
   - What we know: Position:fixed approach restores scroll on close
   - What's unclear: What happens if user navigates while modal is open?
   - Recommendation: Store scrollY only when modal opens, restore only when modal closes normally (not on navigate)

3. **Character list thumbnail size on mobile**
   - What we know: 35x45px thumbnails currently displayed
   - What's unclear: Is this large enough to see details on small screen?
   - Recommendation: Test on iPhone 12 (390px width) - may need 40x50px minimum

## Sources

### Primary (HIGH confidence)
- Existing modals: `modules/AppVideoWizard/resources/views/livewire/modals/character-bible.blade.php` - Current structure
- Existing modals: `modules/AppVideoWizard/resources/views/livewire/modals/location-bible.blade.php` - Current structure
- Existing responsive: `modules/AppVideoWizard/resources/views/livewire/modals/project-manager.blade.php` lines 490-520 - Already uses 768px breakpoint
- [I fixed a decade-long iOS Safari problem](https://stripearmy.medium.com/i-fixed-a-decade-long-ios-safari-problem-0d85f76caec0) - iOS scroll locking technique
- [Locking body scroll for modals on iOS](https://www.jayfreestone.com/writing/locking-body-scroll-ios/) - iOS-specific solution

### Secondary (MEDIUM confidence)
- [Prevent Page Scrolling When a Modal is Open](https://css-tricks.com/prevent-page-scrolling-when-a-modal-is-open/) - General scroll prevention patterns
- [How To Prevent Scrolling The Page On iOS Safari 15](https://pqina.nl/blog/how-to-prevent-scrolling-the-page-on-ios-safari) - iOS 15+ specific approach
- [Responsive Web Design Media Queries - 2026 Guide](https://www.browserstack.com/guide/what-are-css-and-media-query-breakpoints) - Current breakpoint standards
- [Mobile-First UX: Designing for Thumbs](https://dev.to/prateekshaweb/mobile-first-ux-designing-for-thumbs-not-just-screens-339m) - Thumb zone positioning
- [touch-action CSS property](https://developer.mozilla.org/en-US/docs/Web/CSS/touch-action) - Touch interaction control

### Tertiary (LOW confidence)
- None - all primary findings verified with existing codebase

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All existing patterns already in use
- Architecture: HIGH - iOS scroll locking verified with multiple authoritative sources
- Mobile responsiveness: HIGH - 768px breakpoint matches existing codebase pattern
- Touch interactions: MEDIUM - General best practices, but exact implementation depends on codebase's Alpine.js version

**Research date:** 2026-01-23
**Valid until:** 2026-02-20 (moderate-to-fast moving field due to new device resolutions and iOS Safari updates)

---

## Implementation Checklist for Planner

- [ ] Add iOS scroll lock JavaScript on modal open/close
- [ ] Add CSS position:fixed + body.modal-open styles
- [ ] Update Character Bible modal with @media (max-width: 768px)
- [ ] Update Location Bible modal with @media (max-width: 768px)
- [ ] Ensure all buttons have min-width: 48px, min-height: 48px
- [ ] Position close button in bottom-right (sticky positioning)
- [ ] Update copy buttons with @touchstart/@touchend handlers
- [ ] Test on iPhone 12 mini (375px width)
- [ ] Test on iPad (768px width in portrait)
- [ ] Test on iPad (1024px width in landscape)
- [ ] Verify iOS Safari scroll locking works
- [ ] Verify copy button works on iOS (fallback tested)
- [ ] Test with iPhone address bar expansion/contraction
- [ ] Test input fields don't get obscured by mobile keyboard
