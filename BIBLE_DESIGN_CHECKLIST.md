# Bible Design Checklist - How Bibles SHOULD Work

## Purpose of Bibles

The Character Bible and Location Bible exist for ONE purpose: **Visual Consistency**.

They are NOT a comprehensive list of every person/place in the script. They are a curated set of **recurring visual elements** that need to look the same across multiple scenes.

---

## Character Bible - Correct Behavior

### WHO Should Be in the Character Bible

| Character Type | In Bible? | Reason |
|---------------|-----------|--------|
| **Protagonist** | ✅ YES | Main character, appears in most scenes, needs consistent look |
| **Supporting Characters** | ✅ YES | Recurring characters (3+ scenes), need consistent look |
| **Named Recurring Characters** | ✅ YES | If they appear in 2+ scenes with dialogue/focus |
| **Background Extras** | ❌ NO | "Police Officer 1", "Waiter", etc. - no need for consistency |
| **Crowd/Groups** | ❌ NO | "Group of protesters", "Pedestrians" - not tracked |
| **One-Scene Characters** | ❌ NO | Unless they're critically important to the plot |
| **Generic Numbered Characters** | ❌ NO | "Guard 1", "Guard 2", "Enforcer 1" - these are extras |

### Filtering Rules (MUST IMPLEMENT)

1. **Scene Count Filter**: Characters appearing in **< 2 scenes** should NOT be in Bible by default
2. **Role Filter**: Characters with role = "Extra" or "Background" should be EXCLUDED
3. **Generic Name Filter**: Names matching patterns like "[Role] [Number]" (e.g., "Guard 1", "Officer 2") = EXCLUDE
4. **Named Character Exception**: Even 1-scene characters with proper names AND plot importance may be included

### Consolidation Rules (MUST IMPLEMENT)

1. **Age Variants**: "Young Sarah" + "Sarah Chen" = ONE character with age note, not two entries
2. **Alias Detection**: "The Detective" + "Sarah Chen" = ONE character if same person
3. **Flashback Versions**: Same character at different ages = ONE Bible entry with multiple looks

### Expected Output for 5-Minute Film

For a typical 5-minute film with 9 scenes:
- **Target**: 2-4 main characters in Bible
- **Maximum**: 6 characters (exceptional case with ensemble cast)
- **Red Flag**: >6 characters indicates extraction problem

---

## Location Bible - Correct Behavior

### WHAT Should Be in the Location Bible

| Location Type | In Bible? | Reason |
|--------------|-----------|--------|
| **Primary Setting** | ✅ YES | Main location where most action happens |
| **Recurring Locations** | ✅ YES | Locations appearing in 2+ scenes |
| **Distinctive One-Scene Locations** | ✅ MAYBE | Only if visually unique and important |
| **Different Rooms in Same Building** | ❌ NO | Should be ONE entry (e.g., "Elias's Home") |
| **Generic Transitional Spaces** | ❌ NO | "Hallway", "Corridor" unless visually distinctive |
| **Variations of Same Place** | ❌ NO | "Forest Clearing" + "Forest Path" = ONE "Forest" entry |

### Consolidation Rules (MUST IMPLEMENT)

1. **Building Hierarchy**: All rooms/areas of the same building = ONE location
   - "Elias's Home Exterior" + "Elias's Home Living Room" + "Elias's Home Study" + "Elias's Home Basement" = **"Elias's Home"**
   - "Police Station Lobby" + "Police Station Interrogation Room" = **"Police Station"**

2. **Area Consolidation**: Related outdoor areas = ONE location
   - "Forest Path" + "Forest Clearing" + "Dense Forest" = **"Forest"**
   - "Beach Shore" + "Beach Dunes" = **"Beach"**

3. **Time-of-Day is NOT a Location**: Same place at different times = ONE entry
   - "Dock at Dawn" + "Dock at Night" = **"Dock"** (with time notes)

### Expected Output for 5-Minute Film

For a typical 5-minute film with 9 scenes:
- **Target**: 3-5 distinct locations
- **Maximum**: 7 locations (exceptional case)
- **Red Flag**: >7 locations indicates consolidation problem

---

## Current vs Expected (Test Case Analysis)

### Character Bible - Current State (WRONG)

| # | Character | Scenes | Should Be In Bible? |
|---|-----------|--------|---------------------|
| 1 | Sarah Chen | 8 | ✅ YES - Protagonist |
| 2 | Elias Chen | 7 | ✅ YES - Major supporting |
| 3 | Young Sarah | 4 | ❌ NO - Merge with Sarah Chen |
| 4 | Marcus Hale | 4 | ✅ YES - Supporting |
| 5 | Amelia Hawthorne | 4 | ✅ YES - Key plot character |
| 6 | Corrupt Logging Heir | 1 | ⚠️ MAYBE - Antagonist, 1 scene |
| 7+ | Shadowy Enforcer 1, Shadowy Enforcer 2, Police Officer 1, Female Official 1, Female Official 2, etc. | 1 | ❌ NO - Extras |

