# Hollywood Upgrade Testing Guide

## Test Objective
Validate that Phases 2-5 of the Hollywood Upgrade Plan deliver Hollywood-level visual consistency and production quality through systematic end-to-end testing.

---

## Test Configuration

| Setting | Value |
|---------|-------|
| Aspect Ratio | Widescreen (16:9) |
| Format | Movie/Film |
| Duration | 5:00 minutes |
| Visual Mode | Cinematic Realistic |
| Character Intelligence | Enabled |
| Narrative Structure | Enabled |
| Image Model | NanoBanana Pro |

---

## Phase-by-Phase Testing Checklist

### Phase 2: Smart Reference Generation

**What it does:** Automatically extracts character portraits from Scene 1 (hero frame) to establish visual consistency.

**Test Points:**
- [ ] When clicking "Generate All Images", Scene 1 generates FIRST
- [ ] Progress shows "Extracting character references..."
- [ ] Character Bible auto-populates with extracted portraits
- [ ] Extracted portraits show same face/features as hero frame
- [ ] `referenceImageStatus` shows "ready" after extraction

**Expected Behavior:**
- Hero frame generates with all main characters visible
- AI analyzes hero frame and detects characters
- Isolated portraits extracted and stored in Character Bible
- Subsequent scenes use these portraits for consistency

**Quality Metrics:**
- Portrait extraction accuracy: >80% facial match
- Character detection confidence: >0.7 threshold
- No background artifacts in extracted portraits

---

### Phase 3: Reference Cascade Implementation

**What it does:** Passes multiple reference images to Gemini for scene generation (characters + location + style + continuity).

**Test Points:**
- [ ] `getAllReferencesForScene()` returns structured reference data
- [ ] Multiple character references passed via `additionalImages`
- [ ] Location reference included when available
- [ ] Style Bible reference included
- [ ] Previous scene used as continuity reference
- [ ] Cascade triggers when: 2+ characters OR character+location

**Expected Behavior:**
```
Reference Priority:
1. Primary: First character portrait (main reference)
2. Additional: Other character portraits (up to 4)
3. Additional: Location reference
4. Additional: Style reference
5. Additional: Previous scene (continuity)
```

**Quality Metrics:**
- Scenes maintain character consistency across all shots
- Location details match reference (lighting, architecture)
- Style consistency (color grading, mood) preserved
- Smooth visual transitions between scenes

---

### Phase 4: Character Look System Enhancement

**What it does:** Hollywood-level character DNA system with templates and wardrobe tracking.

**Test Points:**
- [ ] Character DNA templates available (8 archetypes)
- [ ] AI extraction populates DNA from description
- [ ] DNA fields visible: hair, wardrobe, makeup, accessories, physical
- [ ] Wardrobe continuity tracking per scene
- [ ] Intentional wardrobe changes logged with reasons

**Expected DNA Templates:**
1. `action_hero` - Athletic build, practical tactical gear
2. `tech_professional` - Modern smart-casual, clean aesthetic
3. `corporate_executive` - Premium tailored suit, luxury watch
4. `mysterious_figure` - Dark layers, hooded/obscured
5. `narrator` - Timeless elegant attire
6. `young_professional` - Modern business casual
7. `scientist_researcher` - Lab coat, glasses, practical
8. `creative_artist` - Eclectic, artistic, colorful

**Quality Metrics:**
- DNA extraction captures 70%+ of visual details
- Templates provide consistent starting point
- Wardrobe tracking prevents unintentional changes

---

### Phase 5: Intelligent Ordering & UI

**What it does:** Smart Bible ordering and Visual Consistency Dashboard.

**Test Points:**
- [ ] Characters sorted by: Role Priority > Scene Count > Alphabetical
- [ ] Locations sorted by: First Appearance > Frequency > Alphabetical
- [ ] Consistency Dashboard shows overall score
- [ ] Issues flagged with severity (high/medium/low)
- [ ] Recommendations generated
- [ ] "Generate All Missing References" button works

**Role Priority Order:**
1. Protagonist/Main/Lead (priority 1)
2. Supporting/Secondary/Recurring (priority 2)
3. Background (priority 3)
4. Extra/Minor (priority 4)
5. Crowd (priority 5)

**Dashboard Scoring:**
- Characters: 50% weight
- Locations: 30% weight
- Style: 20% weight
- Score = (ready/total) * 100 per category

**Quality Metrics:**
- Protagonist always appears first in Bible
- Dashboard accurately reflects reference status
- One-click generation initiates all missing references

