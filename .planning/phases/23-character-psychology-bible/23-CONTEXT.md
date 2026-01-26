# Phase 23: Character Psychology & Bible Integration - Context

**Gathered:** 2026-01-26
**Status:** Ready for planning

<domain>
## Phase Boundary

Prompts capture nuanced human behavior (FACS micro-expressions, body language, subtext) and integrate Story Bible data (character visual DNA, wardrobe, location anchors) into image generation prompts. This phase extends the prompt pipeline from Phase 22 with psychology-aware content and Bible consistency.

</domain>

<decisions>
## Implementation Decisions

### FACS Expression Depth
- **Terminology level:** RESEARCH NEEDED — Determine what image models respond to best (AU codes vs. muscle descriptions vs. physical manifestations)
- **Expression scope:** Full-body integration — face + posture + breath + hands work together, not face-only
- **Intensity conveying:** RESEARCH NEEDED — Determine best practice (percentage scale vs. qualitative descriptors vs. physical gradients)
- **Emotional conflict:** RESEARCH NEEDED — Hollywood standard for expressing conflicting emotions (primary/secondary layers vs. unified vs. dominant only)

### Bible Data Integration
- **Required character data in every prompt:**
  - Visual DNA (physical appearance, defining features, age, build)
  - Current wardrobe (what they're wearing in this scene)
  - Character portrait reference (for model consistency)
- **Personality markers:** NOT automatically included (deferred)
- **Location integration:** RESEARCH NEEDED — Hollywood mise-en-scene standards for environment reflecting emotional state
- **Multi-character shots:** Full Bible data for EACH character — complete visual DNA for all, not primary/secondary hierarchy
- **Data format in prompts:** RESEARCH NEEDED — Inline vs. tagged references, what image models respond to best

### Subtext & Hidden Emotions
- **Subtext structure:** RESEARCH NEEDED — Hollywood standard (explicit SHOWS/HIDES layers vs. contrast prose vs. physical tells only)
- **Psychological depth:** Full psychology — include motivation, not just physical cues ("Gripping cup tightly, suppressing the urge to confront")
- **Visibility balance:** Scene-dependent — vary based on tension level (subtle tells early, more obvious at climax)
- **Inference behavior:** ALWAYS add subtext — every character has something beneath the surface, system creates depth even when script doesn't specify

### Continuity Anchors
- **Required persistent elements (ALL shots in scene):**
  - Wardrobe details (exact clothing, accessories, colors)
  - Physical positioning (seated/standing state unless action changes)
  - Props and objects (items in hand, on table)
  - Environmental lighting (same time of day, light quality)
- **Wardrobe specificity:** RESEARCH NEEDED — Build on existing JSON prompt structure that already achieves cinematic realism
- **Tracking approach:** Hybrid — Bible provides base anchors, system enriches from first shot in scene
- **Conflict handling:** Warn user — flag inconsistencies, let user decide (don't silently override or accept)

### Claude's Discretion
- Technical implementation of anchor extraction algorithm
- Exact prompt structure within established patterns
- How to gracefully degrade when Bible data is incomplete
- Performance optimization for Bible lookups

</decisions>

<specifics>
## Specific Ideas

**Existing success to build on:**
- JSON prompts in admin panel already achieve cinematic realism and consistency
- Research should examine this existing structure and extend it rather than starting fresh

**Hollywood standard research:**
- Multiple decisions deferred to research phase
- Researcher should investigate professional cinematography/acting direction techniques
- Focus on what actually improves image model output, not just theoretical best practices

</specifics>

<deferred>
## Deferred Ideas

- Personality markers affecting posture/body language — could be future enhancement
- Real-time continuity conflict resolution UI — Phase 27 (UI Polish) scope

</deferred>

---

*Phase: 23-character-psychology-bible*
*Context gathered: 2026-01-26*
