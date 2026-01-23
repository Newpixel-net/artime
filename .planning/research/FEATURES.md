# Feature Landscape: Scene Text Inspector

**Domain:** Video production text inspection and metadata display
**Researched:** 2026-01-23
**Confidence:** HIGH (based on professional tools analysis)

## Executive Summary

Scene Text Inspectors sit at the intersection of transcript viewers (Descript, Adobe Premiere), metadata panels (Frame.io, Adobe Metadata Panel), and AI generation tool prompt displays (RunwayML, Midjourney). The best implementations prioritize **non-blocking access** (drawers over modals for lengthy content), **click-to-copy efficiency**, **clear speech type indicators**, and **collapsible organization** for different metadata categories.

Professional tools have converged on drawer/side-panel patterns for high-volume text inspection while keeping the main workspace visible. This differs from blocking modals, which are reserved for short, critical information requiring immediate action.

---

## Table Stakes

Features users expect from any text inspector. Missing these = product feels incomplete.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **Full Text Display** | Core purpose - users can't see truncated text currently | Low | No character limits, proper word wrapping |
| **Speech Type Indicators** | Distinguish narrator/dialogue/monologue/internal | Low | Visual labels or badges (see Descript speaker labels) |
| **Copy-to-Clipboard** | Users need to extract text for editing/review | Low | One-click copy with toast confirmation |
| **Non-Blocking Container** | Users need to reference main storyboard while reading | Medium | Drawer/side-panel, not blocking modal |
| **Close/Dismiss Action** | Basic modal/drawer hygiene | Low | X button + ESC key + click-outside |
| **Character/Speaker Attribution** | Who is speaking this segment | Low | Link to Character Bible if available |
| **Prompt Display** | Show image/video generation prompts | Low | Separate sections for image vs video prompts |
| **Scene Metadata** | Basic scene info (number, duration, shot type) | Low | Read from existing scene data structure |

### Rationale

**Full Text Display:** The explicit problem statement - truncated text is unusable. Industry standard is unlimited display with proper scrolling (see transcript viewers in Descript, VEED.io, Adobe Premiere Text-Based Editing).

**Speech Type Indicators:** Professional video tools distinguish between dialogue, narration, voiceover, and internal monologue. Film theory categorizes speech into: dialogue (synchronous/asynchronous), monologue (soliloquy, dramatic, internal), and voice-over/narrator. Current Video Wizard has the types (NARRATOR, DIALOGUE, INTERNAL, MONOLOGUE) but displays them incorrectly.

**Copy-to-Clipboard:** Universal pattern in developer tools (Chrome DevTools), transcript editors (Reduct.video), and AI prompt viewers (RunwayML metadata display). Users expect one-click copy with visual confirmation (toast notification, typically green, 4-5 second duration).

**Non-Blocking Container:** Research shows drawers are superior for "high-volume content" like reviews, transcripts, and metadata inspection. Adobe's Admin Design Pattern Library explicitly recommends drawers over modals for keeping "main object in view." Modals work for <400 characters; drawers for longer content.

**Character/Speaker Attribution:** Scene text is always spoken by someone. Professional script formats always attribute lines. Links to Character Bible create context continuity.

**Prompt Display:** AI video generation tools (Runway, Kling, Midjourney) always show the original prompt alongside generated content for reproducibility. This is metadata hygiene in AI workflows.

---

## Differentiators

Features that set Scene Text Inspector apart. Not expected, but valuable for power users.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| **Segment-Level Copy** | Copy individual segments vs entire scene | Low | Multiple copy buttons per segment |
| **Collapsible Metadata Sections** | Reduce cognitive load, show only relevant info | Medium | Accordion pattern (see PatternFly, Carbon Design) |
| **Click-to-Jump Timeline Link** | Click segment → jump to that moment in video preview | High | Requires video timeline integration (see Reduct.video) |
| **Inline Type Correction** | Fix "Dialogue" vs "Narrator" labeling errors directly | Medium | Quick-edit dropdown without leaving inspector |
| **Prompt History/Versions** | Show prompt iterations if scene was regenerated | Medium | Useful for AI iteration workflows |
| **Export Scene Text** | Export formatted text (Markdown, plain text, Fountain script format) | Medium | Useful for handoff to scriptwriters |
| **Search/Filter Segments** | Find specific dialogue across all scenes | High | Requires global search architecture |
| **Accessibility Metadata** | Show audio descriptions, caption timing | Medium | Important for accessibility compliance |

