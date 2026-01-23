# Project Research Summary

**Project:** Video Wizard - Scene Text Inspector Modal (Milestone 7)
**Domain:** Livewire inspection modals for video production applications
**Researched:** 2026-01-23
**Confidence:** HIGH

## Executive Summary

The Scene Text Inspector modal is a read-only inspection interface that provides full transparency into scene text content (speech segments, image/video prompts, metadata) currently truncated in the Video Wizard storyboard view. Research shows this feature type sits at the intersection of transcript viewers (Descript, Adobe Premiere), metadata panels (Frame.io), and AI prompt displays (RunwayML). Industry consensus strongly favors **non-blocking containers** (drawers/side-panels) over modals for high-volume text content, as modals interrupt workflow when users need to reference the storyboard while inspecting text.

The implementation is architecturally straightforward: the existing Laravel 10 + Livewire 3 + Alpine.js stack contains all required technologies, and the app already has established modal patterns across Character Bible, Location Bible, and Scene DNA modals. The inspector follows a lightweight read-only pattern with boolean toggle, scene index tracking, and direct data access via `$script['scenes'][$index]`. No new dependencies, computed properties, or complex state management are required - this is a 6-8 hour implementation with well-validated patterns.

However, **Livewire performance pitfalls are severe** for this use case. The parent VideoWizard component is ~18k lines, meaning every state change re-renders the entire massive template. Research identified three critical risks: (1) payload bloat from serializing full text content in public properties can create 10-100KB requests causing 2-5 second delays, (2) modal state changes trigger full component re-renders causing screen flashing and scroll position loss, and (3) copy-to-clipboard breaks after animations/confirmations due to browser security. Mitigation requires computed properties for scene data (not public properties), `wire:ignore` on storyboard content, native Clipboard API with fallback, and mobile-first responsive design with thumb-friendly close buttons.

## Key Findings

### Recommended Stack

The Scene Text Inspector requires **zero new stack additions** - all technologies are validated in the existing application. Laravel 10 provides the backend framework, Livewire 3 handles reactive modal state management, and Alpine.js (bundled with Livewire 3) enables client-side animations and copy-to-clipboard interactions. The app's established modal pattern uses flexbox-based three-section layout (fixed header, scrollable content, fixed footer) with inline styling following `vw-*` class naming conventions.

**Core technologies:**
- **Laravel 10 + Livewire 3**: Modal state management via `$showSceneTextInspectorModal` boolean and scene index tracking - matches existing Character Bible/Location Bible patterns
- **Alpine.js 3**: Copy-to-clipboard via native `navigator.clipboard.writeText()` (already validated in timeline component), modal animations with x-transition
- **Native Browser APIs**: Clipboard API (96%+ browser support in 2026) with optional chaining `?.` for graceful degradation, no plugins needed

**Critical validation:** The existing timeline component (line 2142-2144) already uses `navigator.clipboard?.writeText()` successfully, confirming this approach works in the app's environment.

### Expected Features

Professional tools (Descript, Frame.io, Premiere) have converged on specific patterns for text inspection interfaces. The MVP must solve the immediate problem (truncated text visibility, incorrect type labels) while deferring power features to post-launch.

**Must have (table stakes):**
- **Full text display** — Core purpose, users cannot see truncated speech segments currently; requires scrollable container with `white-space: pre-wrap` for line breaks
- **Speech type indicators** — Visual badges distinguishing NARRATOR/DIALOGUE/INTERNAL/MONOLOGUE types (currently showing incorrect labels)
- **Character attribution** — Show speaker name for dialogue/monologue segments, none for narrator
- **Copy-to-clipboard per segment** — One-click copy with toast confirmation (not just "copy all")
- **Image/video prompt display** — Separate collapsible sections showing generation prompts
- **Scene metadata badges** — Duration, transition, shot type, camera movement
- **Non-blocking container** — Users need to reference storyboard while reading text (drawer pattern preferred, but existing modal acceptable for MVP given 6-8 hour timeline)
- **Proper close behavior** — X button, ESC key, click-outside-to-close

