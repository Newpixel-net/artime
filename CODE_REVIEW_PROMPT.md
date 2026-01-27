# Critical Code Review: Shot Generation & Voice Prompt Issues

## Context
This is a video creation wizard application (Laravel/Livewire) that generates shots with AI-powered image prompts, video prompts, and voice/narration. The system has sophisticated prompt building services, but there are disconnections causing poor output quality.

Review the attached screenshots carefully - they show the current broken state of the shot preview system.

---

## ISSUE 1: Image Prompts Are Too Short/Banal (High Priority)

### What We See
- In the screenshots, the IMAGE PROMPT section shows truncated, incomplete prompts like:
  - "Establishing shot revealing scene geography. Wide view establishing the scene environment and atmosphere..."
- These prompts are **generic and don't tell the story** of the scene
- The shots look banal and repetitive instead of unique and narrative-driven

### What Should Happen
The codebase has a sophisticated **StructuredPromptBuilderService** (`modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php`) that includes:
- Detailed `VISUAL_MODE_TEMPLATES` with authenticity markers, camera language, lighting language
- `CAMERA_PRESETS` with lens psychology (85mm portrait, 24mm wide, etc.)
- `LIGHTING_PRESETS` (golden_hour, dramatic_low_key, neon_night, etc.)
- Psychology layer building for character expressions
- Mise-en-scene environmental overlays

There's also:
- `PromptTemplateLibrary` (`modules/AppVideoWizard/app/Services/PromptTemplateLibrary.php`) with shot-type specific word budgets and emphasis areas
- `CinematographyVocabulary` for professional Hollywood terminology
- `VideoPromptBuilderService` for video animation prompts

### Investigation Tasks
1. **Trace the prompt flow**: Find where `imagePrompt` is set on shots in `VideoWizard.php`:
   - Line ~19710: `'imagePrompt' => $this->buildShotPrompt(...)`
   - Line ~20803: `'imagePrompt' => $this->buildEnhancedShotImagePrompt($engineShot)`

2. **Check if StructuredPromptBuilderService is being used**: The service exists but may not be properly integrated into shot generation

3. **Verify prompt building methods**:
   - `buildShotPrompt()` - is it using the full template library?
   - `buildEnhancedShotImagePrompt()` - does it integrate character Bible, location Bible, style references?

4. **Check data flow to frontend**: The prompts may be built correctly but truncated when displayed

### Key Files to Investigate
```
modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php
modules/AppVideoWizard/app/Services/PromptTemplateLibrary.php
modules/AppVideoWizard/app/Services/VideoPromptBuilderService.php
modules/AppVideoWizard/app/Services/ImageGenerationService.php (line ~226: buildImagePrompt call)
modules/AppVideoWizard/app/Livewire/VideoWizard.php (search for buildShotPrompt, imagePrompt)
```

---

## ISSUE 2: Voice/Narrator Prompt Is Cut Off or Missing (High Priority)

### What We See
- In the screenshots, the narrator text shows only: "In a near-future"
- This appears to be **truncated** - there should be full narration/dialogue content
- The voice prompt builder system exists but content is not flowing properly

### What Should Happen
The codebase has comprehensive voice services:
- `VoicePromptBuilderService` (`modules/AppVideoWizard/app/Services/VoicePromptBuilderService.php`)
  - Handles narrator, dialogue, internal monologue types
  - Has `AMBIENT_AUDIO_CUES` for scene atmosphere
  - Has `EMOTIONAL_ARC_PATTERNS` for emotional progression
- `VoiceoverService` for TTS generation
- `VoiceDirectionVocabulary` for emotional direction
- `VoicePacingService` for pacing markers
- `MultiSpeakerDialogueBuilder` for complex multi-speaker dialogue

### Investigation Tasks
1. **Trace voice data flow**:
   - Find where `voiceover.text` or `speechSegments` are populated on scenes
   - Check `updateSceneVoiceover()` method (line ~5095)
   - Check speech segment handling

2. **Check if voice content is being generated**:
   - The script generation should produce narration text
   - Find where narration is extracted from script and assigned to shots

3. **Verify shot voiceover assignment**:
   - Shots have a `voiceover` property - is it being set correctly?
   - Is the narrator/dialogue/internal type being properly detected?

4. **Check frontend display**:
   - The Blade/Livewire templates may be truncating or not displaying the full text
   - Find the shot preview modal component

### Key Files to Investigate
```
modules/AppVideoWizard/app/Services/VoicePromptBuilderService.php
modules/AppVideoWizard/app/Services/VoiceoverService.php
modules/AppVideoWizard/app/Services/Voice/MultiSpeakerDialogueBuilder.php
modules/AppVideoWizard/app/Services/SpeechSegmentParser.php
modules/AppVideoWizard/app/Livewire/VideoWizard.php (search for voiceover, speechSegments, narrator)
modules/AppVideoWizard/resources/views/ (look for shot preview components)
```

---

## ISSUE 3: Wrong Default Image Model (Medium Priority)

### What We See
- The system defaults to `nanobanana` (Gemini 2.5 Flash Image)
- This gives lower quality results

### What Should Be Default
- `nanobanana-pro` (Gemini 3 Pro Image Preview) should be the default
- It has: 4K resolution, up to 5 reference faces, best quality

### Current Default Locations
The default is hardcoded in multiple places in `VideoWizard.php`:
```php
// Line 721
'imageModel' => 'nanobanana', // Default to NanoBanana (Gemini) - HiDream requires RunPod setup

// Line 1739 (reset method)
'imageModel' => 'nanobanana',

// Line 11947 (another reset)
'imageModel' => 'nanobanana',
```

### Fix Required
Change all instances of:
```php
'imageModel' => 'nanobanana',
```
To:
```php
'imageModel' => 'nanobanana-pro',
```

### Also Check
- `ImageGenerationService.php` line ~182 has fallback logic:
  ```php
  $modelId = $options['model'] ?? $project->storyboard['imageModel'] ?? 'nanobanana';
  ```
  This should also fallback to `'nanobanana-pro'`

---

## Architecture Overview

### Shot Structure (VideoWizard.php lines ~941-960)
```php
$shot = [
    'id' => 'shot-{sceneId}-{index}',
    'sceneId' => 'scene_1',
    'index' => 0,
    'imagePrompt' => 'Visual description for image generation',  // ISSUE: Too short
    'videoPrompt' => 'Action description for video generation',
    'cameraMovement' => 'Pan left',
    'duration' => 5,
    'voiceover' => [...],  // ISSUE: Cut off or missing
    // ... more fields
];
```

### Service Dependencies
```
ShotIntelligenceService
  └── DynamicShotEngine (Hollywood pacing formula)
  └── DialogueSceneDecomposerService
  └── ShotProgressionService

ImageGenerationService
  └── StructuredPromptBuilderService
      └── PromptTemplateLibrary
      └── CinematographyVocabulary
  └── VisualConsistencyService
  └── ModelPromptAdapterService

VoiceoverService
  └── VoicePromptBuilderService
      └── VoiceDirectionVocabulary
      └── VoicePacingService
  └── MultiSpeakerDialogueBuilder
```

### Model Constants (ImageGenerationService.php)
```php
'nanobanana-pro' => [
    'name' => 'NanoBanana Pro',
    'description' => 'Best quality, superior face consistency (up to 5 reference faces), 4K output',
    'model' => 'gemini-3-pro-image-preview',
    'resolution' => '4K',
    'maxHumanRefs' => 5,
],
'nanobanana' => [
    'name' => 'NanoBanana',
    'description' => 'Quick drafts, good balance of speed and quality',
    'model' => 'gemini-2.5-flash-image',
    'resolution' => '1K',
    'maxHumanRefs' => 3,
],
```

---

## Debugging Steps

### Step 1: Enable Debug Logging
Add logging to see what prompts are being generated:
```php
// In buildShotPrompt or buildEnhancedShotImagePrompt
Log::debug('Shot prompt built', [
    'shotIndex' => $index,
    'promptLength' => strlen($prompt),
    'prompt' => substr($prompt, 0, 500),
]);
```

### Step 2: Check Frontend Data
In browser dev tools, inspect the Livewire component data:
- Check `$wire.animation.scenes[0].shots[0]`
- Verify the full imagePrompt and voiceover content

### Step 3: Trace Service Calls
Add breakpoints or logging in:
- `StructuredPromptBuilderService::buildStructuredPrompt()`
- `VoicePromptBuilderService::buildEnhancedVoicePrompt()`
- `ShotIntelligenceService::analyzeScene()`

---

## Expected Outcome

After fixing these issues:

1. **Image Prompts** should be rich, detailed, and unique per shot:
   - Include character psychology (expressions, body language)
   - Include cinematography vocabulary (lens, lighting ratios)
   - Include mise-en-scene environmental details
   - Be properly adapted to the shot type (close-up vs wide vs establishing)

2. **Voice Prompts** should contain:
   - Full narration/dialogue text for the shot
   - Proper type identification (narrator, dialogue, internal, monologue)
   - Emotional direction markers
   - Character voice assignments

3. **Model Default** should be `nanobanana-pro` for best quality output

---

## Summary of Changes Needed

| Issue | Location | Change |
|-------|----------|--------|
| Image prompts too short | VideoWizard.php + StructuredPromptBuilderService integration | Connect shot building to full prompt templates |
| Voice prompts cut off | VideoWizard.php voiceover handling | Trace and fix voice content flow |
| Wrong default model | VideoWizard.php lines 721, 1739, 11947 + ImageGenerationService.php | Change 'nanobanana' to 'nanobanana-pro' |

Good luck with the investigation and fixes!
