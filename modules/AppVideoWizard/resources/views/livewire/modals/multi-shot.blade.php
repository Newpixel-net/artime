{{-- Multi-Shot Decomposition Modal --}}
@if($showMultiShotModal)
<div class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 800px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">✂️ {{ __('Multi-Shot Decomposition') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Split scene into multiple camera shots for dynamic storytelling') }}</p>
            </div>
            <button type="button" wire:click="closeMultiShotModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem;">
            @php
                $scene = $script['scenes'][$multiShotSceneIndex] ?? null;
                $decomposed = $multiShotMode['decomposedScenes'][$multiShotSceneIndex] ?? null;
            @endphp

            @if($scene)
                {{-- Scene Preview --}}
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; gap: 1rem; align-items: start;">
                        @php
                            $storyboardScene = $storyboard['scenes'][$multiShotSceneIndex] ?? null;
                        @endphp
                        @if($storyboardScene && !empty($storyboardScene['imageUrl']))
                            <img src="{{ $storyboardScene['imageUrl'] }}"
                                 alt="Scene {{ $multiShotSceneIndex + 1 }}"
                                 style="width: 160px; height: 90px; object-fit: cover; border-radius: 0.5rem;">
                        @else
                            <div style="width: 160px; height: 90px; background: rgba(255,255,255,0.05); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: rgba(255,255,255,0.4);">🎬</span>
                            </div>
                        @endif
                        <div style="flex: 1;">
                            <div style="color: white; font-weight: 600; margin-bottom: 0.35rem;">{{ __('Scene') }} {{ $multiShotSceneIndex + 1 }}</div>
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0; line-height: 1.4;">
                                {{ Str::limit($scene['visualDescription'] ?? $scene['narration'] ?? '', 150) }}
                            </p>
                        </div>
                    </div>
                </div>

                @if(!$decomposed)
                    {{-- Shot Count Selector --}}
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem;">{{ __('Number of Shots') }}</label>
                        <div style="display: flex; gap: 0.5rem;">
                            @foreach([2, 3, 4, 5, 6] as $count)
                                <button type="button"
                                        wire:click="$set('multiShotCount', {{ $count }})"
                                        style="flex: 1; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid {{ $multiShotCount === $count ? 'rgba(139,92,246,0.6)' : 'rgba(255,255,255,0.15)' }}; background: {{ $multiShotCount === $count ? 'rgba(139,92,246,0.2)' : 'rgba(255,255,255,0.05)' }}; color: white; cursor: pointer; font-size: 1rem; font-weight: 600;">
                                    {{ $count }}
                                </button>
                            @endforeach
                        </div>
                        <p style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin-top: 0.5rem;">
                            💡 {{ __('More shots = more dynamic scene, but requires more generation') }}
                        </p>
                    </div>

                    {{-- Shot Types Preview --}}
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem;">{{ __('Shot Sequence Preview') }}</label>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            @php
                                $shotTypes = [
                                    ['type' => 'establishing', 'icon' => '🏔️', 'label' => 'Establishing'],
                                    ['type' => 'medium', 'icon' => '👤', 'label' => 'Medium'],
                                    ['type' => 'close-up', 'icon' => '🔍', 'label' => 'Close-up'],
                                    ['type' => 'reaction', 'icon' => '😮', 'label' => 'Reaction'],
                                    ['type' => 'detail', 'icon' => '✨', 'label' => 'Detail'],
                                    ['type' => 'wide', 'icon' => '🌄', 'label' => 'Wide'],
                                ];
                            @endphp
                            @for($i = 0; $i < $multiShotCount; $i++)
                                @php $shot = $shotTypes[$i % count($shotTypes)]; @endphp
                                <div style="background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.5rem; padding: 0.5rem 0.75rem; text-align: center;">
                                    <div style="font-size: 1.25rem;">{{ $shot['icon'] }}</div>
                                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.7);">{{ __($shot['label']) }}</div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- Decompose Button --}}
                    <button type="button"
                            wire:click="decomposeScene({{ $multiShotSceneIndex }})"
                            wire:loading.attr="disabled"
                            wire:target="decomposeScene"
                            style="width: 100%; padding: 0.85rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <span wire:loading.remove wire:target="decomposeScene">✂️ {{ __('Decompose Scene') }}</span>
                        <span wire:loading wire:target="decomposeScene">
                            <svg style="width: 16px; height: 16px; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                            </svg>
                            {{ __('Decomposing...') }}
                        </span>
                    </button>
                @else
                    {{-- Decomposed Shots --}}
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <span style="color: rgba(255,255,255,0.8); font-weight: 600;">{{ __('Shots') }} ({{ count($decomposed['shots']) }})</span>
                            <button type="button"
                                    wire:click="generateAllShots({{ $multiShotSceneIndex }})"
                                    wire:loading.attr="disabled"
                                    style="padding: 0.4rem 0.75rem; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); border-radius: 0.35rem; color: #10b981; font-size: 0.75rem; cursor: pointer;">
                                🚀 {{ __('Generate All') }}
                            </button>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.75rem;">
                            @foreach($decomposed['shots'] as $shotIndex => $shot)
                                <div style="background: rgba(255,255,255,0.03); border: 1px solid {{ ($decomposed['selectedShot'] ?? 0) === $shotIndex ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.5rem; overflow: hidden; cursor: pointer;"
                                     wire:click="selectShot({{ $multiShotSceneIndex }}, {{ $shotIndex }})">
                                    {{-- Shot Image --}}
                                    <div style="aspect-ratio: 16/9; background: rgba(0,0,0,0.3); position: relative;">
                                        @if($shot['status'] === 'ready' && !empty($shot['imageUrl']))
                                            <img src="{{ $shot['imageUrl'] }}" alt="Shot {{ $shotIndex + 1 }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @elseif($shot['status'] === 'generating')
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                <svg style="width: 24px; height: 24px; animation: spin 0.8s linear infinite; color: #8b5cf6;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.25rem;">
                                                <span style="font-size: 1.5rem;">🎬</span>
                                                <button type="button"
                                                        wire:click.stop="generateShotImage({{ $multiShotSceneIndex }}, {{ $shotIndex }})"
                                                        style="padding: 0.25rem 0.5rem; background: rgba(139,92,246,0.3); border: 1px solid rgba(139,92,246,0.5); border-radius: 0.25rem; color: white; font-size: 0.6rem; cursor: pointer;">
                                                    Generate
                                                </button>
                                            </div>
                                        @endif
                                        <div style="position: absolute; top: 0.25rem; left: 0.25rem; background: rgba(0,0,0,0.7); color: white; padding: 0.15rem 0.35rem; border-radius: 0.2rem; font-size: 0.6rem;">
                                            #{{ $shotIndex + 1 }}
                                        </div>
                                        @if(($decomposed['selectedShot'] ?? 0) === $shotIndex)
                                            <div style="position: absolute; top: 0.25rem; right: 0.25rem; background: #10b981; color: white; padding: 0.15rem 0.35rem; border-radius: 0.2rem; font-size: 0.6rem;">
                                                ✓
                                            </div>
                                        @endif
                                    </div>
                                    {{-- Shot Info --}}
                                    <div style="padding: 0.5rem;">
                                        <div style="color: white; font-size: 0.75rem; font-weight: 500;">{{ ucfirst($shot['type']) }}</div>
                                        <div style="color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-top: 0.15rem;">{{ Str::limit($shot['description'], 40) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Reset Button --}}
                    <div style="text-align: center; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <button type="button"
                                wire:click="$set('multiShotMode.decomposedScenes.{{ $multiShotSceneIndex }}', null)"
                                style="padding: 0.5rem 1rem; background: transparent; border: 1px solid rgba(239,68,68,0.4); border-radius: 0.35rem; color: #ef4444; font-size: 0.8rem; cursor: pointer;">
                            🗑️ {{ __('Reset Decomposition') }}
                        </button>
                    </div>
                @endif
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end;">
            <button type="button"
                    wire:click="closeMultiShotModal"
                    style="padding: 0.6rem 1.25rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: white; cursor: pointer;">
                {{ __('Close') }}
            </button>
        </div>
    </div>
</div>
@endif

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
