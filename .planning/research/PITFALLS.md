# Domain Pitfalls: Hollywood-Quality AI Prompt Generation Systems

**Domain:** AI prompt generation for multi-model video production (image, video, TTS)
**Researched:** 2026-01-25
**Confidence:** HIGH (verified against Runway, CLIP, and industry best practices)

---

## Critical Pitfalls

Mistakes that cause rewrites, model failures, or unusable output.

---

### Pitfall 1: Exceeding Model Token Limits (Prompt Truncation)

**What goes wrong:** Expanded prompts of 600-1000 words get silently truncated by image generation models, causing the most important visual details to be cut off. CLIP-based models (Stable Diffusion, HiDream) have a hard 77-token limit per text encoder. Prompts are truncated without warning, and words appearing later in the prompt carry less weight.

**Why it happens:** Teams build sophisticated prompt expanders targeting "Hollywood-quality" detail without understanding the underlying model architecture. The system generates beautiful 800-word prompts, but only the first 75 tokens actually influence the image.

**Consequences:**
- Critical visual details (lighting, style, color grading) at the end of prompts are ignored
- Character consistency markers at prompt end never reach the model
- Expensive AI expansion wasted on text that gets cut
- Output quality is no better than a 50-word prompt

**Prevention:**
1. **Know your model limits** - Research exact token limits for each target model:
   - CLIP-based (SD, HiDream): 77 tokens per encoder (75 usable)
   - Stable Diffusion 3 T5 encoder: 512 tokens
   - NanoBanana/Gemini: 32,768 tokens (generous)
   - Runway Gen-4: No strict limit, but prefers concise prompts

2. **Front-load critical information** - Structure prompts with most important elements first:
   ```
   [Subject + Action] > [Camera] > [Lighting] > [Style] > [Details]
   ```
   Words early in prompts carry more weight in image generation.

3. **Implement model-specific formatters** - Create adapters that reformat prompts per model:
   ```php
   interface PromptFormatter {
       public function format(string $expandedPrompt, int $tokenLimit): string;
   }

   class CLIPPromptFormatter implements PromptFormatter {
       public function format(string $prompt, int $tokenLimit = 75): string {
           // Compress to essentials, prioritize subject + action
           // Use CLIP tokenizer to count actual tokens
       }
   }
   ```

4. **Use prompt compression for CLIP models** - The 77-token limit can be worked around using chunking (breaking prompt into 75-token chunks), but this adds complexity and may reduce coherence.

**Detection:**
- Compare generated images from 50-word vs 800-word prompts - if identical, truncation occurring
- Use CLIP tokenizer to count tokens: `openai/clip-vit-base-patch32` in Python
- Monitor for style/lighting keywords being ignored in output

**Which phase should address:** Phase 1 (Foundation) - Must establish model-specific formatters before any expansion work

