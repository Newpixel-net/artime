# Video Wizard Pipeline Audit

**Date:** 2026-01-23
**Purpose:** Comprehensive audit before major upgrade planning

---

## Executive Summary

The Video Wizard is a **7-step AI-powered video creation pipeline** built on Laravel Livewire. It transforms concepts into fully-produced Hollywood-quality videos using multiple AI services.

**Current Size:** 28,160 lines (main component)
**Services:** 27+ specialized services
**AI Integrations:** Grok, GPT-4o, Gemini, MiniMax, Hailuo, RunPod

---

## The 7 Wizard Steps

| Step | Name | Automation Level | User Actions Required |
|------|------|-----------------|----------------------|
| 1 | Platform & Format | Manual | Select platform, format, duration, production type |
| 2 | Concept | AI-Assisted | Enter idea, optionally generate Story Bible |
| 3 | Script | FULLY AUTO | Click "Generate Script" |
| 4 | Storyboard | Semi-Auto | Click "Generate" per scene or batch |
| 5 | Animation | Semi-Auto | Click "Animate" per scene or queue all |
| 6 | Assembly | Semi-Auto | Preview, reorder scenes, adjust settings |
| 7 | Export | Semi-Auto | Click "Render" and wait |

---

## Step-by-Step Breakdown

### Step 1: Platform & Format Selection

**Purpose:** Configure output specifications and production intelligence

**Data Collected:**
- Platform (YouTube, TikTok, Instagram, etc.)
- Aspect Ratio (16:9, 9:16, 1:1, 4:5)
- Target Duration (15s to 1200s / 20 minutes)
- Production Type & Subtype
- Visual Mode (cinematic-realistic, stylized-animation, mixed-hybrid)
- AI Model Tier (economy/standard/premium)
- Content Language (40+ supported)
- Video Model (Hailuo 2.3, MiniMax, etc.)
- Pacing (fast, balanced, contemplative)

**Services:** ProductionIntelligenceService

**Automation Opportunities:**
- [ ] Auto-detect optimal settings from concept keywords
- [ ] Preset profiles for common use cases
- [ ] Smart duration calculation from concept length

---

### Step 2: Concept Development

**Purpose:** Refine and enhance the initial video concept using AI

**Key Methods:**
- `improveConcept()` - AI-enhanced concept refinement
- `generateStoryBible()` - Comprehensive story planning (OPTIONAL)

**Data Structure:**
```php
$concept = [
    'rawInput' => '',          // User's initial idea
    'refinedConcept' => '',    // AI-enhanced concept
    'keywords' => [],
    'keyElements' => [],
    'logline' => '',
    'suggestedMood' => null,
    'suggestedTone' => null,
    'styleReference' => '',
    'targetAudience' => '',
];
```

**Story Bible (Optional):**
- Title, Logline, Theme, Tone
- Three-act structure with turning points
- 3-5+ character profiles with descriptions
- All location settings
- Visual style (color palette, lighting, camera language)
- Pacing & emotional beats

**Services:** ConceptService, StoryBibleService

**Automation Opportunities:**
- [x] AI concept enhancement (exists)
- [x] Story Bible generation (exists)
- [ ] Auto-generate Story Bible for long content (>3 min)
- [ ] Auto-proceed to script when ready

---

### Step 3: Script Generation

**Purpose:** Generate complete screenplay with scenes, dialogue, and timing

**Key Methods:**
- `generateScript()` - Main script generation
- `parseScriptIntoSegments()` - Auto-parses dialogue
- `autoDetectCharacterIntelligence()` - Detects characters

**Data Structure:**
```php
$script = [
    'title' => '',
    'hook' => '',
    'scenes' => [
        [
            'sceneNumber' => 1,
            'location' => '',
            'timeOfDay' => '',
            'narration' => '',
            'visualDescription' => '',
            'mood' => '',
            'cameraAngle' => '',
            'duration' => 0,
            'speechSegments' => [],
            'shotType' => '',
            'cameraMovement' => '',
        ],
    ],
    'cta' => '',
    'totalDuration' => 0,
];
```

