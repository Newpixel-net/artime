# Architecture: Scene Text Inspector Integration

**Project:** Scene Text Inspector Modal for Video Wizard
**Researched:** 2026-01-23
**Confidence:** HIGH

## Executive Summary

Scene Text Inspector integrates into VideoWizard's existing modal architecture using the established pattern: boolean toggle property, open/close methods, and direct data access via `$this->script['scenes'][$index]` and `$this->storyboard[$index]`. The modal is read-only (inspection only, no editing), so it requires minimal new PHP logic.

**Integration approach:** Lightweight read-only modal following Character Bible/Location Bible pattern with scene-scoped data access.

## Recommended Architecture

### Modal Display Pattern

```
User action (button click on scene card)
    → wire:click="openSceneTextInspectorModal({{ $index }})"
    → Sets $showSceneTextInspectorModal = true
    → Sets $inspectorSceneIndex = $index
    → Modal blade file renders (@if($showSceneTextInspectorModal))
    → Accesses $script['scenes'][$inspectorSceneIndex] for data
    → User closes modal
    → wire:click="closeSceneTextInspectorModal()"
    → Sets $showSceneTextInspectorModal = false
```

This matches the pattern used by:
- Character Bible Modal (`$showCharacterBibleModal`)
- Location Bible Modal (`$showLocationBibleModal`)
- Scene DNA Modal (`$showSceneDNAModal`)
- Edit Prompt Modal (`$showEditPromptModal`, `$editPromptSceneIndex`)

## Data Flow

### Source Data Locations

| Data Type | Primary Source | Fallback | Structure |
|-----------|---------------|----------|-----------|
| Speech Segments | `$script['scenes'][$index]['speechSegments']` | `[]` | Array of segment objects with type, speaker, text, needsLipSync |
| Image Prompt | `$storyboard[$index]['prompt']` | `$script['scenes'][$index]['visualDescription']` | String |
| Video Prompt | `$storyboard[$index]['videoPrompt']` | `''` | String |
| Scene Duration | `$storyboard[$index]['duration']` | `$script['scenes'][$index]['duration']` | Integer (seconds) |
| Transition | `$storyboard[$index]['transition']` | `'cut'` | String |
| Shot Type | `$storyboard[$index]['shotType']` | `null` | String |
| Camera Movement | `$storyboard[$index]['cameraMovement']` | `null` | String |

### Data Structure Reference

**Speech Segment (from `$script['scenes'][N]['speechSegments'][N]`):**
```php
[
    'id' => 'seg-abc123',
    'type' => 'narrator' | 'dialogue' | 'internal' | 'monologue',
    'text' => 'The spoken text content',
    'speaker' => 'CHARACTER NAME' | null,
    'characterId' => 'char-xyz789' | null,
    'voiceId' => 'voice-id' | null,
    'needsLipSync' => true | false,
    'startTime' => 0.0 | null,
    'duration' => 5.2 | null,
    'audioUrl' => 'https://...' | null,
    'order' => 0,
    'emotion' => 'whispering' | null,
]
```

**Scene Data (from `$script['scenes'][N]`):**
```php
[
    'id' => 'scene-abc123',
    'title' => 'Scene Title',
    'visualDescription' => 'Long description...',
    'speechSegments' => [/* array of segments */],
    'speechType' => 'mixed' | 'narrator' | 'dialogue' | 'monologue',
    'duration' => 10, // seconds
    // ... other fields
]
```

**Storyboard Data (from `$storyboard[$index]`):**
```php
[
    'prompt' => 'Cinematic image prompt...',
    'videoPrompt' => 'Action description for video generation...',
    'imageUrl' => 'https://...',
    'duration' => 10,
    'transition' => 'cut' | 'fade' | 'dissolve',
    'shotType' => 'wide' | 'medium' | 'close-up' | etc.,
    'cameraMovement' => 'static' | 'push_in' | 'tracking' | etc.,
    // ... other fields
]
```

## Integration Points with VideoWizard.php

### New Properties Required

```php
// Modal state (add to VideoWizard.php class properties around line 1024-1030)
public bool $showSceneTextInspectorModal = false;
public int $inspectorSceneIndex = 0;
```

### New Methods Required

