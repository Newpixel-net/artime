---
phase: 07-foundation-modal-shell-scene-card-fixes-metadata
plan: 03
subsystem: ui-modal-inspector-metadata
tags: [modal, metadata, badges, scene-inspector, ui-display]

requires:
  - 07-02: Scene Text Inspector modal shell
  - Scene data structure with metadata fields
  - Characters array in scene data

provides:
  - Complete scene metadata display (6 badge types)
  - Duration badge with MM:SS formatting
  - Transition badge with type-specific icons
  - Location badge with text truncation
  - Characters badge with +N more indicator
  - Emotional intensity badge with gradient colors
  - Climax badge for pivotal scenes

affects:
  - 07-04: Next plan will add speech segment display below metadata
  - Phase 8: Speech segments will follow metadata section pattern
  - Phase 9: Prompts will follow metadata section pattern

tech-stack:
  added:
    - CSS Grid auto-fit minmax pattern for responsive layout
    - Semantic color system (blue=time, purple=action, green=place, yellow=people, gradient=intensity)
    - Gradient border technique for climax badge
  patterns:
    - Badge component structure (icon + label + value)
    - Graceful fallback for missing/null metadata
    - Character name extraction from array or object format
    - Intensity color mapping (1-3 blue, 4-6 yellow, 7-10 red)
    - Full-width badge using grid-column: 1 / -1

key-files:
  created: []
  modified:
    - modules/AppVideoWizard/resources/views/livewire/modals/scene-text-inspector.blade.php

metrics:
  duration: 2m 20s
  completed: 2026-01-23

decisions:
  - id: metadata-badge-color-system
    choice: Use semantic colors for different metadata types
    rationale: Blue=time, purple=action, green=place, yellow=people creates instant visual categorization
    alternatives: [Uniform color (rejected - less scannable), Random colors (rejected - no meaning)]
    impact: Medium - Improves scannability and professional appearance

  - id: intensity-gradient-mapping
    choice: Map intensity 1-3 to blue, 4-6 to yellow, 7-10 to red
    rationale: Color progression provides instant understanding of emotional level without reading numbers
    alternatives: [Single color (rejected - no visual differentiation), Text labels (rejected - requires reading)]
    impact: High - Critical for quick emotional arc assessment

  - id: climax-badge-full-width
    choice: Make climax badge span full width with gradient border
    rationale: Climax scenes are pivotal moments that deserve prominent visual treatment
    alternatives: [Regular badge size (rejected - not prominent enough), Separate section (rejected - breaks flow)]
    impact: Medium - Makes climax scenes unmissable in inspector

  - id: character-truncation-strategy
    choice: Show first 3 characters with +N more indicator
    rationale: Balances information density with readability, users can see who's present without overwhelming
    alternatives: [Show all (rejected - too long), Show 1 (rejected - not enough context)]
    impact: Low - Practical display constraint for character lists

---

# Phase 07 Plan 03: Scene Metadata Display

**One-liner:** Comprehensive metadata badge system with duration (MM:SS), transition icons, location, characters (+N more), intensity gradient (blue/yellow/red), and full-width climax indicator

## What Was Built

Added complete scene metadata display section to Scene Text Inspector modal with 6 badge types:

1. **META-01 Duration Badge** - Shows scene duration formatted as MM:SS (e.g., "02:45" for 165 seconds) with blue theme
2. **META-02 Transition Badge** - Shows transition type with specific icons (CUT=✂️, FADE=🌫️, DISSOLVE=💫, etc.) with purple theme
3. **META-03 Location Badge** - Shows scene location with 30-character truncation and ellipsis with green theme
4. **META-04 Characters Badge** - Shows first 3 character names with "+N more" indicator with yellow theme
5. **META-05 Intensity Badge** - Shows emotional intensity (1-10) with gradient color mapping (1-3=blue, 4-6=yellow, 7-10=red)
6. **META-06 Climax Badge** - Full-width prominent badge with gradient border, only appears on climax scenes

All badges use responsive CSS Grid layout (auto-fit minmax 200px) and handle missing/null values gracefully.

## Tasks Completed

### Task 1: Create metadata section UI structure
**Status:** ✅ Complete
**Commit:** 1ae3078

- Added "Scene Metadata" section with heading and responsive grid container
- Added placeholder sections for Phase 8 (Speech Segments) and Phase 9 (Prompts)
- Grid layout uses `auto-fit minmax(200px, 1fr)` for responsive behavior without media queries

### Task 2: Add duration, transition, and location metadata badges
**Status:** ✅ Complete
**Commit:** f5cfc43

- Duration badge formats seconds as MM:SS (e.g., 165 → "02:45"), shows "N/A" if missing
- Transition badge maps types to icons: CUT=✂️, FADE=🌫️, DISSOLVE=💫, WIPE=↔️, IRIS=⭕, default=🎬
- Location badge handles both string and object formats, truncates at 30 chars with ellipsis
- Semantic colors: blue for duration (time), purple for transition (action), green for location (place)