### Rationale

**Segment-Level Copy:** Transcript editors (Descript, CapCut) allow copying individual segments. This is more precise than copying entire scene text. Low complexity - just scope the copy action to segment text.

**Collapsible Metadata Sections:** Best practice for displaying heterogeneous metadata. Chrome DevTools uses collapsible sections extensively. Accordions reduce visual clutter and let users expand only relevant sections (speech, prompts, metadata). W3C ARIA guidance: use accordions when "content is divided into logical sections and not all is needed at once."

**Click-to-Jump Timeline Link:** Reduct.video's killer feature - "Click on a word in the transcript—we'll jump you right to that moment in that video." Powerful but requires video player integration. This is a V2 feature.

**Inline Type Correction:** Addresses the bug where "Dialogue" shows for narrator segments. Allowing inline correction creates a feedback loop for improving speech classification. Medium complexity because it requires validation and persistence.

**Prompt History/Versions:** RunwayML and other AI tools store seed, prompt, model version for reproducibility. If scenes are regenerated with different prompts, showing history helps users understand evolution. Important for AI-driven workflows.

**Export Scene Text:** Professional scriptwriting workflow. Export to Fountain format (industry standard screenplay format) or Markdown enables handoff to writers, editors, or archival. Medium complexity - formatting export logic.

---

## Anti-Features

Features to explicitly NOT build. Common mistakes in this domain.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| **Blocking Modal for Long Text** | Interrupts workflow, users can't reference storyboard | Use drawer/side-panel that slides in from edge |
| **Inline Editing of Speech Text** | Creates sync issues with generated video/audio | Make text read-only; link to regeneration flow |
| **Auto-Expand All Sections** | Overwhelming, defeats purpose of inspector | Default to collapsed; let users expand what they need |
| **Truncation in Inspector** | Defeats the purpose - inspector exists to show full text | Show complete text with scrolling |
| **Generic "Copy All" Only** | Too coarse-grained, users want specific segments | Offer both per-segment and full-scene copy |
| **Separate Modal for Each Prompt** | Creates modal proliferation, breaks workflow | Group all prompts/metadata in single inspector |
| **Custom Scrollbar Styling** | Accessibility issues, breaks native behavior | Use native scrollbars |
| **Auto-Dismissing Inspector** | Users need time to read, copy, reference | Manual close only (X button, ESC, outside click) |

### Rationale

**Blocking Modal for Long Text:** LogRocket's modal UX research: "Modals don't need too much information, so minimize the content." For detailed content, "alternative placements like dedicated pages or sidebars may serve better." Telus Design System caps modal body text at 400 characters. Scene text exceeds this. Medium article on Modal vs Drawer: "Drawers offer a more subtle and less disruptive way to access additional content... providing a seamless transition that does not fully interrupt the user's flow."

**Inline Editing of Speech Text:** Speech segments are generated or parsed from scripts. Editing text here creates divergence from source. If text is wrong, user should regenerate or edit source, not patch in inspector. Inspectors are for viewing and copying, not editing.

**Auto-Expand All Sections:** Accordion best practices (NN/G, W3C ARIA): "Avoid using the region role in circumstances that create landmark region proliferation." Auto-expanding defeats the purpose of progressive disclosure. Let users control what they see.

**Truncation in Inspector:** The entire purpose of the inspector is to solve the truncation problem in the storyboard cards. Truncating again would be absurd. If content is too long, use scrolling and collapsible sections.

**Generic "Copy All" Only:** Professional transcript tools (Descript, VEED.io) allow selecting specific segments. Users often want a single line of dialogue or one prompt, not the entire scene dump.

**Separate Modal for Each Prompt:** Modal proliferation creates "click fatigue." Frame.io and Adobe Premiere consolidate metadata into single panels with sections. One inspector with collapsible sections is cleaner.

**Custom Scrollbar Styling:** WebKit scrollbar styling breaks accessibility, touch scrolling, and native OS behavior. Design systems (Carbon, PatternFly) recommend native scrollbars for usability.

**Auto-Dismissing Inspector:** Unlike toast notifications (which should auto-dismiss), inspectors contain content users need to read, copy, and reference. Toast notifications research: "It is not advisable to time out the toaster if the toaster has an action." Copy actions require time.

