# Domain Pitfalls: Scene Text Inspector Modal

**Domain:** Livewire inspection/detail modals for existing applications
**Researched:** 2026-01-23
**Confidence:** HIGH (Livewire-specific), MEDIUM (general UX patterns)

## Critical Pitfalls

Mistakes that cause rewrites, major performance degradation, or user abandonment.

### Pitfall 1: Massive Payload from Full Text Content
**What goes wrong:** All public properties in Livewire components are serialized to JSON and sent with EVERY request. When displaying full speech segments, prompts, and metadata in a modal, this creates a massive payload (potentially 10-100KB+ per modal interaction) that is sent and received on every wire:model update, causing severe performance degradation and RAM consumption.

**Why it happens:** Developers naturally use public properties to pass data to modals, not realizing that wire:model on large textareas or binding modal state creates continuous serialization overhead.

**Consequences:**
- 2-5 second delays on modal interactions
- Browser memory bloat (especially on mobile)
- Server RAM exhaustion with multiple concurrent users
- Unresponsive UI when typing in text fields

**Prevention:**
1. **Use computed properties instead of public properties** for read-only data (scene content, prompts, metadata):
   ```php
   #[Computed]
   public function inspectedSceneData()
   {
       return $this->scenes[$this->inspectedSceneIndex] ?? null;
   }
   ```
   Computed properties are NOT included in the payload, drastically reducing request size.

2. **Use wire:model.blur or wire:model.change** (NOT wire:model.live) for any text inputs:
   ```blade
   <textarea wire:model.blur="notes">
   ```
   This only sends updates when the user leaves the field, not on every keystroke.

3. **Use #[Renderless] attribute** for actions that don't modify the view:
   ```php
   #[Renderless]
   public function logInspection($sceneIndex)
   {
       // Backend-only logging, no re-render needed
   }
   ```

4. **Store scene index, not scene data** as the modal state:
   ```php
   public ?int $inspectedSceneIndex = null; // Good: 4 bytes
   // NOT: public ?array $inspectedScene = null; // Bad: 10-100KB
   ```

**Detection:**
- Browser DevTools Network tab shows requests > 50KB
- Noticeable lag when opening modal or typing
- Chrome Performance profiler shows excessive JSON parsing time