**Progressive Generation (for long videos):**
- Batches of 5 scenes each
- Auto-retry on failure (up to 3 times)
- Status tracking per batch

**Services:**
- ScriptGenerationService
- ContextWindowService
- SpeechSegmentParser
- CharacterExtractionService
- LocationExtractionService
- StoryBibleService

**Automation Opportunities:**
- [x] Calculate optimal scene count based on duration (exists)
- [x] Apply Story Bible constraints (exists)
- [x] Auto-detect characters and locations (exists)
- [x] Parse dialogue into speech segments (exists)
- [ ] Auto-proceed to storyboard when complete
- [ ] Auto-link detected characters to Character Bible

---

### Step 4: Storyboard (Visual Generation)

**Purpose:** Generate images for each scene/shot using AI image generators

**Key Features:**
- Full-screen studio interface
- Scene-by-scene image generation
- Multi-Shot Decomposition (Hollywood-style breakdown)
- Scene Memory (Character Bible, Location Bible, Style Bible)
- Batch generation support
- Prompt Chain for visual consistency

**Multi-Shot Mode:**
- Breaks scenes into 3-10 shots (wide, medium, close-up, etc.)
- Follows cinematic patterns (shot/reverse-shot, action-reaction)
- Dynamic shot count based on content analysis

**Scene Memory:**
- **Character Bible:** Consistent character appearance
- **Location Bible:** Consistent location rendering
- **Style Bible:** Visual style consistency

**Image Models:**
- NanoBanana (Gemini 2.5 Flash) - Fast, 1K
- NanoBanana Pro (Gemini 3 Pro) - Best quality, 4K, 5 face refs
- HiDream (RunPod) - Artistic/cinematic

**Services:**
- ImageGenerationService
- GeminiService
- RunPodService
- VisualConsistencyService
- StructuredPromptBuilderService
- DynamicShotEngine
- DialogueSceneDecomposerService
- CinematicIntelligenceService
- ShotIntelligenceService (Phase 2)
- NarrativeMomentService (Phase 2)
- ShotProgressionService
- SmartReferenceService
- CharacterLookService
- BibleOrderingService

**Automation Opportunities:**
- [x] Multi-shot decomposition (exists)
- [x] Batch generation (exists)
- [x] Prompt enhancement (exists)
- [ ] AUTO-START storyboard generation on step entry
- [ ] Auto-proceed to animation when all images complete
- [ ] Smart retry on failed generations
- [ ] Auto-populate Scene Memory BEFORE entering step

---

### Step 5: Animation (Video Generation)

**Purpose:** Convert static images into animated video clips with audio

**Key Features:**
- Per-scene video generation
- Voice generation (TTS or Multitalk lip-sync)
- Multiple video models
- Duration control (5s/6s/10s clips)

**Key Methods:**
- `animateScene($sceneIndex)` - Generates video
- `generateVoiceover($sceneIndex)` - TTS voice
- `generateMultitalkAudio($sceneIndex)` - Lip-sync audio

**Video Models:**
- MiniMax (video-01) - 5s/6s/10s clips
- Hailuo (2.3) - 5s/6s/10s clips, high quality
- Multitalk (RunPod) - Lip-sync for dialogue

**Voice Options:**
- OpenAI TTS (nova, alloy, echo, fable, onyx, shimmer)
- Kokoro TTS (alternative)
- Per-character voices from Character Bible

**Services:**
- AnimationService
- MiniMaxService
- RunPodService
- VoiceoverService
- KokoroTtsService
- VideoPromptBuilderService
- CameraMovementService
- QueuedJobsManager

**Automation Opportunities:**
- [x] Batch queue exists but requires trigger
- [ ] AUTO-START animation on step entry
- [ ] Auto-proceed to assembly when complete
- [ ] Parallel video+audio generation
- [ ] Smart voice assignment from Character Bible

---

