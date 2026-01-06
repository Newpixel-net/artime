<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardProcessingJob;

class ScriptGenerationService
{
    /**
     * Generate a video script based on project configuration.
     */
    public function generateScript(WizardProject $project, array $options = []): array
    {
        $concept = $project->concept ?? [];
        $contentConfig = $project->content_config ?? [];
        $productionType = $project->getProductionTypeConfig();
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        $topic = $concept['refinedConcept'] ?? $concept['rawInput'] ?? $contentConfig['topic'] ?? '';
        $tone = $contentConfig['tone'] ?? 'engaging';
        $duration = $project->target_duration;
        $style = $contentConfig['style'] ?? 'engaging';

        // Calculate target word count based on duration
        // Average speaking rate is ~150 words per minute
        $targetWords = (int) ($duration / 60 * 150);

        $prompt = $this->buildScriptPrompt([
            'topic' => $topic,
            'tone' => $tone,
            'duration' => $duration,
            'targetWords' => $targetWords,
            'style' => $style,
            'productionType' => $productionType,
            'concept' => $concept,
            'aspectRatio' => $project->aspect_ratio,
        ]);

        // Use ArTime's existing AI service
        \Log::info('VideoWizard: Generating script', [
            'teamId' => $teamId,
            'topic' => substr($topic, 0, 100),
            'duration' => $duration,
        ]);

        $result = AI::process($prompt, 'text', [
            'maxResult' => 1
        ], $teamId);

        \Log::info('VideoWizard: AI response received', [
            'hasError' => !empty($result['error']),
            'dataCount' => count($result['data'] ?? []),
            'model' => $result['model'] ?? 'unknown',
        ]);

        if (!empty($result['error'])) {
            \Log::error('VideoWizard: AI error', ['error' => $result['error']]);
            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';

        if (empty($response)) {
            \Log::error('VideoWizard: Empty AI response', [
                'result' => $result,
            ]);
            throw new \Exception('AI returned an empty response. Please try again.');
        }

        \Log::info('VideoWizard: Parsing response', [
            'responseLength' => strlen($response),
            'responsePreview' => substr($response, 0, 200),
        ]);

        // Parse the response
        $script = $this->parseScriptResponse($response);

        return $script;
    }

    /**
     * Build the script generation prompt.
     */
    protected function buildScriptPrompt(array $params): string
    {
        $topic = $params['topic'];
        $tone = $params['tone'];
        $duration = $params['duration'];
        $targetWords = $params['targetWords'];
        $concept = $params['concept'];

        $toneDescriptions = [
            'engaging' => 'conversational, energetic, keeps viewers hooked with dynamic pacing and relatable language',
            'educational' => 'informative, clear explanations, authoritative yet accessible, with structured learning points',
            'entertaining' => 'fun, humorous, uses storytelling and personality to captivate, includes jokes',
            'professional' => 'polished, business-appropriate, credible and trustworthy, with data-backed insights',
        ];

        $toneGuide = $toneDescriptions[$tone] ?? $toneDescriptions['engaging'];

        // Calculate scene count based on duration
        $sceneCount = max(3, min(10, (int) ($duration / 20)));

        $prompt = <<<PROMPT
You are an expert video script writer. Create an engaging video script.

REQUIREMENTS:
- Topic: {$topic}
- Tone: {$tone} - {$toneGuide}
- Target Duration: {$duration} seconds (~{$targetWords} words)
- Number of Scenes: {$sceneCount}

SCRIPT STRUCTURE:
1. Hook (first 3-5 seconds) - Grab attention immediately
2. Introduction - Brief overview of what viewers will learn
3. Main Content - {$sceneCount} scenes with clear value
4. Call to Action - Subscribe, like, comment
5. Outro - Wrap up

FORMAT YOUR RESPONSE AS JSON:
{
  "title": "Video title (SEO optimized, max 60 chars)",
  "hook": "Opening hook text (attention grabber)",
  "scenes": [
    {
      "id": "scene-1",
      "title": "Scene title",
      "narration": "What the narrator says",
      "visualDescription": "What should be shown visually",
      "duration": 15,
      "kenBurns": {
        "startScale": 1.0,
        "endScale": 1.1,
        "startX": 0.5,
        "startY": 0.5,
        "endX": 0.5,
        "endY": 0.4
      }
    }
  ],
  "cta": "Call to action text",
  "totalDuration": {$duration},
  "wordCount": {$targetWords}
}

IMPORTANT:
- Each scene narration should be 10-30 words
- Visual descriptions should be detailed enough for image generation
- Include smooth Ken Burns camera movements for each scene
- Ensure total duration adds up to approximately {$duration} seconds
PROMPT;

        return $prompt;
    }

    /**
     * Parse the AI response into a structured script.
     */
    protected function parseScriptResponse(string $response): array
    {
        $originalResponse = $response;

        // Clean up response - extract JSON
        $response = trim($response);

        // Remove markdown code blocks
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        // Remove any text before the first {
        if (($pos = strpos($response, '{')) !== false) {
            $response = substr($response, $pos);
        }

        // Remove any text after the last }
        if (($pos = strrpos($response, '}')) !== false) {
            $response = substr($response, 0, $pos + 1);
        }

        // Fix common JSON issues
        $response = preg_replace('/,\s*}/', '}', $response); // trailing commas in objects
        $response = preg_replace('/,\s*]/', ']', $response); // trailing commas in arrays

        // Fix unescaped newlines in strings
        $response = preg_replace('/([^\\\\])"([^"]*)\n([^"]*)"/', '$1"$2\\n$3"', $response);

        // Try to parse JSON
        $script = json_decode($response, true);
        $jsonError = json_last_error();

        if ($jsonError !== JSON_ERROR_NONE) {
            \Log::warning('VideoWizard: Initial JSON parse failed', [
                'error' => json_last_error_msg(),
                'responsePreview' => substr($response, 0, 300),
            ]);

            // Try to extract JSON from the response using different patterns
            $patterns = [
                '/\{[^{}]*"scenes"\s*:\s*\[[\s\S]*?\][\s\S]*?\}/s',
                '/\{[\s\S]*"scenes"[\s\S]*\}/s',
            ];

            foreach ($patterns as $pattern) {
                preg_match($pattern, $response, $matches);
                if (!empty($matches[0])) {
                    $extracted = $matches[0];
                    // Fix common issues in extracted JSON
                    $extracted = preg_replace('/,\s*}/', '}', $extracted);
                    $extracted = preg_replace('/,\s*]/', ']', $extracted);
                    $script = json_decode($extracted, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($script['scenes'])) {
                        \Log::info('VideoWizard: JSON extracted with pattern');
                        break;
                    }
                }
            }
        }

        // If still no valid script, try to create a fallback from the raw text
        if (!$script || !isset($script['scenes']) || !is_array($script['scenes'])) {
            \Log::warning('VideoWizard: Script parsing failed, attempting text fallback', [
                'responseLength' => strlen($originalResponse),
                'response' => substr($originalResponse, 0, 1000),
            ]);

            // Try to create a basic script from the text content
            $script = $this->createFallbackScript($originalResponse);

            if (!$script) {
                throw new \Exception('Failed to parse script response. The AI returned an invalid format.');
            }
        }

        // Validate and fix scenes
        foreach ($script['scenes'] as $index => &$scene) {
            if (!isset($scene['id'])) {
                $scene['id'] = 'scene-' . ($index + 1);
            }
            if (!isset($scene['duration'])) {
                $scene['duration'] = 15;
            }
            if (!isset($scene['title'])) {
                $scene['title'] = 'Scene ' . ($index + 1);
            }
            if (!isset($scene['narration'])) {
                $scene['narration'] = '';
            }
            if (!isset($scene['visualDescription'])) {
                $scene['visualDescription'] = $scene['narration'];
            }
            if (!isset($scene['kenBurns'])) {
                $scene['kenBurns'] = [
                    'startScale' => 1.0,
                    'endScale' => 1.1,
                    'startX' => 0.5,
                    'startY' => 0.5,
                    'endX' => 0.5,
                    'endY' => 0.4,
                ];
            }
        }

        // Ensure required fields exist
        if (!isset($script['title'])) {
            $script['title'] = 'Untitled Script';
        }
        if (!isset($script['hook'])) {
            $script['hook'] = '';
        }
        if (!isset($script['cta'])) {
            $script['cta'] = '';
        }

        \Log::info('VideoWizard: Script parsed successfully', [
            'sceneCount' => count($script['scenes']),
            'title' => $script['title'],
        ]);

        return $script;
    }

    /**
     * Create a fallback script from raw text when JSON parsing fails.
     */
    protected function createFallbackScript(string $response): ?array
    {
        // Try to extract meaningful content from the response
        $lines = array_filter(array_map('trim', explode("\n", $response)));

        if (count($lines) < 3) {
            return null;
        }

        // Look for scene markers or numbered sections
        $scenes = [];
        $currentScene = null;
        $sceneNumber = 0;

        foreach ($lines as $line) {
            // Check for scene markers like "Scene 1:", "1.", "[Scene 1]", etc.
            if (preg_match('/^(?:scene\s*)?(\d+)[\.:)\]]/i', $line, $matches)) {
                if ($currentScene) {
                    $scenes[] = $currentScene;
                }
                $sceneNumber = (int) $matches[1];
                $currentScene = [
                    'id' => 'scene-' . $sceneNumber,
                    'title' => 'Scene ' . $sceneNumber,
                    'narration' => '',
                    'visualDescription' => '',
                    'duration' => 15,
                ];
                // Rest of line might be the title
                $rest = trim(preg_replace('/^(?:scene\s*)?(\d+)[\.:)\]]\s*/i', '', $line));
                if ($rest) {
                    $currentScene['title'] = $rest;
                }
            } elseif ($currentScene) {
                // Add to current scene narration
                if (stripos($line, 'visual') !== false || stripos($line, 'show') !== false) {
                    $currentScene['visualDescription'] .= ' ' . preg_replace('/^visual[s]?:?\s*/i', '', $line);
                } else {
                    $currentScene['narration'] .= ' ' . $line;
                }
            }
        }

        if ($currentScene) {
            $scenes[] = $currentScene;
        }

        // Clean up scenes
        foreach ($scenes as &$scene) {
            $scene['narration'] = trim($scene['narration']);
            $scene['visualDescription'] = trim($scene['visualDescription']) ?: $scene['narration'];
        }

        if (empty($scenes)) {
            // Last resort: create a single scene with all content
            $scenes = [[
                'id' => 'scene-1',
                'title' => 'Main Content',
                'narration' => implode(' ', array_slice($lines, 0, 10)),
                'visualDescription' => 'Visual representation of the content',
                'duration' => 30,
            ]];
        }

        return [
            'title' => 'Generated Script',
            'hook' => $scenes[0]['narration'] ?? '',
            'scenes' => $scenes,
            'cta' => '',
        ];
    }

    /**
     * Improve/refine an existing script.
     */
    public function improveScript(array $script, string $instruction, array $options = []): array
    {
        $teamId = $options['teamId'] ?? session('current_team_id', 0);

        $prompt = <<<PROMPT
You are an expert video script editor. Improve the following script based on the instruction.

CURRENT SCRIPT:
```json
{$this->jsonEncode($script)}
```

INSTRUCTION: {$instruction}

Return the improved script in the same JSON format.
PROMPT;

        $result = AI::process($prompt, 'text', [
            'maxResult' => 1
        ], $teamId);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $response = $result['data'][0] ?? '';

        return $this->parseScriptResponse($response);
    }

    /**
     * Encode array to JSON with proper formatting.
     */
    protected function jsonEncode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
