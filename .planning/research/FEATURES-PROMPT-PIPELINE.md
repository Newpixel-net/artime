# Feature Landscape: Hollywood-Quality Prompt Pipeline

**Domain:** AI-generated cinematography prompts (image, video, voice)
**Researched:** 2026-01-25
**Confidence:** HIGH (verified with cinematography sources, AI prompt engineering best practices)

## Executive Summary

Hollywood-quality prompts differ from amateur prompts in their **specificity, technical precision, and emotional depth**. Professional cinematographers think in terms of exact lens choices, lighting ratios, camera movements with timing, actor positioning with subtext, and micro-expressions that reveal inner psychology. Amateur prompts describe what is seen; professional prompts describe why it looks that way and what emotional response it should evoke.

The 600-1000 word target length is justified by the density of professional cinematography: a single shot requires camera specs (lens, aperture, angle, movement), lighting setup (key, fill, back, color temperature, ratios), actor direction (blocking, micro-expressions, body language, emotional beats), production design (set dressing, props, color palette), and temporal choreography (for video). This density cannot be achieved in 50-word prompts.

Key insight: **Professional prompts are directing instructions, not scene descriptions.** They tell the AI how to create the shot, not just what the shot contains.

---

## Table Stakes

Features required for prompts to be considered "Hollywood quality." Missing these = prompts feel amateur.

| Feature | Why Required | Complexity | Category |
|---------|--------------|------------|----------|
| **Specific Camera Specs** | Professionals never say "close-up" without lens choice | Low | Technical |
| **Quantified Framing** | "80% of frame" vs "close" — precision matters | Low | Technical |
| **Lighting Setup Description** | Key/fill/back with ratios; not just "dramatic lighting" | Medium | Technical |
| **Camera Angle Psychology** | Low angle = power; high angle = vulnerability | Low | Emotional |
| **Micro-Expression Details** | FACS-informed facial descriptions | Medium | Emotional |
| **Body Language Specifics** | Posture, gesture, weight distribution | Low | Emotional |
| **Emotional State in Physicality** | "Tears pooling but not falling" vs "sad" | Medium | Emotional |
| **Color Palette Intention** | Warm/cool with psychological rationale | Low | Mood |
| **Depth of Field Purpose** | Shallow DOF to isolate subject, deep for context | Low | Technical |

### Rationale

**Specific Camera Specs:**
Cinematographers choose lenses for specific psychological effects. A 35mm lens exaggerates perspective and creates unease; an 85mm compresses and flatters; a 50mm feels natural. Professional prompts specify: "Shot on 85mm f/1.4, creating shallow depth of field that isolates her face from the blurred background." Amateur prompts say "close-up of her face."

