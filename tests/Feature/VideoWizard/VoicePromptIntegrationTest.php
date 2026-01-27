<?php

namespace Tests\Feature\VideoWizard;

use Tests\TestCase;
use Modules\AppVideoWizard\Services\VoicePromptBuilderService;
use Modules\AppVideoWizard\Services\VoiceDirectionVocabulary;
use Modules\AppVideoWizard\Services\VoicePacingService;
use Modules\AppVideoWizard\Services\SpeechSegment;

/**
 * Integration tests for Phase 25: Voice Prompt Enhancement
 *
 * Verifies that all voice prompt services are properly integrated through
 * the VoicePromptBuilderService pipeline.
 *
 * Key requirements tested:
 * - VOC-01: Emotional direction tags [trembling], [whisper], [voice cracks]
 * - VOC-02: Pacing markers with specific timing [PAUSE 2.5s]
 * - VOC-03: Vocal quality descriptions (gravelly, exhausted, breathless)
 * - VOC-04: Ambient audio cues for scene atmosphere
 * - VOC-05: Breath and non-verbal sound markers [sighs], [gasps], [stammers]
 * - VOC-06: Emotional arc direction across dialogue sequences
 */
class VoicePromptIntegrationTest extends TestCase
{
    protected VoicePromptBuilderService $builder;
    protected VoiceDirectionVocabulary $voiceDirection;
    protected VoicePacingService $pacingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate real services for integration testing
        $this->voiceDirection = new VoiceDirectionVocabulary();
        $this->pacingService = new VoicePacingService();
        $this->builder = new VoicePromptBuilderService($this->voiceDirection, $this->pacingService);
    }

    /**
     * Create a dialogue segment with optional emotion.
     */
    protected function createDialogueSegment(string $speaker, string $text, ?string $emotion = null): SpeechSegment
    {
        $segment = SpeechSegment::dialogue($speaker, $text);
        if ($emotion !== null) {
            $segment->emotion = $emotion;
        }
        return $segment;
    }

    // =========================================================================
    // Test: Full Voice Prompt Enhancement Pipeline
    // =========================================================================

    /** @test */
    public function testFullVoicePromptEnhancementPipeline()
    {
        // Create 3 SpeechSegment objects with different emotions
        $segments = [
            $this->createDialogueSegment('ALICE', 'I cannot believe this is happening.', 'anxiety'),
            $this->createDialogueSegment('BOB', 'I know. It is too late now.', 'grief'),
            $this->createDialogueSegment('ALICE', 'What are we going to do?', 'fear'),
        ];

        // Process through buildDialogueDirectionPrompt
        $result = $this->builder->buildDialogueDirectionPrompt(
            $segments,
            'building',
            'tense',
            'elevenlabs'
        );

        // Verify structure
        $this->assertArrayHasKey('segments', $result);
        $this->assertArrayHasKey('arcSummary', $result);
        $this->assertArrayHasKey('ambient', $result);
        $this->assertCount(3, $result['segments']);

        // Verify each segment has emotional direction
        foreach ($result['segments'] as $index => $item) {
            $this->assertArrayHasKey('segment', $item);
            $this->assertArrayHasKey('enhanced', $item);

            $segment = $item['segment'];
            $enhanced = $item['enhanced'];

            // Each segment should have arc note assigned
            $this->assertNotEmpty(
                $segment->emotionalArcNote,
                "Segment {$index} should have emotionalArcNote"
            );

            // Enhanced text should contain emotional tags for ElevenLabs
            if (!empty($segment->emotion)) {
                $this->assertStringContainsString(
                    '[',
                    $enhanced['text'],
                    "Segment {$index} with emotion should have bracketed tag"
                );
            }
        }

        // Verify ambient cue present
        $this->assertStringContainsString('silence', $result['ambient']);
        $this->assertStringContainsString('anticipation', $result['ambient']);

        // Verify arc summary present
        $this->assertNotEmpty($result['arcSummary']);
    }

    // =========================================================================
    // Test: Voice Prompt Includes All VOC Requirements
    // =========================================================================

    /** @test */
    public function testVoicePromptIncludesAllVocRequirements()
    {
        // Create segment with grief emotion
        $segment = $this->createDialogueSegment(
            'SARAH',
            'I miss him every single day.',
            'grief'
        );

        // Build enhanced prompt for ElevenLabs
        $enhanced = $this->builder->buildEnhancedVoicePrompt($segment, [
            'provider' => 'elevenlabs',
            'includeAmbient' => true,
            'sceneType' => 'intimate',
        ]);

        // VOC-01: Assert contains emotional direction tag like [crying] or [grieving]
        $this->assertTrue(
            str_contains($enhanced['text'], '[crying]') ||
            str_contains($enhanced['text'], '[grieving]'),
            'VOC-01: Should contain emotional direction tag for grief'
        );

        // VOC-02: Assert can add pacing markers via pacingService
        $textWithPause = $segment->text . ' ' . $this->pacingService->insertPauseMarker(2.0);
        $this->assertStringContainsString('[PAUSE 2s]', $textWithPause, 'VOC-02: Should be able to add pacing markers');

        // VOC-03: Assert vocal quality can be retrieved for segment
        $vocalQuality = $this->voiceDirection->getVocalQuality('exhausted');
        $this->assertNotEmpty($vocalQuality, 'VOC-03: Should retrieve vocal quality description');
        $this->assertStringContainsString('drained', $vocalQuality);

        // VOC-04: Assert ambient cue present in output
        $this->assertNotEmpty($enhanced['ambient'], 'VOC-04: Ambient cue should be present');
        $this->assertStringContainsString('quiet room', $enhanced['ambient']);

        // VOC-05: Assert non-verbal sounds available via vocabulary
        $sighSound = $this->voiceDirection->getNonVerbalSound('sigh');
        $this->assertNotEmpty($sighSound, 'VOC-05: Non-verbal sounds should be available');
        $this->assertArrayHasKey('tag', $sighSound);
        $this->assertEquals('[sighs]', $sighSound['tag']);

        // VOC-06: Assert arc summary describes emotional progression
        $arcSummary = $this->builder->buildArcSummary('building', 3);
        $this->assertNotEmpty($arcSummary, 'VOC-06: Arc summary should describe progression');
        $this->assertStringContainsString('quiet', $arcSummary);
    }

    // =========================================================================
    // Test: OpenAI Provider Returns Instructions Separately
    // =========================================================================

    /** @test */
    public function testOpenaiProviderReturnsInstructionsSeparately()
    {
        $segment = $this->createDialogueSegment(
            'DAVID',
            'This is the moment we have been waiting for.',
            'anxiety'
        );

        // Build enhanced prompt for OpenAI provider
        $enhanced = $this->builder->buildEnhancedVoicePrompt($segment, [
            'provider' => 'openai',
        ]);

        // Text should NOT contain bracketed tags
        $this->assertStringNotContainsString('[', $enhanced['text'], 'OpenAI text should not contain bracketed tags');
        $this->assertStringNotContainsString(']', $enhanced['text'], 'OpenAI text should not contain bracketed tags');
        $this->assertEquals('This is the moment we have been waiting for.', $enhanced['text']);

        // Instructions array should contain emotional direction description
        $this->assertNotEmpty($enhanced['instructions'], 'OpenAI should have instructions');
        $this->assertStringContainsString('tight', $enhanced['instructions']);
    }

    // =========================================================================
    // Test: Emotional Arc Spans Dialogue Sequence
    // =========================================================================

    /** @test */
    public function testEmotionalArcSpansDialogueSequence()
    {
        // Create 4 segments representing a conversation
        $segments = [
            $this->createDialogueSegment('ALICE', 'Everything is fine.', 'neutral'),
            $this->createDialogueSegment('BOB', 'Are you sure?', 'neutral'),
            $this->createDialogueSegment('ALICE', 'Well, maybe not.', 'neutral'),
            $this->createDialogueSegment('BOB', 'I knew it!', 'neutral'),
        ];

        // Apply 'building' arc
        $result = $this->builder->buildEmotionalArc($segments, 'building');

        // Assert first segment has 'quiet' note
        $this->assertEquals(
            'quiet',
            $result[0]->emotionalArcNote,
            'First segment should have "quiet" arc note'
        );

        // Assert last segment has 'peak' note
        $this->assertEquals(
            'peak',
            $result[3]->emotionalArcNote,
            'Last segment should have "peak" arc note'
        );

        // Assert arc summary describes "start quiet...reach peak"
        $arcSummary = $this->builder->buildArcSummary('building', 4);
        $this->assertStringContainsString('quiet', $arcSummary);
        $this->assertStringContainsString('peak', $arcSummary);
    }

    // =========================================================================
    // Test: Kokoro Provider Uses Descriptive Style
    // =========================================================================

    /** @test */
    public function testKokoroProviderUsesDescriptiveStyle()
    {
        $segment = $this->createDialogueSegment(
            'EMMA',
            'I have something to tell you.',
            'grief'
        );

        // Build enhanced prompt for Kokoro provider
        $enhanced = $this->builder->buildEnhancedVoicePrompt($segment, [
            'provider' => 'kokoro',
        ]);

        // Text should not contain bracketed tags (Kokoro uses descriptive style)
        $this->assertStringNotContainsString('[crying]', $enhanced['text']);

        // Instructions should contain descriptive direction
        $this->assertNotEmpty($enhanced['instructions']);
        $this->assertStringContainsString('sorrow', $enhanced['instructions']);
    }

    // =========================================================================
    // Test: Ambient Cues Cover All Scene Types
    // =========================================================================

    /** @test */
    public function testAmbientCuesCoverAllSceneTypes()
    {
        $sceneTypes = ['intimate', 'outdoor', 'crowded', 'tense', 'storm', 'night', 'office', 'vehicle'];

        foreach ($sceneTypes as $sceneType) {
            $cue = $this->builder->buildAmbientCue($sceneType);

            $this->assertNotEmpty(
                $cue,
                "Scene type '{$sceneType}' should have ambient cue"
            );

            // Each cue should be descriptive enough for audio direction
            $this->assertGreaterThan(
                20,
                strlen($cue),
                "Ambient cue for '{$sceneType}' should be descriptive"
            );
        }
    }

    // =========================================================================
    // Test: All Arc Types Apply Correctly
    // =========================================================================

    /** @test */
    public function testAllArcTypesApplyCorrectly()
    {
        $arcTypes = ['building', 'crashing', 'recovering', 'masking', 'revealing', 'confronting'];

        $segments = [
            $this->createDialogueSegment('A', 'Line 1'),
            $this->createDialogueSegment('A', 'Line 2'),
            $this->createDialogueSegment('A', 'Line 3'),
            $this->createDialogueSegment('A', 'Line 4'),
        ];

        foreach ($arcTypes as $arcType) {
            // Clone segments for each arc test
            $testSegments = array_map(fn($s) => clone $s, $segments);

            $result = $this->builder->buildEmotionalArc($testSegments, $arcType);

            // Each segment should have an arc note
            foreach ($result as $index => $segment) {
                $this->assertNotEmpty(
                    $segment->emotionalArcNote,
                    "Arc '{$arcType}' segment {$index} should have arc note"
                );
            }

            // Arc pattern should be used
            $expectedPattern = VoicePromptBuilderService::EMOTIONAL_ARC_PATTERNS[$arcType];
            $this->assertEquals(
                $expectedPattern[0],
                $result[0]->emotionalArcNote,
                "Arc '{$arcType}' first segment should match pattern start"
            );
            $this->assertEquals(
                $expectedPattern[3],
                $result[3]->emotionalArcNote,
                "Arc '{$arcType}' last segment should match pattern end"
            );
        }
    }

    // =========================================================================
    // Test: Integration with VoiceDirectionVocabulary
    // =========================================================================

    /** @test */
    public function testIntegrationWithVoiceDirectionVocabulary()
    {
        // Verify the builder can access all VoiceDirectionVocabulary features
        $voiceDir = $this->builder->getVoiceDirection();

        // Should be able to get emotions
        $this->assertTrue($voiceDir->hasEmotion('grief'));
        $this->assertTrue($voiceDir->hasEmotion('anxiety'));
        $this->assertTrue($voiceDir->hasEmotion('fear'));

        // Should be able to get vocal qualities
        $this->assertTrue($voiceDir->hasVocalQuality('gravelly'));
        $this->assertTrue($voiceDir->hasVocalQuality('exhausted'));

        // Should be able to get non-verbal sounds
        $this->assertTrue($voiceDir->hasNonVerbalSound('sigh'));
        $this->assertTrue($voiceDir->hasNonVerbalSound('gasp'));
    }

    // =========================================================================
    // Test: Integration with VoicePacingService
    // =========================================================================

    /** @test */
    public function testIntegrationWithVoicePacingService()
    {
        // Verify the builder can access VoicePacingService features
        $pacing = $this->builder->getPacingService();

        // Should be able to create pause markers
        $pause = $pacing->insertPauseMarker(2.5);
        $this->assertEquals('[PAUSE 2.5s]', $pause);

        // Should be able to get pause notations
        $beatNotation = $pacing->getPauseNotation('beat');
        $this->assertEquals('[beat]', $beatNotation);

        // Should be able to convert to SSML
        $text = 'Hello [PAUSE 1s] world';
        $ssml = $pacing->toSSML($text);
        $this->assertStringContainsString('<break time="1s"/>', $ssml);
    }
}
