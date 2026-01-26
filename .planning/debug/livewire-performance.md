---
status: diagnosed
trigger: "Critical Livewire slowness throughout Video Wizard, especially at storyboard stage"
created: 2026-01-25T00:00:00Z
updated: 2026-01-25T00:01:00Z
---

## Current Focus

hypothesis: CONFIRMED - Multiple architectural issues causing massive Livewire payload and slow re-renders
test: Analysis complete - root causes identified
expecting: N/A - analysis mode
next_action: Generate comprehensive recommendations

## Symptoms

expected: Smooth, responsive UI with fast interactions and no disconnections between wizard stages
actual: UI freezes/hangs, slow page loads, laggy interactions - all combined. Sometimes Livewire errors in console.
errors: Intermittent Livewire errors in browser console
reproduction: General usage throughout video wizard, worst at storyboard stage
started: Ongoing issue

## Eliminated

## Evidence

- timestamp: 2026-01-25T00:00:30Z
  checked: VideoWizard.php file size
  found: 1.3MB file size, 31,489 lines of code
  implication: CRITICAL - This is 10-20x larger than recommended for a Livewire component

- timestamp: 2026-01-25T00:00:35Z
  checked: Public array properties (serialized on every request)
  found: 35+ public array properties including storyboard, script, sceneMemory, multiShotMode, storyBible, etc.
  implication: All these serialize on EVERY Livewire request, creating massive payload

- timestamp: 2026-01-25T00:00:40Z
  checked: Base64 image data in component state
  found: referenceImageBase64 stored directly in sceneMemory.characterBible.characters, locationBible.locations, styleBible
  implication: Each character portrait can be 100KB-500KB of base64 data - multiplied by characters = megabytes in component state

- timestamp: 2026-01-25T00:00:45Z
  checked: Livewire attributes usage
  found: ZERO #[Locked] or #[Computed] attributes used
  implication: All properties are serialized even when they could be computed or locked

- timestamp: 2026-01-25T00:00:50Z
  checked: wire:model bindings
  found: 154+ wire:model bindings across blade files, many using wire:model.live on deeply nested arrays
  implication: Live bindings trigger full re-render and payload transmission on every keystroke

- timestamp: 2026-01-25T00:00:55Z
  checked: wire:poll usage
  found: Only 1 polling instance (multi-shot.blade.php with 5s interval) - minimal impact
  implication: Polling is not the main issue

- timestamp: 2026-01-25T00:01:00Z
  checked: updated() hook implementation
  found: Generic updated() hook processes ALL property changes, includes regex matching and buildSceneDNA() calls
  implication: Every property change triggers expensive pattern matching and potential DNA rebuilding

- timestamp: 2026-01-25T00:01:05Z
  checked: Blade template sizes
  found: storyboard.blade.php is 275KB (6,899 lines), video-wizard.blade.php also large
  implication: Large templates with many conditionals slow down Livewire diffing

- timestamp: 2026-01-25T00:01:10Z
  checked: Scene iteration patterns
  found: Multiple @foreach loops over script['scenes'] in different partials (timeline, transitions, etc.)
  implication: Same data iterated multiple times per render

## Resolution

root_cause: |
  MULTIPLE ARCHITECTURAL ISSUES (in order of impact):

  1. GIANT MONOLITHIC COMPONENT (31,489 lines)
     - Single component handles ALL wizard functionality
     - Every interaction serializes/deserializes the entire component state
     - No component splitting for independent functionality

  2. MASSIVE PAYLOAD FROM NESTED ARRAYS
     - script.scenes can contain 45+ scenes, each with narration, visualDescription, shots, etc.
     - storyboard.scenes mirrors this with imageUrl, prompt, chainData
     - multiShotMode.decomposedScenes stores shots per scene (5-10 per scene)
     - sceneMemory.sceneDNA.scenes duplicates scene data again
     - Estimated payload: 500KB-5MB per request for active projects

  3. BASE64 IMAGES IN COMPONENT STATE
     - referenceImageBase64 stored for characters, locations, style references
     - Each image: 100KB-500KB base64 encoded
     - 5 characters + 3 locations = potentially 4MB+ just in images
     - This data serializes on EVERY Livewire request

  4. NO LIVEWIRE 3 ATTRIBUTES USED
     - Missing #[Locked] for read-only properties (constants, configs)
     - Missing #[Computed] for derived values
     - All 35+ array properties serialize every time

  5. INEFFICIENT updated() HOOK
     - Generic hook catches ALL property changes
     - Uses regex matching on every update
     - Triggers buildSceneDNA() which iterates all scenes

  6. LIVE BINDINGS ON NESTED ARRAYS
     - wire:model.live on assembly.captions.*, storyboard.*, sceneMemory.*
     - Each keystroke triggers full component re-render
     - Should use wire:model.blur or wire:model.debounce

fix: See comprehensive recommendations below
verification: Performance testing after implementation
files_changed: []