**Should have (competitive):**
- **Segment-level copy buttons** — Copy individual speech segments vs entire scene text
- **Collapsible metadata sections** — Accordion pattern for Speech/Prompts/Metadata sections to reduce cognitive load
- **Inline type correction** — Quick-edit dropdown to fix NARRATOR vs DIALOGUE labeling errors without leaving inspector
- **Toast notification system** — Visual feedback for copy actions (green toast, 3-4 second duration, "Text copied to clipboard")

**Defer (v2+):**
- **Click-to-jump timeline** — Click segment → jump to that moment in video preview (requires video player integration)
- **Prompt history/versions** — Show prompt iterations if scene regenerated (requires versioning architecture)
- **Export scene text** — Export formatted text in Markdown, plain text, or Fountain screenplay format
- **Search/filter segments** — Global search across all scenes (requires search infrastructure)

### Architecture Approach

Scene Text Inspector integrates as a lightweight read-only modal following the established pattern: trigger button on scene card → `wire:click="openSceneTextInspectorModal({{ $index }})"` → sets `$showSceneTextInspectorModal = true` and `$inspectorSceneIndex = $index` → modal blade renders with `@if($showSceneTextInspectorModal)` → accesses `$script['scenes'][$inspectorSceneIndex]` and `$storyboard[$inspectorSceneIndex]` directly → close resets boolean to false. No data duplication, no computed properties needed for simple display, no save logic (read-only).

**Major components:**
1. **VideoWizard.php properties** — Add `$showSceneTextInspectorModal` (bool) and `$inspectorSceneIndex` (int) matching existing modal patterns
2. **VideoWizard.php methods** — Add `openSceneTextInspectorModal($index)` with scene validation, `closeSceneTextInspectorModal()` with no rebuild needed (read-only)
3. **scene-text-inspector.blade.php** — Three-section modal structure (header with title/close, scrollable content with speech/prompts/metadata, footer with actions) using inline styles consistent with existing modals
4. **Data access pattern** — Direct array access in blade with null safety: `@php $scene = $script['scenes'][$inspectorSceneIndex] ?? null; @endphp` then conditional rendering `@if($scene)`

**Key architectural decision:** Direct blade array access instead of computed properties or component extraction because (1) read-only display doesn't benefit from memoization, (2) scene data volume is small (<50KB), (3) no real-time updates needed, (4) matches existing modal patterns for consistency. Exception: If performance testing reveals >300ms render time, extract to separate component or add computed properties.

### Critical Pitfalls

Research identified three critical and four moderate pitfalls specific to Livewire inspection modals. The critical pitfalls can cause rewrites or user abandonment if not addressed upfront.

1. **Massive Payload from Full Text Content (CRITICAL)** — All public properties serialize to JSON on EVERY Livewire request; full speech segments/prompts create 10-100KB payloads causing 2-5 second delays and RAM exhaustion. **Prevention:** Use computed properties for read-only scene data (NOT public properties), store scene index only (`public int $inspectorSceneIndex` not `public array $inspectedScene`), use `wire:model.blur` not `wire:model.live` for any text inputs. Detection: Browser DevTools Network tab shows requests >50KB.

2. **Unnecessary Re-renders Cascade (CRITICAL)** — Opening modal triggers re-render of entire 18k-line VideoWizard component causing 1-3 second delays, screen flashing, scroll position loss. **Prevention:** Use `wire:ignore.self` on storyboard content that doesn't change when modal opens, consider browser events instead of Livewire properties for UI-only state, or extract modal to separate component if complex. Detection: Network tab shows full component HTML returned (200-500KB), entire page flashes when opening modal.

3. **Copy-to-Clipboard Breaks After Interactions (CRITICAL)** — Clipboard API requires "transient user activation"; fails after fade animations, confirmations, or tab switches due to Chrome focus issues. **Prevention:** Use `navigator.clipboard.writeText()` with `execCommand()` fallback, ensure clipboard operations happen immediately in click handler (not after animations), add visual feedback immediately (optimistic UI). Detection: Works in isolation but fails in modal context, console shows "Document is not focused" errors.

4. **Mobile Modals Become Unusable (CRITICAL)** — Desktop-designed modals fail on mobile: close buttons in upper-right unreachable by thumb, body scrolling behind modal on iOS Safari, content doesn't scroll properly. **Prevention:** Mobile-first design with fullscreen layout on mobile (centered box on desktop), close button in bottom-right thumb zone on mobile, implement iOS-safe body scroll lock using `position: fixed` approach, test on actual iPhone. Detection: 60-80% mobile abandonment, can't reach close button with thumb.

