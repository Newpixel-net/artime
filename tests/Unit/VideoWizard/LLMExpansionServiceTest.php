<?php

use Modules\AppVideoWizard\Services\LLMExpansionService;
use Modules\AppVideoWizard\Services\ComplexityDetectorService;
use Modules\AppVideoWizard\Services\CinematographyVocabulary;
use Modules\AppVideoWizard\Services\CharacterPsychologyService;
use Modules\AppVideoWizard\Services\CharacterDynamicsService;
use Modules\AppVideoWizard\Services\PromptExpanderService;
use App\Services\GrokService;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Create mock services
    $this->mockGrokService = Mockery::mock(GrokService::class);
    $this->mockAIService = Mockery::mock(AIService::class);

    // Create service with mocked LLM providers
    $this->service = new LLMExpansionService(
        new ComplexityDetectorService(),
        new CinematographyVocabulary(),
        new CharacterPsychologyService(),
        new CharacterDynamicsService(),
        new PromptExpanderService(),
        $this->mockGrokService,
        $this->mockAIService
    );
});

afterEach(function () {
    Mockery::close();
});

describe('LLMExpansionService', function () {

    describe('complexity-based routing', function () {

        test('simple_shot_uses_template', function () {
            // Single character, known shot type, known emotion = simple shot
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'fear',
                'environment' => 'dark room',
            ];

            $result = $this->service->expand($shotData);

            // Should NOT attempt LLM - simple shots use template
            expect($result['method'])->toBe('template');
            expect($result['provider'])->toBe('rules');
            expect($result['complexity']['is_complex'])->toBeFalse();
        });

        test('complex_shot_attempts_llm', function () {
            // Two characters with subtext = complex shot
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'subtext' => 'Hidden agenda beneath calm facade',
                'environment' => 'dimly lit office',
            ];

            // Mock Grok to return a valid response
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'data' => ['[LENS: 85mm, flattering compression] [SUBJECT: Marcus and Elena face each other] [DYNAMICS: social distance, conflict positioning]'],
                    'error' => null,
                    'totalTokens' => 150,
                ]);

            $result = $this->service->expand($shotData);

            expect($result['method'])->toBe('llm');
            expect($result['provider'])->toBe('grok');
            expect($result['complexity']['is_complex'])->toBeTrue();
        });

        test('three_plus_characters_always_attempts_llm', function () {
            // 3+ characters always triggers complexity
            $shotData = [
                'characters' => [
                    ['name' => 'Alice'],
                    ['name' => 'Bob'],
                    ['name' => 'Charlie'],
                ],
                'shot_type' => 'wide',
                'emotion' => 'peace',
            ];

            // Mock Grok to return a valid response
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'data' => ['[LENS: 24mm wide] [SUBJECT: Three characters in frame] [DYNAMICS: equal spacing]'],
                    'error' => null,
                    'totalTokens' => 100,
                ]);

            $result = $this->service->expand($shotData);

            expect($result['complexity']['is_complex'])->toBeTrue();
            expect($result['method'])->toBe('llm');
        });

    });

    describe('system prompt construction', function () {

        test('system_prompt_contains_vocabulary', function () {
            // Use reflection to access protected method
            $reflection = new ReflectionClass(LLMExpansionService::class);
            $method = $reflection->getMethod('buildSystemPrompt');
            $method->setAccessible(true);

            $systemPrompt = $method->invoke($this->service);

            // Should contain lens psychology vocabulary
            expect($systemPrompt)->toContain('24mm');
            expect($systemPrompt)->toContain('85mm');
            expect($systemPrompt)->toContain('135mm');

            // Should contain lighting ratios
            expect($systemPrompt)->toContain('1:1');
            expect($systemPrompt)->toContain('4:1');
            expect($systemPrompt)->toContain('8:1');

            // Should contain emotion physical manifestations
            expect($systemPrompt)->toContain('jaw');
            expect($systemPrompt)->toContain('brow');
            expect($systemPrompt)->toContain('eyes');

            // Should contain proxemic zones
            expect($systemPrompt)->toContain('intimate');
            expect($systemPrompt)->toContain('personal');
            expect($systemPrompt)->toContain('social');
        });

        test('system_prompt_contains_rules', function () {
            $reflection = new ReflectionClass(LLMExpansionService::class);
            $method = $reflection->getMethod('buildSystemPrompt');
            $method->setAccessible(true);

            $systemPrompt = $method->invoke($this->service);

            // Should contain critical rules
            expect($systemPrompt)->toContain('NEVER use emotion labels');
            expect($systemPrompt)->toContain('NEVER invent technical terms');
            expect($systemPrompt)->toContain('under 200 words');
            expect($systemPrompt)->toContain('spatial relationships');
        });

        test('system_prompt_contains_semantic_markers', function () {
            $reflection = new ReflectionClass(LLMExpansionService::class);
            $method = $reflection->getMethod('buildSystemPrompt');
            $method->setAccessible(true);

            $systemPrompt = $method->invoke($this->service);

            // Should define all semantic markers
            expect($systemPrompt)->toContain('[LENS:]');
            expect($systemPrompt)->toContain('[LIGHTING:]');
            expect($systemPrompt)->toContain('[FRAME:]');
            expect($systemPrompt)->toContain('[SUBJECT:]');
            expect($systemPrompt)->toContain('[DYNAMICS:]');
            expect($systemPrompt)->toContain('[ENVIRONMENT:]');
        });

    });

    describe('fallback cascade', function () {

        test('fallback_on_grok_failure', function () {
            // Complex shot
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'subtext' => 'Conflict brewing',
            ];

            // Mock Grok to fail
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andThrow(new Exception('Grok API error'));

            // Mock Gemini to succeed
            $this->mockAIService->shouldReceive('processWithOverride')
                ->once()
                ->andReturn([
                    'data' => ['[LENS: 50mm natural] [SUBJECT: Two figures] [DYNAMICS: conflict positioning]'],
                    'error' => null,
                    'totalTokens' => 120,
                ]);

            $result = $this->service->expand($shotData);

            expect($result['method'])->toBe('llm');
            expect($result['provider'])->toBe('gemini');
        });

        test('fallback_to_template_on_all_failure', function () {
            // Complex shot
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'subtext' => 'Hidden tension',
            ];

            // Mock Grok to fail
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andThrow(new Exception('Grok API error'));

            // Mock Gemini to also fail
            $this->mockAIService->shouldReceive('processWithOverride')
                ->once()
                ->andThrow(new Exception('Gemini API error'));

            $result = $this->service->expand($shotData);

            // Should fall back to template
            expect($result['method'])->toBe('template');
            expect($result['provider'])->toBe('rules');
            // But still marked as complex since it WAS complex
            expect($result['complexity']['is_complex'])->toBeTrue();
        });

        test('grok_error_response_triggers_gemini_fallback', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'subtext' => 'Deception',
            ];

            // Mock Grok to return error in response
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'data' => [],
                    'error' => 'Rate limit exceeded',
                    'totalTokens' => 0,
                ]);

            // Mock Gemini to succeed
            $this->mockAIService->shouldReceive('processWithOverride')
                ->once()
                ->andReturn([
                    'data' => ['[LENS: 85mm] [SUBJECT: Marcus and Elena] [DYNAMICS: tension]'],
                    'error' => null,
                    'totalTokens' => 100,
                ]);

            $result = $this->service->expand($shotData);

            expect($result['method'])->toBe('llm');
            expect($result['provider'])->toBe('gemini');
        });

    });

    describe('caching', function () {

        test('cache_hit_returns_cached', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'fear',
            ];

            // First call - should execute and cache
            $result1 = $this->service->expandWithCache($shotData);

            // Second call - should return cached (no LLM calls)
            $result2 = $this->service->expandWithCache($shotData);

            expect($result1)->toBe($result2);
            expect($result1['method'])->toBe('template'); // Simple shot uses template
        });

        test('different_shot_data_different_cache_key', function () {
            $shotData1 = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'fear',
            ];

            $shotData2 = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'joy', // Different emotion
            ];

            $result1 = $this->service->expandWithCache($shotData1);
            $result2 = $this->service->expandWithCache($shotData2);

            // Results should be different (different cache keys)
            expect($result1['expanded_prompt'])->not->toBe($result2['expanded_prompt']);
        });

    });

    describe('post-processing', function () {

        test('post_processing_validates_markers', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'subtext' => 'Conflict',
            ];

            // Mock Grok to return output with markers
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'data' => ['[LENS: 85mm] [SUBJECT: Two characters facing each other]'],
                    'error' => null,
                    'totalTokens' => 50,
                ]);

            $result = $this->service->expand($shotData);

            expect($result)->toHaveKey('markers_valid');
            expect($result['markers_valid'])->toBeTrue();
        });

        test('post_processing_flags_missing_markers', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'subtext' => 'Conflict',
            ];

            // Mock Grok to return output WITHOUT markers
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'data' => ['Two characters in a tense conversation in an office.'],
                    'error' => null,
                    'totalTokens' => 30,
                ]);

            $result = $this->service->expand($shotData);

            expect($result)->toHaveKey('markers_valid');
            expect($result['markers_valid'])->toBeFalse();
        });

        test('post_processing_trims_long_output', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'subtext' => 'Conflict',
            ];

            // Create a very long output (over 200 words)
            $longText = '[LENS: 85mm] [SUBJECT: ' . str_repeat('word ', 250) . ']';

            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'data' => [$longText],
                    'error' => null,
                    'totalTokens' => 400,
                ]);

            $result = $this->service->expand($shotData);

            expect($result)->toHaveKey('trimmed');
            expect($result['trimmed'])->toBeTrue();
            expect($result['word_count'])->toBeLessThanOrEqual(200);
        });

        test('multi_character_shot_includes_dynamics', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'relationship' => 'rivals',
            ];

            // Mock Grok to return output WITHOUT dynamics marker
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'data' => ['[LENS: 85mm] [SUBJECT: Marcus and Elena in frame]'],
                    'error' => null,
                    'totalTokens' => 50,
                ]);

            $result = $this->service->expand($shotData);

            // Should flag that dynamics is missing for multi-character
            expect($result)->toHaveKey('dynamics_missing');
            expect($result['dynamics_missing'])->toBeTrue();
        });

        test('multi_character_with_dynamics_not_flagged', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'relationship' => 'rivals',
            ];

            // Mock Grok to return output WITH dynamics marker
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'data' => ['[LENS: 85mm] [SUBJECT: Marcus and Elena] [DYNAMICS: conflict positioning, bodies angled away]'],
                    'error' => null,
                    'totalTokens' => 80,
                ]);

            $result = $this->service->expand($shotData);

            // Should NOT flag dynamics_missing
            expect($result)->not->toHaveKey('dynamics_missing');
        });

    });

    describe('template fallback output', function () {

        test('template_output_has_semantic_markers', function () {
            // Simple shot uses template
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'close-up',
                'emotion' => 'fear',
                'environment' => 'dark corridor',
            ];

            $result = $this->service->expand($shotData);

            expect($result['method'])->toBe('template');
            // Template output should still have semantic markers
            expect($result['expanded_prompt'])->toContain('[LENS:');
            expect($result['expanded_prompt'])->toContain('[SUBJECT:');
        });

        test('template_two_character_includes_dynamics', function () {
            // Two-character shot that somehow uses template (e.g., LLM fails)
            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'relationship' => 'colleagues',
            ];

            // Mock both LLMs to fail so it falls back to template
            $this->mockGrokService->shouldReceive('generateText')
                ->once()
                ->andThrow(new Exception('Grok error'));

            $this->mockAIService->shouldReceive('processWithOverride')
                ->once()
                ->andThrow(new Exception('Gemini error'));

            $result = $this->service->expand($shotData);

            expect($result['method'])->toBe('template');
            // Template should include dynamics for multi-character
            expect($result['expanded_prompt'])->toContain('[DYNAMICS:');
        });

        test('template_includes_environment_when_provided', function () {
            $shotData = [
                'characters' => [['name' => 'Marcus']],
                'shot_type' => 'medium',
                'environment' => 'rainy street at night',
            ];

            $result = $this->service->expand($shotData);

            expect($result['method'])->toBe('template');
            expect($result['expanded_prompt'])->toContain('[ENVIRONMENT:');
            expect($result['expanded_prompt'])->toContain('rainy street at night');
        });

    });

    describe('user prompt construction', function () {

        test('user_prompt_includes_shot_data', function () {
            $reflection = new ReflectionClass(LLMExpansionService::class);
            $method = $reflection->getMethod('buildUserPrompt');
            $method->setAccessible(true);

            $shotData = [
                'characters' => [['name' => 'Marcus'], ['name' => 'Elena']],
                'shot_type' => 'two-shot',
                'emotion' => 'tension',
                'subtext' => 'Hidden agenda',
                'environment' => 'corporate boardroom',
                'relationship' => 'rivals',
            ];

            $userPrompt = $method->invoke($this->service, $shotData);

            expect($userPrompt)->toContain('two-shot');
            expect($userPrompt)->toContain('Marcus');
            expect($userPrompt)->toContain('Elena');
            expect($userPrompt)->toContain('tension');
            expect($userPrompt)->toContain('Hidden agenda');
            expect($userPrompt)->toContain('corporate boardroom');
            expect($userPrompt)->toContain('rivals');
        });

        test('user_prompt_handles_string_characters', function () {
            $reflection = new ReflectionClass(LLMExpansionService::class);
            $method = $reflection->getMethod('buildUserPrompt');
            $method->setAccessible(true);

            $shotData = [
                'characters' => ['Marcus', 'Elena'], // Strings instead of arrays
                'shot_type' => 'two-shot',
            ];

            $userPrompt = $method->invoke($this->service, $shotData);

            expect($userPrompt)->toContain('Marcus');
            expect($userPrompt)->toContain('Elena');
        });

    });

    describe('vocabulary formatting', function () {

        test('format_vocabulary_handles_arrays', function () {
            $reflection = new ReflectionClass(LLMExpansionService::class);
            $method = $reflection->getMethod('formatVocabulary');
            $method->setAccessible(true);

            $vocabulary = [
                'key1' => ['effect' => 'some effect', 'other' => 'data'],
                'key2' => ['description' => 'some description'],
                'key3' => 'simple string',
            ];

            $formatted = $method->invoke($this->service, $vocabulary);

            expect($formatted)->toContain('key1: some effect');
            expect($formatted)->toContain('key2: some description');
            expect($formatted)->toContain('key3: simple string');
        });

    });

});