**Current Count: 12 characters**
**Expected Count: 4-5 characters** (Sarah, Elias, Marcus, Amelia, maybe Corrupt Heir)

### Location Bible - Current State (WRONG)

| # | Location | Should Be In Bible? | Consolidate To |
|---|----------|---------------------|----------------|
| 1 | Eldridge Bay Coastal Town Dock | ✅ YES | Eldridge Bay Dock |
| 2 | Sarah's City Apartment | ✅ YES | Sarah's Apartment |
| 3 | Coastal Highway | ⚠️ MAYBE | - |
| 4 | Eldridge Bay Police Station | ⚠️ MAYBE | Eldridge Bay Town |
| 5 | Elias's Home Exterior | ❌ MERGE | → Elias's Home |
| 6 | Elias's Home Living Room | ❌ MERGE | → Elias's Home |
| 7 | Elias's Home Study | ❌ MERGE | → Elias's Home |
| 8 | Eldridge Bay Diner | ⚠️ MAYBE | Eldridge Bay Town |
| 9 | Foggy Forest Paths | ✅ YES | Forest |
| 10 | Abandoned Logging Mill | ✅ YES | Logging Mill |
| 11 | Elias's Home Basement | ❌ MERGE | → Elias's Home |
| 12 | Eldridge Bay Storm Shelter | ⚠️ MAYBE | - |

**Current Count: 12 locations**
**Expected Count: 5-6 locations** (Dock, Sarah's Apt, Elias's Home, Forest, Mill, maybe Diner)

---

## Root Cause Analysis

### Character Extraction Problems

1. **AI Prompt Issue** (CharacterExtractionService.php lines 269-274):
   - Tells AI: "EXTRACT ALL CHARACTERS"
   - Tells AI: "If script mentions 'a group of people', extract each individual separately"
   - **Fix**: Add instructions to EXCLUDE generic extras

2. **No Filtering** (VideoWizard.php):
   - Characters are sorted by importance but NEVER filtered
   - **Fix**: Add filter that removes Extra/Background roles AND characters with <2 scenes

3. **No Age Variant Merging**:
   - "Young Sarah" and "Sarah Chen" treated as separate
   - **Fix**: Detect age variants and merge into single character with multiple looks

### Location Extraction Problems

1. **Contradictory AI Prompt** (LocationExtractionService.php):
   - Line 206: "Merge similar locations"
   - Line 286: "EXTRACT ALL LOCATIONS - Do NOT limit yourself"
   - **Fix**: Remove contradiction, add explicit consolidation rules

2. **No Hierarchical Consolidation** (VideoWizard.php):
   - `findSynonymousLocation()` only checks predefined synonym groups
   - Doesn't understand "Elias's Home *" pattern
   - **Fix**: Add prefix-based consolidation for building/area hierarchies

3. **No Post-Extraction Merge**:
   - After AI returns locations, no cleanup happens
   - **Fix**: Add consolidation pass that merges related locations

---

## Fix Priority

### CRITICAL (Must Fix)

1. **Filter extras from Character Bible** - Remove characters with role=Extra/Background AND sceneCount<2
2. **Consolidate building locations** - Merge "Location Room/Area" patterns into "Location"
3. **Merge age variants** - "Young X" + "X" = single character

### HIGH (Should Fix)

4. **Update AI prompts** - Remove contradictions, add explicit exclusion rules
5. **Add smart prefix matching** - Detect "Elias's Home *" patterns
6. **Add minimum scene threshold** - Locations appearing in only 1 scene = maybe exclude

### MEDIUM (Nice to Have)

7. **Add user confirmation** - "We found 12 characters, consolidating to 5. Review?"
8. **Add Bible health indicator** - Warn if >6 characters or >7 locations

---

## Validation Checklist

Before considering Bibles "working correctly", verify:

### Character Bible Validation
- [ ] No extras/background characters (Guard 1, Waiter, etc.)
- [ ] No age variants as separate entries (Young X should merge with X)
- [ ] All characters appear in 2+ scenes (or are plot-critical)
- [ ] Total count is reasonable (2-6 for typical short film)
- [ ] Protagonist appears FIRST in the list

### Location Bible Validation
- [ ] No fragmented building entries (all rooms merged to one building)
- [ ] No time-of-day variants as separate entries
- [ ] Related outdoor areas consolidated
- [ ] Total count is reasonable (3-7 for typical short film)
- [ ] Primary setting appears FIRST in the list

---

## Success Metrics

| Metric | Target | Red Flag |
|--------|--------|----------|
| Characters per 5-min film | 3-5 | >6 |
| Locations per 5-min film | 4-6 | >8 |
| Characters with 1 scene | 0-1 | >2 |
| Fragmented locations | 0 | >0 |
| Extras in Bible | 0 | >0 |