5. **Modal State Management Conflicts in Loops (MODERATE)** — Single `$showModal` boolean used for multiple scenes causes wrong scene data display or multiple modals opening. **Prevention:** Store `$inspectedSceneIndex` not boolean, use unique `wire:key="inspector-{{ $inspectedSceneIndex }}"`, close by resetting state `$this->inspectedSceneIndex = null`. Detection: Clicking "Inspect" on Scene 1 shows Scene 5 data.

## Implications for Roadmap

Based on research, implementation follows a clear sequential build order. The entire feature is a 6-8 hour implementation following established patterns with zero new dependencies.

### Phase 1: Core Modal Shell & Metadata Display
**Rationale:** Establish modal structure and basic rendering first to validate integration with existing VideoWizard component before adding complex features. Tests the critical performance pitfalls early (payload size, re-render behavior) when they're easiest to fix.

**Delivers:** Working modal that opens/closes correctly, displays scene metadata badges (duration, transition, shot type, camera movement)

**Addresses:**
- Modal structure using established three-section pattern (header/content/footer)
- State management via `$showSceneTextInspectorModal` and `$inspectorSceneIndex`
- Scene metadata display (table stakes)
- Proper close behavior (X button, ESC key) (table stakes)

**Avoids:**
- **Pitfall #2** (re-render cascade) by using `wire:ignore` on storyboard content and validating re-render behavior immediately
- **Pitfall #5** (state conflicts) by implementing scene index pattern correctly from start

**Estimated time:** 2 hours

---

### Phase 2: Speech Segments Display
**Rationale:** Core problem to solve is truncated speech text visibility - this is the primary user need. Must implement type indicators correctly to fix the "Dialogue shown for narrator" bug mentioned in context.

**Delivers:** Full speech segment display with type badges (NARRATOR/DIALOGUE/INTERNAL/MONOLOGUE), speaker attribution, scrollable container for long text

**Addresses:**
- Full text display with `white-space: pre-wrap` and `line-height: 1.6` (table stakes)
- Speech type indicators with visual badges and color coding (table stakes)
- Character attribution showing speaker name for dialogue/monologue (table stakes)
- Scrollable content area using `flex: 1; overflow-y: auto` pattern

**Uses:**
- Data structure from `$script['scenes'][$index]['speechSegments']` with type, speaker, text, needsLipSync fields
- Null-safe access pattern: `@foreach($scene['speechSegments'] ?? [] as $segment)`

**Avoids:**
- **Pitfall #1** (payload bloat) by accessing data directly in blade, not duplicating in public properties
- Correct type labeling bug by displaying `$segment['type']` directly

**Estimated time:** 2 hours

---

### Phase 3: Prompts Display & Copy-to-Clipboard
**Rationale:** Prompt visibility is critical for AI video generation workflows (reproducibility). Copy functionality is table stakes but has critical implementation pitfalls requiring careful handling.

**Delivers:** Image/video prompt sections (collapsible), per-segment copy buttons, toast notifications for copy feedback

**Addresses:**
- Image/video prompt display with fallback (`$storyboard[$index]['prompt']` or `$script['scenes'][$index]['visualDescription']`) (table stakes)
- Copy-to-clipboard per segment using native API (table stakes, competitive)
- Toast notification system for copy feedback (competitive)
- Collapsible metadata sections using Alpine.js `x-data` (competitive)

