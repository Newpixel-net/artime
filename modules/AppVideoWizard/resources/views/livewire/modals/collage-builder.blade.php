{{-- Collage Builder Modal - Detailed Image Editing --}}
@if($showCollageModal ?? false)
<div class="vw-modal-overlay"
     wire:key="collage-builder-modal"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 0.5rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.75rem; width: 100%; max-width: 1200px; max-height: 96vh; display: flex; flex-direction: column; overflow: hidden;">

        {{-- Header --}}
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: linear-gradient(135deg, rgba(139,92,246,0.1), rgba(236,72,153,0.05));">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.25rem;">🖼️</span>
                    {{ __('Collage Builder') }}
                    @if(count($collage['images'] ?? []) > 0)
                        <span style="background: rgba(139,92,246,0.2); color: #a78bfa; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600;">
                            {{ count($collage['images']) }} {{ __('images') }}
                        </span>
                    @endif
                    @if($collage['status'] === 'ready')
                        <span style="background: rgba(16,185,129,0.2); color: #10b981; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.65rem; font-weight: 600;">{{ __('READY') }}</span>
                    @endif
                </h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.7rem;">{{ __('Arrange and style your images into a stunning visual collage') }}</p>
            </div>
            <button type="button" wire:click="closeCollageModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Error Display --}}
        @if($error)
            <div style="padding: 0.5rem 1rem; background: rgba(239,68,68,0.15); border-bottom: 1px solid rgba(239,68,68,0.3); color: #fca5a5; font-size: 0.7rem; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <span>{{ $error }}</span>
                <button type="button" wire:click="$set('error', null)" style="margin-left: auto; background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 0.9rem;">&times;</button>
            </div>
        @endif

        {{-- Main Content Area --}}
        <div style="flex: 1; display: flex; overflow: hidden;">

            {{-- Left Panel: Image List --}}
            <div style="width: 280px; border-right: 1px solid rgba(255,255,255,0.1); overflow-y: auto; flex-shrink: 0; padding: 1rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <span style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">{{ __('Images') }}</span>
                    <span style="background: rgba(139,92,246,0.2); color: #a78bfa; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.65rem;">
                        {{ count($collage['images'] ?? []) }}
                    </span>
                </div>

                {{-- Image List --}}
                @if(count($collage['images'] ?? []) > 0)
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        @foreach($collage['images'] as $index => $image)
                            <div wire:click="selectCollageImage({{ $index }})"
                                 style="display: flex; gap: 0.75rem; padding: 0.5rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s; {{ $collageSelectedImageIndex === $index ? 'background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.5);' : 'background: rgba(255,255,255,0.03); border: 1px solid transparent;' }}">
                                <div style="width: 48px; height: 48px; border-radius: 0.35rem; overflow: hidden; flex-shrink: 0;">
                                    <img src="{{ $image['url'] }}" alt="Image {{ $index + 1 }}" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.8rem; font-weight: 500; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ __('Image') }} {{ $index + 1 }}
                                    </div>
                                    <div style="font-size: 0.65rem; color: rgba(255,255,255,0.5);">
                                        {{ ucfirst($image['source'] ?? 'unknown') }}
                                    </div>
                                    <div style="font-size: 0.6rem; color: rgba(139,92,246,0.8); margin-top: 0.15rem;">
                                        {{ $image['animation']['type'] ?? 'ken-burns' }} • {{ $image['animation']['duration'] ?? 5 }}s
                                    </div>
                                </div>
                                <button type="button"
                                        wire:click.stop="removeCollageImage({{ $index }})"
                                        style="background: rgba(239,68,68,0.2); border: none; border-radius: 0.25rem; color: #fca5a5; font-size: 0.7rem; padding: 0.25rem 0.4rem; cursor: pointer; align-self: center;"
                                        title="{{ __('Remove') }}">
                                    ✕
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align: center; padding: 2rem 1rem; color: rgba(255,255,255,0.4);">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;">🖼️</div>
                        <div style="font-size: 0.75rem;">{{ __('No images yet') }}</div>
                    </div>
                @endif

                {{-- Add Image Button --}}
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08);">
                    <label for="modalCollageImageUpload" style="display: block; padding: 0.75rem; border: 2px dashed rgba(139,92,246,0.3); border-radius: 0.5rem; text-align: center; cursor: pointer; transition: all 0.2s; background: rgba(139,92,246,0.05);">
                        <input type="file"
                               wire:model="collageImageUpload"
                               accept="image/*"
                               id="modalCollageImageUpload"
                               style="display: none;">
                        <span style="font-size: 1.25rem;">➕</span>
                        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.7); margin-top: 0.25rem;">{{ __('Add Image') }}</div>
                    </label>
                </div>
            </div>

            {{-- Center Panel: Preview & Editor --}}
            <div style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">

                {{-- Preview Area --}}
                <div style="flex: 1; padding: 1rem; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3);">
                    @if($collageSelectedImageIndex >= 0 && isset($collage['images'][$collageSelectedImageIndex]))
                        @php $selectedImage = $collage['images'][$collageSelectedImageIndex]; @endphp
                        <div style="max-width: 100%; max-height: 100%; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                            <img src="{{ $selectedImage['url'] }}"
                                 alt="Selected image"
                                 style="max-width: 500px; max-height: 400px; object-fit: contain; transform: rotate({{ $selectedImage['rotation'] ?? 0 }}deg) scale({{ $selectedImage['scale'] ?? 1 }}); opacity: {{ $selectedImage['opacity'] ?? 1 }};">
                        </div>
                    @elseif(count($collage['images'] ?? []) > 0)
                        {{-- Show Collage Grid Preview --}}
                        @php
                            $layout = $collage['layout'] ?? [];
                            $columns = $layout['columns'] ?? 3;
                            $gap = $layout['gap'] ?? 8;
                            $bgColor = $layout['backgroundColor'] ?? '#000000';
                        @endphp
                        <div style="width: 100%; max-width: 600px; aspect-ratio: {{ str_replace(':', '/', $aspectRatio ?? '16:9') }}; display: grid; grid-template-columns: repeat({{ $columns }}, 1fr); gap: {{ $gap }}px; padding: {{ $gap }}px; background-color: {{ $bgColor }}; border-radius: 0.5rem; overflow: hidden;">
                            @foreach($collage['images'] as $image)
                                <div style="overflow: hidden; border-radius: {{ $layout['borderRadius'] ?? 4 }}px;">
                                    <img src="{{ $image['url'] }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align: center; color: rgba(255,255,255,0.4);">
                            <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">🖼️</div>
                            <div style="font-size: 0.9rem;">{{ __('Add images to start building your collage') }}</div>
                        </div>
                    @endif
                </div>

                {{-- Bottom Toolbar --}}
                <div style="padding: 0.75rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); display: flex; align-items: center; gap: 1rem; flex-shrink: 0;">
                    {{-- Layout Selector --}}
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5);">{{ __('Layout:') }}</span>
                        <select wire:model.live="collage.layout.type"
                                wire:change="setCollageLayout($event.target.value)"
                                style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.35rem; padding: 0.35rem 0.6rem; color: white; font-size: 0.75rem;">
                            @foreach(\Modules\AppVideoWizard\Livewire\VideoWizard::COLLAGE_LAYOUTS as $layoutId => $layoutConfig)
                                <option value="{{ $layoutId }}">{{ $layoutConfig['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Animation Style --}}
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5);">{{ __('Animation:') }}</span>
                        <select wire:model.live="collage.animation.style"
                                wire:change="setCollageAnimationStyle($event.target.value)"
                                style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.35rem; padding: 0.35rem 0.6rem; color: white; font-size: 0.75rem;">
                            @foreach(\Modules\AppVideoWizard\Livewire\VideoWizard::COLLAGE_ANIMATION_STYLES as $styleId => $styleConfig)
                                <option value="{{ $styleId }}">{{ $styleConfig['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Duration Display --}}
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5);">{{ __('Duration:') }}</span>
                        @php $duration = $this->calculateCollageDuration(); @endphp
                        <span style="font-size: 0.8rem; color: #a78bfa; font-weight: 600;">
                            {{ $duration > 0 ? gmdate($duration >= 60 ? "i:s" : "0:s", $duration) : '--' }}
                        </span>
                    </div>

                    <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                        @if(count($collage['images'] ?? []) > 0)
                            <button type="button"
                                    wire:click="clearCollageImages"
                                    wire:confirm="{{ __('Are you sure you want to remove all images?') }}"
                                    style="padding: 0.4rem 0.75rem; background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.35rem; color: #fca5a5; font-size: 0.75rem; cursor: pointer;">
                                {{ __('Clear All') }}
                            </button>
                        @endif
                        <button type="button"
                                wire:click="finalizeCollage"
                                {{ count($collage['images'] ?? []) === 0 ? 'disabled' : '' }}
                                style="padding: 0.4rem 0.75rem; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 0.35rem; color: white; font-size: 0.75rem; font-weight: 600; cursor: pointer; {{ count($collage['images'] ?? []) === 0 ? 'opacity: 0.5;' : '' }}">
                            {{ __('Finalize Collage') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right Panel: Image Properties --}}
            @if($collageSelectedImageIndex >= 0 && isset($collage['images'][$collageSelectedImageIndex]))
            @php $selectedImage = $collage['images'][$collageSelectedImageIndex]; @endphp
            <div style="width: 280px; border-left: 1px solid rgba(255,255,255,0.1); overflow-y: auto; flex-shrink: 0; padding: 1rem;">
                <div style="color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-bottom: 1rem;">
                    {{ __('Image Properties') }}
                </div>

                {{-- Caption --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase;">{{ __('Caption') }}</label>
                    <input type="text"
                           wire:model.live="collage.images.{{ $collageSelectedImageIndex }}.caption"
                           placeholder="{{ __('Add a caption...') }}"
                           style="width: 100%; padding: 0.4rem 0.6rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 0.35rem; color: white; font-size: 0.8rem;">
                </div>

                {{-- Animation Settings --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Animation') }}</label>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.4); font-size: 0.6rem; margin-bottom: 0.2rem;">{{ __('Effect') }}</label>
                            <select wire:model.live="collage.images.{{ $collageSelectedImageIndex }}.animation.type"
                                    style="width: 100%; padding: 0.35rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 0.3rem; color: white; font-size: 0.7rem;">
                                <option value="ken-burns">Ken Burns</option>
                                <option value="pan">Pan</option>
                                <option value="zoom">Zoom</option>
                                <option value="fade">Fade</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.4); font-size: 0.6rem; margin-bottom: 0.2rem;">{{ __('Duration') }}</label>
                            <input type="number"
                                   wire:model.live="collage.images.{{ $collageSelectedImageIndex }}.animation.duration"
                                   min="1"
                                   max="30"
                                   style="width: 100%; padding: 0.35rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 0.3rem; color: white; font-size: 0.7rem; text-align: center;">
                        </div>
                    </div>
                </div>

                {{-- Visual Adjustments --}}
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Visual') }}</label>

                    {{-- Scale --}}
                    <div style="margin-bottom: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.2rem;">
                            <label style="color: rgba(255,255,255,0.4); font-size: 0.6rem;">{{ __('Scale') }}</label>
                            <span style="color: rgba(255,255,255,0.6); font-size: 0.6rem;">{{ ($selectedImage['scale'] ?? 1) * 100 }}%</span>
                        </div>
                        <input type="range"
                               wire:model.live="collage.images.{{ $collageSelectedImageIndex }}.scale"
                               min="0.5"
                               max="2"
                               step="0.1"
                               style="width: 100%; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.1); cursor: pointer;">
                    </div>

                    {{-- Rotation --}}
                    <div style="margin-bottom: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.2rem;">
                            <label style="color: rgba(255,255,255,0.4); font-size: 0.6rem;">{{ __('Rotation') }}</label>
                            <span style="color: rgba(255,255,255,0.6); font-size: 0.6rem;">{{ $selectedImage['rotation'] ?? 0 }}deg</span>
                        </div>
                        <input type="range"
                               wire:model.live="collage.images.{{ $collageSelectedImageIndex }}.rotation"
                               min="-45"
                               max="45"
                               step="1"
                               style="width: 100%; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.1); cursor: pointer;">
                    </div>

                    {{-- Opacity --}}
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.2rem;">
                            <label style="color: rgba(255,255,255,0.4); font-size: 0.6rem;">{{ __('Opacity') }}</label>
                            <span style="color: rgba(255,255,255,0.6); font-size: 0.6rem;">{{ ($selectedImage['opacity'] ?? 1) * 100 }}%</span>
                        </div>
                        <input type="range"
                               wire:model.live="collage.images.{{ $collageSelectedImageIndex }}.opacity"
                               min="0.1"
                               max="1"
                               step="0.1"
                               style="width: 100%; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.1); cursor: pointer;">
                    </div>
                </div>

                {{-- Metadata --}}
                <div style="padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08);">
                    <label style="display: block; color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Info') }}</label>
                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); display: flex; flex-direction: column; gap: 0.25rem;">
                        <div>{{ __('Source') }}: <span style="color: rgba(255,255,255,0.7);">{{ ucfirst($selectedImage['source'] ?? 'unknown') }}</span></div>
                        @if(!empty($selectedImage['metadata']['originalFilename']))
                            <div>{{ __('File') }}: <span style="color: rgba(255,255,255,0.7);">{{ $selectedImage['metadata']['originalFilename'] }}</span></div>
                        @endif
                        @if(!empty($selectedImage['metadata']['generatedPrompt']))
                            <div style="margin-top: 0.25rem;">
                                <span style="display: block; margin-bottom: 0.15rem;">{{ __('Prompt') }}:</span>
                                <span style="color: rgba(139,92,246,0.8); font-size: 0.65rem;">{{ Str::limit($selectedImage['metadata']['generatedPrompt'], 100) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08);">
                    <button type="button"
                            wire:click="removeCollageImage({{ $collageSelectedImageIndex }})"
                            style="width: 100%; padding: 0.5rem; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.35rem; color: #fca5a5; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                        <span>🗑️</span>
                        {{ __('Remove Image') }}
                    </button>
                </div>
            </div>
            @endif
        </div>

        {{-- Footer --}}
        <div style="padding: 0.75rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: rgba(0,0,0,0.2);">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">
                    {{ __('Layout') }}: <span style="color: #a78bfa;">{{ ucfirst($collage['layout']['type'] ?? 'grid') }}</span>
                </span>
                <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">
                    {{ __('Animation') }}: <span style="color: #a78bfa;">{{ ucfirst($collage['animation']['style'] ?? 'cinematic') }}</span>
                </span>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button"
                        wire:click="closeCollageModal"
                        style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.35rem; color: white; font-size: 0.8rem; cursor: pointer;">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif
