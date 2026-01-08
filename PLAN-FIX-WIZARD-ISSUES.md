# Video Wizard Critical Fixes Plan

## Overview
Two critical issues identified in the Video Wizard that need to be fixed:
1. **TypeError on Character Portrait Generation** - Type mismatch when generating character portraits
2. **Transition Scenes Problem** - Script generation creates placeholder "Transition" scenes instead of real content

---

## Issue 1: Character Portrait TypeError

### Root Cause
**File:** `modules/AppVideoWizard/app/Livewire/VideoWizard.php` (line 5750)
**Error:** `ImageGenerationService::buildImagePrompt(): Argument #6 ($sceneIndex) must be of type ?int, string given`

The `generateCharacterPortrait` method passes:
```php
'sceneIndex' => 'char_' . $index  // String like 'char_0', 'char_1'
```

But `buildImagePrompt()` expects:
```php
?int $sceneIndex = null  // Integer or null
```

### Reference Implementation Analysis
In `video-creation-wizard.html`, character portrait generation:
- Uses a dedicated `buildPortraitPrompt(char)` function
- Does NOT use sceneIndex at all - portraits are standalone
- Focuses on "Professional studio portrait photograph" with clean white background

### Fix Strategy
1. **Option A (Simple):** Pass `null` for sceneIndex when generating portraits
   - Portraits don't belong to any scene, so null is semantically correct
   - Minimal code change

2. **Option B (Better):** Create a dedicated portrait generation method
   - Add `generatePortraitImage()` method in ImageGenerationService
   - Build a portrait-specific prompt (clean background, studio lighting)
   - More control over portrait quality

### Recommended Fix (Option A - Quick)
```php
// In VideoWizard.php line 5745-5751
$result = $imageService->generateSceneImage($project, [
    'id' => $character['id'],
    'visualDescription' => $prompt,
], [
    'model' => 'nanobanana-pro',
    'sceneIndex' => null,  // Changed from 'char_' . $index
    'isPortrait' => true,  // Add flag for special handling
]);
```

### Enhanced Fix (Option B - Recommended)
Add portrait-specific generation in ImageGenerationService:
```php
public function generateCharacterPortrait(WizardProject $project, array $character, array $options = []): array
{
    // Build portrait-optimized prompt
    $prompt = $this->buildPortraitPrompt($character);

    // Use square resolution for portraits
    $resolution = ['width' => 1024, 'height' => 1024];

    // Generate with appropriate model
    return $this->generateWithGemini($project, $character, $prompt, $resolution, ...);
}

protected function buildPortraitPrompt(array $character): string
{
    $parts = [
        'Professional studio portrait photograph',
        $character['description'],
        'Standing pose facing camera',
        'Clean pure white background',
        'Professional studio lighting with soft shadows',
        'High quality, detailed, sharp focus',
        'Full body visible from head to feet',
        'Fashion photography style, catalog quality'
    ];
    return implode('. ', $parts);
}
```

---

## Issue 2: Transition Scenes Problem

### Root Cause
**File:** `modules/AppVideoWizard/app/Services/ScriptGenerationService.php` (lines 2322-2343)

When AI returns fewer scenes than expected, `interpolateScenes()` creates placeholder "Transition" scenes:
```php
$transitionScene = [
    'id' => 'scene-' . ($insertIndex + 1),
    'title' => 'Transition',  // <-- BAD: Generic title
    'narration' => $this->generateTransitionNarration($prevScene),  // <-- BAD: Generic content
    'visualDescription' => $this->generateTransitionVisual($prevScene),  // <-- BAD: "Soft focus transition..."
    // ...
];
```

This results in scenes like:
- Title: "Transition"
- Narration: "But there is more to this story..."
- Visual: "Soft focus transition, dreamy atmosphere, Wide angle perspective..."

**This is fundamentally wrong.** Real scenes should have meaningful content that advances the narrative.

### Reference Implementation Analysis
The reference implementation does NOT create transition scenes. Instead:
- It trusts the AI to generate the correct number of scenes
- Scene count is based on duration: `sceneCount = Math.ceil(duration / 6)` (~6 seconds per scene)
- Each scene has proper narrative content, not placeholder text

### Fix Strategy

#### Strategy 1: Remove Transition Scene Creation (Recommended)
Instead of creating placeholder transitions, accept the AI's scene count:
- If AI returns fewer scenes, **don't pad with fake content**
- Trust the AI's judgment on narrative structure
- Better to have 5 good scenes than 10 mediocre ones