```php
/**
 * Open Scene Text Inspector modal for a specific scene.
 */
public function openSceneTextInspectorModal(int $sceneIndex): void
{
    if (!isset($this->script['scenes'][$sceneIndex])) {
        $this->dispatch('toast-error', message: __('Scene not found'));
        return;
    }

    $this->inspectorSceneIndex = $sceneIndex;
    $this->showSceneTextInspectorModal = true;
}

/**
 * Close Scene Text Inspector modal.
 */
public function closeSceneTextInspectorModal(): void
{
    $this->showSceneTextInspectorModal = false;
    // No save needed - read-only modal
}
```

### Blade View Location

```
modules/AppVideoWizard/resources/views/livewire/modals/scene-text-inspector.blade.php
```

### Render Integration

Add to main VideoWizard blade view (likely in `steps/4-storyboard.blade.php` or main component view):

```blade
@include('livewire.modals.scene-text-inspector')
```

## Component Architecture

### Modal Structure

```
scene-text-inspector.blade.php
├── Modal Overlay (@if($showSceneTextInspectorModal))
│   ├── Header
│   │   ├── Title: "Scene Text Inspector - Scene {N}"
│   │   └── Close Button (wire:click="closeSceneTextInspectorModal()")
│   │
│   ├── Scene Metadata Section
│   │   ├── Duration badge
│   │   ├── Transition badge
│   │   ├── Shot type badge (if set)
│   │   └── Camera movement badge (if set)
│   │
│   ├── Speech Segments Section
│   │   └── @foreach($script['scenes'][$inspectorSceneIndex]['speechSegments'] ?? [] as $segment)
│   │       ├── Segment card
│   │       │   ├── Type badge (Narrator/Dialogue/Internal/Monologue)
│   │       │   ├── Speaker name (if applicable)
│   │       │   ├── Text content
│   │       │   └── Lip-sync indicator
│   │
│   ├── Image Prompt Section
│   │   └── Display: $storyboard[$inspectorSceneIndex]['prompt']
│   │       or fallback to $script['scenes'][$inspectorSceneIndex]['visualDescription']
│   │
│   └── Video Prompt Section
│       └── Display: $storyboard[$inspectorSceneIndex]['videoPrompt']
```

### Data Access Pattern

**Preferred pattern (used throughout VideoWizard modals):**

```blade
@php
    $scene = $script['scenes'][$inspectorSceneIndex] ?? null;
    $storyboardScene = $storyboard[$inspectorSceneIndex] ?? null;
@endphp

@if($scene)
    {{-- Access scene data --}}
    {{ $scene['title'] ?? 'Untitled Scene' }}

    @foreach($scene['speechSegments'] ?? [] as $segment)
        {{-- Display segment --}}
    @endforeach
@endif
```

**Why this pattern:**
1. Matches existing codebase style (see character-bible.blade.php, scene-dna.blade.php)
2. Provides null safety with `??` operator
3. Allows conditional rendering if scene doesn't exist
4. Clear variable naming for template readability

## Build Order Considerations

### Phase 1: Core Modal Shell
**Estimated time:** 1 hour
**Files:**
- `scene-text-inspector.blade.php` (create modal structure, header, close button)
- `VideoWizard.php` (add properties, open/close methods)

**Validation:**
- Modal opens/closes correctly
- Scene index tracks properly
- No console errors

### Phase 2: Metadata Display
**Estimated time:** 1 hour
**Files:**
- `scene-text-inspector.blade.php` (add metadata badges section)

**Validation:**
- Duration, transition, shot type, camera movement display correctly
- Handles missing data gracefully (null checks)

### Phase 3: Speech Segments Display
**Estimated time:** 2 hours
**Files:**
- `scene-text-inspector.blade.php` (add speech segments section)

**Validation:**
- All segment types render with correct badges
- Narrator segments show no speaker
- Dialogue/monologue/internal show speaker name
- Lip-sync indicator displays correctly
- Empty speechSegments array handled gracefully

### Phase 4: Prompts Display
**Estimated time:** 1 hour
**Files:**
- `scene-text-inspector.blade.php` (add image/video prompt sections)

**Validation:**
- Image prompt displays (with fallback to visualDescription)
- Video prompt displays (gracefully handles empty)
- Long prompts are scrollable/readable

### Phase 5: Styling & Polish
**Estimated time:** 1 hour
**Files:**
- `scene-text-inspector.blade.php` (apply consistent styling)