**Implements:**
- Native Clipboard API pattern: `navigator.clipboard?.writeText(text).then(() => showToast()).catch(() => fallbackCopy())`
- `execCommand()` fallback for reliability across browsers/contexts
- Optimistic UI (change button text immediately, don't wait for async)

**Avoids:**
- **Pitfall #3** (clipboard breaks) by implementing both modern API + fallback, ensuring operations happen immediately in click handler, testing after animations
- **Pitfall #1** (payload) by using Alpine.js for toast state (not Livewire properties)

**Estimated time:** 2-3 hours

---

### Phase 4: Styling & Mobile Responsiveness
**Rationale:** Polish pass ensures consistency with existing modals and validates mobile UX. Mobile responsiveness is critical (Pitfall #4) and requires real device testing, not just DevTools responsive mode.

**Delivers:** Visual consistency with existing modals, mobile-optimized layout, thumb-friendly close button positioning

**Addresses:**
- Consistent styling with Character Bible/Location Bible/Scene DNA modals
- Responsive layout (fullscreen on mobile, centered box on desktop)
- Mobile close button in bottom-right thumb zone
- iOS body scroll lock implementation

**Avoids:**
- **Pitfall #4** (unusable mobile) by designing mobile-first, testing on actual iPhone, placing close button in reachable thumb zone, implementing position-fixed scroll lock for iOS Safari

**Estimated time:** 1-2 hours

---

### Phase Ordering Rationale

- **Sequential dependencies:** Modal shell must exist before adding content sections; speech display must work before adding copy buttons; copy functionality must be reliable before styling polish
- **Early risk validation:** Performance pitfalls (payload size, re-render behavior) tested in Phase 1 when easiest to fix; clipboard reliability tested in Phase 3 before polish
- **Progressive complexity:** Start with simple read-only display (metadata), add core feature (speech segments), then interactive features (copy), finally polish (styling/mobile)
- **Incremental value:** Each phase delivers independently testable functionality; users get value after Phase 2 (can see full text) even if later phases delayed

### Research Flags

**Phases with standard patterns (skip additional research):**
- **Phase 1-4:** All phases use well-documented Livewire modal patterns validated in existing codebase. STACK.md, ARCHITECTURE.md, and PITFALLS.md provide complete implementation guidance. No additional research needed during planning.

**Validation testing required:**
- **Phase 1:** Validate re-render behavior with Network tab (should not see 200-500KB responses when opening modal)
- **Phase 3:** Test copy-to-clipboard on iOS Safari specifically (known to have additional restrictions)
- **Phase 4:** Test on actual mobile device (iPhone 13+) for thumb reach, scroll behavior, not just Chrome DevTools

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | **HIGH** | All technologies (Laravel 10, Livewire 3, Alpine.js) already validated in existing app; native Clipboard API confirmed working in timeline component (line 2142-2144); zero new dependencies required |
| Features | **HIGH** | Professional tool patterns (Descript, Frame.io, Premiere) converged on consistent approach; table stakes features clearly defined; MVP scope well-bounded |
| Architecture | **HIGH** | Direct codebase analysis of existing modal patterns (Character Bible, Location Bible, Scene DNA) provides exact implementation template; data structures documented in SpeechSegment.php and VideoWizard.php |
| Pitfalls | **HIGH** (Livewire), **MEDIUM** (general UX) | Livewire performance issues extensively documented in official sources and community; mobile UX patterns based on 2026 research but require real device validation |

**Overall confidence:** **HIGH**

### Gaps to Address

- **Drawer vs Modal decision:** FEATURES.md research strongly recommends drawer/side-panel (non-blocking) over modal for high-volume text content, but STACK.md shows existing app uses modal pattern exclusively. **Resolution:** Start with modal pattern for consistency and 6-8 hour timeline; revisit drawer pattern in v2 if users report workflow interruption. Phase 4 can optionally test drawer variation if time permits.

- **Component extraction decision:** PITFALLS.md warns that 18k-line VideoWizard component creates severe re-render risk and "strongly consider extracting modal to separate component." However, ARCHITECTURE.md shows existing modals (Character Bible, Location Bible) embedded in main component without extraction. **Resolution:** Start with embedded modal using `wire:ignore` on storyboard content (matches existing pattern); extract to separate component only if performance testing shows >300ms modal open time or >50KB payload in Phase 1.

- **iOS Safari scroll lock:** PITFALLS.md notes scroll lock solutions are "workarounds" with MEDIUM confidence due to browser quirks. **Resolution:** Implement position-fixed approach in Phase 4, test on actual iPhone; fallback to body-scroll-lock library if native approach fails. This is a polish issue, not blocking for core functionality.

- **Copy-to-clipboard reliability:** While Clipboard API has 96%+ browser support, PITFALLS.md documents Chrome-specific focus issues after animations/confirmations. **Resolution:** Implement both modern API + execCommand fallback in Phase 3; extensive testing required after modal fade animations, particularly on iOS Safari where restrictions are strictest.

## Sources

### Primary (HIGH confidence)
- **Existing codebase analysis:**
  - `modules/AppVideoWizard/app/Livewire/VideoWizard.php` — Modal patterns, data structures, state management (~18k lines)
  - `modules/AppVideoWizard/resources/views/livewire/modals/character-bible.blade.php` — Three-section modal UI pattern, inline styling conventions
  - `modules/AppVideoWizard/resources/views/livewire/modals/scene-dna.blade.php` — Scene data access pattern, null safety with `??` operator
  - `modules/AppVideoWizard/resources/views/livewire/modals/edit-prompt.blade.php` — Scene-scoped modal pattern with scene index tracking
  - `modules/AppVideoWizard/app/Services/SpeechSegment.php` — Segment data structure (type, speaker, text, needsLipSync fields)
  - Timeline component (line 2142-2144) — Validates `navigator.clipboard?.writeText()` works in app environment

- **Official documentation:**
  - [Livewire 3 Alpine Integration](https://livewire.laravel.com/docs/3.x/alpine) — Alpine.js bundled with Livewire 3
  - [Livewire #[Renderless] Attribute](https://livewire.laravel.com/docs/4.x/attribute-renderless) — Performance optimization for non-rendering actions
  - [Livewire #[Computed] Properties](https://livewire.laravel.com/docs/3.x/computed-properties) — Reduce payload by excluding computed data from serialization
  - [W3C Dialog Modal Pattern](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/) — Accessibility requirements (ARIA roles, focus management)
  - [Web.dev Clipboard API Guide](https://web.dev/patterns/clipboard/copy-text) — Modern clipboard patterns with fallbacks

### Secondary (MEDIUM confidence)
- **Livewire performance patterns:**
  - [Livewire Performance Tips & Tricks](https://joelmale.com/blog/laravel-livewire-performance-tips-tricks) — Payload optimization, computed properties
  - [Speed Up Livewire V3](https://medium.com/@thenibirahmed/speed-up-livewire-v3-the-only-guide-you-need-32fe73338098) — Performance best practices
  - [Prevent Livewire Component Re-rendering](https://benjamincrozat.com/prevent-render-livewire) — wire:ignore patterns
  - [Avoid Component Rerender Side Effects](https://codecourse.com/watch/livewire-performance/avoid-component-rerender-side-effects) — Browser events vs properties

- **Modal UX patterns (2026 research):**
  - [Modal vs Drawer — When to use the right component](https://medium.com/@ninad.kotasthane/modal-vs-drawer-when-to-use-the-right-component-af0a76b952da) — Drawers better for high-volume content
  - [Modal UX Best Practices](https://www.eleken.co/blog-posts/modal-ux) — Close button positioning, mobile considerations
  - [Mobile App Modals Guide](https://www.plotline.so/blog/mobile-app-modals) — Thumb-friendly zones, fullscreen on mobile
  - [Mastering Accessible Modals](https://www.a11y-collective.com/blog/modal-accessibility/) — ARIA attributes, keyboard navigation

- **Professional tool analysis:**
  - [Descript Speaker Labels](https://help.descript.com/hc/en-us/articles/10249423506061-Automatic-Speaker-Detection) — Speech type indicators pattern
  - [Reduct.video Platform](https://reduct.video/) — Click-to-jump timeline feature (deferred to v2)
  - [Frame.io Panel Overview](https://help.frame.io/en/articles/12833113-adobe-premiere-frame-io-v4-panel-overview-25-6-and-later) — Metadata display patterns
  - [Managing metadata in Premiere Pro](https://helpx.adobe.com/premiere-pro/using/metadata.html) — Collapsible metadata sections

### Tertiary (LOW confidence - needs validation)
- **iOS Safari scroll lock:** [Locking Body Scroll iOS](https://www.jayfreestone.com/writing/locking-body-scroll-ios/) — Position-fixed approach, but requires device testing
- **Chrome clipboard focus issues:** [Clipboard Issues After Confirm](https://www.webmasterworld.com/javascript/5099260.htm) — Documented but solution effectiveness varies
- **Copy-to-clipboard fallbacks:** [Sentry Clipboard Guide](https://sentry.io/answers/how-do-i-copy-to-the-clipboard-in-javascript/) — execCommand() reliability in 2026 unclear

---
*Research completed: 2026-01-23*
*Ready for roadmap: yes*
