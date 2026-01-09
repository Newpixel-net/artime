{{--
    Scenes Tab Content
    Shows scene thumbnails and allows reordering
--}}

<div class="vw-scenes-tab">
    <div class="vw-section-header">
        <span class="vw-section-icon">📹</span>
        <span class="vw-section-title">{{ __('Scenes') }}</span>
        <span class="vw-section-badge">{{ count($script['scenes'] ?? []) }}</span>
    </div>

    <div class="vw-scenes-list">
        @forelse($script['scenes'] ?? [] as $index => $scene)
            @php
                $sceneId = $scene['id'] ?? "scene-{$index}";
                $storyboard = collect($storyboard['scenes'] ?? [])->firstWhere('sceneId', $sceneId);
                $animation = collect($animation['scenes'] ?? [])->firstWhere('sceneId', $sceneId);
                $hasImage = !empty($storyboard['imageUrl']);
                $hasVideo = !empty($animation['videoUrl']);
                $hasVoice = !empty($animation['voiceoverUrl']);
            @endphp
            <div
                class="vw-scene-item"
                :class="{ 'active': currentSceneIndex === {{ $index }} }"
                @click="jumpToScene({{ $index }})"
            >
                <div class="vw-scene-thumb">
                    @if($hasImage)
                        <img src="{{ $storyboard['imageUrl'] }}" alt="Scene {{ $index + 1 }}">
                    @else
                        <div class="vw-scene-placeholder">{{ $index + 1 }}</div>
                    @endif
                    <div class="vw-scene-number">{{ $index + 1 }}</div>
                </div>
                <div class="vw-scene-info">
                    <div class="vw-scene-duration">{{ $scene['duration'] ?? 8 }}s</div>
                    <div class="vw-scene-status">
                        <span class="status-dot {{ $hasImage ? 'green' : 'yellow' }}" title="{{ $hasImage ? 'Has image' : 'No image' }}"></span>
                        <span class="status-dot {{ $hasVideo ? 'green' : 'gray' }}" title="{{ $hasVideo ? 'Has video' : 'No video' }}"></span>
                        <span class="status-dot {{ $hasVoice ? 'green' : 'gray' }}" title="{{ $hasVoice ? 'Has voiceover' : 'No voiceover' }}"></span>
                    </div>
                </div>
            </div>
        @empty
            <div class="vw-empty-state">
                <span class="vw-empty-icon">📭</span>
                <span class="vw-empty-text">{{ __('No scenes yet') }}</span>
            </div>
        @endforelse
    </div>

    {{-- Scene Legend --}}
    <div class="vw-scene-legend">
        <div class="vw-legend-item">
            <span class="status-dot green"></span>
            <span>{{ __('Ready') }}</span>
        </div>
        <div class="vw-legend-item">
            <span class="status-dot yellow"></span>
            <span>{{ __('Partial') }}</span>
        </div>
        <div class="vw-legend-item">
            <span class="status-dot gray"></span>
            <span>{{ __('Missing') }}</span>
        </div>
    </div>
</div>

<style>
    .vw-scenes-tab {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .vw-section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-section-icon {
        font-size: 1rem;
    }

    .vw-section-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-section-badge {
        font-size: 0.7rem;
        padding: 0.15rem 0.5rem;
        background: rgba(139, 92, 246, 0.2);
        color: #a78bfa;
        border-radius: 1rem;
    }

    .vw-scenes-list {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vw-scene-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .vw-scene-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-scene-item.active {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.3);
    }

    .vw-scene-thumb {
        position: relative;
        width: 80px;
        min-width: 80px;
        aspect-ratio: 16/9;
        background: #111;
        border-radius: 0.35rem;
        overflow: hidden;
    }

    .vw-scene-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-scene-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.2);
    }

    .vw-scene-number {
        position: absolute;
        bottom: 2px;
        left: 2px;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 0.1rem 0.3rem;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        border-radius: 0.2rem;
    }

    .vw-scene-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.35rem;
    }

    .vw-scene-duration {
        font-size: 0.8rem;
        font-weight: 600;
        color: white;
    }

    .vw-scene-status {
        display: flex;
        gap: 0.35rem;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-dot.green {
        background: #10b981;
    }

    .status-dot.yellow {
        background: #f59e0b;
    }

    .status-dot.gray {
        background: rgba(255, 255, 255, 0.2);
    }

    .vw-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-empty-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .vw-empty-text {
        font-size: 0.85rem;
    }

    .vw-scene-legend {
        display: flex;
        justify-content: center;
        gap: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        margin-top: 0.75rem;
    }

    .vw-legend-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }
</style>