**Validation:**
- Matches existing modal visual style
- Responsive layout
- Dark theme consistency
- Typography hierarchy clear

## Architecture Patterns

### Pattern 1: Read-Only Data Display
**What:** Modal accesses but never modifies scene data
**Why:** Simpler implementation, no save logic, no validation needed
**How:**
```blade
{{-- Read data, never wire:model --}}
<div>{{ $scene['title'] }}</div>

{{-- NOT this (editing) --}}
<input wire:model="scene.title">
```

### Pattern 2: Computed Properties Not Needed
**What:** No computed properties required - direct array access in blade
**Why:** Livewire can access public properties directly, computed overhead unnecessary for simple display
**How:**
```blade
{{-- Direct access (preferred) --}}
@foreach($script['scenes'][$inspectorSceneIndex]['speechSegments'] ?? [] as $segment)

{{-- NOT this (adds complexity) --}}
@foreach($this->getInspectorSpeechSegments() as $segment)
```

**Exception:** Only add computed property if:
- Complex data transformation needed
- Multiple template locations use same computation
- Performance optimization required (memoization)

### Pattern 3: Null Safety with Fallbacks
**What:** Always provide fallback values for potentially missing data
**Why:** VideoWizard data structures evolve, old projects may lack new fields
**How:**
```blade
{{-- Good: Null-safe with fallback --}}
{{ $storyboard[$inspectorSceneIndex]['videoPrompt'] ?? __('No video prompt set') }}

{{-- Bad: Assumes field exists --}}
{{ $storyboard[$inspectorSceneIndex]['videoPrompt'] }}
```

### Pattern 4: Inline PHP for Data Preparation
**What:** Use `@php` blocks to prepare data before rendering complex sections
**Why:** Keeps template logic readable, follows existing modal patterns
**How:**
```blade
@php
    $segments = $scene['speechSegments'] ?? [];
    $hasSegments = count($segments) > 0;
    $narratorCount = count(array_filter($segments, fn($s) => $s['type'] === 'narrator'));
@endphp

@if($hasSegments)
    {{-- Render with prepared data --}}
@endif
```

## Existing Modal Patterns Reference

### Character Bible Modal Pattern
```php
// Properties
public bool $showCharacterBibleModal = false;
public int $editingCharacterIndex = 0;

// Methods
public function openCharacterBibleModal(): void
{
    // Auto-sync from Story Bible if needed
    $this->showCharacterBibleModal = true;
    $this->editingCharacterIndex = 0;
}

public function closeCharacterBibleModal(): void
{
    $this->showCharacterBibleModal = false;
    // Rebuild Scene DNA after changes
    $this->buildSceneDNA();
}
```

**Key difference for Scene Text Inspector:**
- No rebuild needed on close (read-only)
- No auto-sync needed (not editing data)
- Scene index parameter (Character Bible edits all characters, Inspector views one scene)

### Scene DNA Modal Pattern
```php
// Properties
public bool $showSceneDNAModal = false;
public string $sceneDNAActiveTab = 'overview';

// Methods
public function openSceneDNAModal(string $tab = 'overview'): void
{
    // Build Scene DNA first to ensure fresh data
    if (!empty($this->script['scenes'])) {
        $this->buildSceneDNA();
    }

    $this->sceneDNAActiveTab = $tab;
    $this->showSceneDNAModal = true;
}
```

**Key difference for Scene Text Inspector:**
- No tab navigation needed (single view)
- No data building needed (direct access to existing data)
- Scene-scoped (DNA is global across all scenes)

## Performance Considerations

### No Computed Properties Needed
- VideoWizard is ~18k lines, already heavy
- Read-only display doesn't benefit from memoization
- Direct array access in Blade is fast enough for <100 scenes
- Computed properties add method calls, memory overhead

### Livewire Reactivity Not Needed
- Modal displays snapshot of scene at open time
- No real-time updates while modal open
- If scene data changes externally, user must close/reopen to see updates
- This matches existing modal behavior (e.g., Edit Prompt modal)

### Data Volume Assessment
- Typical scene: 3-5 speech segments
- Max segments per scene: 50 (enforced by `SpeechSegment::MAX_SEGMENTS_PER_SCENE`)
- Prompts: ~500-2000 characters each
- **Total data per modal render:** <50KB
- **Blade rendering time:** <50ms
- **Conclusion:** Direct rendering is performant

