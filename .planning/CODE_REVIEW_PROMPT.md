# Critical Code Review: Shot Generation Quality Issues

## Overview

This document describes three critical issues affecting video production quality in the Video Wizard. After reviewing screenshots and analyzing the codebase, these problems need investigation and resolution.

---

## Issue #1: Image Prompts Are Short/Basic - Not Using Full Prompt System

### What's Wrong
The screenshot shows IMAGE PROMPT displaying basic text like:
> "Establishing shot revealing scene geography. Wide view establishing the scene environment and atmosphere. Opening moment of the scene. Conveying exciting emotion.. wide-angle 24mm. High-energy action style, dynamic camera work, intense visuals, high quality, detailed, professional, 8K..."

This is generic and doesn't tell the actual story of the scene. The images generated look "banal" and don't capture the narrative.

### Root Cause Investigation

The codebase has sophisticated prompt building systems that may NOT be connected properly:

1. **StructuredPromptBuilderService** (`modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php`)
   - Builds structured JSON-based prompts for photorealistic image generation
   - Contains detailed visual mode templates: `cinematic-realistic`, `documentary-realistic`, etc.
   - Includes authenticity markers: "subtle 35mm film grain, visible pores and texture, micro-imperfections..."
   - Has comprehensive negative prompts to avoid AI artifacts
   - **THIS MAY NOT BE CONNECTED TO SHOT GENERATION**

2. **VideoPromptBuilderService** (`modules/AppVideoWizard/app/Services/VideoPromptBuilderService.php`)
   - Implements Hollywood formula: [Camera Shot + Motion] + [Subject + Action] + [Environment] + [Lighting] + [Style]
   - Has quality markers (cinematic, broadcast, premium, documentary)
   - Contains ACTION_VERBS library for realistic subject actions
   - Has COLOR_GRADING presets (teal-orange, bleach-bypass, etc.)
   - **MAY NOT BE FULLY INTEGRATED INTO SHOT DECOMPOSITION**

3. **Shot Decomposition Flow** (in `VideoWizard.php`)
   - Line 19710-19712 shows shots being built with `buildShotPrompt()` and `getMotionDescriptionForShot()`
   - The prompts stored in `$shot['imagePrompt']` and `$shot['videoPrompt']` may be basic templates
   - The sophisticated services above may not be called during shot decomposition

### Where to Investigate

```
modules/AppVideoWizard/app/Livewire/VideoWizard.php
├── Line 19710: buildShotPrompt() - Check what this actually returns
├── Line 19711: getMotionDescriptionForShot() - Check if VideoPromptBuilderService is used
├── Line 11157: buildScenePrompt() - Scene-level prompt building
└── Search for where StructuredPromptBuilderService is instantiated/used

modules/AppVideoWizard/app/Services/
├── StructuredPromptBuilderService.php - Is this being called?
├── VideoPromptBuilderService.php - Is this being called during decomposition?
├── ShotIntelligenceService.php - Does this use the prompt builders?
└── ImageGenerationService.php - Line 81-88 injects StructuredPromptBuilderService
```

### Likely Fix

The `StructuredPromptBuilderService` is injected into `ImageGenerationService` (line 81-88) but may not be used when building shot prompts during decomposition. The full prompt system exists but the connection is broken somewhere in the flow:

```
Script → Shot Decomposition → [BROKEN?] → Full Prompt Building → Image Generation
```

Need to ensure that when shots are decomposed, they call the full `StructuredPromptBuilderService` and `VideoPromptBuilderService` to build rich, narrative-driven prompts instead of basic templates.

---

## Issue #2: Voice/Narrator Prompt Is Cut Off or Missing

### What's Wrong
The screenshot shows NARRATOR section with only partial text:
> "In a near-future"

This appears to be truncated. A proper narrator voiceover should have complete sentences describing what's happening in the scene.

### Root Cause Investigation

The voice/narration flow:

1. **Script Generation** creates scenes with `narration` field
2. **ShotIntelligenceService** (`modules/AppVideoWizard/app/Services/ShotIntelligenceService.php`)
   - Line 170-171: Uses `$scene['narration']`
   - Passes narration to NarrativeMomentService for decomposition
3. **VoicePromptBuilderService** (`modules/AppVideoWizard/app/Services/VoicePromptBuilderService.php`)
   - Builds enhanced voice prompts with emotional direction
   - Has ambient audio cues and emotional arc patterns
   - **MAY NOT BE INTEGRATED WITH SHOT DATA**

4. **Shot Preview Modal** (`shot-preview.blade.php`)
   - Line 293-295: Displays `$shot['narration']`
   - The narration may not be properly populated from the script

### Where to Investigate