---

## Feature Dependencies

```
Scene Text Inspector
├─ Speech Segments (already built)
│  ├─ Type classification (NARRATOR, DIALOGUE, etc.)
│  └─ Speaker attribution
├─ Scene Prompts (already exist)
│  ├─ Image generation prompt
│  └─ Video generation prompt
├─ Scene Metadata (already exists)
│  ├─ Scene number
│  ├─ Duration
│  └─ Shot type
└─ UI Container (NEW)
   ├─ Drawer/side-panel component
   ├─ Copy-to-clipboard utility
   ├─ Collapsible sections (accordion)
   └─ Toast notification system
```

**Critical Dependency:** The drawer/side-panel UI component. If framework doesn't have one, this adds complexity. Most modern UI libraries (Ant Design, Material-UI, Chakra, shadcn/ui) provide drawer components.

**Optional Enhancement:** Click-to-jump requires video player integration, which depends on video preview implementation.

---

## MVP Recommendation

For MVP (Scene Text Inspector v1), prioritize:

### Must-Have (Core Table Stakes)
1. **Drawer container** - Non-blocking, slides in from right
2. **Full speech segment display** - All text visible, no truncation
3. **Speech type indicators** - Visual badges (NARRATOR, DIALOGUE, INTERNAL, MONOLOGUE)
4. **Character/speaker labels** - Show who is speaking
5. **Copy button per segment** - One-click copy with toast confirmation
6. **Prompt display sections** - Collapsible sections for image/video prompts
7. **Basic scene metadata** - Scene number, duration, shot type
8. **Proper close behavior** - X button, ESC key, click-outside

### Defer to Post-MVP
- **Inline type correction** - Requires validation flow
- **Click-to-jump timeline** - Requires video player integration
- **Prompt history/versions** - Requires versioning architecture
- **Export scene text** - Requires export formatting logic
- **Search/filter segments** - Requires global search infrastructure
- **Accessibility metadata** - Add when accessibility audit happens

### Why This Prioritization

The MVP solves the immediate problem: **users can't see full text and type labels are wrong**. Drawer + full text + correct labels + copy buttons = problem solved. The deferred features are valuable but not blocking for the core use case.

Professional tools (Descript, Premiere, Frame.io) got their core inspectors right first, then added power features later. Follow that pattern.

---

## UX Pattern Comparison

| Tool | Container Type | Content Organization | Copy Mechanism | Key Insight |
|------|---------------|---------------------|----------------|-------------|
| **Descript** | Side panel | Segments with speaker labels | Select text → copy | Word-level timestamps for jump-to |
| **Adobe Premiere Metadata Panel** | Dockable panel | Schema-based sections | Right-click field → copy | Collapsible schemas (Dynamic Media, Exif, Dublin Core) |
| **Frame.io** | Side drawer | Tabbed sections | Context menu | Keeps video visible while reviewing metadata |
| **Reduct.video** | Transcript panel | Searchable segments | Click text → selects video | Text and video are bidirectionally linked |
| **RunwayML** | Info panel ("i" icon) | Metadata fields | Copy button per field | Shows seed, prompt, model, parameters |
| **Chrome DevTools** | Bottom/side panel | Collapsible sections | Context menu + copy buttons | Gold standard for data inspection |

**Common Pattern:** All use non-blocking containers (panels/drawers). All use collapsible sections for different metadata types. All provide granular copying (per-field or per-segment).

**Video Wizard Approach:** Follow Frame.io/Descript pattern - drawer from right, collapsible sections (Speech Segments, Prompts, Metadata), copy buttons per segment.

---

## Implementation Notes

### Drawer vs Modal Decision

**Recommendation: Drawer**

**Evidence:**
- Modal vs Drawer research (Medium): "Drawers are better suited to high-volume content... A drawer provides contextual information while also keeping the main object in view."
- LogRocket modal UX: "If a user needs to repeatedly perform a task, consider making it completable on the main page."
- Professional tools: Frame.io, Premiere, Descript all use side panels/drawers

**Specifications:**
- Slides in from right edge
- 400-600px wide (enough for readable text, not overwhelming)
- Semi-transparent backdrop (darkens main content slightly)
- Click backdrop → closes drawer
- Storyboard remains visible and interactive (though slightly dimmed)

### Copy Button Feedback

**Recommendation: Toast Notification**

