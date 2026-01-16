<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardAsset;

class StockMediaService
{
    protected string $pexelsApiKey;

    public function __construct()
    {
        $this->pexelsApiKey = (string) get_option('pexels_api_key', '');
    }

    /**
     * Check if Pexels API is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->pexelsApiKey);
    }

    /**
     * Search stock media from Pexels.
     *
     * @param string $query Search query
     * @param string $type 'image' | 'video'
     * @param array $filters ['orientation' => 'landscape'|'portrait'|'square', 'page' => 1, 'perPage' => 20]
     * @return array
     */
    public function searchPexels(string $query, string $type = 'image', array $filters = []): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Pexels API key not configured');
        }

        $orientation = $filters['orientation'] ?? 'landscape';
        $page = $filters['page'] ?? 1;
        $perPage = min($filters['perPage'] ?? 20, 40);

        try {
            $endpoint = $type === 'video'
                ? 'https://api.pexels.com/videos/search'
                : 'https://api.pexels.com/v1/search';

            $response = Http::withHeaders([
                'Authorization' => $this->pexelsApiKey,
            ])->timeout(15)->get($endpoint, [
                'query' => $query,
                'orientation' => $orientation,
                'page' => $page,
                'per_page' => $perPage,
            ]);

            if ($response->failed()) {
                return $this->errorResponse('Pexels API request failed: ' . $response->status());
            }

            $data = $response->json();

            if ($type === 'video') {
                $results = collect($data['videos'] ?? [])->map(function ($video) {
                    $hdVideo = collect($video['video_files'] ?? [])->firstWhere('quality', 'hd');
                    $sdVideo = collect($video['video_files'] ?? [])->firstWhere('quality', 'sd');
                    $videoFile = $hdVideo ?? $sdVideo ?? ($video['video_files'][0] ?? null);

                    return [
                        'id' => 'pexels-video-' . $video['id'],
                        'source' => 'pexels',
                        'type' => 'video',
                        'thumbnail' => $video['image'] ?? null,
                        'preview' => $sdVideo['link'] ?? $videoFile['link'] ?? null,
                        'url' => $hdVideo['link'] ?? $videoFile['link'] ?? null,
                        'width' => $video['width'] ?? null,
                        'height' => $video['height'] ?? null,
                        'duration' => $video['duration'] ?? null,
                        'author' => $video['user']['name'] ?? 'Pexels',
                        'authorUrl' => $video['user']['url'] ?? null,
                        'license' => 'Pexels License (Free)',
                        'originalUrl' => $video['url'] ?? null,
                    ];
                })->toArray();
            } else {
                $results = collect($data['photos'] ?? [])->map(function ($photo) {
                    return [
                        'id' => 'pexels-image-' . $photo['id'],
                        'source' => 'pexels',
                        'type' => 'image',
                        'thumbnail' => $photo['src']['small'] ?? $photo['src']['medium'] ?? null,
                        'preview' => $photo['src']['medium'] ?? $photo['src']['large'] ?? null,
                        'url' => $photo['src']['original'] ?? $photo['src']['large2x'] ?? null,
                        'width' => $photo['width'] ?? null,
                        'height' => $photo['height'] ?? null,
                        'author' => $photo['photographer'] ?? 'Pexels',
                        'authorUrl' => $photo['photographer_url'] ?? null,
                        'license' => 'Pexels License (Free)',
                        'originalUrl' => $photo['url'] ?? null,
                        'avgColor' => $photo['avg_color'] ?? null,
                    ];
                })->toArray();
            }

            return [
                'success' => true,
                'results' => $results,
                'total' => $data['total_results'] ?? count($results),
                'page' => $page,
                'perPage' => $perPage,
            ];

        } catch (\Exception $e) {
            Log::error('Pexels API error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Generate smart search queries for a scene.
     */
    public function generateSearchQueries(string $sceneDescription, string $narration = ''): array
    {
        // Simple keyword extraction for stock media search
        $combined = $sceneDescription . ' ' . $narration;

        // Remove common words and extract key concepts
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'can', 'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from',
            'as', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'between',
            'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why',
            'how', 'all', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor',
            'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'just', 'and'];

        // Extract words
        $words = preg_split('/\s+/', strtolower($combined));
        $words = array_filter($words, function ($word) use ($stopWords) {
            $word = preg_replace('/[^a-z0-9]/', '', $word);
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });

        // Get top unique words
        $wordCounts = array_count_values($words);
        arsort($wordCounts);
        $topWords = array_slice(array_keys($wordCounts), 0, 5);

        // Build search queries
        $queries = [];

        // Primary query - first 3 keywords
        if (count($topWords) >= 3) {
            $queries[] = implode(' ', array_slice($topWords, 0, 3));
        }

        // Secondary queries - individual keywords
        foreach (array_slice($topWords, 0, 3) as $word) {
            if (strlen($word) > 3) {
                $queries[] = $word;
            }
        }

        return array_unique($queries);
    }

    /**
     * Import stock media to project storage.
     */
    public function importMedia(
        WizardProject $project,
        string $mediaUrl,
        string $mediaId,
        string $type,
        string $sceneId,
        array $metadata = []
    ): array {
        try {
            // Download the media
            $response = Http::timeout(120)->get($mediaUrl);

            if ($response->failed()) {
                throw new \Exception('Failed to download media');
            }

            $contents = $response->body();

            // Determine file extension
            $extension = $type === 'video' ? 'mp4' : 'jpg';
            $contentType = $response->header('Content-Type');
            if ($contentType) {
                if (str_contains($contentType, 'png')) {
                    $extension = 'png';
                } elseif (str_contains($contentType, 'webp')) {
                    $extension = 'webp';
                } elseif (str_contains($contentType, 'webm')) {
                    $extension = 'webm';
                }
            }

            // Generate filename and path
            $filename = Str::slug($sceneId) . '-stock-' . time() . '.' . $extension;
            $folder = $type === 'video' ? 'videos' : 'images';
            $path = "wizard-projects/{$project->id}/{$folder}/{$filename}";

            // Store file
            Storage::disk('public')->put($path, $contents);

            // Create asset record
            $asset = WizardAsset::create([
                'project_id' => $project->id,
                'user_id' => $project->user_id,
                'type' => $type === 'video' ? WizardAsset::TYPE_VIDEO : WizardAsset::TYPE_IMAGE,
                'name' => $sceneId . ' - Stock Media',
                'path' => $path,
                'url' => url('/files/' . $path),
                'mime_type' => $type === 'video' ? 'video/mp4' : 'image/jpeg',
                'scene_id' => $sceneId,
                'metadata' => array_merge($metadata, [
                    'source' => 'stock',
                    'stockMediaId' => $mediaId,
                    'originalUrl' => $mediaUrl,
                    'importedAt' => now()->toISOString(),
                ]),
            ]);

            return [
                'success' => true,
                'assetId' => $asset->id,
                'url' => $asset->url,
                'path' => $path,
            ];

        } catch (\Exception $e) {
            Log::error('Stock media import error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get curated collections for common scene types.
     */
    public function getCuratedQueries(string $sceneType = ''): array
    {
        $collections = [
            'city' => ['city skyline', 'urban street', 'downtown', 'cityscape aerial'],
            'nature' => ['forest landscape', 'mountains sunset', 'ocean waves', 'green nature'],
            'business' => ['office meeting', 'business team', 'corporate', 'professional workspace'],
            'technology' => ['technology modern', 'computer coding', 'digital network', 'futuristic tech'],
            'people' => ['diverse people', 'happy lifestyle', 'portrait professional', 'team collaboration'],
            'abstract' => ['abstract background', 'motion graphics', 'particles animation', 'geometric shapes'],
            'food' => ['food photography', 'restaurant dining', 'cooking kitchen', 'healthy food'],
            'travel' => ['travel adventure', 'vacation destination', 'airplane journey', 'road trip'],
            'fitness' => ['fitness workout', 'gym training', 'yoga meditation', 'running exercise'],
            'education' => ['education learning', 'student classroom', 'library books', 'teaching'],
        ];

        if ($sceneType && isset($collections[$sceneType])) {
            return $collections[$sceneType];
        }

        return $collections;
    }

    /**
     * Map aspect ratio to Pexels orientation.
     */
    public function getOrientation(string $aspectRatio): string
    {
        return match ($aspectRatio) {
            '9:16', '4:5' => 'portrait',
            '1:1' => 'square',
            default => 'landscape',
        };
    }

    /**
     * Error response format.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'results' => [],
        ];
    }
}
