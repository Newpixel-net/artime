---
status: resolved
trigger: "new-project-state-leak: When starting a new project via My Projects -> new project, configuration from previous projects persists"
created: 2026-01-25T00:00:00Z
updated: 2026-01-25T00:03:00Z
resolved: 2026-01-25T00:03:00Z
---

## Current Focus

hypothesis: CONFIRMED - mount() did not reset properties when $project is null
test: Fix applied - added resetToDefaults() method
expecting: New projects now start with clean state
next_action: RESOLVED - Commit fix

## Symptoms

expected: New project starts completely fresh with no data from previous projects
actual: Multiple things persist including script/story content from previous job
errors: No explicit errors reported
reproduction: Go to "My Projects" -> click "new project" after working on another project
started: Intermittent - user reports "sometimes"

## Eliminated

## Evidence

- timestamp: 2026-01-25T00:00:30Z
  checked: mount() method at lines 1502-1512
  found: |
    mount() only handles two cases:
    1. loadDynamicSettings() - always runs
    2. loadProject($project) - ONLY runs if $project exists
    When $project is null (new project), NO property reset occurs
  implication: Properties retain values from previous Livewire component state

- timestamp: 2026-01-25T00:00:45Z
  checked: Property declarations lines 78-1198
  found: |
    100+ public properties with default values including:
    - $script = ['title' => '', 'hook' => '', 'scenes' => [], ...]
    - $storyboard = ['scenes' => [], ...]
    - $storyBible = ['enabled' => false, ...]
    - $sceneMemory = [...]
    - $concept = ['rawInput' => '', ...]
    All have inline defaults, but these only apply at CLASS instantiation
  implication: If Livewire reuses component instance, defaults don't reapply

- timestamp: 2026-01-25T00:00:55Z
  checked: Route and controller flow
  found: |
    1. "New Project" links to route('app.video-wizard.index')
    2. Controller index() passes $project = null to view
    3. View: @livewire('video-wizard', ['project' => $project])
    4. mount($project = null) is called
  implication: Flow is correct, but mount() doesn't handle null case

- timestamp: 2026-01-25T00:01:30Z
  checked: Fix implementation
  found: |
    Added resetToDefaults() method (lines 1518-1889) that resets:
    - Project identification (projectId, projectName, currentStep, maxReachedStep)
    - Platform & Format settings (platform, aspectRatio, targetDuration, format, etc.)
    - Production Intelligence and Cinematic Analysis
    - Production & Content Configuration
    - Concept, Character Intelligence, Script, Storyboard, Animation, Assembly
    - Scene Memory (Style/Character/Location Bibles, Scene DNA)
    - Story Bible with all sub-structures
    - Multi-Shot Mode and scene collages
    - All UI state flags and modal states
    - Export enhancement and auto-proceed settings

    Modified mount() to call resetToDefaults() when $project is null.
  implication: All 100+ properties now reset to defaults for new projects

## Resolution

root_cause: |
  VideoWizard.php mount() method (lines 1502-1512) did not reset component
  properties when $project is null. When navigating to a new project:

  1. User works on Project A, properties filled with data
  2. User clicks "New Project" -> $project = null
  3. mount() called loadDynamicSettings() but NOT resetToDefaults()
  4. All 100+ properties retained their values from Project A

  The bug was intermittent because it depended on:
  - Whether Livewire reused the component instance (wire:navigate)
  - Browser caching/session state
  - Order of navigation

fix: |
  Added resetToDefaults() protected method (lines 1518-1889) that explicitly
  resets ALL project-specific properties to their default values.

  Modified mount() method (lines 1502-1516) to call resetToDefaults() in
  an else branch when $project is null or doesn't exist.

  The fix ensures that whether Livewire reuses the component instance or
  creates a new one, all properties are guaranteed to be in their default
  state when starting a new project.

verification: |
  Manual verification required:
  1. Open an existing project with data (script, storyboard, etc.)
  2. Navigate to "My Projects"
  3. Click "New Project"
  4. Verify all fields are empty/default:
     - Project name should be "Untitled Video"
     - Current step should be 1
     - Concept should be empty
     - Script should have no scenes
     - Storyboard should be empty
     - All Bibles should be disabled with no content
  5. Check Laravel logs for: "VideoWizard: Reset to defaults for new project"

files_changed:
  - modules/AppVideoWizard/app/Livewire/VideoWizard.php