**Sources:**
- [Overcoming 77 Token Limit in Diffusers](https://github.com/huggingface/diffusers/issues/2136)
- [SDXL Token Limit Discussion](https://huggingface.co/stabilityai/stable-diffusion-xl-base-1.0/discussions/60)
- [Stable Diffusion 3 Token Limits](https://huggingface.co/stabilityai/stable-diffusion-3-medium-diffusers/discussions/22)

---

### Pitfall 2: Prompt Bloat (Too Much Detail Confuses Models)

**What goes wrong:** AI video models receive overly complex prompts with multiple scene changes, contradictory instructions, or excessive detail, causing unpredictable outputs, hallucinations, and ignored instructions. Models "do their own thing" instead of following the prompt.

**Why it happens:** Teams assume "more detail = better output" and stuff prompts with every possible descriptor. The Hollywood formula gets over-applied, adding lighting AND atmosphere AND color grading AND film style AND grain texture to every single shot.

**Consequences:**
- Runway reports: "Many users report that Runway has a tendency to ignore prompt instructions"
- Conflicting requests cause model confusion (e.g., "cinematic shallow DOF" + "sharp environmental detail")
- Multiple style shifts in single prompt cause visual inconsistency
- Wasted AI expansion tokens on detail that hurts output

**Prevention:**
1. **Follow the "single scene" rule** - Runway Gen-4 generates 5-10 second clips. Each prompt should describe ONE scene, ONE action, ONE style:
   ```
   BAD: "Jack walks into cafe, sits down, orders coffee, drinks it, pays"
   GOOD: "Jack enters busy cafe, eyes scanning for someone, determined expression"
   ```

2. **Avoid negative phrasing** - Gen-4 explicitly doesn't support negative prompts:
   ```
   BAD: "no blurry faces, avoid distortion, don't include artifacts"
   GOOD: "sharp focus on face, clean detailed features"
   ```

3. **Limit style descriptors** - Pick 3-5 key style elements, not 15:
   ```
   BAD: "cinematic, film grain, anamorphic, shallow DOF, teal orange, dramatic lighting,
        volumetric fog, 8K, professional cinematography, IMAX quality..."

   GOOD: "cinematic, golden hour lighting, teal-orange grade"
   ```

4. **Use subject pronouns in video prompts** - Per Runway best practices:
   ```
   BAD: "The rugged 45-year-old Japanese-American detective with salt-and-pepper hair turns"
   GOOD: "The subject turns slowly, expression hardening"
   ```
   Image-to-video inherits subject from reference image; don't re-describe.

5. **Implement prompt complexity scoring**:
   ```php
   public function getComplexityScore(string $prompt): array {
       return [
           'word_count' => str_word_count($prompt),
           'style_keywords' => $this->countStyleKeywords($prompt),
           'action_verbs' => $this->countActionVerbs($prompt),
           'contradictions' => $this->detectContradictions($prompt),
           'score' => $this->calculateOverallComplexity($prompt),
           'recommendation' => $score > 0.7 ? 'simplify' : 'acceptable'
       ];
   }
   ```

**Detection:**
- Video output ignores key prompt elements
- Output shows "hallucinations" - elements not in prompt
- Same prompt generates wildly different outputs each time
- Model adds unwanted scene changes or style shifts

**Which phase should address:** Phase 2 (Prompt Pipeline) - Build complexity guardrails into expansion system

**Sources:**
- [Runway Gen-4 Prompting Guide](https://help.runwayml.com/hc/en-us/articles/39789879462419-Gen-4-Video-Prompting-Guide)
- [Runway Text to Video Guide](https://help.runwayml.com/hc/en-us/articles/42460036199443-Text-to-Video-Prompting-Guide)

---

### Pitfall 3: Shot-to-Shot Inconsistency ("Identity Drift")

**What goes wrong:** Characters look different across shots - face shape changes, clothing shifts, hair color varies. The protagonist in Shot 1 doesn't look like the protagonist in Shot 5. This destroys narrative coherence and makes the video unwatchable.

**Why it happens:** Each shot's prompt is generated independently without shared visual anchors. Prompt expansions add different descriptors each time ("weathered face" vs "rugged features" vs "tired expression"). No reference images or style anchors are passed between shots.

**Consequences:**
- "Identity drift" - characters morph between shots
- Environments shift unexpectedly (sunset becomes midday)
- Color grading varies shot-to-shot
- Professional quality impossible without manual post-editing

**Prevention:**
1. **Use reference images** - Most 2026 models support "Ingredients to Video" (Veo 3.1) or reference images (Runway Gen-4):
   ```php
   // Extract and store character reference from first shot
   $characterReference = $this->extractCharacterReference($firstShotImage);

   // Pass reference to all subsequent shots featuring same character
   foreach ($shots as $shot) {
       if ($shot->hasCharacter($character->id)) {
           $shot->addReference($characterReference);
       }
   }
   ```

2. **Create and maintain Style Anchors** - Extract visual constants from first scene:
   ```php
   $styleAnchors = [
       'colorGrading' => 'teal shadows with warm orange highlights',
       'lightingStyle' => 'dramatic side lighting',
       'filmLook' => 'cinematic film grain, anamorphic',
       'atmosphere' => 'moody noir aesthetic',
   ];

   // Inject into EVERY prompt in the scene
   $prompt = $this->injectStyleAnchors($basePrompt, $styleAnchors);
   ```

3. **Use canonical character descriptions** - Store in Story Bible, reference by ID:
   ```php
   // Story Bible entry
   'character_jack' => [
       'visual' => 'Japanese-American man, early 40s, salt-and-pepper hair,
                    weathered face, prominent jaw, dark eyes',
       'costume' => 'worn leather jacket, dark jeans, boots',
       'reference_image' => 'storage/characters/jack_reference.png'
   ]

   // In prompt: reference the canonical description, don't paraphrase
   ```

4. **Implement visual continuity pipeline**:
   - Scene 1: Generate, extract style anchors, store character reference
   - Scene 2+: Load anchors, inject into prompt, use reference image
   - Validate: Compare output to reference for drift detection

**Detection:**
- Side-by-side comparison of character across shots
- Color histogram comparison between adjacent shots
- User reports of "character looks different"
- Manual review in storyboard showing visible inconsistency

**Which phase should address:** Phase 2 (Prompt Pipeline) - Build continuity system into core architecture

**Sources:**
- [Google Veo 3.1 Character Consistency](https://www.financialcontent.com/article/tokenring-2026-1-21-google-launches-veo-31-a-paradigm-shift-in-cinematic-ai-video-and-character-consistency)
- [Multi-Shot Character Consistency Research](https://arxiv.org/html/2412.07750v1)
- [Runway Gen-4 Consistent Characters](https://runwayml.com/research/introducing-runway-gen-4)

---

### Pitfall 4: TTS Emotion Mismatch

**What goes wrong:** Text-to-speech output sounds robotic, emotionless, or has the wrong emotional tone for the scene. Angry dialogue sounds cheerful. Sad moments sound neutral. Emotional direction cues are either ignored or spoken aloud by the TTS model.

**Why it happens:** TTS models interpret emotional context from the text itself. If the text says "she said angrily" but the actual dialogue is "I understand," the model gets confused. Some models speak the emotional tags instead of interpreting them.

**Consequences:**
- Dialogue sounds mechanical and detached
- Emotional beats don't land
- Spoken stage directions: "The character says nervously, Hello"
- Mismatched emotion destroys scene impact

**Prevention:**
1. **Separate clean text from emotional direction**:
   ```php
   // Parse TTS input to separate dialogue from direction
   public function prepareTTSInput(string $dialogueWithDirection): array {
       // "[angrily] I understand what you mean"
       preg_match('/\[([^\]]+)\]\s*(.+)/', $dialogueWithDirection, $matches);

       return [
           'emotion' => $matches[1] ?? 'neutral',     // For TTS emotional direction
           'text' => $matches[2] ?? $dialogueWithDirection,  // Clean text to speak
       ];
   }
   ```

2. **Use model-appropriate emotional cues**:
   - **ElevenLabs**: Explicit tags like `<excited>` or voice prompt descriptions
   - **Hume Octave**: Natural language Voice Prompt ("The speaker is ecstatic")
   - **Generic TTS**: Embed emotion in dialogue text naturally

3. **Match text sentiment to desired emotion**:
   ```php
   // Emotional guidance should align with actual text sentiment
   // BAD: Happy delivery of "I hate you"
   // GOOD: Ensure text supports the emotional direction

   if ($this->analyzeTextSentiment($text) !== $desiredEmotion) {
       Log::warning('TTS emotion mismatch', [
           'text_sentiment' => $textSentiment,
           'desired_emotion' => $desiredEmotion
       ]);
   }
   ```

4. **Plan for post-production removal** - Some models speak direction tags:
   ```
   Input: "She said excitedly, Hello there!"
   Output audio: "She said excitedly, Hello there!" (speaks the tag)

   Solution: Remove tags before TTS, use model's native emotion API
   ```

**Detection:**
- Listen to TTS output - does emotion match scene?
- Check for spoken stage directions in audio
- Compare emotional tone across dialogue in same scene
- User feedback: "voices sound robotic"

**Which phase should address:** Phase 3 (TTS Integration) - Build emotion extraction pipeline

**Sources:**
- [ElevenLabs TTS Best Practices](https://elevenlabs.io/docs/overview/capabilities/text-to-speech/best-practices)
- [Hume Octave TTS Prompting Guide](https://www.hume.ai/blog/octave-tts-prompting-guide)
- [Controlling Emotion in TTS Research](https://www.isca-archive.org/interspeech_2024/bott24_interspeech.pdf)

---

## Moderate Pitfalls

Mistakes that cause delays, technical debt, or poor quality but are recoverable.

---

### Pitfall 5: Over-Engineering the Prompt Pipeline

**What goes wrong:** Teams build complex prompt orchestration systems with dynamic template engines, multi-stage expansion pipelines, LLM-generated prompts that generate other prompts, feedback loops, and version-controlled prompt libraries - when a simple template system would suffice.

**Why it happens:** 2026 industry trends push toward "PromptOps" and "AI orchestration" with sophisticated tooling. Teams treat prompts as "first-class production artifacts" requiring governance protocols, approval workflows, and automated testing suites before the core feature even works.

**Consequences:**
- Development paralysis - building infrastructure instead of features
- Maintenance burden of complex system
- Debugging nightmare when prompts fail
- Performance overhead from multi-stage processing
- Technical debt from abstraction layers

**Prevention:**
1. **Start simple, add complexity when proven necessary**:
   ```php
   // Phase 1: String templates
   $prompt = "Close-up of {$character}, {$action}, {$lighting}";

   // Phase 2: Only add AI expansion IF templates insufficient
   // Phase 3: Only add orchestration IF multiple AI steps needed
   ```

2. **Follow the "one LLM call" principle** initially:
   ```php
   // BAD: Prompt generates prompt generates prompt
   $outline = $llm->expand($basic);
   $detailed = $llm->enhance($outline);
   $final = $llm->polish($detailed);  // 3 API calls, 3x latency, 3x cost

   // GOOD: Single well-crafted prompt
   $final = $llm->expand($basic, ['style' => 'hollywood']);
   ```

3. **Defer prompt versioning until you need rollback**:
   ```php
   // Start: Prompts in code
   const TEMPLATE = "...";

   // Later (IF needed): Move to database only when A/B testing required
   // Even later (IF needed): Add version control when rollback needed
   ```

4. **Measure before optimizing** - Track actual prompt performance:
   ```php
   $metrics = [
       'prompt_generation_time' => $timer->elapsed(),
       'output_quality_score' => $this->scoreOutput($result),
       'model_compliance' => $this->checkPromptFollowed($prompt, $result),
   ];
   // Only add complexity if metrics show problems
   ```

**Detection:**
- More code for prompt generation than actual feature logic
- Prompts pass through 3+ transformation stages
- Debug sessions require understanding multiple abstraction layers
- Adding a new prompt type requires changes in 5+ files

**Which phase should address:** All phases - Continuously resist complexity creep

**Sources:**
- [PromptOps Why Prompts Break Production](https://www.v2solutions.com/blogs/promptops-for-engineering-leaders/)
- [AI in 2026: The Model Is Not Your Problem](https://medium.com/@kaycee.lai/ai-in-2026-the-model-is-not-your-problem-and-it-never-really-was-597d0018310b)

---

### Pitfall 6: Ignoring Prompt Caching and Performance

**What goes wrong:** Every shot generates a fresh expanded prompt via LLM call, causing 500ms-2s latency per shot. A 20-shot scene takes 10-40 seconds just for prompt generation. Users experience painful delays, and API costs balloon.

**Why it happens:** Teams focus on prompt quality without considering performance. Each shot calls the AI expander even when similar shots exist in the same scene.

**Consequences:**
- 500ms-2s latency per prompt expansion (per shot)
- 20-shot scene = 10-40 seconds just for prompts
- API costs multiply with every regeneration
- Poor user experience during "Generate All" operations

**Prevention:**
1. **Cache expanded prompts by semantic similarity**:
   ```php
   public function expandWithCache(string $basicPrompt, array $context): array {
       $cacheKey = $this->generateSemanticKey($basicPrompt, $context);

       if ($cached = Cache::get($cacheKey)) {
           return ['prompt' => $cached, 'source' => 'cache'];
       }

       $expanded = $this->expandPrompt($basicPrompt, $context);
       Cache::put($cacheKey, $expanded, now()->addHours(24));

       return ['prompt' => $expanded, 'source' => 'generated'];
   }
   ```

2. **Batch similar prompts** - Process multiple shots together:
   ```php
   // Instead of 20 individual API calls:
   public function expandBatch(array $shots, array $sharedContext): array {
       $systemPrompt = $this->buildSystemPrompt($sharedContext);
       $batchPrompt = $this->formatBatchRequest($shots);

       // Single API call returns all expansions
       $response = $this->llm->chat([
           'messages' => [
               ['role' => 'system', 'content' => $systemPrompt],
               ['role' => 'user', 'content' => $batchPrompt],
           ]
       ]);

       return $this->parseBatchResponse($response);
   }
   ```

3. **Use rule-based expansion for simple shots**:
   ```php
   // AI expansion only for complex/emotional shots
   if ($this->isSimpleShot($shot)) {
       return $this->expandWithRules($prompt);  // ~5ms
   }
   return $this->expandWithAI($prompt);  // ~800ms
   ```

4. **Leverage LLM prompt caching** - Structure prompts for cache hits:
   ```
   Static prefix (system prompt, style guide): Cacheable
   Dynamic suffix (shot-specific): Variable

   Result: 40% token reduction on repeated patterns
   ```

**Detection:**
- Profile prompt generation time per shot
- Monitor API costs trending upward
- User complaints about "Generate All" being slow
- Network tab shows sequential AI calls

**Which phase should address:** Phase 2 (Prompt Pipeline) - Build caching into expansion service

**Sources:**
- [OpenAI Prompt Caching Guide](https://platform.openai.com/docs/guides/prompt-caching)
- [LLM Latency Optimization](https://incident.io/building-with-ai/optimizing-llm-prompts)
- [Prompt Caching and KV Cache](https://ubos.tech/news/prompt-caching-and-kv-cache-boosting-llm-optimization-and-reducing-ai-costs/)

---

### Pitfall 7: Mismatched Prompt Style for Target Model

**What goes wrong:** A prompt optimized for one model performs terribly on another. ChatGPT-style conversational prompts fail on Midjourney. SDXL keyword-weighted prompts confuse Runway. Paragraph prompts meant for GPT-5 overwhelm Stable Diffusion.

**Why it happens:** Teams build one prompt expansion system and use it for all models without adaptation. Different AI models have fundamentally different prompt interpretation architectures.

**Consequences:**
- Same input generates great images on Model A, garbage on Model B
- Style keywords work on one platform, ignored on another
- Time wasted debugging "bad prompts" when it's model mismatch
- Inconsistent quality across the pipeline

**Prevention:**
1. **Know each model's prompt preferences**:
   | Model | Prompt Style | Ideal Length |
   |-------|--------------|--------------|
   | ChatGPT/GPT-5 | Paragraphs, multi-turn edits | 200-500 words |
   | Midjourney V7 | Short, high-signal phrases + refs | 20-60 words |
   | Stable Diffusion 3.5 | Structured, weighted keywords | 50-150 words |
   | Runway Gen-4 | Visual detail, no conversation | 50-100 words |
   | HiDream | Similar to SD, CLIP-based | 50-100 words |
   | NanoBanana (Gemini) | Structured with spatial anchors | Flexible |

2. **Implement model-specific prompt adapters**:
   ```php
   interface ModelPromptAdapter {
       public function adapt(string $canonicalPrompt): string;
       public function getMaxTokens(): int;
       public function supportsNegativePrompt(): bool;
   }

   class RunwayAdapter implements ModelPromptAdapter {
       public function adapt(string $prompt): string {
           // Remove negative phrasing
           // Add "the subject" references
           // Keep under 100 words
           // Focus on visual detail
       }
   }

   class StableDiffusionAdapter implements ModelPromptAdapter {
       public function adapt(string $prompt): string {
           // Add keyword weights: (important detail:1.3)
           // Chunk for 77-token limit
           // Structure as comma-separated tags
       }
   }
   ```

3. **Generate canonical prompt first, then adapt**:
   ```php
   // Canonical Hollywood prompt (model-agnostic)
   $canonical = $this->expandToCanonical($basic, $context);

   // Model-specific adaptation
   $imagePrompt = $this->adapters['hidream']->adapt($canonical);
   $videoPrompt = $this->adapters['runway']->adapt($canonical);
   $ttsPrompt = $this->adapters['elevenlabs']->adapt($canonical);
   ```

4. **Test prompts on actual target models** before deployment - don't assume cross-model compatibility

**Detection:**
- Same prompt works great on test model, fails on production model
- Style keywords present in prompt but absent in output
- Model-specific features (weights, refs) not being used
- Quality varies significantly between models in pipeline

**Which phase should address:** Phase 1 (Foundation) - Build adapter architecture from start

**Sources:**
- [Image Prompting Mistakes and Fixes](https://roblaughter.medium.com/10-image-prompting-mistakes-and-how-to-avoid-them-244f972d0c2a)
- [Model-Specific Prompting](https://levelup.gitconnected.com/image-generation-with-ai-aka-prompt-engineering-8b6cc54aa7a8)

---

### Pitfall 8: Subject Placement Wrong in Prompt

**What goes wrong:** The main subject or action is buried in the middle or end of the prompt. AI models give it less weight, focusing instead on early-prompt elements like camera or lighting. The beautiful lighting description gets rendered perfectly, but the character action is weak or missing.

**Why it happens:** Teams follow prompt formulas rigidly: "[Camera] + [Lighting] + [Subject] + [Action]" puts camera first. But CLIP and similar encoders weight early tokens more heavily.

**Consequences:**
- Camera/lighting perfect, subject action weak
- Character doing wrong action or no action
- Model focuses on environment, ignores protagonist
- Prompt looks correct but output is wrong

**Prevention:**
1. **Front-load subject and action** for image models:
   ```
   BAD: "Cinematic close-up with dramatic side lighting of a detective
         investigating a crime scene"

   GOOD: "Detective crouches over evidence, examining it intently,
          dramatic side lighting, cinematic close-up"
   ```

2. **Follow different order for different models**:
   - **Image models (CLIP-based)**: Subject + Action FIRST
   - **Video models (Runway)**: Can use "[Camera] + [Subject] + [Action]" structure
   - **LLMs (GPT)**: Order matters less, context matters more

3. **For Video Wizard existing code**, adjust the `combineComponents` method:
   ```php
   // Current order in VideoPromptBuilderService
   // 1. Style, 2. Subject, 3. Action, 4. Camera, 5. Lighting, 6. Atmosphere

   // Better for CLIP-based image generation:
   // 1. Subject + Action (combined), 2. Camera, 3. Lighting, 4. Style
   ```

4. **Test with subject at different positions** - empirically verify what works for each model

**Detection:**
- Subject action weak/missing in output but lighting/style perfect
- Comparing outputs with subject at prompt start vs end
- Model seems to "forget" the main action

**Which phase should address:** Phase 2 (Prompt Pipeline) - Implement model-aware ordering

**Sources:**
- [AI Image Prompts Best Practices](https://leonardo.ai/news/ai-image-prompts/)
- [Writing AI Image Prompts Mistakes](https://chatsmith.io/blogs/ai-guide/writing-ai-image-prompts-mistakes-00052)

---

## Minor Pitfalls

Mistakes that cause annoyance but are easily fixable.

---

### Pitfall 9: Contradictory Style Terms

**What goes wrong:** Prompts include conflicting style descriptors that confuse the model. "Hyperrealistic cartoon style." "Shallow DOF with sharp environmental detail." "Low-key high-key lighting." The model picks one or creates visual confusion.

**Prevention:**
```php
public function detectContradictions(string $prompt): array {
    $contradictions = [
        ['realistic', 'cartoon'],
        ['shallow depth of field', 'sharp background'],
        ['low-key', 'high-key'],
        ['desaturated', 'vibrant saturated'],
        ['harsh lighting', 'soft diffused'],
        ['static camera', 'dynamic movement'],
    ];

    $found = [];
    foreach ($contradictions as [$term1, $term2]) {
        if (str_contains($prompt, $term1) && str_contains($prompt, $term2)) {
            $found[] = [$term1, $term2];
        }
    }
    return $found;
}
```

**Detection:** Output has visual confusion or model picks unexpected style

**Which phase should address:** Phase 2 - Add validation to prompt builder

---

### Pitfall 10: Using Conversational Language in Image Prompts

**What goes wrong:** Prompts include conversational filler that wastes token budget and confuses pattern-matching models. "Please create an image of..." "I would like to see..." "Can you generate..."

**Prevention:**
```php
public function stripConversationalFiller(string $prompt): string {
    $fillers = [
        '/^please (create|generate|make)/i',
        '/^(I would like|I want) (to see|you to)/i',
        '/^can you (create|generate|make)/i',
        '/\bplease\b/i',
        '/\bthank you\b/i',
    ];

    foreach ($fillers as $pattern) {
        $prompt = preg_replace($pattern, '', $prompt);
    }
    return trim($prompt);
}
```

**Detection:** First words of prompt are instructions to the model rather than visual description

**Which phase should address:** Phase 1 - Add to prompt preprocessor

---

### Pitfall 11: No Fallback When AI Expansion Fails

**What goes wrong:** AI expansion API times out or returns error, and the system has no fallback. Users see error messages or broken prompts instead of graceful degradation to rule-based expansion.

**Prevention:**
```php
public function expandPrompt(string $basic, array $options): array {
    try {
        return $this->expandWithAI($basic, $options);
    } catch (\Exception $e) {
        Log::warning('AI expansion failed, using rules', ['error' => $e->getMessage()]);

        // Rule-based fallback always works
        return $this->expandWithRules($basic, $options);
    }
}
```

**Detection:** Monitor error rates on expansion endpoint, check for "expansion failed" in logs

**Which phase should address:** Phase 1 - Already implemented in existing PromptExpanderService, verify coverage

---

### Pitfall 12: Prompt Testing Only at Generation Time

**What goes wrong:** Teams only discover prompt problems when viewing generated images/videos, after expensive API calls and long generation waits. No preview or validation of prompts before generation.

**Prevention:**
1. **Add prompt preview** before generation:
   ```blade
   <div class="prompt-preview">
       <h4>Generated Prompt</h4>
       <p>{{ $expandedPrompt }}</p>
       <div class="metrics">
           Word count: {{ str_word_count($expandedPrompt) }}
           Token estimate: {{ $this->estimateTokens($expandedPrompt) }}
           Complexity: {{ $complexityScore }}
       </div>
       <button wire:click="regeneratePrompt">Regenerate</button>
       <button wire:click="proceedToGeneration">Generate Image</button>
   </div>
   ```

2. **Implement prompt linting** that catches issues before generation

**Detection:** Users report "I generated 5 times to get a good prompt" - indicates no preview

**Which phase should address:** Phase 3 (UI/UX) - Add prompt preview and editing

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Foundation | Token limits (#1) | Build model formatters FIRST |
| Foundation | Model mismatch (#7) | Implement adapter pattern from start |
| Prompt Pipeline | Bloat (#2) | Add complexity scoring and limits |
| Prompt Pipeline | Consistency (#3) | Build style anchor system |
| Prompt Pipeline | Performance (#6) | Implement caching from start |
| Prompt Pipeline | Subject placement (#8) | Test ordering per model |
| TTS Integration | Emotion mismatch (#4) | Separate clean text from direction |
| UI/UX | Testing only at gen (#12) | Add prompt preview before generation |
| All phases | Over-engineering (#5) | Start simple, add complexity only when proven necessary |

---

## Model-Specific Quick Reference

### HiDream (CLIP-based)
- Token limit: 77 per encoder (75 usable)
- Best practice: Subject + action first, comma-separated keywords
- Avoid: Long paragraphs, conversational language

### NanoBanana Pro (Gemini-based)
- Token limit: 32,768 (generous)
- Best practice: Structured prompts with spatial anchors
- Avoid: Overly vague prompts

### Runway Gen-4
- Token limit: No strict limit, prefers concise
- Best practice: Visual detail, "the subject" for motion, single scene
- Avoid: Negative prompts, multiple scene changes

### ElevenLabs / TTS
- Best practice: Clean text separate from emotional direction
- Avoid: Stage directions in spoken text

---

## Research Confidence Notes

| Area | Confidence | Source Quality |
|------|------------|----------------|
| Token limits (CLIP) | **HIGH** | Official HuggingFace discussions, documented architecture |
| Runway best practices | **HIGH** | Official Runway prompting guides |
| TTS emotion handling | **MEDIUM** | Official docs + research papers, varies by model |
| Consistency solutions | **MEDIUM** | Industry research + emerging tools (Veo 3.1, Gen-4) |
| Performance optimization | **HIGH** | OpenAI docs + production experience |
| Over-engineering | **MEDIUM** | Industry trend analysis, may not apply to all projects |

---

## Sources

### Model Token Limits
- [Overcoming 77 Token Limit](https://github.com/huggingface/diffusers/issues/2136)
- [SDXL Token Discussions](https://huggingface.co/stabilityai/stable-diffusion-xl-base-1.0/discussions/60)
- [SD3 Token Limits](https://huggingface.co/stabilityai/stable-diffusion-3-medium-diffusers/discussions/22)

### Runway/Video Generation
- [Gen-4 Prompting Guide](https://help.runwayml.com/hc/en-us/articles/39789879462419-Gen-4-Video-Prompting-Guide)
- [Text to Video Guide](https://help.runwayml.com/hc/en-us/articles/42460036199443-Text-to-Video-Prompting-Guide)

### Character Consistency
- [Google Veo 3.1 Announcement](https://www.financialcontent.com/article/tokenring-2026-1-21-google-launches-veo-31-a-paradigm-shift-in-cinematic-ai-video-and-character-consistency)
- [Multi-Shot Character Consistency Research](https://arxiv.org/html/2412.07750v1)

### TTS and Emotion
- [ElevenLabs Best Practices](https://elevenlabs.io/docs/overview/capabilities/text-to-speech/best-practices)
- [Hume Octave Prompting](https://www.hume.ai/blog/octave-tts-prompting-guide)

### Performance and Caching
- [OpenAI Prompt Caching](https://platform.openai.com/docs/guides/prompt-caching)
- [LLM Latency Optimization](https://incident.io/building-with-ai/optimizing-llm-prompts)
- [Prompt Caching Techniques](https://ubos.tech/news/prompt-caching-and-kv-cache-boosting-llm-optimization-and-reducing-ai-costs/)

### General Prompt Engineering
- [10 Image Prompting Mistakes](https://roblaughter.medium.com/10-image-prompting-mistakes-and-how-to-avoid-them-244f972d0c2a)
- [AI Image Prompts Guide](https://leonardo.ai/news/ai-image-prompts/)
- [Prompt Versioning Best Practices](https://latitude-blog.ghost.io/blog/prompt-versioning-best-practices/)

---

*Research completed 2026-01-25. Pitfalls verified against current model documentation and industry best practices.*
