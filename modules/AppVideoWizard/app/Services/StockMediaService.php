<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardAsset;

class StockMediaService
{
    /**
     * Cache duration for search results (15 minutes).
     */
    protected int $cacheDuration = 900;

    /**
     * Search stock media from Pexels.
     */
    public function search(
        string $query,
        string $type = 'image',
        array $filters = []
    ): array {
        $pexelsKey = setting('pexels_api_key') ?? config('services.pexels.key');

        if (!$pexelsKey) {
            throw new \Exception('Pexels API key not configured. Please add your Pexels API key in settings.');
        }

        $orientation = $filters['orientation'] ?? 'landscape';
        $page = $filters['page'] ?? 1;
        $perPage = min($filters['perPage'] ?? 20, 40);

        // Create cache key
        $cacheKey = "stock_media:{$type}:" . md5("{$query}:{$orientation}:{$page}:{$perPage}");

        // Check cache
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $results = [
            'items' => [],
            'total' => 0,
            'page' => $page,
            'perPage' => $perPage,
        ];

        try {
            if ($type === 'video') {
                $results = $this->searchPexelsVideos($pexelsKey, $query, $orientation, $page, $perPage);
            } else {
                $results = $this->searchPexelsImages($pexelsKey, $query, $orientation, $page, $perPage);
            }

            // Cache results
            Cache::put($cacheKey, $results, $this->cacheDuration);

        } catch (\Exception $e) {
            Log::error('Stock media search failed: ' . $e->getMessage());
            throw new \Exception('Failed to search stock media: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Search Pexels for images.
     */
    protected function searchPexelsImages(
        string $apiKey,
        string $query,
        string $orientation,
        int $page,
        int $perPage
    ): array {
        $response = Http::timeout(10)
            ->withHeaders([
                'Authorization' => $apiKey,
            ])
            ->get('https://api.pexels.com/v1/search', [
                'query' => $query,
                'orientation' => $orientation,
                'page' => $page,
                'per_page' => $perPage,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Pexels API error: ' . $response->status());
        }

        $data = $response->json();

        $items = collect($data['photos'] ?? [])->map(function ($photo) {
            return [
                'id' => "pexels-{$photo['id']}",
                'source' => 'pexels',
                'type' => 'image',
                'thumbnail' => $photo['src']['small'] ?? $photo['src']['medium'],
                'preview' => $photo['src']['medium'] ?? $photo['src']['large'],
                'url' => $photo['src']['large2x'] ?? $photo['src']['original'],
                'width' => $photo['width'],
                'height' => $photo['height'],
                'author' => $photo['photographer'],
                'authorUrl' => $photo['photographer_url'],
                'license' => 'Pexels License (Free)',
                'originalUrl' => $photo['url'],
                'avgColor' => $photo['avg_color'] ?? null,
            ];
        })->toArray();

        return [
            'items' => $items,
            'total' => $data['total_results'] ?? count($items),
            'page' => $page,
            'perPage' => $perPage,
            'hasMore' => ($page * $perPage) < ($data['total_results'] ?? 0),
        ];
    }

    /**
     * Search Pexels for videos.
     */
    protected function searchPexelsVideos(
        string $apiKey,
        string $query,
        string $orientation,
        int $page,
        int $perPage
    ): array {
        $response = Http::timeout(10)
            ->withHeaders([
                'Authorization' => $apiKey,
            ])
            ->get('https://api.pexels.com/videos/search', [
                'query' => $query,
                'orientation' => $orientation,
                'page' => $page,
                'per_page' => $perPage,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Pexels API error: ' . $response->status());
        }

        $data = $response->json();

        $items = collect($data['videos'] ?? [])->map(function ($video) {
            $hdVideo = collect($video['video_files'] ?? [])->firstWhere('quality', 'hd');
            $sdVideo = collect($video['video_files'] ?? [])->firstWhere('quality', 'sd');
            $videoUrl = $hdVideo['link'] ?? $sdVideo['link'] ?? ($video['video_files'][0]['link'] ?? null);

            return [
                'id' => "pexels-{$video['id']}",
                'source' => 'pexels',
                'type' => 'video',
                'thumbnail' => $video['image'],
                'preview' => $sdVideo['link'] ?? ($video['video_files'][0]['link'] ?? null),
                'url' => $videoUrl,
                'videoUrl' => $videoUrl,
                'width' => $video['width'],
                'height' => $video['height'],
                'duration' => $video['duration'],
                'author' => $video['user']['name'] ?? 'Pexels',
                'authorUrl' => $video['user']['url'] ?? null,
                'license' => 'Pexels License (Free)',
                'originalUrl' => $video['url'],
            ];
        })->toArray();

        return [
            'items' => $items,
            'total' => $data['total_results'] ?? count($items),
            'page' => $page,
            'perPage' => $perPage,
            'hasMore' => ($page * $perPage) < ($data['total_results'] ?? 0),
        ];
    }

    /**
     * Get curated photos from Pexels.
     */
    public function getCurated(int $page = 1, int $perPage = 20): array
    {
        $pexelsKey = setting('pexels_api_key') ?? config('services.pexels.key');

        if (!$pexelsKey) {
            throw new \Exception('Pexels API key not configured');
        }

        $cacheKey = "stock_media:curated:{$page}:{$perPage}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $response = Http::timeout(10)
            ->withHeaders([
                'Authorization' => $pexelsKey,
            ])
            ->get('https://api.pexels.com/v1/curated', [
                'page' => $page,
                'per_page' => min($perPage, 40),
            ]);

        if (!$response->successful()) {
            throw new \Exception('Pexels API error: ' . $response->status());
        }

        $data = $response->json();

        $items = collect($data['photos'] ?? [])->map(function ($photo) {
            return [
                'id' => "pexels-{$photo['id']}",
                'source' => 'pexels',
                'type' => 'image',
                'thumbnail' => $photo['src']['small'] ?? $photo['src']['medium'],
                'preview' => $photo['src']['medium'] ?? $photo['src']['large'],
                'url' => $photo['src']['large2x'] ?? $photo['src']['original'],
                'width' => $photo['width'],
                'height' => $photo['height'],
                'author' => $photo['photographer'],
                'authorUrl' => $photo['photographer_url'],
                'license' => 'Pexels License (Free)',
                'originalUrl' => $photo['url'],
            ];
        })->toArray();

        $result = [
            'items' => $items,
            'total' => $data['total_results'] ?? count($items),
            'page' => $page,
            'perPage' => $perPage,
        ];

        Cache::put($cacheKey, $result, $this->cacheDuration);

        return $result;
    }

    /**
     * Download and assign stock media to a scene.
     */
    public function assignToScene(
        WizardProject $project,
        array $scene,
        array $mediaItem,
        array $options = []
    ): array {
        $mediaUrl = $mediaItem['url'];
        $mediaType = $mediaItem['type'] ?? 'image';

        try {
            // Download media
            $contents = file_get_contents($mediaUrl);

            if (!$contents) {
                throw new \Exception('Failed to download media');
            }

            // Determine file extension
            $extension = $mediaType === 'video' ? 'mp4' : 'jpg';
            $filename = Str::slug($scene['id']) . '-stock-' . time() . '.' . $extension;
            $path = "wizard-projects/{$project->id}/images/{$filename}";

            // Store file
            Storage::disk('public')->put($path, $contents);

            // Create asset record
            $asset = WizardAsset::create([
                'project_id' => $project->id,
                'user_id' => $project->user_id,
                'type' => $mediaType === 'video' ? WizardAsset::TYPE_VIDEO : WizardAsset::TYPE_IMAGE,
                'name' => $scene['title'] ?? $scene['id'],
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'mime_type' => $mediaType === 'video' ? 'video/mp4' : 'image/jpeg',
                'scene_index' => $options['sceneIndex'] ?? null,
                'scene_id' => $scene['id'],
                'metadata' => [
                    'source' => 'stock',
                    'provider' => $mediaItem['source'] ?? 'pexels',
                    'originalId' => $mediaItem['id'],
                    'originalUrl' => $mediaItem['originalUrl'] ?? $mediaUrl,
                    'author' => $mediaItem['author'] ?? null,
                    'authorUrl' => $mediaItem['authorUrl'] ?? null,
                    'license' => $mediaItem['license'] ?? 'Pexels License',
                    'width' => $mediaItem['width'] ?? null,
                    'height' => $mediaItem['height'] ?? null,
                ],
            ]);

            return [
                'success' => true,
                'imageUrl' => $asset->url,
                'assetId' => $asset->id,
                'source' => 'stock',
                'status' => 'ready',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to assign stock media: ' . $e->getMessage());
            throw new \Exception('Failed to assign stock media: ' . $e->getMessage());
        }
    }

    /**
     * Get popular search terms for suggestions.
     */
    public function getPopularSearchTerms(): array
    {
        return [
            'Nature & Landscape' => ['nature', 'mountain', 'ocean', 'forest', 'sunset', 'sky'],
            'Business & Technology' => ['business', 'technology', 'office', 'computer', 'meeting', 'startup'],
            'People & Lifestyle' => ['people', 'family', 'friends', 'fitness', 'yoga', 'travel'],
            'Food & Drink' => ['food', 'coffee', 'restaurant', 'cooking', 'healthy', 'breakfast'],
            'Urban & Architecture' => ['city', 'architecture', 'street', 'building', 'night', 'urban'],
            'Abstract & Backgrounds' => ['abstract', 'texture', 'pattern', 'background', 'minimal', 'gradient'],
        ];
    }

    /**
     * Get orientation options for filtering.
     */
    public function getOrientationOptions(): array
    {
        return [
            'landscape' => 'Landscape (16:9)',
            'portrait' => 'Portrait (9:16)',
            'square' => 'Square (1:1)',
        ];
    }
}
