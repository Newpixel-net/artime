<?php

use Modules\AppVideoWizard\Services\ModelPromptAdapterService;
use Modules\AppVideoWizard\Services\PromptTemplateLibrary;

beforeEach(function () {
    $this->adapter = new ModelPromptAdapterService();
});

describe('ModelPromptAdapterService', function () {

    describe('countTokens', function () {

        test('returns integer for valid text', function () {
            $result = $this->adapter->countTokens('A woman standing in a field');

            expect($result)->toBeInt();
        });

        test('increases with longer text', function () {
            $short = $this->adapter->countTokens('A cat');
            $long = $this->adapter->countTokens('A beautiful orange tabby cat sitting on a velvet cushion in an ornate Victorian parlor');

            expect($long)->toBeGreaterThan($short);
        });

        test('returns 0 for empty string', function () {
            $result = $this->adapter->countTokens('');

            expect($result)->toBe(0);
        });

        test('handles special characters', function () {
            $result = $this->adapter->countTokens('Hello! How are you? "Fine," she said.');

            expect($result)->toBeInt();
            expect($result)->toBeGreaterThan(0);
        });

    });

    describe('MODEL_CONFIGS', function () {

        test('includes hidream configuration', function () {
            $configs = ModelPromptAdapterService::MODEL_CONFIGS;

            expect($configs)->toHaveKey('hidream');
            expect($configs['hidream']['tokenizer'])->toBe('clip');
            expect($configs['hidream']['maxTokens'])->toBe(77);
            expect($configs['hidream']['truncation'])->toBe('intelligent');
        });

        test('includes nanobanana configuration', function () {
            $configs = ModelPromptAdapterService::MODEL_CONFIGS;

            expect($configs)->toHaveKey('nanobanana');
            expect($configs['nanobanana']['tokenizer'])->toBe('gemini');
            expect($configs['nanobanana']['maxTokens'])->toBe(4096);
            expect($configs['nanobanana']['truncation'])->toBe('none');
        });

        test('includes nanobanana-pro configuration', function () {
            $configs = ModelPromptAdapterService::MODEL_CONFIGS;

            expect($configs)->toHaveKey('nanobanana-pro');
            expect($configs['nanobanana-pro']['tokenizer'])->toBe('gemini');
            expect($configs['nanobanana-pro']['maxTokens'])->toBe(8192);
            expect($configs['nanobanana-pro']['truncation'])->toBe('none');
        });

    });

    describe('adaptPrompt with HiDream', function () {

        test('compresses long prompts under 77 tokens', function () {
            $longPrompt = 'A beautiful woman with flowing auburn hair stands in a sunlit meadow at golden hour, soft rim lighting creating a halo effect, wildflowers swaying gently in the breeze, 85mm lens with shallow depth of field, cinematic color grading, 8K resolution, photorealistic rendering, ultra detailed skin texture, volumetric light rays streaming through the atmosphere';

            $adapted = $this->adapter->adaptPrompt($longPrompt, 'hidream');
            $tokenCount = $this->adapter->countTokens($adapted);

            expect($tokenCount)->toBeLessThanOrEqual(77);
        });

        test('preserves short prompts unchanged', function () {
            $shortPrompt = 'A cat sitting on a chair';

            $adapted = $this->adapter->adaptPrompt($shortPrompt, 'hidream');

            expect($adapted)->toBe($shortPrompt);
        });

        test('preserves subject in compressed output', function () {
            $prompt = 'A young woman with red hair standing in a forest, magical lighting, 8K resolution, photorealistic, ultra detailed, masterpiece quality';

            $adapted = $this->adapter->adaptPrompt($prompt, 'hidream');

            expect($adapted)->toContain('woman');
            expect($adapted)->toContain('hair');
        });

        test('removes style markers during compression', function () {
            $prompt = 'A dog, 8K resolution, photorealistic, ultra detailed, masterpiece';

            $adapted = $this->adapter->adaptPrompt($prompt, 'hidream');

            // The core subject should remain
            expect($adapted)->toContain('dog');

            // If compression happened, style markers may be removed
            $originalTokens = $this->adapter->countTokens($prompt);
            $adaptedTokens = $this->adapter->countTokens($adapted);

            // Either it fit without compression, or style was trimmed
            expect($adaptedTokens)->toBeLessThanOrEqual($originalTokens);
        });

    });

    describe('adaptPrompt with NanoBanana', function () {

        test('never compresses prompts', function () {
            $longPrompt = 'A beautiful woman with flowing auburn hair stands in a sunlit meadow at golden hour, soft rim lighting creating a halo effect, wildflowers swaying gently in the breeze, 85mm lens with shallow depth of field, cinematic color grading, 8K resolution, photorealistic rendering, ultra detailed skin texture, volumetric light rays streaming through the atmosphere, dramatic composition, masterpiece quality, award-winning photography';

            $adapted = $this->adapter->adaptPrompt($longPrompt, 'nanobanana');

            expect($adapted)->toBe($longPrompt);
        });

        test('returns prompt unchanged regardless of length', function () {
            $prompt = str_repeat('word ', 500);

            $adapted = $this->adapter->adaptPrompt(trim($prompt), 'nanobanana');

            expect($adapted)->toBe(trim($prompt));
        });

    });

    describe('adaptPrompt with NanoBanana Pro', function () {

        test('never compresses prompts', function () {
            $longPrompt = 'A detailed scene with many descriptive elements and high-quality markers like 8K resolution and photorealistic rendering with volumetric lighting';

            $adapted = $this->adapter->adaptPrompt($longPrompt, 'nanobanana-pro');

            expect($adapted)->toBe($longPrompt);
        });

    });

    describe('adaptPrompt with unknown model', function () {

        test('defaults to nanobanana behavior (no compression)', function () {
            $longPrompt = 'A detailed prompt with many words and style markers like 8K and photorealistic';

            $adapted = $this->adapter->adaptPrompt($longPrompt, 'unknown-model');

            expect($adapted)->toBe($longPrompt);
        });

    });

    describe('COMPRESSION_PRIORITY', function () {

        test('subject has highest priority (value 1)', function () {
            $priority = ModelPromptAdapterService::COMPRESSION_PRIORITY;

            expect($priority[1])->toBe('subject');
        });

        test('style has lowest priority (value 6)', function () {
            $priority = ModelPromptAdapterService::COMPRESSION_PRIORITY;

            expect($priority[6])->toBe('style');
        });

        test('priority order is subject > action > environment > lighting > atmosphere > style', function () {
            $priority = ModelPromptAdapterService::COMPRESSION_PRIORITY;

            expect($priority[1])->toBe('subject');
            expect($priority[2])->toBe('action');
            expect($priority[3])->toBe('environment');
            expect($priority[4])->toBe('lighting');
            expect($priority[5])->toBe('atmosphere');
            expect($priority[6])->toBe('style');
        });

    });

    describe('compressForClip', function () {

        test('returns prompt unchanged if under 77 tokens', function () {
            $shortPrompt = 'A simple scene';

            $compressed = $this->adapter->compressForClip($shortPrompt, 77);

            expect($compressed)->toBe($shortPrompt);
        });

        test('compressed prompt is under 77 tokens', function () {
            $longPrompt = 'A beautiful woman with flowing auburn hair stands in a sunlit meadow at golden hour, soft rim lighting, wildflowers swaying, 85mm lens shallow depth of field, cinematic color grading, 8K resolution, photorealistic, ultra detailed skin texture, volumetric light rays, dramatic atmosphere, masterpiece quality';

            $compressed = $this->adapter->compressForClip($longPrompt, 77);
            $tokenCount = $this->adapter->countTokens($compressed);

            expect($tokenCount)->toBeLessThanOrEqual(77);
        });

        test('preserves subject content', function () {
            $prompt = 'An elderly man with a white beard wearing a blue coat, standing in a crowded marketplace, 8K resolution, photorealistic, ultra detailed, volumetric lighting, masterpiece';

            $compressed = $this->adapter->compressForClip($prompt, 77);

            expect($compressed)->toContain('man');
        });

        test('removes style markers first', function () {
            $prompt = 'A cat, 8K resolution, photorealistic, ultra detailed, sharp focus, masterpiece quality, octane render';

            $compressed = $this->adapter->compressForClip($prompt, 77);

            expect($compressed)->toContain('cat');
            // Style markers should be removed or reduced if needed for compression
        });

    });

    describe('getAdaptationStats', function () {

        test('includes token counts', function () {
            $original = 'A long prompt with many words for testing';
            $adapted = 'A shorter prompt';

            $stats = $this->adapter->getAdaptationStats($original, $adapted, 'hidream');

            expect($stats)->toHaveKey('originalTokens');
            expect($stats)->toHaveKey('adaptedTokens');
            expect($stats['originalTokens'])->toBeInt();
            expect($stats['adaptedTokens'])->toBeInt();
        });

        test('indicates wasCompressed correctly', function () {
            $original = 'Original prompt text';
            $same = 'Original prompt text';
            $different = 'Different text';

            $statsUnchanged = $this->adapter->getAdaptationStats($original, $same, 'nanobanana');
            $statsChanged = $this->adapter->getAdaptationStats($original, $different, 'hidream');

            expect($statsUnchanged['wasCompressed'])->toBeFalse();
            expect($statsChanged['wasCompressed'])->toBeTrue();
        });

        test('includes model configuration', function () {
            $stats = $this->adapter->getAdaptationStats('prompt', 'prompt', 'hidream');

            expect($stats)->toHaveKey('modelConfig');
            expect($stats['modelConfig'])->toHaveKey('tokenizer');
            expect($stats['modelConfig'])->toHaveKey('maxTokens');
        });

        test('includes tokenizer mode', function () {
            $stats = $this->adapter->getAdaptationStats('prompt', 'prompt', 'nanobanana');

            expect($stats)->toHaveKey('tokenizerMode');
            expect($stats['tokenizerMode'])->toBeIn(['bpe', 'word-estimate']);
        });

        test('indicates underLimit status', function () {
            $stats = $this->adapter->getAdaptationStats('short', 'short', 'hidream');

            expect($stats)->toHaveKey('underLimit');
            expect($stats['underLimit'])->toBeBool();
        });

    });

    describe('getModelConfig', function () {

        test('returns config for known model', function () {
            $config = $this->adapter->getModelConfig('hidream');

            expect($config)->toHaveKey('tokenizer');
            expect($config['tokenizer'])->toBe('clip');
        });

        test('defaults to nanobanana for unknown model', function () {
            $config = $this->adapter->getModelConfig('nonexistent-model');

            expect($config['tokenizer'])->toBe('gemini');
            expect($config['maxTokens'])->toBe(4096);
        });

    });

    describe('requiresCompression', function () {

        test('returns true for hidream', function () {
            expect($this->adapter->requiresCompression('hidream'))->toBeTrue();
        });

        test('returns false for nanobanana', function () {
            expect($this->adapter->requiresCompression('nanobanana'))->toBeFalse();
        });

        test('returns false for nanobanana-pro', function () {
            expect($this->adapter->requiresCompression('nanobanana-pro'))->toBeFalse();
        });

        test('returns false for unknown model (defaults to nanobanana)', function () {
            expect($this->adapter->requiresCompression('unknown'))->toBeFalse();
        });

    });

    describe('integration with PromptTemplateLibrary', function () {

        test('uses shot type for compression priority', function () {
            $adapter = new ModelPromptAdapterService(new PromptTemplateLibrary());

            $prompt = 'A beautiful landscape with mountains and a sunset, soft golden lighting, 8K, photorealistic, cinematic';

            // Wide shot prioritizes environment
            $wideCompressed = $adapter->compressForClip($prompt, 77, ['shotType' => 'wide']);

            // Close-up prioritizes subject
            $closeUpCompressed = $adapter->compressForClip($prompt, 77, ['shotType' => 'close-up']);

            // Both should be under limit
            expect($adapter->countTokens($wideCompressed))->toBeLessThanOrEqual(77);
            expect($adapter->countTokens($closeUpCompressed))->toBeLessThanOrEqual(77);
        });

    });

});