**Evidence:**
- Toast notification research: "Success toasts use green color... as a response to an action."
- Timing: "Up to 10 words can be processed in 4000ms + 1000ms buffer"
- Discord pattern: "Reverse loading bar, countdown pauses on hover"

**Specifications:**
- Copy button → green toast appears (top-right or bottom-right)
- Message: "Text copied to clipboard" or "Segment copied"
- Duration: 3-4 seconds
- Icon: Checkmark or clipboard icon
- Auto-dismiss (but pause on hover)

### Collapsible Sections

**Recommendation: Accordion Pattern**

**Evidence:**
- W3C ARIA guidance: Use accordions "when content is divided into logical sections and not all is needed at once"
- PatternFly best practices: "Allow multiple sections to open at once... provide 'Expand/Collapse All' options"
- Avoid auto-expansion (creates "landmark region proliferation")

**Specifications:**
- Three sections: "Speech Segments", "Generation Prompts", "Scene Metadata"
- Default: "Speech Segments" expanded, others collapsed
- Chevron icons: ▼ (expanded) / ▶ (collapsed)
- "Expand All" / "Collapse All" buttons at top
- Smooth height transitions (200-300ms ease-in-out)

### Speech Type Indicators

**Recommendation: Badge/Tag UI**

**Evidence:**
- Descript speaker labels: "Descriptive tags including 'Soothing and narrative', 'Assertive and inspirational'"
- Film theory classification: Dialogue, Monologue (soliloquy/dramatic/internal), Voice-over/Narrator

**Specifications:**
- Visual badge before each segment: `[NARRATOR]`, `[DIALOGUE]`, `[INTERNAL]`, `[MONOLOGUE]`
- Color coding: Narrator (blue), Dialogue (green), Internal (purple), Monologue (orange)
- Small, non-intrusive (12-14px font, rounded corners)
- Tooltip on hover explaining the type

---

## Accessibility Considerations

### ARIA Roles for Drawer
- Drawer container: `role="dialog"` with `aria-modal="false"` (non-modal dialog)
- Drawer label: `aria-labelledby="drawer-title"`
- Focus trap: Focus moves to drawer when opened, ESC returns focus to trigger button

### ARIA Roles for Accordion
- Accordion header: `role="button"` inside `role="heading"`
- Panel visibility: `aria-expanded="true/false"` on header button
- Panel content: `aria-labelledby` pointing to header

### Copy Button Accessibility
- Button label: "Copy segment text" (not just icon)
- `aria-live="polite"` region for "Copied!" confirmation
- Keyboard: ENTER or SPACE triggers copy

### Text Contrast
- Ensure 4.5:1 contrast ratio for all text
- Badge colors must have sufficient contrast with background

---

## Technical Considerations

### Copy-to-Clipboard API
- Use Clipboard API: `navigator.clipboard.writeText(text)`
- Requires HTTPS in production (works on localhost for dev)
- Fallback: Create temporary `<textarea>`, select, `document.execCommand('copy')`
- Handle permissions (some browsers require user interaction first)

### Drawer Animation Performance
- Use CSS transforms (`translateX`) not `left` property (GPU-accelerated)
- Backdrop: `transition: opacity 200ms ease-in-out`
- Drawer: `transition: transform 300ms ease-in-out`

### Text Rendering
- Use `white-space: pre-wrap` for preserving line breaks in speech segments
- `word-break: break-word` for long words/URLs in prompts
- `overflow-y: auto` for scrolling within drawer

### Data Structure
Scene inspector receives:
```typescript
{
  sceneId: number,
  sceneNumber: number,
  duration: string,
  shotType: string,
  speechSegments: [
    { type: 'NARRATOR' | 'DIALOGUE' | 'INTERNAL' | 'MONOLOGUE',
      speaker: string,
      text: string }
  ],
  imagePrompt: string,
  videoPrompt: string,
  metadata: { [key: string]: any }
}
```

---

## Success Metrics

### User Satisfaction
- **Reduction in support requests** about "can't see full text"
- **Time to find information** - <5 seconds to open inspector and find segment
- **Copy actions** - Track how often users copy text (validates feature utility)

### Technical Performance
- **Drawer open time** - <300ms (perceived as instant)
- **Scroll smoothness** - 60fps within drawer
- **Copy latency** - <100ms from click to clipboard

