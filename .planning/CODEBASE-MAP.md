# Video Wizard Prompt Pipeline - Codebase Map

**Created:** 2026-01-25
**Purpose:** Complete mapping of all prompt-related code for Hollywood-Quality Pipeline implementation

---

## 1. CORE PROMPT INFRASTRUCTURE

### A. Prompt Service Foundation
**File:** `modules/AppVideoWizard/app/Services/PromptService.php`

**Key Responsibilities:**
- Central prompt management and compilation
- Database prompt loading with caching (VwPrompt model)
- Fallback prompt templates for when DB is empty
- Prompt versioning and history tracking
- Generation logging with token usage tracking

**Key Methods:**
- `getCompiledPrompt($slug, $variables)` - Loads DB prompt by slug and compiles with variables
- `getPromptSettings($slug)` - Returns model, temperature, max_tokens
- `getAllPrompts()` - Lists all available prompts
- `logGeneration()` - Logs AI calls with metrics
- `seedDefaultPrompts()` - Initializes DB with default prompts

**Default Prompts Available:**
1. `script_generation` - Main script with scenes (gpt-4, 4000 tokens)
2. `script_outline` - Long-form outline (gpt-4, 2000 tokens)
3. `section_scenes` - Section detail scenes (gpt-4, 3000 tokens)
4. `scene_regenerate` - Single scene refresh (gpt-4, 1000 tokens)
5. `visual_prompt` - AI image generation prompts (gpt-4, 500 tokens)
6. `voiceover_dialogue` - Narration to dialogue conversion (gpt-4, 500 tokens)
7. `script_improve` - Script refinement (gpt-4, 4000 tokens)

---

### B. VwPrompt Model - Database Schema
**File:** `modules/AppVideoWizard/app/Models/VwPrompt.php`

**Database Fields:**
- `id`, `slug` (unique), `name`, `description`
- `prompt_template` - The actual prompt text with {{variable}} placeholders
- `variables` - JSON array of variable names
- `model` - AI model to use (gpt-4, gpt-4o-mini, etc.)
- `temperature` - Creativity level (0.7 default)
- `max_tokens` - Response length limit
- `is_active`, `version`, timestamps

**Cache:** 1 hour TTL, key prefix `vw_prompt_`

---

## 2. STORY BIBLE & CHARACTER/LOCATION EXTRACTION

### A. Story Bible Service
**File:** `modules/AppVideoWizard/app/Services/StoryBibleService.php` (1024 lines)

**AI Model Tiers:**
```php
'economy'   => ['provider' => 'grok', 'model' => 'grok-4-fast']
'standard'  => ['provider' => 'openai', 'model' => 'gpt-4o-mini']
'premium'   => ['provider' => 'openai', 'model' => 'gpt-4o']
```

