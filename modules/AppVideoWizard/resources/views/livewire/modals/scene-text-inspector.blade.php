{{-- Scene Text Inspector Modal --}}
@if($showSceneTextInspectorModal ?? false)
<div class="vw-modal-overlay"
     x-data="{ show: @entangle('showSceneTextInspectorModal') }"
     x-show="show"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$wire.closeSceneTextInspector()"
     wire:key="scene-inspector-{{ $inspectorSceneIndex ?? 'main' }}"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">

    <div class="vw-modal"
         @click.outside="$wire.closeSceneTextInspector()"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.75rem; width: 100%; max-width: 920px; max-height: 96vh; display: flex; flex-direction: column; overflow: hidden;">

        {{-- Header --}}
        <div style="padding: 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            @php
                $scene = $this->inspectorScene['script'] ?? null;
                $sceneNum = ($inspectorSceneIndex ?? 0) + 1;
            @endphp
            <div>
                <h3 style="margin: 0; color: white; font-size: 1rem; font-weight: 600;">
                    Scene {{ $sceneNum }}{{ !empty($scene['title']) ? ': ' . $scene['title'] : '' }}
                </h3>
                <p style="margin: 0.15rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">
                    {{ __('Complete scene text, prompts, and metadata') }}
                </p>
            </div>
            <button type="button"
                    wire:click="closeSceneTextInspector"
                    style="background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1rem;">
            @if($scene)
                {{-- Metadata Section --}}
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                        Scene Metadata
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem;">
                        {{-- META-01: Duration --}}
                        @php
                            $duration = $scene['metadata']['duration'] ?? null;
                            $durationFormatted = 'N/A';
                            if ($duration && is_numeric($duration)) {
                                $minutes = floor($duration / 60);
                                $seconds = $duration % 60;
                                $durationFormatted = sprintf('%02d:%02d', $minutes, $seconds);
                            }
                        @endphp
                        <div style="padding: 0.5rem 0.75rem; background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem;">⏱️</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Duration</div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500;">{{ $durationFormatted }}</div>
                            </div>
                        </div>

                        {{-- META-02: Transition --}}
                        @php
                            $transition = $scene['metadata']['transition'] ?? 'CUT';
                            $transitionIcons = [
                                'CUT' => '✂️',
                                'FADE' => '🌫️',
                                'DISSOLVE' => '💫',
                                'WIPE' => '↔️',
                                'IRIS' => '⭕',
                            ];
                            $transitionIcon = $transitionIcons[strtoupper($transition)] ?? '🎬';
                        @endphp
                        <div style="padding: 0.5rem 0.75rem; background: rgba(168,85,247,0.15); border: 1px solid rgba(168,85,247,0.3); border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem;">{{ $transitionIcon }}</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Transition</div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500;">{{ strtoupper($transition) }}</div>
                            </div>
                        </div>

                        {{-- META-03: Location --}}
                        @php
                            $location = $scene['metadata']['location'] ?? $scene['location'] ?? 'Unknown';
                            if (is_array($location)) {
                                $location = $location['name'] ?? $location['description'] ?? 'Unknown';
                            }
                            $locationDisplay = strlen($location) > 30 ? substr($location, 0, 27) . '...' : $location;
                        @endphp
                        <div style="padding: 0.5rem 0.75rem; background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.875rem;">📍</span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em;">Location</div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.95); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $location }}">{{ $locationDisplay }}</div>
                            </div>
                        </div>

                        {{-- Characters, Intensity, and Climax badges will be added in task 3 --}}
                    </div>
                </div>

                {{-- Speech Segments Section (Phase 8) --}}
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                        Speech Segments
                    </h4>
                    <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; text-align: center; color: rgba(255,255,255,0.6); font-size: 0.75rem;">
                        Speech segment display coming in Phase 8
                    </div>
                </div>

                {{-- Prompts Section (Phase 9) --}}
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                        Prompts
                    </h4>
                    <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; text-align: center; color: rgba(255,255,255,0.6); font-size: 0.75rem;">
                        Prompt display coming in Phase 9
                    </div>
                </div>
            @else
                <div style="padding: 2rem; text-align: center; color: rgba(255,255,255,0.4);">
                    {{ __('Scene not found') }}
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; gap: 0.5rem; flex-shrink: 0;">
            <button type="button"
                    wire:click="closeSceneTextInspector"
                    style="padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.375rem; color: white; font-size: 0.75rem; cursor: pointer;">
                {{ __('Close') }}
            </button>
        </div>

    </div>
</div>
@endif