### Step 6: Assembly (Video Editing)

**Purpose:** Combine clips into final timeline with transitions, music, captions

**Key Features:**
- Real-time preview canvas (JavaScript engine)
- Scene reordering (drag-and-drop)
- Transition effects
- Background music integration
- Caption/subtitle generation (karaoke style)
- Timeline scrubbing

**Services:**
- VideoRenderService
- SceneSyncService
- ExportEnhancementService

**Frontend Components:**
- video-preview-engine.js
- preview-controller.js

**Automation Opportunities:**
- [x] Default transitions exist
- [x] Caption generation exists
- [ ] Auto-apply transitions based on scene type
- [ ] Auto-suggest music based on mood
- [ ] Auto-proceed to export when ready

---

### Step 7: Export

**Purpose:** Render final video and download/share

**Export Configuration:**
- Resolution (480p to 4K)
- Quality (fast/balanced/best)
- Format (mp4)
- FPS (30)
- Include music/captions options
- Watermark option

**Services:**
- VideoRenderService
- VideoExportJob (Laravel queue)
- ExportEnhancementService

**Automation Opportunities:**
- [ ] Auto-render when assembly complete
- [ ] Auto-upload to selected platform
- [ ] Send notification when ready

---

## Data Flow Summary

```
Step 1 (Platform)
  ↓ platform, aspectRatio, targetDuration, productionType, visualMode

Step 2 (Concept)
  ↓ concept['refinedConcept'], storyBible (optional)

Step 3 (Script)
  ↓ script['scenes'] with narration, visualDescription, speechSegments
  ↓ Auto-detected characters/locations

Step 4 (Storyboard)
  ↓ storyboard['scenes'] with imageUrl, shots (if multi-shot)
  ↓ Character Bible, Location Bible, Style Bible

Step 5 (Animation)
  ↓ animation['scenes'] with videoUrl, audioUrl

Step 6 (Assembly)
  ↓ timeline, transitions, music, captions

Step 7 (Export)
  → Final video URL
```

---

## Current Automation Analysis

### What's FULLY AUTOMATIC:
1. Script generation from concept (after click)
2. Character/location detection from script
3. Speech segment parsing
4. Story Bible generation (after click)
5. Multi-shot decomposition (AI mode)
6. Prompt enhancement
7. Visual consistency via Scene Memory
8. Scene Memory population when entering Step 4

### What Requires Manual Clicks:
1. Platform/format selection (Step 1)
2. Concept input (Step 2)
3. "Generate Script" button (Step 3)
4. "Generate" per scene/batch (Step 4)
5. "Animate" per scene/batch (Step 5)
6. Scene reordering (Step 6)
7. "Render" button (Step 7)

### What Could Be MORE Automatic:
1. **One-Click Full Generation** - Concept → Final Video
2. **Auto-proceed through steps** - When one step completes, start next
3. **Auto-start storyboard** - When script is done
4. **Auto-start animation** - When images are done
5. **Auto-render** - When assembly is ready
6. **Smart defaults** - Infer settings from concept
7. **Parallel processing** - Generate audio while generating video

---

## Step Navigation & Validation

**Navigation Logic:**
```php
public function goToStep(int $step): void
{
    // Can only go to steps we've reached or the next step
    if ($step <= $this->maxReachedStep + 1) {
        $this->currentStep = $step;
        $this->maxReachedStep = max($this->maxReachedStep, $step);
        // Auto-actions for specific steps
        if ($step === 4) {
            $this->dispatch('step-changed', needsPopulation: true);
        }
        $this->saveProject();
    }
}
```

**Step Completion Checks:**
- Step 1: `platform` OR `format` is set
- Step 2: `concept['rawInput']` OR `concept['refinedConcept']` exists
- Step 3: `script['scenes']` is not empty
- Step 4: At least one scene has `imageUrl`
- Step 5: At least one scene has `voiceoverUrl` or `videoUrl`
- Step 6: Always optional (returns true)
- Step 7: Never marked complete (final step)