### Correctness
- **Type label accuracy** - No more "Dialogue" for narrator segments
- **Text completeness** - 100% of text visible (no truncation)

---

## Sources

### Modal vs Drawer Patterns
- [Modal vs Drawer — When to use the right component (Medium)](https://medium.com/@ninad.kotasthane/modal-vs-drawer-when-to-use-the-right-component-af0a76b952da)
- [Modal UX design: Patterns, examples, and best practices (LogRocket)](https://blog.logrocket.com/ux-design/modal-ux-design-patterns-examples-best-practices/)
- [Drawer UI Design: Best practices (Mobbin)](https://mobbin.com/glossary/drawer)

### Transcript Viewer Patterns
- [Descript – AI Video & Podcast Editor](https://www.descript.com/)
- [Reduct.Video – Collaborative transcript-based platform](https://reduct.video/)
- [Transcribe video to text with AI (Adobe Premiere)](https://www.adobe.com/products/premiere/speech-to-text.html)
- [Detect and label speakers in your transcript (Descript Help)](https://help.descript.com/hc/en-us/articles/10249423506061-Automatic-Speaker-Detection)

### Metadata Display Patterns
- [7 Best Practices For Media Metadata Management (MASV)](https://massive.io/file-transfer/best-practices-for-metadata-management/)
- [Managing metadata in Premiere Pro (Adobe)](https://helpx.adobe.com/premiere-pro/using/metadata.html)
- [Frame.io Panel Overview (Adobe Integration)](https://help.frame.io/en/articles/12833113-adobe-premiere-frame-io-v4-panel-overview-25-6-and-later)

### Copy-to-Clipboard UX
- [Improving Copying to Clipboard Experience (Prototypr)](https://blog.prototypr.io/3-ways-to-copy-to-clipboard-5077f5774b55)
- [PatternFly Clipboard copy component](https://www.patternfly.org/components/clipboard-copy/)

### Toast Notifications
- [What is a toast notification? Best practices for UX (LogRocket)](https://blog.logrocket.com/ux-design/toast-notifications/)
- [Toast notifications — how to make it efficient (Medium)](https://medium.com/design-bootcamp/toast-notifications-how-to-make-it-efficient-400cab6026e9)

### Accordion Patterns
- [Accordion UI Examples: Best Practices (Eleken)](https://www.eleken.co/blog-posts/accordion-ui)
- [Accordion Pattern (W3C WAI ARIA)](https://www.w3.org/WAI/ARIA/apg/patterns/accordion/)
- [Accordions on Desktop: When and How to Use (Nielsen Norman Group)](https://www.nngroup.com/articles/accordions-on-desktop/)

### Text Truncation Patterns
- [PatternFly Truncation guidelines](https://www.patternfly.org/ux-writing/truncation/)
- [Carbon Design System Overflow content patterns](https://carbondesignsystem.com/patterns/overflow-content/)
- [Design for truncation (Medium)](https://medium.com/design-bootcamp/design-for-truncation-946951d5b6b8)

### Speech Classification
- [Dialogue and Voice-Over Narration (Fiveable)](https://library.fiveable.me/understanding-film/unit-8/dialogue-voice-over-narration/study-guide/KpcERHseVfJpehAg)
- [What is a Monologue — Definition, Examples & Types (StudioBinder)](https://www.studiobinder.com/blog/what-is-a-monologue-definition/)

### AI Prompt Metadata
- [Runway Resources: Text-to-Image Prompting Tips](https://runwayml.com/resources/how-to-make-an-ai-image-with-text-prompt)
- [Runway Resources: What is an AI Image Seed?](https://runwayml.com/resources/what-is-an-ai-image-seed)
- [AI Storyboard with Midjourney](https://shaicreative.ai/ai-storyboard-with-midjourney/)

---

**Research Confidence Level: HIGH**
- Professional tool patterns verified with official documentation
- UX best practices sourced from design system authorities (W3C, Nielsen Norman Group, LogRocket)
- Technical patterns validated against current (2026) implementations
- Speech classification based on film theory and industry tools

**Next Steps for Implementation:**
1. Choose/build drawer component (check if Livewire has drawer, or use Alpine.js + Tailwind)
2. Implement copy-to-clipboard utility with Clipboard API
3. Create accordion component for collapsible sections
4. Design badge/tag system for speech type indicators
5. Build toast notification system for copy feedback
