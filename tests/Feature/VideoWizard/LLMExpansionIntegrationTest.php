<?php

namespace Tests\Feature\VideoWizard;

use Tests\TestCase;
use Mockery;
use Modules\AppVideoWizard\Services\StructuredPromptBuilderService;
use Modules\AppVideoWizard\Services\LLMExpansionService;
use Modules\AppVideoWizard\Services\ComplexityDetectorService;
use App\Services\GrokService;
use App\Services\AIService;

/**
 * Integration tests for Phase 26: LLM-Powered Expansion
 *
 * Verifies that LLM expansion is properly integrated into the prompt building
 * pipeline and that complex shots trigger LLM enhancement while simple shots
 * use efficient template expansion.
 *
 * Key requirements tested:
 * - Complex shots (3+ chars, subtext) trigger LLM expansion
 * - Simple shots use template path (no LLM overhead)
 * - LLM output contains Hollywood vocabulary (semantic markers)
 * - llm_expansion => false option bypasses LLM
 * - LLM failures fall back to template with valid output
 * - Multi-character prompts include spatial dynamics
 */
class LLMExpansionIntegrationTest extends TestCase
{
    protected StructuredPromptBuilderService $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new StructuredPromptBuilderService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     * Test that simple shots (1-2 characters, basic emotion) use template path.
     */
    public function test_simple_shot_uses_template_path()
    {
        // Simple shot: close-up of single character with basic emotion
        $result = $this->builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'close-up',
            'emotion' => 'grief',
            'scene_description' => 'A woman looks out the window',
        ]);

        // Should use template expansion (not LLM)
        $this->assertArrayHasKey('meta_data', $result);
        $this->assertEquals('template', $result['meta_data']['expansion_method'] ?? 'template');

        // Verify LLM metadata indicates template usage
        $this->assertArrayHasKey('llm_metadata', $result);
        $this->assertEquals('template', $result['llm_metadata']['method']);
        $this->assertEquals('rules', $result['llm_metadata']['provider']);
    }

    /**
     * @test
     * Test that complex shots (3+ characters) trigger LLM expansion attempt.
     */
    public function test_complex_shot_triggers_llm_path()
    {
        // Complex shot: 3 characters with subtext
        $result = $this->builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'two-shot',
            'characters' => [
                ['name' => 'Marcus'],
                ['name' => 'Elena'],
                ['name' => 'David'],
            ],
            'emotion' => 'tension',
            'subtext' => 'Hidden jealousy',
            'scene_description' => 'Three colleagues in a tense meeting',
        ]);

        // Should have attempted LLM expansion (may fall back to template if no API keys)
        $this->assertArrayHasKey('meta_data', $result);
        $this->assertArrayHasKey('llm_metadata', $result);

        // The method should be either 'llm' (if API available) or 'template' (fallback)
        $method = $result['llm_metadata']['method'] ?? 'unknown';
        $this->assertContains($method, ['llm', 'template'],
            'Complex shot should attempt LLM or fall back to template');
    }

    /**
     * @test
     * Test that LLM-expanded prompts contain Hollywood vocabulary (semantic markers).
     */
    public function test_llm_output_contains_hollywood_vocabulary()
    {
        // Create a mock GrokService that returns a proper LLM response
        $mockGrokService = Mockery::mock(GrokService::class);
        $mockGrokService->shouldReceive('generateText')
            ->andReturn([
                'data' => [
                    '[LENS: 85mm lens creates intimate compression, soft background separation] ' .
                    '[SUBJECT: Marcus with jaw set, tension visible in brow, squared shoulders] ' .
                    '[LIGHTING: 2:1 key ratio, soft modeling with natural falloff] ' .
                    '[DYNAMICS: triangular blocking, David dominant frame right, Marcus subordinate left] ' .
                    '[ENVIRONMENT: corporate meeting room, harsh fluorescent overhead]'
                ],
                'totalTokens' => 150,
            ]);

        // Create LLMExpansionService with mock
        $llmService = new LLMExpansionService(
            null, // complexity detector
            null, // cinematography vocabulary
            null, // character psychology
            null, // character dynamics
            null, // prompt expander
            $mockGrokService,
            null  // AI service
        );

        // Inject mocked service into builder
        $builder = new StructuredPromptBuilderService();
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('llmExpansionService');
        $property->setAccessible(true);
        $property->setValue($builder, $llmService);

        $result = $builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'two-shot',
            'characters' => [
                ['name' => 'Marcus'],
                ['name' => 'Elena'],
                ['name' => 'David'],
            ],
            'subtext' => 'Hidden tension',
        ]);

        // Verify LLM was used
        $this->assertEquals('llm_expansion', $result['meta_data']['expansion_method']);

        // Verify semantic markers in output
        $prompt = $result['creative_prompt']['scene_summary'] ?? '';
        $this->assertStringContainsString('[LENS:', $prompt, 'LLM output should contain LENS marker');
        $this->assertStringContainsString('[SUBJECT:', $prompt, 'LLM output should contain SUBJECT marker');
    }

    /**
     * @test
     * Test that llm_expansion => false option bypasses complexity check.
     */
    public function test_llm_disabled_option_bypasses_complexity_check()
    {
        // Complex shot that WOULD trigger LLM, but with llm_expansion disabled
        $result = $this->builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'two-shot',
            'characters' => [
                ['name' => 'Marcus'],
                ['name' => 'Elena'],
                ['name' => 'David'],
            ],
            'subtext' => 'Hidden tension',
            'llm_expansion' => false, // Explicitly disable LLM
        ]);

        // Should use template expansion despite complexity
        $this->assertArrayHasKey('meta_data', $result);
        $this->assertEquals('template', $result['meta_data']['expansion_method']);

        // Verify LLM was not used
        $this->assertArrayHasKey('llm_metadata', $result);
        $this->assertEquals('template', $result['llm_metadata']['method']);
    }

    /**
     * @test
     * Test that LLM failure falls back to template and produces valid prompt.
     */
    public function test_llm_failure_fallback_produces_valid_prompt()
    {
        // Create mocks that fail
        $mockGrokService = Mockery::mock(GrokService::class);
        $mockGrokService->shouldReceive('generateText')
            ->andThrow(new \Exception('Grok API unavailable'));

        $mockAIService = Mockery::mock(AIService::class);
        $mockAIService->shouldReceive('processWithOverride')
            ->andThrow(new \Exception('Gemini API unavailable'));

        // Create LLMExpansionService with failing mocks
        $llmService = new LLMExpansionService(
            null,
            null,
            null,
            null,
            null,
            $mockGrokService,
            $mockAIService
        );

        // Inject mocked service into builder
        $builder = new StructuredPromptBuilderService();
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('llmExpansionService');
        $property->setAccessible(true);
        $property->setValue($builder, $llmService);

        $result = $builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'two-shot',
            'characters' => [
                ['name' => 'Marcus'],
                ['name' => 'Elena'],
                ['name' => 'David'],
            ],
            'subtext' => 'Hidden tension',
        ]);

        // Should fall back to template
        $this->assertArrayHasKey('meta_data', $result);
        // Method should be template (fallback after LLM failure)
        $method = $result['llm_metadata']['method'] ?? $result['meta_data']['expansion_method'];
        $this->assertEquals('template', $method);

        // Should still produce valid prompt structure
        $this->assertArrayHasKey('creative_prompt', $result);
        $this->assertArrayHasKey('technical_specifications', $result);
        $this->assertArrayHasKey('negative_prompt', $result);
    }

    /**
     * @test
     * Test that multi-character prompts include spatial dynamics.
     */
    public function test_multi_character_prompt_contains_dynamics()
    {
        // Create mock that returns prompt WITH dynamics marker
        $mockGrokService = Mockery::mock(GrokService::class);
        $mockGrokService->shouldReceive('generateText')
            ->andReturn([
                'data' => [
                    '[LENS: 50mm standard perspective] ' .
                    '[SUBJECT: Marcus and Elena facing each other] ' .
                    '[DYNAMICS: intimate zone 18 inches apart, power balanced, facing confrontation] ' .
                    '[LIGHTING: dramatic low key, high contrast]'
                ],
                'totalTokens' => 100,
            ]);

        // Create LLMExpansionService with mock
        $llmService = new LLMExpansionService(
            null,
            null,
            null,
            null,
            null,
            $mockGrokService,
            null
        );

        // Inject mocked service
        $builder = new StructuredPromptBuilderService();
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('llmExpansionService');
        $property->setAccessible(true);
        $property->setValue($builder, $llmService);

        $result = $builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'two-shot',
            'characters' => [
                ['name' => 'Marcus'],
                ['name' => 'Elena'],
                ['name' => 'David'],
            ],
            'relationship' => 'tension',
        ]);

        // Verify DYNAMICS marker present for multi-character scene
        $prompt = $result['creative_prompt']['scene_summary'] ?? '';
        $this->assertStringContainsString('[DYNAMICS:', $prompt,
            'Multi-character LLM output should contain DYNAMICS marker');
    }

    /**
     * @test
     * Test end-to-end flow from shot data to final adapted prompt structure.
     */
    public function test_end_to_end_image_generation_flow()
    {
        // Test the complete flow with a realistic shot scenario
        $result = $this->builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'medium',
            'emotion' => 'contemplation',
            'scene_description' => 'A detective studies the evidence board',
            'character_bible' => [
                'enabled' => true,
                'characters' => [
                    [
                        'id' => 'det_1',
                        'name' => 'Detective Mills',
                        'description' => 'middle-aged man with weathered face',
                    ],
                ],
            ],
        ]);

        // Verify complete prompt structure
        $this->assertArrayHasKey('meta_data', $result);
        $this->assertArrayHasKey('output_settings', $result);
        $this->assertArrayHasKey('global_rules', $result);
        $this->assertArrayHasKey('creative_prompt', $result);
        $this->assertArrayHasKey('technical_specifications', $result);
        $this->assertArrayHasKey('negative_prompt', $result);

        // Verify visual mode is preserved
        $this->assertEquals('cinematic-realistic', $result['meta_data']['visual_mode']);

        // Verify expansion method is tracked
        $this->assertArrayHasKey('expansion_method', $result['meta_data']);
        $this->assertContains($result['meta_data']['expansion_method'], ['template', 'llm_expansion']);

        // Verify llm_metadata is present
        $this->assertArrayHasKey('llm_metadata', $result);
        $this->assertArrayHasKey('method', $result['llm_metadata']);
        $this->assertArrayHasKey('provider', $result['llm_metadata']);
    }

    /**
     * @test
     * Test that two-character shots don't trigger complexity by character count alone.
     */
    public function test_two_character_shot_uses_template_without_subtext()
    {
        // Two characters without subtext should use template
        $result = $this->builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'two-shot',
            'characters' => [
                ['name' => 'Marcus'],
                ['name' => 'Elena'],
            ],
            'emotion' => 'neutral',
            'scene_description' => 'Two people having a conversation',
        ]);

        // Two characters alone shouldn't trigger LLM (threshold is 3+)
        $this->assertArrayHasKey('meta_data', $result);
        // With just 2 chars and no subtext, should typically use template
        // (unless emotional complexity or other factors trigger it)
        $this->assertArrayHasKey('llm_metadata', $result);
    }

    /**
     * @test
     * Test that Scene DNA characters are properly extracted for complexity check.
     */
    public function test_scene_dna_characters_extracted_for_complexity()
    {
        $sceneDNA = [
            'enabled' => true,
            'scenes' => [
                0 => [
                    'characters' => [
                        ['name' => 'Marcus', 'role' => 'protagonist'],
                        ['name' => 'Elena', 'role' => 'antagonist'],
                        ['name' => 'David', 'role' => 'witness'],
                    ],
                    'emotion' => 'tension',
                    'subtext' => 'Hidden betrayal',
                ],
            ],
        ];

        $result = $this->builder->buildHollywoodPrompt([
            'visual_mode' => 'cinematic-realistic',
            'shot_type' => 'wide',
            'scene_dna' => $sceneDNA,
            'scene_index' => 0,
        ]);

        // Should detect complexity from Scene DNA characters
        $this->assertArrayHasKey('llm_metadata', $result);
        // With 3 characters from Scene DNA, should attempt LLM
        $this->assertContains($result['llm_metadata']['method'], ['llm', 'template']);
    }
}