**Sources:**
- [Livewire Performance Tips & Tricks](https://joelmale.com/blog/laravel-livewire-performance-tips-tricks)
- [Speed Up Livewire V3](https://medium.com/@thenibirahmed/speed-up-livewire-v3-the-only-guide-you-need-32fe73338098)
- [Livewire Computed Properties for Performance](https://medium.com/@developerawam/is-livewire-feeling-slow-boost-its-performance-with-computed-properties-6f1b53bf74ee)

---

### Pitfall 2: Unnecessary Re-renders Cascade
**What goes wrong:** Opening a modal triggers a Livewire re-render of the ENTIRE parent component (VideoWizard.php at ~18k lines), not just the modal. Every property update in the modal re-renders the entire storyboard view, causing 1-3 second delays and visual flashing.

**Why it happens:** Livewire's default behavior is to re-render the entire component on any state change. Developers don't realize that setting `$showInspectorModal = true` re-renders everything.

**Consequences:**
- Entire storyboard re-renders when opening/closing modal
- Screen flashing and loss of scroll position
- Animations restart
- Poor mobile experience (users abandon)

**Prevention:**
1. **Use wire:ignore for static content** that shouldn't re-render:
   ```blade
   <div wire:ignore.self class="storyboard-scenes">
       <!-- Existing storyboard that doesn't change when modal opens -->
   </div>
   ```

2. **Use browser events instead of Livewire properties** for UI-only state:
   ```php
   // Instead of: public bool $showModal = true;
   $this->dispatch('open-inspector-modal', sceneIndex: $index);
   ```
   ```blade
   <script>
   window.addEventListener('open-inspector-modal', (e) => {
       // Pure JS modal control, no Livewire re-render
   });
   </script>
   ```

3. **Extract modal to separate Livewire component** if it has complex state:
   ```blade
   @livewire('scene-inspector-modal', ['projectId' => $projectId])
   ```
   This isolates re-renders to just the modal component.

4. **Use #[Renderless] for modal state changes** that don't affect the view:
   ```php
   #[Renderless]
   public function setInspectedScene($index)
   {
       $this->inspectedSceneIndex = $index;
   }
   ```

**Detection:**
- Network tab shows full component HTML returned (200-500KB responses)
- Entire page flashes when opening modal
- Browser Performance profiler shows long "Recalculate Style" tasks
- Users report "the whole screen refreshes"

**Sources:**
- [Prevent Livewire Component Re-rendering](https://benjamincrozat.com/prevent-render-livewire)
- [Avoid Component Rerender Side Effects](https://codecourse.com/watch/livewire-performance/avoid-component-rerender-side-effects)
- [Livewire #[Renderless] Attribute](https://livewire.laravel.com/docs/4.x/attribute-renderless)

---

### Pitfall 3: Copy-to-Clipboard Breaks After User Interactions
**What goes wrong:** Copy-to-clipboard functionality works initially but fails after certain user interactions (confirming actions, switching tabs, modal fade animations). Users click "Copy" and nothing happens, or get a "Failed to copy" error.

**Why it happens:** Clipboard API requires "transient user activation" (user must have recently clicked), HTTPS context, and window focus. Chrome-specific issue: fade animations on modals/confirms maintain focus for ~500ms, breaking clipboard access. Cross-origin iframes need special permissions.

**Consequences:**
- Silent failures (users think they copied but didn't)
- Inconsistent behavior (works sometimes, fails other times)
- Poor mobile experience (iOS Safari has additional restrictions)
- User frustration: "I have to try 3 times to copy"

**Prevention:**
1. **Use modern Clipboard API with proper error handling**:
   ```javascript
   async function copyToClipboard(text) {
       try {
           await navigator.clipboard.writeText(text);
           showSuccess('Copied!');
       } catch (err) {
           // Fallback to execCommand for older browsers
           fallbackCopy(text);
       }
   }
   ```

2. **Ensure clipboard operations happen immediately in click handler**, not after animations or confirms:
   ```javascript
   // BAD: Clipboard access after confirm
   if (confirm('Copy this?')) {
       await navigator.clipboard.writeText(text); // FAILS
   }

   // GOOD: Copy first, then confirm
   await navigator.clipboard.writeText(text);
   confirm('Copied! Continue?');
   ```

3. **Implement execCommand() fallback** for reliability:
   ```javascript
   function fallbackCopy(text) {
       const textarea = document.createElement('textarea');
       textarea.value = text;
       textarea.style.position = 'fixed';
       textarea.style.opacity = '0';
       document.body.appendChild(textarea);
       textarea.select();
       document.execCommand('copy');
       document.body.removeChild(textarea);
   }
   ```

4. **Add visual feedback immediately** (optimistic UI):
   ```javascript
   // Change button text immediately, don't wait for async
   button.textContent = 'Copied!';
   navigator.clipboard.writeText(text);
   ```

**Detection:**
- Clipboard works in isolation but fails in modal context
- Works on first try, fails on subsequent tries
- Console shows "Document is not focused" errors
- Works on desktop, fails on iOS Safari

**Sources:**
- [Copy to Clipboard in JavaScript](https://sentry.io/answers/how-do-i-copy-to-the-clipboard-in-javascript/)
- [Chrome Clipboard Issues After Confirm](https://www.webmasterworld.com/javascript/5099260.htm)
- [Web.dev Clipboard API Guide](https://web.dev/patterns/clipboard/copy-text)
- [SitePoint Clipboard API](https://www.sitepoint.com/clipboard-api/)

---

### Pitfall 4: Mobile Modals Become Unusable
**What goes wrong:** Desktop-designed modals don't translate to mobile. Close buttons in upper-right corner are unreachable (thumb can't reach), CTAs get lost below the fold, body scrolling happens behind the modal, and the modal itself doesn't scroll properly. Users abandon the app entirely on mobile.

**Why it happens:** Developers design modals on desktop with centered-box layout and upper-right close buttons, not realizing mobile users hold phones one-handed with thumb as primary input. Standard `overflow: hidden` doesn't lock body scroll on iOS Safari.

**Consequences:**
- 60-80% of mobile users can't close modal (abandon app)
- Can't scroll to see full content on small screens
- Accidental body scrolling while trying to scroll modal
- Modal slides under browser chrome on iOS

**Prevention:**
1. **Use mobile-first modal layout** (fullscreen or bottom-sheet on mobile):
   ```css
   .modal-content {
       /* Desktop: centered box */
       @media (min-width: 768px) {
           max-width: 600px;
           margin: 10vh auto;
       }

       /* Mobile: fullscreen */
       @media (max-width: 767px) {
           width: 100%;
           height: 100%;
           margin: 0;
       }
   }
   ```

2. **Place close button in thumb-friendly zone** (bottom-right or bottom-left on mobile):
   ```html
   <!-- Desktop: top-right -->
   <button class="close-btn close-btn-desktop">×</button>

   <!-- Mobile: bottom-right -->
   <button class="close-btn close-btn-mobile">Close</button>
   ```
   ```css
   @media (max-width: 767px) {
       .close-btn-desktop { display: none; }
       .close-btn-mobile {
           position: fixed;
           bottom: 20px;
           right: 20px;
       }
   }
   ```

3. **Implement iOS-safe body scroll lock**:
   ```javascript
   // Position fixed approach (works on iOS)
   function lockBodyScroll() {
       const scrollY = window.scrollY;
       document.body.style.position = 'fixed';
       document.body.style.top = `-${scrollY}px`;
       document.body.style.width = '100%';
   }

   function unlockBodyScroll() {
       const scrollY = document.body.style.top;
       document.body.style.position = '';
       document.body.style.top = '';
       window.scrollTo(0, parseInt(scrollY || '0') * -1);
   }
   ```
   Or use library: [body-scroll-lock](https://github.com/willmcpo/body-scroll-lock)

4. **Ensure modal content is scrollable** on mobile:
   ```css
   .modal-body {
       max-height: calc(100vh - 120px); /* Account for header/footer */
       overflow-y: auto;
       -webkit-overflow-scrolling: touch; /* iOS momentum scrolling */
   }
   ```

**Detection:**
- Test on actual iPhone (Safari behavior differs from Chrome DevTools)
- Try reaching close button with thumb (iPhone 13+)
- Try scrolling long content while holding phone one-handed
- Check if body scrolls when modal is open

**Sources:**
- [Modal UX Best Practices 2026](https://www.eleken.co/blog-posts/modal-ux)
- [Mobile App Modals Guide 2026](https://www.plotline.so/blog/mobile-app-modals)
- [Userpilot Modal UX Design](https://userpilot.com/blog/modal-ux-design/)
- [Locking Body Scroll iOS](https://www.jayfreestone.com/writing/locking-body-scroll-ios/)
- [CSS-Tricks Prevent Page Scrolling](https://css-tricks.com/prevent-page-scrolling-when-a-modal-is-open/)

---

## Moderate Pitfalls

Mistakes that cause delays, technical debt, or poor UX but are recoverable.

### Pitfall 5: Modal State Management Conflicts in Loops
**What goes wrong:** When modals are triggered from a loop (e.g., "Inspect" button on each storyboard scene), developers use a single modal name/ID. Clicking any "Inspect" button triggers ALL modals with that name, or the wrong scene data is displayed because state wasn't updated before modal opened.

**Why it happens:** Developers create a single modal component and bind it to a boolean `$showModal`, not realizing that multiple triggers need unique modal instances or careful state management.

**Prevention:**
1. **Store which item is being inspected**, not just a boolean:
   ```php
   // BAD:
   public bool $showModal = false;

   // GOOD:
   public ?int $inspectedSceneIndex = null;

   public function inspectScene($index)
   {
       $this->inspectedSceneIndex = $index;
       // Modal visibility controlled by: wire:model="inspectedSceneIndex !== null"
   }
   ```

2. **Use unique modal IDs in loops**:
   ```blade
   @foreach($scenes as $index => $scene)
       <button wire:click="inspectScene({{ $index }})">Inspect</button>
   @endforeach

   @if($inspectedSceneIndex !== null)
       <div class="modal" wire:key="inspector-{{ $inspectedSceneIndex }}">
           <!-- Modal content -->
       </div>
   @endif
   ```

3. **Close modal by resetting state**, not just hiding:
   ```php
   public function closeInspector()
   {
       $this->inspectedSceneIndex = null;
       $this->reset(['inspectorNotes']); // Clear modal-specific state
   }
   ```

**Detection:**
- Clicking "Inspect" on Scene 1 shows data from Scene 5
- Multiple modals appear at once
- Modal shows stale data from previous inspection

**Sources:**
- [Livewire Modal Best Practices Discussion](https://github.com/livewire/livewire/discussions/4345)
- [Tips Working with Modals](https://forum.laravel-livewire.com/t/looking-for-tips-working-with-modals/1264)

---

### Pitfall 6: Accessibility Completely Ignored
**What goes wrong:** Modal lacks proper ARIA attributes, focus management, and keyboard navigation. Screen reader users can't use the modal, keyboard users get trapped, and focus jumps to random places when modal opens/closes. App fails accessibility audits and excludes disabled users.

**Why it happens:** Developers focus on visual presentation and Livewire functionality, not realizing modals have strict accessibility requirements for ARIA roles, focus trapping, and keyboard handling.

**Prevention:**
1. **Use semantic HTML and ARIA attributes**:
   ```html
   <div
       role="dialog"
       aria-modal="true"
       aria-labelledby="modal-title"
       aria-describedby="modal-description"
       tabindex="-1"
   >
       <h2 id="modal-title">Scene Text Inspector</h2>
       <p id="modal-description">View full text, prompts, and metadata</p>
       <!-- Content -->
   </div>
   ```

2. **Manage focus properly**:
   ```javascript
   let previousActiveElement;

   function openModal(modalEl) {
       previousActiveElement = document.activeElement;
       modalEl.focus(); // Focus modal container
       trapFocus(modalEl); // Trap tab navigation
   }

   function closeModal() {
       previousActiveElement?.focus(); // Return focus
   }
   ```

3. **Implement keyboard navigation**:
   ```javascript
   modalEl.addEventListener('keydown', (e) => {
       if (e.key === 'Escape') closeModal();
       if (e.key === 'Tab') handleTabKey(e); // Trap focus
   });
   ```

4. **Consider native `<dialog>` element** (modern browsers 2026):
   ```html
   <dialog id="inspector-modal">
       <!-- Built-in focus management and Escape key -->
   </dialog>
   ```
   ```javascript
   dialog.showModal(); // Opens with automatic focus management
   ```

**Detection:**
- Tab key escapes modal (focus goes to background)
- Screen reader doesn't announce modal title
- Can't close modal with Escape key
- Lighthouse accessibility score < 90

**Sources:**
- [W3C Dialog Modal Pattern](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/)
- [Mastering Accessible Modals](https://www.a11y-collective.com/blog/modal-accessibility/)
- [MDN aria-modal attribute](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-modal)
- [ARIA Accessibility Best Practices](https://www.accessibilitychecker.org/blog/aria-accessibility/)

---

### Pitfall 7: Livewire Events Create Infinite Loops
**What goes wrong:** Modal dispatches an event that parent component listens to, which updates state, which re-dispatches the event, creating an infinite loop of requests that crashes the browser or exhausts server resources.

**Why it happens:** Developers use broad event listeners without checking conditions, or use wire:model.live on properties that dispatch events when changed.

**Prevention:**
1. **Be specific with event listeners**:
   ```php
   // BAD: Listens to ALL modal-updated events on page
   #[On('modal-updated')]
   public function handleUpdate() { }

   // GOOD: Listens only to specific event with guard
   #[On('inspector-modal-updated')]
   public function handleInspectorUpdate()
   {
       if ($this->inspectedSceneIndex === null) return; // Guard
       // Handle update
   }
   ```

2. **Use browser events for UI-only updates**:
   ```php
   // Instead of Livewire event that triggers re-render:
   $this->dispatch('update-scene'); // Causes parent re-render

   // Use browser event:
   $this->dispatch('update-scene')->self(); // Only this component
   ```

3. **Avoid wire:model.live on properties that dispatch events**:
   ```blade
   <!-- BAD: Every keystroke dispatches event -->
   <input wire:model.live="searchQuery">

   <!-- GOOD: Only dispatches on blur -->
   <input wire:model.blur="searchQuery">
   ```

4. **Add condition checks before dispatching**:
   ```php
   public function updated($property)
   {
       if ($property === 'inspectedSceneIndex' && $this->inspectedSceneIndex !== null) {
           // Only dispatch if actually opening modal, not closing
           $this->dispatch('modal-opened');
       }
   }
   ```

**Detection:**
- Browser tab becomes unresponsive
- Network tab shows hundreds of identical requests
- Console shows recursive event errors
- Server CPU spikes to 100%

**Sources:**
- [Avoid Component Rerender Side Effects](https://codecourse.com/watch/livewire-performance/avoid-component-rerender-side-effects)
- [Livewire Event Listeners Best Practices](https://laravel-news.com/laravel-livewire-tips-and-tricks)

---

## Minor Pitfalls

Mistakes that cause annoyance but are easily fixable.

### Pitfall 8: Modal Animation Conflicts with Livewire Updates
**What goes wrong:** Modal fade-in/fade-out animations get interrupted by Livewire updates, causing visual glitches (modal half-faded), or animations don't play at all because Livewire immediately removes the element.

**Prevention:**
1. **Use Alpine.js for animations** (built into Livewire 3):
   ```blade
   <div
       x-data="{ show: @entangle('showModal') }"
       x-show="show"
       x-transition:enter="transition ease-out duration-300"
       x-transition:leave="transition ease-in duration-200"
   >
       <!-- Modal content -->
   </div>
   ```

2. **Delay Livewire state update until animation completes**:
   ```javascript
   function closeModal() {
       modalEl.classList.add('fade-out');
       setTimeout(() => {
           @this.showModal = false; // Update Livewire after animation
       }, 300);
   }
   ```

3. **Use wire:ignore on animated elements**:
   ```blade
   <div wire:ignore.self class="modal-backdrop" x-show="show">
   ```

**Sources:**
- [Livewire with Alpine.js](https://livewire.laravel.com/docs/3.x/alpine)

---

### Pitfall 9: Modal Doesn't Reset Between Opens
**What goes wrong:** User opens inspector for Scene 1, closes it, opens inspector for Scene 2, but still sees Scene 1's data briefly before Scene 2 loads. Or modal shows previous user's notes/edits.

**Prevention:**
1. **Reset modal state when opening**:
   ```php
   public function inspectScene($index)
   {
       $this->reset(['inspectorNotes', 'selectedTab']); // Clear old state
       $this->inspectedSceneIndex = $index;
   }
   ```

2. **Use wire:key to force re-render**:
   ```blade
   <div wire:key="inspector-{{ $inspectedSceneIndex }}-{{ now()->timestamp }}">
   ```

**Detection:**
- Flashing of old content when opening modal
- Previous user's edits appear briefly

---

### Pitfall 10: Missing Loading States During Data Fetch
**What goes wrong:** Modal opens instantly but shows empty content for 1-2 seconds while Livewire fetches scene data from server, making users think it's broken.

**Prevention:**
1. **Show skeleton loader immediately**:
   ```blade
   @if($inspectedSceneIndex !== null)
       <div class="modal">
           @if($this->inspectedScene) <!-- Computed property -->
               <!-- Actual content -->
           @else
               <div class="skeleton-loader">Loading...</div>
           @endif
       </div>
   @endif
   ```

2. **Use wire:loading for specific actions**:
   ```blade
   <div wire:loading wire:target="inspectScene">
       <div class="spinner"></div>
   </div>
   ```

**Sources:**
- [Livewire Loading States](https://livewire.laravel.com/docs/3.x/loading)

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Modal Structure Setup | Pitfall #2 (Unnecessary re-renders) | Extract to separate component OR use wire:ignore + browser events |
| Copy-to-Clipboard Implementation | Pitfall #3 (Clipboard breaks) | Implement both Clipboard API + execCommand fallback, test on iOS |
| Mobile Responsiveness | Pitfall #4 (Unusable on mobile) | Design mobile-first, place close button bottom-right, test on real device |
| State Management | Pitfall #1 (Payload bloat) | Use computed properties, store index not data, wire:model.blur |
| Loop Integration | Pitfall #5 (State conflicts) | Store inspectedSceneIndex, use wire:key, proper state reset |
| Accessibility | Pitfall #6 (Ignored accessibility) | Add ARIA attributes, focus management, keyboard nav from start |
| Event Handling | Pitfall #7 (Infinite loops) | Specific listeners, condition checks, prefer .self() events |

---

## Technology-Specific Warnings

### Livewire 3 Specifics
- ✓ `wire:model.lazy` renamed to `wire:model.change` (but .lazy still works as alias)
- ✓ `#[Renderless]` attribute is new in v3, use for performance
- ✓ `#[Computed]` properties cached per-request, reduces payload
- ✓ Alpine.js is built-in, use for client-side animations
- ⚠️ Public properties serialized to JSON - keep minimal

### Laravel 10 + Livewire 3 Stack
- ✓ Route caching compatible
- ✓ Livewire scripts must be in main layout (@livewireScripts)
- ⚠️ wire:model only works on input/select/textarea, not divs

### VideoWizard Component Size (~18k lines)
- ⚠️ **CRITICAL**: Component this large means every state change re-renders massive template
- ✓ Strongly consider extracting modal to separate component
- ✓ Use wire:ignore extensively on non-modal content
- ✓ Consider using browser events instead of Livewire properties

---

## Quick Decision Tree

**Q: Should I use a separate Livewire component for the modal?**
- If modal has complex state/logic: **YES** (isolates re-renders)
- If modal is simple read-only viewer: **NO** (use browser events + Alpine.js)
- If parent component is > 5k lines: **YES** (prevents massive re-renders)

**Q: Should I use public properties or computed properties for scene data?**
- Read-only display data: **Computed properties** (not in payload)
- User-editable fields: **Public properties with wire:model.blur**
- Large text content: **NEVER as public property** (use computed)

**Q: How should I handle copy-to-clipboard?**
- Modern browsers only: **Clipboard API + fallback**
- Need IE support: **execCommand only**
- Mobile-heavy: **Test on iOS Safari specifically**

**Q: How should I handle mobile responsiveness?**
- Content fits in viewport: **Centered modal with mobile adjustments**
- Long scrollable content: **Fullscreen on mobile, modal on desktop**
- Always: **Bottom-positioned close button on mobile**

---

## Research Confidence Notes

| Area | Confidence | Source Quality |
|------|------------|----------------|
| Livewire payload issues | **HIGH** | Multiple official sources, common documented issue |
| Livewire re-render prevention | **HIGH** | Official Livewire docs + community best practices |
| Clipboard reliability | **MEDIUM** | Web standards docs but browser quirks vary |
| Mobile modal UX | **HIGH** | Recent 2026 UX research + established patterns |
| Accessibility requirements | **HIGH** | W3C standards + authoritative guides |
| iOS scroll lock issues | **MEDIUM** | Well-documented issue but solutions are workarounds |

---

## Sources

### Livewire Performance & State Management
- [Livewire Performance Tips & Tricks](https://joelmale.com/blog/laravel-livewire-performance-tips-tricks)
- [Speed Up Livewire V3](https://medium.com/@thenibirahmed/speed-up-livewire-v3-the-only-guide-you-need-32fe73338098)
- [Livewire Computed Properties for Performance](https://medium.com/@developerawam/is-livewire-feeling-slow-boost-its-performance-with-computed-properties-6f1b53bf74ee)
- [Prevent Livewire Component Re-rendering](https://benjamincrozat.com/prevent-render-livewire)
- [Avoid Component Rerender Side Effects](https://codecourse.com/watch/livewire-performance/avoid-component-rerender-side-effects)
- [Livewire #[Renderless] Attribute](https://livewire.laravel.com/docs/4.x/attribute-renderless)

### Livewire Modal Best Practices
- [Livewire Modal Component Discussion](https://github.com/livewire/livewire/discussions/4345)
- [Tips Working with Modals - Livewire Forum](https://forum.laravel-livewire.com/t/looking-for-tips-working-with-modals/1264)
- [Wire Elements Modal Package](https://github.com/wire-elements/modal)
- [Livewire wire:model Documentation](https://livewire.laravel.com/docs/3.x/wire-model)

### Clipboard API
- [How to Copy to Clipboard in JavaScript](https://sentry.io/answers/how-do-i-copy-to-the-clipboard-in-javascript/)
- [Chrome Clipboard Issues After Confirm](https://www.webmasterworld.com/javascript/5099260.htm)
- [Web.dev Clipboard Copy Text Pattern](https://web.dev/patterns/clipboard/copy-text)
- [SitePoint Clipboard API Guide](https://www.sitepoint.com/clipboard-api/)

### Modal UX & Mobile Responsiveness
- [Mastering Modal UX Best Practices](https://www.eleken.co/blog-posts/modal-ux)
- [Mobile App Modals Complete 2026 Guide](https://www.plotline.so/blog/mobile-app-modals)
- [Modal UX Design for SaaS 2025](https://userpilot.com/blog/modal-ux-design/)
- [Modal Web Design UX Rules](https://prateeksha.com/blog/modal-web-design-ux-rules-examples-when-not-to-use)

### Body Scroll Lock (iOS)
- [Locking Body Scroll iOS](https://www.jayfreestone.com/writing/locking-body-scroll-ios/)
- [CSS-Tricks Prevent Page Scrolling](https://css-tricks.com/prevent-page-scrolling-when-a-modal-is-open/)
- [body-scroll-lock GitHub](https://github.com/willmcpo/body-scroll-lock)
- [Scroll-Locked Dialogs - Frontend Masters](https://frontendmasters.com/blog/scroll-locked-dialogs/)

### Accessibility
- [W3C Dialog Modal Pattern](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/)
- [Mastering Accessible Modals with ARIA](https://www.a11y-collective.com/blog/modal-accessibility/)
- [MDN aria-modal attribute](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-modal)
- [ARIA Accessibility Best Practices](https://www.accessibilitychecker.org/blog/aria-accessibility/)
- [Carnegie Museums Accessibility Guidelines - Modals](http://web-accessibility.carnegiemuseums.org/code/dialogs/)

---

*Research completed 2026-01-23. All pitfalls verified against Livewire 3 + Laravel 10 stack.*