### Task 3: Add characters, intensity, and climax indicator badges
**Status:** ✅ Complete
**Commit:** 0256ae7

- Characters badge shows first 3 names with "+N more" if more than 3 characters present
- Characters handles both array and object formats, shows "None" if empty
- Intensity badge maps 1-10 scale to gradient colors:
  - 1-3: Blue (low intensity)
  - 4-6: Yellow (medium intensity)
  - 7-10: Red (high intensity)
- Climax badge only displays when `isClimax` flag is true
- Climax badge spans full width using `grid-column: 1 / -1` with gradient border effect

## Deviations from Plan

None - plan executed exactly as written.

## Technical Implementation

### Badge Structure
Each badge follows consistent structure:
```blade
<div style="padding: 0.5rem 0.75rem; background: [color]; border: 1px solid [border]; border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
    <span style="font-size: 0.875rem;">[icon]</span>
    <div style="flex: 1; min-width: 0;">
        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">[Label]</div>
        <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500;">[Value]</div>
    </div>
</div>
```

### Responsive Grid Layout
```blade
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem;">
```
- Auto-fit creates as many columns as possible
- minmax(200px, 1fr) ensures badges never smaller than 200px
- Automatically responsive without media queries

### Data Access Pattern
All metadata accessed via computed property `$this->inspectorScene`:
```php
$scene = $this->inspectorScene['script'] ?? null;
$duration = $scene['metadata']['duration'] ?? null;
$transition = $scene['metadata']['transition'] ?? 'CUT';
$characters = $scene['characters'] ?? [];
$intensity = $scene['emotionalIntensity'] ?? null;
```

### Graceful Fallback Handling
- Duration: Shows "N/A" if null/missing
- Transition: Defaults to "CUT" if missing
- Location: Shows "Unknown" if missing
- Characters: Shows "None" if empty array
- Intensity: Shows "N/A" if null, gray badge color
- Climax: Only renders if `isClimax` flag is true

## Testing Performed

**Manual verification:**
- ✅ Modal content section has metadata grid with proper heading
- ✅ Duration badge exists with MM:SS formatting logic
- ✅ Transition badge exists with type-specific icon mapping
- ✅ Location badge exists with 30-char truncation
- ✅ Characters badge exists with +N more indicator logic
- ✅ Intensity badge exists with gradient color mapping
- ✅ Climax badge exists with conditional rendering and full-width style

**Data access verified:**
- ✅ All badges access data via `$scene` array from computed property
- ✅ Fallback values defined for all nullable fields
- ✅ Character name extraction handles both array and object formats

**Layout verified:**
- ✅ Grid uses auto-fit minmax pattern for responsiveness
- ✅ Climax badge spans full width via grid-column: 1 / -1

## Impact Assessment

**Performance:**
- No performance impact - metadata accessed via existing computed property
- All PHP processing done server-side during render
- No additional Livewire requests

**User Experience:**
- HIGH: Users can now view complete scene metadata in modal
- Users see duration formatted as readable MM:SS
- Users see transition type with visual icon
- Users see location with proper text truncation
- Users see character list with smart truncation
- Users see emotional intensity with instant color coding
- Climax scenes have prominent visual indicator

**Code Quality:**
- Consistent badge structure across all metadata types
- Graceful fallback handling for missing data
- Semantic color system improves scannability
- Responsive grid layout works on all screen sizes

## Next Phase Readiness

**Ready for Phase 07-04:**
- ✅ Metadata section complete and displaying properly
- ✅ Placeholder sections created for Speech Segments (Phase 8) and Prompts (Phase 9)
- ✅ Modal shell and metadata foundation solid

**Phase 8 Requirements:**
- Speech segments will be added to existing placeholder section
- Will follow same badge pattern as metadata
- Modal content scrolling already functional from Plan 07-02

**Phase 9 Requirements:**
- Prompts will be added to existing placeholder section
- Will follow same visual style as metadata and speech sections
- Copy-to-clipboard functionality will be added

## Known Issues

None.

## Files Modified

**Modified (1 file, 153 lines added):**
- `modules/AppVideoWizard/resources/views/livewire/modals/scene-text-inspector.blade.php`
  - Added Scene Metadata section (lines 42-172)
  - Added placeholder sections for Phase 8 and Phase 9 (lines 52-70)
  - Implemented 6 metadata badge types (META-01 through META-06)
  - Grid layout with responsive auto-fit pattern
  - All badges handle missing data gracefully

## Commits

| Hash    | Type | Description                                               |
|---------|------|-----------------------------------------------------------|
| 1ae3078 | feat | Add metadata section structure to scene text inspector modal |
| f5cfc43 | feat | Add duration, transition, and location metadata badges    |
| 0256ae7 | feat | Add characters, intensity, and climax metadata badges     |

---

**Plan Status:** ✅ Complete
**All Requirements Met:** META-01 ✅ META-02 ✅ META-03 ✅ META-04 ✅ META-05 ✅ META-06 ✅
**Next:** Plan 07-04 - Modal UX polish (scroll, mobile responsive)