**Story Bible Structure:**
- Title & Logline
- Theme & Tone
- Act Structure (3-act, 5-act, Hero's Journey)
- **Character Profiles** (50-100 words visual description each)
- **Location Index** (50-100 words visual description each)
- **Visual Style Guide** (mode, color palette, lighting, camera language)
- **Master Style Guide** (color grading, film look, lens characteristics)
- Pacing & Emotional Journey

**Key Methods:**
- `getMasterStyleGuide()` - Returns visual anchors for ALL scenes
- `buildContinuityPromptBlock()` - Formats guide for injection into image prompts

---

### B. Character Extraction Service
**File:** `modules/AppVideoWizard/app/Services/CharacterExtractionService.php` (1145 lines)

**Character DNA Extracted:**
- Hair DNA: color, style, length, texture
- Wardrobe DNA: outfit, colors, style, footwear
- Makeup DNA: style, details
- Accessories array

**Key Methods:**
- `extractCharacters($script, $options)` - 10,000 tokens
- `filterAndConsolidateCharacters()`
- `enrichIncompleteCharacters()` - AI batch enrichment

---

### C. Location Extraction Service
**File:** `modules/AppVideoWizard/app/Services/LocationExtractionService.php` (716 lines)

**Location DNA:**
- name, type, description, timeOfDay, weather
- atmosphere, mood, lightingStyle
- scenes array, stateChanges array

---

## 3. IMAGE PROMPT GENERATION

### A. Structured Prompt Builder Service
**File:** `modules/AppVideoWizard/app/Services/StructuredPromptBuilderService.php` (87K)

**Visual Mode Templates:**

**Cinematic-Realistic:**
- Camera: ARRI Alexa Mini LF + Zeiss Master Prime 50mm
- Film Grain: Subtle 35mm
- Authenticity: grain, halation, chromatic aberration, visible pores
- Quality: 8K UHD, professional color grading

**Documentary-Realistic:**
- Camera: Leica M / Canon R5, eye-level framing
- Lighting: Natural only (window, overcast, golden hour)

**Stylized-Animation:**
- Artistic interpretation, exaggerated features

---

### B. Visual Consistency Service
**File:** `modules/AppVideoWizard/app/Services/VisualConsistencyService.php` (521 lines)

**Injection Modes:**
- `auto` - Auto-detect and inject character/location descriptions
- `strict` - Only exact Story Bible descriptions
- `enhanced` - Bible + AI enhancement
- `disabled` - Raw prompts only

**Consistent Prompt Structure:**
1. Style Prefix (from Bible)
2. Character Descriptions (Bible-defined)
3. Location Description (Primary from Bible)
4. Original Visual Description (scene-specific)
5. Style Suffix (technical quality)

---

### C. Image Generation Service
**File:** `modules/AppVideoWizard/app/Services/ImageGenerationService.php` (156K)

**Available Models:**

| Model | Provider | Tokens | Quality | Max Refs |
|-------|----------|--------|---------|----------|
| HiDream | RunPod | 2t | Artistic | N/A |
| NanoBanana Pro | Gemini | 3t | HD (best) | 5 |
| NanoBanana | Gemini | 1t | Basic | 3 |

**CRITICAL - Token Limits:**
- HiDream uses CLIP encoder with **77-token limit**
- NanoBanana Pro/NanoBanana use Gemini (longer prompts OK)

---

## 4. VIDEO PROMPT GENERATION

### Video Prompt Builder Service
**File:** `modules/AppVideoWizard/app/Services/VideoPromptBuilderService.php` (42K)

**Hollywood Formula (Ordered):**
1. Camera Shot - type and framing
2. Camera Movement - dolly, pan, track
3. Subject - who/what
4. Action - CRITICAL: verb-based
5. Environment - where
6. Lighting - conditions and mood
7. Atmosphere - environmental mood
8. Style - quality markers

**Camera Shot Library:**
```
extreme-wide, wide, medium-wide, medium, medium-close, close-up,
extreme-close-up, over-shoulder, two-shot, pov, low-angle, high-angle
```

**Camera Movement Library:**
```
static, slow-push, slow-pull, dolly-forward, dolly-backward, tracking-left,
tracking-right, crane-up, crane-down, pan-left, pan-right, handheld, orbit
```

**Action Verbs by Shot Type:**
- Close-up: reveals emotion, shows determination, furrows brow, narrows eyes
- Reaction: realizes, processes, absorbs, recoils, softens, transforms

---

## 5. PROMPT EXPANSION

### Prompt Expander Service
**File:** `modules/AppVideoWizard/app/Services/PromptExpanderService.php` (30K)

**Enhancement Styles:**
- `cinematic` - Hollywood film-quality
- `action` - Dynamic, high-energy
- `emotional` - Intimate, character-focused
- `atmospheric` - Environment-rich
- `documentary` - Authentic, naturalistic

**Current Issue:** Prompts are 50-80 words, need 600-1000 words.

---

## 6. ADMIN PANEL CONFIGURATION

### Settings Database
**File:** `modules/AppVideoWizard/database/migrations/2026_01_11_000001_create_vw_settings_table.php`

**Setting Categories:**
- `production_intelligence`
- `cinematic_intelligence`
- `motion_intelligence`
- `shot_continuity`
- `scene_detection`
- `shot_progression`
- `narrative_beats`
- `character_enrichment`
- `shot_intelligence`
- `animation`, `duration`, `scene`
- `export`, `general`, `api`, `credits`, `ai_providers`

### VwSetting Model
**File:** `modules/AppVideoWizard/app/Models/VwSetting.php`

**Key Methods:**
- `getValue($slug, $default)`
- `setValue($slug, $value)`
- `getByCategory($category)`
- `getValueWithFallback($slug, $default)` - Falls back to legacy `get_option`

---

## 7. TOKEN & WORD COUNT MANAGEMENT

### Current Token Limits:
```php
script_generation: 4000 tokens
visual_prompt: 500 tokens  // TOO LOW for Hollywood quality
StoryBible: 15000 tokens
CharacterExtraction: 10000 tokens
```

### Context Window Budgeting:
```php
Story Bible: max 30% of input limit
Existing Scenes: max 50% of remaining
Production Context: fill remaining
```

---

## 8. CRITICAL FILES FOR M11 IMPLEMENTATION

### Phase 22 - Foundation & Model Adapters
- `ImageGenerationService.php` - Add model adapters for token limits
- `StructuredPromptBuilderService.php` - Add template library by shot type
- NEW: `ModelPromptAdapterService.php` - CLIP 77-token compression

### Phase 23 - Character Psychology & Bible
- `StoryBibleService.php` - Enhanced character DNA
- `VisualConsistencyService.php` - Inject FACS, body language
- `CharacterExtractionService.php` - Enrichment with psychology

### Phase 24 - Video Temporal
- `VideoPromptBuilderService.php` - Add temporal beats
- Add beat-by-beat timing ([0-2s: action, 2-4s: reaction])

### Phase 25 - Voice Enhancement
- NEW: `VoicePromptBuilderService.php` - Emotional direction tags
- Integration with TTS models

### Phase 26 - LLM Expansion
- `PromptExpanderService.php` - Enhance for 600-1000 words
- Add complexity detection for auto-expansion

### Phase 27 - UI Polish
- Admin panel: `SettingsController.php`
- NEW: Prompt comparison view

---

## 9. INTEGRATION FLOW

```
User Concept
    ↓
StoryBibleService (Generate DNA)
    ├→ Character Profiles (50-100 words)
    ├→ Location Descriptions (50-100 words)
    └→ Visual Style Guide
    ↓
ScriptGenerationService
    ↓
CharacterExtractionService + LocationExtractionService
    ↓
For Each Scene:
    ├→ VisualConsistencyService (inject Bible)
    ├→ StructuredPromptBuilderService (format)
    ├→ [NEW] HollywoodPromptExpanderService (600-1000 words)
    ├→ [NEW] ModelPromptAdapterService (CLIP compression)
    └→ ImageGenerationService (send to models)
    ↓
VideoPromptBuilderService (animation)
    ├→ [NEW] TemporalBeatService
    └→ Video models (Runway, etc.)
```

---

## 10. KEY FINDINGS FOR IMPLEMENTATION

1. **CLIP 77-token limit is CRITICAL** - HiDream silently truncates
2. **Story Bible already provides 50-100 word descriptions** - need to expand
3. **PromptExpanderService exists** - needs enhancement, not replacement
4. **Visual modes are well-defined** - add Hollywood vocabulary
5. **Admin settings infrastructure ready** - use VwSetting for configuration
6. **Token tracking exists** - generation logs track usage
7. **Multiple AI tiers** - economy/standard/premium already configured

---

*Codebase map created: 2026-01-25*
*For: Milestone 11 - Hollywood-Quality Prompt Pipeline*