---

## Story Structure Evaluation

### Narrative Beats (5-minute film)
For a 5-minute cinematic film, expect:

| Beat | Timing | Purpose |
|------|--------|---------|
| Opening Hook | 0:00-0:30 | Establish world, grab attention |
| Setup | 0:30-1:30 | Introduce characters, situation |
| Inciting Incident | 1:30-2:00 | Disruption, call to action |
| Rising Action | 2:00-3:30 | Conflict escalation |
| Climax | 3:30-4:15 | Peak confrontation |
| Resolution | 4:15-5:00 | Conclusion, emotional landing |

**Quality Metrics:**
- Clear 3-act structure visible
- Character arcs established
- Visual storytelling (show don't tell)
- Emotional progression

---

## Visual Prompt Quality Checklist

### Scene 1 (Hero Frame) Expectations:
- [ ] All main characters visible in frame
- [ ] Cinematic composition (rule of thirds, depth)
- [ ] Establishing shot of location
- [ ] Mood-setting lighting
- [ ] High production value aesthetic

### Image Prompt Structure:
```
Expected components:
1. Scene description (what's happening)
2. Character DNA references
3. Location details
4. Lighting/atmosphere
5. Camera angle/composition
6. Style modifiers (cinematic, film grain, etc.)
```

### Shot Progression:
- [ ] Variety of shot types (wide, medium, close-up)
- [ ] Logical shot flow (establishing → action → reaction)
- [ ] Visual continuity between shots
- [ ] Dynamic camera angles

---

## Test Execution Steps

### Step 1: Project Setup
1. Navigate to Video Wizard
2. Select Widescreen (16:9)
3. Select Movie/Film format
4. Set duration to 5:00 minutes
5. Select Cinematic Realistic visual mode
6. Enable Character Intelligence checkbox
7. Enable Narrative Structure checkbox

### Step 2: Script Generation
1. Provide creative brief/topic
2. Generate script
3. Review scene breakdown
4. Check narrative structure

### Step 3: Character Bible Setup
1. Add 2-3 characters manually
2. Apply DNA template to at least one character
3. Auto-populate DNA for another character
4. Verify DNA fields populated
5. Check character ordering (protagonist first?)
6. Check Consistency Dashboard

### Step 4: Location Bible Setup
1. Add 2-3 locations
2. Verify location ordering
3. Check Consistency Dashboard updates

### Step 5: Scene 1 Generation
1. Select NanoBanana Pro model
2. Generate Scene 1 (hero frame)
3. Observe progress messages
4. Check if reference extraction triggers
5. Verify Character Bible portraits updated

### Step 6: Evaluation
1. Review generated image quality
2. Check video prompt
3. Check image prompt
4. Verify shot composition
5. Compare characters to Bible descriptions
6. Document findings

---

## Expected Results Summary

### Success Criteria:
1. **Visual Consistency**: Characters look the same across scenes
2. **Story Structure**: Clear narrative arc in 5 minutes
3. **Production Value**: Cinematic composition, lighting, mood
4. **Smart Ordering**: Bible items ordered by importance
5. **Dashboard Accuracy**: Scores reflect actual reference status
6. **Reference Cascade**: Multiple references used in generation

### Red Flags (Issues):
- Characters look different between scenes
- No reference extraction attempted
- Dashboard shows incorrect scores
- Prompts missing character DNA details
- Shot variety lacking
- Narrative structure unclear

---

## Final Report Template

### Test Results Summary
| Phase | Feature | Status | Notes |
|-------|---------|--------|-------|
| 2 | Hero Frame Extraction | | |
| 2 | Character Detection | | |
| 2 | Portrait Isolation | | |
| 3 | Reference Cascade | | |
| 3 | Multi-Reference Support | | |
| 4 | DNA Templates | | |
| 4 | AI DNA Extraction | | |
| 4 | Wardrobe Tracking | | |
| 5 | Character Ordering | | |
| 5 | Location Ordering | | |
| 5 | Consistency Dashboard | | |
| 5 | One-Click Generation | | |

### Quality Assessment
| Aspect | Score (1-10) | Notes |
|--------|--------------|-------|
| Visual Consistency | | |
| Story Structure | | |
| Production Value | | |
| Character Detail | | |
| Shot Composition | | |
| Overall Hollywood Feel | | |

### Issues Found
1. [Issue description + severity + fix recommendation]

### Recommendations
1. [Improvement suggestion]

---

## Notes Section
(For documenting observations during testing)