## Anti-Patterns to Avoid

### Anti-Pattern 1: Over-Abstraction
**Bad:**
```php
// VideoWizard.php
public function getInspectorSceneTitle()
{
    return $this->script['scenes'][$this->inspectorSceneIndex]['title'] ?? 'Untitled';
}
```

**Why bad:** Adds method for trivial one-liner, increases maintenance burden

**Good:**
```blade
{{-- scene-text-inspector.blade.php --}}
{{ $scene['title'] ?? 'Untitled Scene' }}
```

### Anti-Pattern 2: Computed Properties for Display
**Bad:**
```php
// VideoWizard.php
#[Computed]
public function inspectorScene()
{
    return $this->script['scenes'][$this->inspectorSceneIndex] ?? null;
}
```

**Why bad:**
- Livewire caches computed properties per request, but modal is single-render
- No performance benefit for read-once data
- Adds complexity for zero gain

**Good:**
```blade
@php
    $scene = $script['scenes'][$inspectorSceneIndex] ?? null;
@endphp
```

### Anti-Pattern 3: Scene Data Duplication
**Bad:**
```php
// VideoWizard.php
public function openSceneTextInspectorModal(int $sceneIndex): void
{
    $this->inspectorSceneIndex = $sceneIndex;
    $this->inspectorSceneData = $this->script['scenes'][$sceneIndex]; // DUPLICATION
    $this->showSceneTextInspectorModal = true;
}
```

**Why bad:**
- Duplicates data in component state
- Increases memory usage
- Data can become stale if scene edited elsewhere
- Violates single source of truth

**Good:**
```php
public function openSceneTextInspectorModal(int $sceneIndex): void
{
    $this->inspectorSceneIndex = $sceneIndex; // Just store index
    $this->showSceneTextInspectorModal = true;
}
```

## Validation & Error Handling

### Scene Index Validation

```php
public function openSceneTextInspectorModal(int $sceneIndex): void
{
    // Validate scene exists before opening
    if (!isset($this->script['scenes'][$sceneIndex])) {
        $this->dispatch('toast-error', message: __('Scene not found'));
        return;
    }

    $this->inspectorSceneIndex = $sceneIndex;
    $this->showSceneTextInspectorModal = true;
}
```

**Why:** Prevents modal opening with invalid scene index, which would cause blade errors.

### Blade Null Safety

```blade
@php
    $scene = $script['scenes'][$inspectorSceneIndex] ?? null;
@endphp

@if($scene)
    {{-- Render modal content --}}
@else
    <div style="padding: 2rem; text-align: center; color: rgba(255,255,255,0.5);">
        {{ __('Scene data not available') }}
    </div>
@endif
```

**Why:** Defensive programming for edge cases (scene deleted while modal open, data corruption).

## Integration Checklist

- [ ] Add `$showSceneTextInspectorModal` property to VideoWizard.php
- [ ] Add `$inspectorSceneIndex` property to VideoWizard.php
- [ ] Add `openSceneTextInspectorModal()` method to VideoWizard.php
- [ ] Add `closeSceneTextInspectorModal()` method to VideoWizard.php
- [ ] Create `scene-text-inspector.blade.php` modal file
- [ ] Add `@include('livewire.modals.scene-text-inspector')` to main view
- [ ] Add trigger button to scene cards (`wire:click="openSceneTextInspectorModal({{ $index }})"`)
- [ ] Test modal open/close
- [ ] Verify all data displays correctly
- [ ] Test with scenes that have no speechSegments
- [ ] Test with scenes that have missing storyboard data
- [ ] Verify styling matches existing modals
- [ ] Test on different screen sizes (responsive)

## Sources

**HIGH Confidence** - Direct codebase analysis:
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` (existing modal patterns, data structures)
- `modules/AppVideoWizard/resources/views/livewire/modals/character-bible.blade.php` (modal UI pattern)
- `modules/AppVideoWizard/resources/views/livewire/modals/scene-dna.blade.php` (scene data access pattern)
- `modules/AppVideoWizard/resources/views/livewire/modals/edit-prompt.blade.php` (scene-scoped modal pattern)
- `modules/AppVideoWizard/app/Services/SpeechSegment.php` (segment data structure)
- `modules/AppVideoWizard/app/Services/SpeechSegmentParser.php` (segment types, constants)
