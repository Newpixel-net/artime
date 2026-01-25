# Phase 22: Foundation & Model Adapters - Context

**Gathered:** 2026-01-25
**Status:** Ready for planning

<domain>
## Phase Boundary

Build model-aware prompt infrastructure with token limits and professional cinematography vocabulary. This phase creates the foundation that all subsequent prompt expansion phases build upon.

**Requirements covered:**
- INF-01: Model adapters handle token limits (77-token CLIP limit)
- INF-03: Template library organized by shot type
- IMG-01: Camera specs with psychological reasoning
- IMG-02: Quantified framing (percentage, geometry)
- IMG-03: Lighting with specific ratios and Kelvin values

</domain>

<decisions>
## Implementation Decisions

### Claude's Discretion

User indicated satisfaction with default approach. Claude has flexibility on all implementation decisions:

**Model Adapter Behavior:**
- How CLIP 77-token compression prioritizes content (subject > action > environment > style)
- Whether to warn when truncating or silently compress
- Adapter pattern design for HiDream vs NanoBanana Pro vs Gemini

**Template Library Structure:**
- Organization by shot type (close-up, wide, medium, establishing, etc.)
- Template composition and section ordering
- Word budget allocation per template section

**Camera/Lighting Vocabulary:**
- Level of professional terminology (lens specs, f-stops, Kelvin values)
- Balance between precision and AI model comprehension
- Hollywood formula element ordering

**Integration Approach:**
- Hook location in existing ImageGenerationService flow
- Service architecture (new HollywoodPromptExpanderService vs enhancing existing)
- How templates combine with Bible data from VisualConsistencyService

</decisions>

<specifics>
## Specific Ideas

Based on codebase analysis and research:

- **Token counting must be accurate** - Use CLIP tokenizer for real counts, not char/4 estimation
- **Front-load critical content** - Subject and action first, style details last (truncation-safe)
- **Respect existing services** - StructuredPromptBuilderService and VideoPromptBuilderService already have Hollywood formula
- **Model detection** - HiDream uses CLIP (77 tokens), NanoBanana/Pro use Gemini (longer OK)

Reference: `.planning/CODEBASE-MAP.md` has full service inventory.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 22-foundation-model-adapters*
*Context gathered: 2026-01-25*