```
modules/AppVideoWizard/app/Livewire/VideoWizard.php
├── Search for where 'narration' is assigned to shots
├── Check script → shot decomposition narration flow
└── Verify narration is properly split across shots

modules/AppVideoWizard/app/Services/
├── ShotIntelligenceService.php - Line 170-188: narration handling
├── NarrativeMomentService.php - Decomposes narration into moments
├── VoicePromptBuilderService.php - Enhanced voice prompts
└── DialogueSceneDecomposerService.php - Dialogue extraction

modules/AppVideoWizard/resources/views/livewire/modals/shot-preview.blade.php
├── Line 197-210: Speech content detection
├── Line 292-295: Narration display
└── Verify $shot['narration'] is populated
```

### Likely Problem Areas

1. **Narration not being split properly** - Full scene narration may not be distributed across shots
2. **SpeechSegmentParser** not being called during shot decomposition
3. **VoicePromptBuilderService** exists but is not integrated with shot generation
4. **Script scenes have narration but shots lose it during decomposition**

---

## Issue #3: Default Image Model Should Be NanoBanana Pro

### What's Wrong
The default image model is `nanobanana` (regular) instead of `nanobanana-pro`. The Pro model provides:
- 4K resolution (vs 1K for regular)
- Better face consistency (5 refs vs 3 refs)
- HD quality vs basic quality
- Better results for production-quality content

### Exact Location to Fix

**File:** `modules/AppVideoWizard/app/Livewire/VideoWizard.php`
**Line:** 721

```php
public array $storyboard = [
    'scenes' => [],
    'styleBible' => null,
    'imageModel' => 'nanobanana', // <-- CHANGE TO 'nanobanana-pro'
    'visualStyle' => [
        'mood' => '',
        'lighting' => '',
```

### Model Definitions (for reference)

From `ImageGenerationService.php` lines 49-79:

```php
'nanobanana-pro' => [
    'name' => 'NanoBanana Pro',
    'description' => 'Best quality, superior face consistency (up to 5 reference faces), 4K output',
    'tokenCost' => 3,
    'provider' => 'gemini',
    'model' => 'gemini-3-pro-image-preview',
    'resolution' => '4K',
    'quality' => 'hd',
    'maxHumanRefs' => 5,
],
'nanobanana' => [
    'name' => 'NanoBanana',
    'description' => 'Quick drafts, good balance of speed and quality',
    'tokenCost' => 1,
    'provider' => 'gemini',
    'model' => 'gemini-2.5-flash-image',
    'resolution' => '1K',
    'quality' => 'basic',
    'maxHumanRefs' => 3,
],
```

### Simple Fix

Change line 721 in `VideoWizard.php`:
```php
'imageModel' => 'nanobanana-pro', // Default to NanoBanana Pro for best quality
```

---

## Summary of Actions

### Priority 1: Default Model Fix (Quick Win)
- Change `'imageModel' => 'nanobanana'` to `'imageModel' => 'nanobanana-pro'` in VideoWizard.php:721

### Priority 2: Investigate Image Prompt Flow (Medium Complexity)
1. Trace the shot decomposition flow in VideoWizard.php
2. Verify `StructuredPromptBuilderService` is being called during shot prompt building
3. Check if `buildShotPrompt()` uses the full prompt system or just basic templates
4. Connect the sophisticated prompt builders to shot generation if disconnected

### Priority 3: Investigate Narration Flow (Medium Complexity)
1. Trace how scene narration flows to individual shots
2. Check if narration is properly distributed/split across shots during decomposition
3. Verify `VoicePromptBuilderService` is integrated with shot data
4. Ensure shot preview modal receives complete narration text

---

## Key Files to Review

```
modules/AppVideoWizard/app/Livewire/VideoWizard.php
├── Shot decomposition logic (line ~19650-19760)
├── buildShotPrompt() method
├── getMotionDescriptionForShot() method
├── buildScenePrompt() method (line ~11157)
└── Default storyboard settings (line ~718-730)

modules/AppVideoWizard/app/Services/
├── StructuredPromptBuilderService.php - Full image prompt building
├── VideoPromptBuilderService.php - Hollywood video prompts
├── ShotIntelligenceService.php - Shot decomposition with prompts
├── VoicePromptBuilderService.php - Voice/narration prompts
├── ImageGenerationService.php - Model configs & generation
└── NarrativeMomentService.php - Narration → moments decomposition

modules/AppVideoWizard/resources/views/livewire/modals/
└── shot-preview.blade.php - UI showing prompts and narration
```

---

## Expected Outcome

After fixing these issues:
1. **Image prompts** should be rich, detailed, and narrative-driven (using the full StructuredPromptBuilderService)
2. **Narrator/voice text** should be complete and properly distributed across shots
3. **NanoBanana Pro** should be the default for 4K quality output

The sophisticated prompt systems already exist in the codebase - they just need to be properly connected to the shot generation pipeline.