Source: [StudioBinder 35mm vs 50mm Lens](https://www.studiobinder.com/blog/35mm-vs-50mm-lens/)

**Quantified Framing:**
"Face filling 80% of frame" is actionable; "extreme close-up" is ambiguous. Professionals think in frame percentages because it determines what's visible and what's cropped. For AI generation, this precision directly impacts composition.

Source: [Aiarty Midjourney Camera Angles](https://www.aiarty.com/midjourney-prompts/midjourney-camera-angles.htm)

**Lighting Setup Description:**
Professional lighting is described in ratios and positions, not adjectives. "2:1 key-to-fill ratio creating subtle shadows on the right side of face, backlight creating hair separation, color temperature 3200K for warmth" vs "dramatic lighting." Chiaroscuro, Rembrandt lighting, split lighting are all technical terms with specific meanings.

Source: [No Film School 13 Lighting Techniques](https://nofilmschool.com/lighting-techniques-in-film), [StudioBinder Chiaroscuro](https://www.studiobinder.com/blog/what-is-chiaroscuro-definition-examples/)

**Camera Angle Psychology:**
"Shot slightly below eye level making her appear vulnerable" uses the established psychology of camera angles. Low angles convey power/dominance; high angles convey vulnerability/smallness; eye level is neutral. This is film language, not arbitrary choice.

Source: [StudioBinder Camera Shots Ultimate Guide](https://www.studiobinder.com/blog/ultimate-guide-to-camera-shots/)

**Micro-Expression Details:**
Paul Ekman's Facial Action Coding System (FACS) provides vocabulary for precise facial description. "Left eyebrow slightly raised (hope?), corners of mouth pulled down fighting tremor" uses observable action units, not interpreted emotions. This gives AI generators concrete targets.

Source: [Paul Ekman FACS](https://www.paulekman.com/facial-action-coding-system/), [iMotions FACS Guide](https://imotions.com/blog/learning/research-fundamentals/facial-action-coding-system/)

**Emotional State in Physicality:**
"Show don't tell" applies to prompts. "Tears already pooling but not falling, held by sheer willpower" is more generatable than "she's barely holding back tears." The first describes observable physical states; the second requires interpretation.

Source: [No Film School Show Don't Tell](https://nofilmschool.com/show-dont-tell)

---

## Differentiators

Features that elevate prompts from professional to exceptional. These create the "Hollywood magic."

| Feature | Value Proposition | Complexity | Category |
|---------|-------------------|------------|----------|
| **Subtext Layer** | What the visual implies vs what it shows | High | Emotional |
| **Mise-en-Scene Integration** | How environment reflects character psychology | High | Mood |
| **Temporal Choreography** (video) | Beat-by-beat timing: "0.0s-1.0s: X, 1.0s-2.0s: Y" | Medium | Technical |
| **Camera Movement Psychology** | Why the camera moves, not just how | Medium | Emotional |
| **Catchlight Specification** | Eye reflections for "alive" look | Low | Technical |
| **Breath and Micro-Movements** | Chest rise, finger twitch, swallow | Low | Realism |
| **Spatial Relationship Power Dynamics** | Character positioning reveals relationships | Medium | Emotional |
| **Color Grading Intent** | Teal-and-orange, desaturated, specific LUT reference | Low | Mood |
| **Anamorphic/Lens Character** | Oval bokeh, lens flare, specific glass quality | Low | Technical |
| **Continuity Anchors** | Wardrobe details, prop positions, injury progression | Medium | Consistency |

### Rationale

**Subtext Layer:**
Great shots communicate what characters aren't saying. "The way she grips the coffee cup — knuckles white, but face composed — tells us she's barely holding it together while projecting calm." This gives AI generators the emotional context that informs subtle details.

Source: [FilmLocal Subtext in Film](https://filmlocal.com/filmmaking/how-to-write-subtext-in-film/)

**Mise-en-Scene Integration:**
"Cluttered room behind her, reflecting her chaotic mental state; harsh fluorescent light giving a clinical, sterile feel that contrasts with her emotional turmoil." Production design isn't decoration; it's storytelling.

Source: [2Bridges Mise-en-Scene](https://www.2bridges.nyc/nycblog/what-is-mise-en-scene/)

**Temporal Choreography (Video):**
"0.0s-1.0s: Braced fear (eyes slightly squinted). 1.0s-2.0s: Confused hope (eyebrows lift). 2.0s-3.0s: Disbelief (head begins tiny shake). 3.0s-4.0s: The break (chin trembles, first tear escapes)." Video prompts must describe change over time, not static scenes.

Source: [Medium AI Video Prompting Strategies](https://medium.com/@creativeaininja/how-to-actually-control-next-gen-video-ai-runway-kling-veo-and-sora-prompting-strategies-92ef0055658b)

**Camera Movement Psychology:**
"Slow dolly in over 4 seconds, as if we're being drawn into her emotional state, creating intimacy and unease." Professionals describe why the camera moves, not just that it dollies. The movement serves story.

Source: [MasterClass Camera Moves Guide](https://www.masterclass.com/articles/guide-to-camera-moves)

**Catchlight Specification:**
"Catch lights visible in both eyes from soft box at 45 degrees." This detail makes portraits feel alive. Without catchlights, eyes look dead. AI image generators can produce this when prompted specifically.

Source: [Overchat AI Photos Guide](https://overchat.ai/ai-hub/how-to-make-realistic-ai-photos)

**Continuity Anchors:**
"Same tear-stain pattern on left cheek from previous shot; coffee cup now empty (was half-full in previous scene); wedding ring absent (removed in Scene 3)." These details prevent jarring inconsistencies across shots.

---

## Anti-Features

Prompt patterns that degrade quality. Common mistakes in AI prompt engineering.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| **Vague Adjectives** | "Beautiful," "dramatic," "nice" — AI can't act on these | Use technical terms: "chiaroscuro lighting," "35mm lens" |
| **Emotional Labels Without Physical Manifestation** | "She looks sad" — no visual reference | "Eyes downcast, shoulders slumped, hands limp at sides" |
| **Conflicting Instructions** | "Harsh sunlight" + "soft dreamy look" | Choose coherent aesthetic; one lighting approach |
| **Missing Composition Specs** | AI defaults to generic framing | Specify rule of thirds, frame percentages, headroom |
| **Abstract Concepts as Subject** | "Love," "justice," "infinity" — non-visual | Translate to concrete visual: "Their hands almost touching" |
| **Generic Color Descriptions** | "Warm tones" | Specific: "3200K, amber key light, teal shadows" |
| **Ignoring Lens Effects** | "Blurry background" | "f/1.4 aperture creating smooth bokeh, subject at 1.5m focus distance" |
| **Static Video Descriptions** | Describing video like still image | Include motion, timing, progression: "Over 4 seconds..." |
| **Overloading Single Prompt** | Too many conflicting elements | Focus on 2-3 subjects max; break complex scenes into sub-prompts |
| **Missing Negative Space** | Cramming frame with detail | "Negative space on left side creating unbalanced tension" |

### Rationale

**Vague Adjectives:**
"Dramatic lighting" is meaningless to a renderer. "Low key lighting with 4:1 contrast ratio, single hard key light at 45 degrees camera left, deep shadows on right side of face" is reproducible. The difference is specificity.

Source: [Leonardo AI Image Prompts](https://leonardo.ai/news/ai-image-prompts/), [God of Prompt Mistakes](https://www.godofprompt.ai/blog/10-ai-image-generation-mistakes-99percent-of-people-make-and-how-to-fix-them)

**Emotional Labels Without Physical Manifestation:**
AI generators produce images, not emotions. "Sad" must be translated to physical details an image can show: eye position, tear presence, mouth shape, posture. The Facial Action Coding System provides this translation layer.

**Conflicting Instructions:**
"Harsh sunlight and soft dreamy look" forces the AI to choose or average, producing muddy results. Professional DPs commit to aesthetic choices. If you want soft light, use overcast, scrims, diffusion. If you want harsh light, embrace contrast.

Source: [Chatsmith Prompt Mistakes](https://chatsmith.io/blogs/ai-guide/writing-ai-image-prompts-mistakes-00052)

**Static Video Descriptions:**
Video is temporal. "Sarah looks worried" is a photograph. "Over 4 seconds, Sarah's expression transitions from controlled composure to visible distress: first a micro-twitch in her left eye (0.5s), then her breathing becomes visible (1.5s), then her chin begins to tremble (2.5s), and finally her composure breaks (3.5s)" is video.

Source: [Magic Prompt Video Guide](http://mymagicprompt.com/ai/covers-the-hottest-trend-in-ai-generative-video/)

---

## Feature Categories Deep Dive

### 1. Camera Technical Specifications

Components that describe HOW the camera captures the image.

**Lens Choice:**
| Lens | Psychological Effect | Use Case |
|------|---------------------|----------|
| 24mm or wider | Exaggerated perspective, unease, scope | Establishing, tension, isolation |
| 35mm | Slight exaggeration, environmental context | Two-shots, walking scenes |
| 50mm | Natural, neutral, human eye equivalent | General coverage, documentary feel |
| 85mm | Flattering compression, intimate | Close-ups, beauty, romance |
| 135mm+ | Heavy compression, voyeuristic | Surveillance, observation, isolation |
| Anamorphic | Cinematic widescreen, oval bokeh, flares | Epic, stylized, prestige |

**Aperture and Depth of Field:**
- f/1.4-2.0: Extremely shallow DOF, isolates subject, dreamy
- f/2.8-4.0: Moderate DOF, subject sharp with soft background
- f/5.6-8.0: Deep DOF, environmental context visible
- f/11+: Everything sharp, documentary/realist style

**Camera Angle Vocabulary:**
- Eye level: Neutral, objective
- Low angle (below eye): Subject appears powerful, dominant, threatening
- High angle (above eye): Subject appears vulnerable, small, submissive
- Dutch angle: Unease, disorientation, psychological instability
- Overhead/Bird's eye: God's perspective, surveillance, omniscience
- Worm's eye: Extreme low, dramatic scale, unusual perspective

**Camera Movement:**
| Movement | Meaning | Prompt Language |
|----------|---------|-----------------|
| Dolly in | Increasing intimacy, tension | "Slow dolly in over 3s" |
| Dolly out | Reveal, loss, isolation | "Dolly out revealing empty room" |
| Track left/right | Following action, journey | "Camera trucks left with her" |
| Crane up | Transcendence, overview | "Crane up 10 feet over 5s" |
| Steadicam | Flowing, dreamlike, following | "Steadicam follows behind" |
| Handheld | Urgency, documentary, chaos | "Handheld with visible shake" |
| Static | Observation, stability, detachment | "Locked off, static frame" |
| Dolly zoom | Vertigo, realization, distortion | "Dolly in while zooming out" |

### 2. Lighting Specifications

**Three-Point Lighting Components:**
- **Key light**: Primary illumination, sets mood (hard/soft, direction)
- **Fill light**: Reduces contrast, determines shadow density
- **Back light**: Separation, hair light, rim glow

**Lighting Styles:**
| Style | Setup | Emotional Effect |
|-------|-------|------------------|
| High-key | Bright, even, low contrast | Happy, safe, comedy |
| Low-key | Dark, high contrast, deep shadows | Dramatic, thriller, noir |
| Chiaroscuro | Strong contrast, single source feel | Renaissance, moral complexity |
| Rembrandt | Triangle of light under eye | Portrait, depth, classic |
| Split | Half face lit, half shadow | Duality, internal conflict |
| Butterfly | Light from above, shadow under nose | Glamour, beauty |
| Rim/Back | Edge light from behind | Separation, ethereal, mystery |

**Color Temperature:**
- 2700K: Warm, candlelight, intimate
- 3200K: Tungsten, indoor warm
- 4500K: Mixed, fluorescent
- 5600K: Daylight balanced, neutral
- 7000K+: Cool, overcast, cold

**Practical Prompting:**
"Key light at 3200K, 45 degrees camera left, 4 feet above eye line creating Rembrandt triangle under left eye. Fill light at 30% key intensity from camera right. Hair light from behind at 6 o'clock creating rim separation. 3:1 lighting ratio overall."

### 3. Emotional/Performance Specifications

**Micro-Expression Vocabulary (FACS-informed):**
| Action | Description | Emotion Correlation |
|--------|-------------|---------------------|
| Inner brow raise (AU1) | Inner corners of eyebrows up | Sadness, worry |
| Outer brow raise (AU2) | Outer corners of eyebrows up | Surprise |
| Brow lowerer (AU4) | Brows pulled down/together | Anger, concentration |
| Upper lid raise (AU5) | Eyes widened | Fear, surprise |
| Cheek raise (AU6) | Cheeks pushed up | Genuine smile (Duchenne) |
| Lip corner pull (AU12) | Mouth corners up | Smile |
| Dimpler (AU14) | Cheek dimples | Contempt, suppressed smile |
| Lip press (AU24) | Lips pressed together | Tension, restraint |
| Chin raise (AU17) | Chin boss pushed up | Sadness, pout |
| Nose wrinkle (AU9) | Nose wrinkled | Disgust |

**Body Language Elements:**
- **Posture**: Slumped (defeat), rigid (tension), relaxed (comfort)
- **Weight distribution**: Leaning away (avoidance), forward (engagement)
- **Hand position**: Gripping (tension), open (vulnerability), hidden (deception)
- **Shoulder position**: Raised (anxiety), dropped (relief/sadness)
- **Head angle**: Tilted (curiosity/doubt), straight (attention)

**Practical Prompting:**
"Left eyebrow raised slightly (AU1+AU2, hope/uncertainty), corners of mouth pulled down (AU15, fighting tremor), chin slightly raised (AU17, holding back tears), right hand gripping coffee cup (knuckles visible white), shoulders slightly raised (unconscious tension), head tilted 5 degrees left (uncertainty)."

### 4. Temporal Specifications (Video Only)

**Beat Structure for Video Prompts:**
```
[0.0s - 1.0s]: Initial state
[1.0s - 2.0s]: First transition/change
[2.0s - 3.0s]: Development
[3.0s - 4.0s]: Resolution/end state
```

**Motion Description Components:**
- **Path**: "Sarah's hand moves from her side to touch the table"
- **Speed**: "Slowly, hesitantly" / "Quick, decisive" / "In 0.8 seconds"
- **Arc**: "Smooth arc" / "Trembling path" / "Abrupt stop"
- **Interaction**: "Fingers brush the surface" / "Palm presses flat"

**Camera Motion Timing:**
- "Camera dollies in 3 feet over 4 seconds" (specific)
- "Slow push in" (vague, less controllable)
- "Starting wide, ending on close-up" (describes states, not motion)

**Practical Video Prompting:**
"4-second shot: [0.0-1.0s] Sarah's face in profile, composed, slight muscle tension in jaw. [1.0-2.0s] Eyes widen slightly (AU5), catch light reflects as she turns head toward camera. [2.0-3.0s] Micro-expression of hope (AU1+AU2), breath visible as chest rises. [3.0-4.0s] Corners of mouth begin to curve upward (AU12), single tear escapes left eye and tracks down cheek. Camera: slow 6-inch dolly in throughout, starting at medium close-up, ending on close-up."

### 5. Voice/Audio Specifications

**Emotional Delivery Tags:**
| Tag | Effect | Example Use |
|-----|--------|-------------|
| [whispered] | Intimate, secret, fearful | "[whispered] I saw what you did." |
| [trembling] | Emotional, unstable | "[trembling] I can't do this anymore." |
| [stern] | Authoritative, warning | "[stern] This ends now." |
| [sarcastic] | Bitter, mocking | "[sarcastic] Oh, that's just wonderful." |
| [sorrowful] | Grief, loss | "[sorrowful] She's gone." |
| [excited] | Joy, anticipation | "[excited] They said yes!" |
| [monotone] | Detached, shock | "[monotone] It doesn't matter." |

**Pacing Markers:**
- Ellipses (...) for pauses: "I don't... I can't..."
- Em dashes for interruption: "If you think I'm going to--"
- Commas for breath breaks: "Well, I suppose, if you must know"
- [beat] for dramatic pause: "I loved her. [beat] Once."

**Vocal Quality Descriptors:**
- "Voice cracking mid-sentence"
- "Barely above a whisper"
- "Forced steadiness betraying inner turmoil"
- "Rising pitch indicating growing desperation"
- "Clipped, precise diction masking emotion"

**Practical Voice Prompting:**
"[DIALOGUE - Sarah, trembling, barely above whisper, voice cracking on 'tell'] 'Just... [beat, swallow] tell me.' [Note: Rising pitch on 'tell', breath audible between 'Just' and 'tell', swallow sound before 'tell me', final two words rushed together as control slips]"

---

## Prompt Structure Template

### Image Prompt (600-1000 words)

```
[SCENE CONTEXT - 50 words]
Narrative context establishing emotional stakes and story moment.

[CAMERA SPECIFICATIONS - 100 words]
- Lens: [focal length, aperture, type]
- Framing: [shot type, frame percentages, headroom]
- Angle: [eye level/low/high/dutch, degree of deviation]
- Composition: [rule of thirds/golden ratio, leading lines, negative space]
- Depth of field: [shallow/deep, focus plane, bokeh quality]

[LIGHTING SETUP - 150 words]
- Key light: [position, quality, color temp, intensity]
- Fill light: [ratio to key, position, quality]
- Back/rim light: [presence, color, effect]
- Practicals: [visible light sources in frame]
- Overall mood: [high-key/low-key/chiaroscuro]
- Lighting ratio: [specific ratio like 3:1]

[SUBJECT DESCRIPTION - 200 words]
- Physical appearance: [face, body, wardrobe, props]
- Facial expression: [FACS-informed micro-expressions]
- Body language: [posture, gesture, tension points]
- Emotional state manifest in physicality: [how internal state shows externally]
- Eye detail: [catchlights, tear presence, gaze direction]

[ENVIRONMENT/MISE-EN-SCENE - 100 words]
- Setting: [location, time of day, season]
- Production design: [set dressing, props, color palette]
- Background treatment: [focus, blur, content]
- Atmospheric elements: [haze, dust, steam, weather]

[MOOD AND SUBTEXT - 100 words]
- What the image should make viewers feel
- What is implied but not shown
- Color grading intention
- Symbolic elements

[TECHNICAL QUALITY - 50 words]
- Resolution: [8K, 4K]
- Style reference: [photorealistic, cinematic, specific film reference]
- Post-processing: [color grade, contrast, grain]
```

### Video Prompt (600-1000 words)

```
[SCENE CONTEXT - 50 words]
Narrative context and emotional stakes.

[TEMPORAL STRUCTURE - 200 words]
- [0.0s - 1.0s]: Beat 1 description
- [1.0s - 2.0s]: Beat 2 description
- [2.0s - 3.0s]: Beat 3 description
- [3.0s - 4.0s]: Beat 4 description
Include: facial progression, body movement paths, emotional transitions

[CAMERA MOTION - 100 words]
- Starting position: [framing, distance]
- Movement: [dolly/track/crane/static, direction, speed]
- Ending position: [final framing]
- Timing: [specific timing for each phase of movement]

[SUBJECT MOTION - 150 words]
- Character movement paths
- Gesture timing and arcs
- Micro-movement details (breath, blink, twitch)
- Interaction with environment/props

[LIGHTING AND ENVIRONMENT - 100 words]
Same as image prompt, plus:
- Light changes during shot (if any)
- Environmental motion (leaves, curtains, smoke)

[CONTINUITY NOTES - 100 words]
- Connections to previous shot
- Details that must match
- Wardrobe/prop positions
- Emotional arc position

[PHYSICS AND REALISM - 50 words]
- Gravity behavior
- Rigidity of objects
- Collision handling
- Hair/cloth simulation notes
```

### Voice Prompt

```
[SPEECH TYPE]: [NARRATOR/DIALOGUE/INTERNAL/MONOLOGUE]
[SPEAKER]: [Character name]
[EMOTIONAL CONTEXT]: [What just happened, what character knows/feels]

[DELIVERY DIRECTION]:
- Pace: [fast/slow/measured/erratic]
- Volume: [whisper/soft/normal/loud/shouting]
- Quality: [steady/trembling/cracking/forced-steady]
- Subtext: [What they're not saying but feeling]

[TEXT]:
"[emotional tag] Actual dialogue with [beat] markers and... hesitation punctuation."

[TECHNICAL NOTES]:
- Breath placement
- Word emphasis (italics or bold)
- Interruption points
- Ambient sound cues
```

---

## MVP Recommendation

For MVP Hollywood-Quality Prompt Pipeline, prioritize:

### Must-Have (Table Stakes)
1. **Camera spec vocabulary** - Lens, aperture, angle terminology
2. **Lighting terminology** - Key/fill/back, ratios, color temps
3. **Micro-expression vocabulary** - FACS-informed facial descriptions
4. **Quantified framing** - Frame percentages, headroom specs
5. **Body language library** - Posture, gesture, tension markers
6. **Temporal structure (video)** - Beat-by-beat timing format
7. **Voice delivery tags** - Emotional markers and pacing

### Should-Have (Differentiators)
8. **Subtext layer** - What the visual implies
9. **Continuity anchors** - Cross-shot consistency details
10. **Camera movement psychology** - Why, not just what
11. **Mise-en-scene integration** - Environment as character psychology

### Defer to Post-MVP
- Anamorphic lens simulation
- LUT/color grade references
- Multi-character spatial choreography
- Complex camera rig simulations (Steadicam, gimbal)
- Advanced physics descriptions for video

### Why This Prioritization

The table stakes items establish the vocabulary of professional cinematography. Without specific lens choices, lighting ratios, and micro-expressions, prompts cannot achieve Hollywood quality regardless of length. These are the foundation.

The differentiators add the "magic" — the subtext and psychology that separate craft from art. These can be added incrementally after the vocabulary foundation exists.

The deferred items are specialized techniques that add value but aren't necessary for the core quality transformation. They can come later as the system matures.

---

## Implementation Notes

### Prompt Length Budget (600-1000 words)

Approximate allocation for image prompts:
- Scene context: 50 words (5%)
- Camera specs: 100 words (10%)
- Lighting: 150 words (15%)
- Subject: 200 words (20%)
- Environment: 100 words (10%)
- Mood/subtext: 100 words (10%)
- Technical: 50 words (5%)
- **Buffer for specifics: ~250 words (25%)**

This structure ensures comprehensive coverage while leaving room for scene-specific details.

### Vocabulary Database Requirements

The system needs libraries of:
1. **Camera vocabulary**: Lens names, aperture values, movement types
2. **Lighting vocabulary**: Setup names, ratio values, color temps
3. **FACS vocabulary**: Action units mapped to emotions and descriptions
4. **Body language vocabulary**: Postures, gestures, tension indicators
5. **Temporal markers**: Beat structures for different video lengths
6. **Voice tags**: Emotional delivery markers with descriptions

### Context Requirements

To generate Hollywood-quality prompts, the system needs access to:
- Scene's position in emotional arc
- Character's current emotional state
- Previous shot details (for continuity)
- Character Bible (physical descriptions)
- Story context (what just happened, stakes)

---

## Success Metrics

### Qualitative
- Prompts read like professional screenplay action lines
- Generated images match Hollywood cinematography standards
- Videos have clear temporal progression, not static tableaux
- Voice delivery conveys subtext, not just text

### Quantitative
- Prompt length: 600-1000 words (measured)
- Camera spec presence: 100% of prompts include lens, angle, framing
- Lighting spec presence: 100% of prompts include at least key light description
- Micro-expression presence: 90%+ of character shots include FACS-informed details
- Temporal structure: 100% of video prompts include beat-by-beat timing
- Continuity anchors: 80%+ of prompts reference previous/next shot details

---

## Sources

### Cinematography Fundamentals
- [StudioBinder Ultimate Camera Shots Guide](https://www.studiobinder.com/blog/ultimate-guide-to-camera-shots/)
- [StudioBinder Rules of Shot Composition](https://www.studiobinder.com/blog/rules-of-shot-composition-in-film/)
- [StudioBinder 35mm vs 50mm Lens](https://www.studiobinder.com/blog/35mm-vs-50mm-lens/)
- [StudioBinder Blocking and Staging](https://www.studiobinder.com/blog/blocking-and-staging-scenes/)

### Lighting Techniques
- [No Film School 13 Lighting Techniques](https://nofilmschool.com/lighting-techniques-in-film)
- [StudioBinder Chiaroscuro Definition](https://www.studiobinder.com/blog/what-is-chiaroscuro-definition-examples/)
- [Filmmakers Academy Film Noir Lighting](https://www.filmmakersacademy.com/blog-film-noir-lighting/)

### Camera Movement
- [MasterClass Guide to Camera Moves](https://www.masterclass.com/articles/guide-to-camera-moves)
- [NFI Camera Movements](https://www.nfi.edu/camera-movements/)
- [StudioBinder Rack Focus](https://www.studiobinder.com/blog/rack-focus-shot-camera-movement-angles/)

### Micro-Expressions and FACS
- [Paul Ekman Facial Action Coding System](https://www.paulekman.com/facial-action-coding-system/)
- [iMotions FACS Visual Guidebook](https://imotions.com/blog/learning/research-fundamentals/facial-action-coding-system/)
- [EmotionIntell FACS Explained](https://www.emotionintell.com/resources/facial-expressions/facs-explained/)

### Color Psychology
- [No Film School Color Psychology in Film](https://nofilmschool.com/color-psychology-in-film)
- [LTX Studio Color Theory Guide](https://ltx.studio/glossary/color-theory)
- [Noam Kroll Psychology of Color Grading](https://noamkroll.com/the-psychology-of-color-grading-its-emotional-impact-on-your-audience/)

### AI Prompt Engineering
- [Leonardo AI Image Prompts](https://leonardo.ai/news/ai-image-prompts/)
- [Aiarty Midjourney Camera Angles](https://www.aiarty.com/midjourney-prompts/midjourney-camera-angles.htm)
- [Aituts Midjourney Lighting Prompts](https://aituts.com/midjourney-camera-prompts/)
- [Medium AI Video Prompting Strategies](https://medium.com/@creativeaininja/how-to-actually-control-next-gen-video-ai-runway-kling-veo-and-sora-prompting-strategies-92ef0055658b)

### Video Generation
- [Skywork Sora 2 vs Veo 3 Comparison](https://skywork.ai/blog/sora-2-vs-veo-3-vs-runway-gen-3-2025-ai-video-generator-comparison/)
- [Runway Gen 4.5 Review](https://max-productive.ai/ai-tools/runwayml/)

### Voice Direction
- [ElevenLabs Audio Tags](https://elevenlabs.io/blog/eleven-v3-audio-tags-expressing-emotional-context-in-speech)
- [Murf Voiceover Script Guide](https://murf.ai/resources/how-to-write-voiceover-script/)
- [School of Voiceover Script Analysis](https://www.schoolofvoiceover.com/talent-resources/script-analysis-for-voice-actors/)

### Emotional Storytelling
- [ScreenCraft Conveying Character Emotions](https://screencraft.org/blog/the-engine-of-empathy-three-ways-to-convey-characters-emotions/)
- [Filmmakers Academy Emotional Arc](https://www.filmmakersacademy.com/emotional-arc/)
- [No Film School Show Don't Tell](https://nofilmschool.com/show-dont-tell)

---

**Research Confidence Level: HIGH**
- Cinematography terminology verified with professional film education sources
- AI prompt engineering verified with 2025-2026 guides from major platforms
- FACS system verified with Paul Ekman's official documentation
- Color psychology verified with cinematography educational resources
- Video generation strategies verified with platform-specific documentation (Runway, Sora)

**Next Steps for Implementation:**
1. Build vocabulary databases (camera, lighting, FACS, body language)
2. Design prompt template structure matching word budget
3. Create context pipeline feeding story/character data to prompt generator
4. Implement continuity tracking across shots
5. Build temporal structure generator for video prompts
6. Integrate voice tag system with speech segment types