---

## Real-Time Updates

**Mechanism:** Livewire Events + JavaScript Polling (NOT WebSockets)

**Polling for Async Jobs:**
- `pollImageJobs()` - Checks HiDream job status
- `pollVideoJobs()` - Checks RunPod/MiniMax job status
- Frontend calls these periodically via `@this.call()`

**Events Dispatched:**
- `image-generation-started`
- `image-ready`
- `poll-status`
- `batch-generated`
- `step-changed`

---

## Key Services by Category

### AI Generation Services
1. ConceptService - Concept enhancement
2. ScriptGenerationService - Script generation
3. ImageGenerationService - Image generation orchestration
4. AnimationService - Video animation
5. StoryBibleService - Story bible generation

### Intelligence Services
6. CinematicIntelligenceService - Cinematic analysis
7. ShotIntelligenceService - Shot type selection
8. ShotProgressionService - Visual variety
9. ProductionIntelligenceService - Production automation
10. DynamicShotEngine - Content-driven shot decomposition
11. DialogueSceneDecomposerService - Dialogue breakdown
12. SceneTypeDetectorService - Scene classification
13. NarrativeMomentService - Narrative decomposition (Phase 2)

### Consistency Services
14. VisualConsistencyService - Story Bible constraints
15. SmartReferenceService - Reference management
16. CharacterLookService - Character appearance
17. BibleOrderingService - Bible entry ordering

### Extraction Services
18. CharacterExtractionService - Auto-detect characters
19. LocationExtractionService - Auto-detect locations
20. SpeechSegmentParser - Parse dialogue

### Rendering Services
21. VideoRenderService - FFmpeg assembly
22. VideoPromptBuilderService - Motion prompts
23. ExportEnhancementService - Final polish

### Infrastructure Services
24. PromptService - Reusable AI prompts
25. ContextWindowService - AI context management
26. QueuedJobsManager - Async job management
27. PerformanceMonitoringService - Performance tracking

---

## Database Models

**WizardProject** - Main project state (JSON columns)
- concept, story_bible, script, storyboard, animation, assembly, export_config
- current_step, max_reached_step, status

**Supporting Models:**
- WizardProcessingJob - Async job tracking
- WizardAsset - Generated assets
- VwGenerationLog - AI generation logs
- VwPrompt - Reusable AI prompts
- VwSetting - Dynamic settings
- VwGenrePreset - Genre presets
- VwShotType - Shot definitions
- VwCameraMovement - Camera templates
- VwCoveragePattern - Coverage patterns
- VwEmotionalBeat - Emotional templates

---

## Upgrade Vision: "Effortless Hollywood Production"

**Guiding Principle:**
> "Automatic, effortless, Hollywood-quality output from button clicks."

**Goals:**
1. User enters concept → System produces video with minimal clicks
2. Sophisticated AI makes all cinematography decisions
3. Data flows automatically between steps
4. User only intervenes for creative choices
5. Hollywood-quality output by default

**Key Upgrades Needed:**

### Automation Upgrades
- [ ] One-click full pipeline (concept to video)
- [ ] Auto-proceed between steps
- [ ] Smart defaults from concept analysis
- [ ] Parallel processing where possible
- [ ] Background generation while user reviews

### Intelligence Upgrades
- [ ] Smarter shot type selection from narrative
- [ ] Emotional arc → cinematography mapping
- [ ] Character consistency across shots
- [ ] Location consistency across shots
- [ ] Auto-voice assignment per character

### UX Upgrades
- [ ] Progress dashboard showing all steps
- [ ] Real-time status across entire pipeline
- [ ] Preview while generating
- [ ] Smart suggestions at each step
- [ ] One-click regeneration of failed items

### Quality Upgrades
- [ ] Reference image support for characters
- [ ] Style transfer from reference videos
- [ ] A/B testing for shot alternatives
- [ ] Quality scoring per shot
- [ ] Auto-fix low quality outputs

---

*Audit completed: 2026-01-23*