#### Strategy 2: Use AI to Generate Additional Scenes
If more scenes are truly needed:
- Make a follow-up AI call to generate additional **real** scenes
- Give context about existing scenes for narrative continuity
- This ensures quality content, not placeholder text

#### Strategy 3: Improved Scene Splitting
When splitting scenes:
- Don't just split narration in half
- Use AI to create two distinct narrative beats from one
- Each split scene should stand on its own

### Recommended Fix (Strategy 1 + 3)

```php
protected function interpolateScenes(array $script, int $targetSceneCount, int $targetDuration): array
{
    $scenes = $script['scenes'] ?? [];
    $currentCount = count($scenes);

    // Accept AI's scene count if it's reasonable (within 30% of target)
    $minAcceptable = (int) floor($targetSceneCount * 0.7);
    if ($currentCount >= $minAcceptable) {
        // Just re-index and adjust durations, don't add fake scenes
        return $this->normalizeSceneCount($script, $targetDuration);
    }

    // Only split scenes if we're significantly under target
    // Split scenes intelligently based on content, not just duration
    foreach ($scenes as $index => $scene) {
        if ($splitCount < $scenesToAdd && $this->canMeaningfullySplit($scene)) {
            // Split into two meaningful scenes
            $splitScenes = $this->splitSceneWithAI($scene, $project);
            // ... add both scenes
        }
    }

    // REMOVE: Don't create transition scenes
    // while (count($newScenes) < $targetSceneCount) { ... transition scene creation ... }

    return $script;
}
```

### Additional Improvements

1. **Better AI Prompting for Scene Count**
   Add stronger guidance in the script generation prompt:
   ```
   CRITICAL: Generate EXACTLY {$sceneCount} scenes.
   Each scene must have unique, meaningful content.
   DO NOT create generic transitions or filler scenes.
   ```

2. **Scene Validation**
   Add validation to reject scenes with generic content:
   ```php
   protected function isValidScene(array $scene): bool
   {
       $genericPhrases = [
           'transition', 'more to this story', 'journey continues',
           'soft focus', 'dreamy atmosphere', 'wide angle perspective'
       ];

       foreach ($genericPhrases as $phrase) {
           if (stripos($scene['narration'] ?? '', $phrase) !== false) {
               return false;
           }
           if (stripos($scene['visualDescription'] ?? '', $phrase) !== false) {
               return false;
           }
       }

       return !empty($scene['narration']) && strlen($scene['narration']) > 20;
   }
   ```

3. **Fallback: Regenerate If Too Few Scenes**
   If AI returns significantly fewer scenes:
   ```php
   if ($currentCount < $minAcceptable) {
       // Request AI to add more scenes with context
       $additionalScenes = $this->generateAdditionalScenes(
           $script,
           $targetSceneCount - $currentCount,
           $project
       );
       $script['scenes'] = array_merge($scenes, $additionalScenes);
   }
   ```

---

## Implementation Priority

### Phase 1: Quick Fixes (Immediate)
1. Fix TypeError by passing `null` for sceneIndex in character portrait generation
2. Remove transition scene creation from `interpolateScenes()`

### Phase 2: Enhanced Implementation (Follow-up)
1. Create dedicated `generateCharacterPortrait()` method
2. Add `generateAdditionalScenes()` for AI-powered scene expansion
3. Improve AI prompt for better scene count adherence

### Phase 3: Quality Improvements (Future)
1. Add scene content validation
2. Implement intelligent scene splitting with AI
3. Add location portrait generation with same pattern

---

## Files to Modify

1. **VideoWizard.php**
   - `generateCharacterPortrait()` - Fix sceneIndex type
   - Optionally add `generateLocationImage()` fix if similar issue exists

2. **ImageGenerationService.php**
   - Add `generateCharacterPortrait()` method (optional enhancement)
   - Add `buildPortraitPrompt()` helper (optional)

3. **ScriptGenerationService.php**
   - `interpolateScenes()` - Remove transition scene creation
   - Add scene validation
   - Optionally add `generateAdditionalScenes()` for AI expansion

---

## Testing Checklist

- [ ] Character portrait generation works without TypeError
- [ ] Generated portraits have clean background, studio lighting
- [ ] Script generation produces only meaningful scenes
- [ ] No scenes titled "Transition" in generated scripts
- [ ] Scene count matches or is reasonably close to target
- [ ] Each scene has unique narrative content
- [ ] Location image generation works (if applicable)
