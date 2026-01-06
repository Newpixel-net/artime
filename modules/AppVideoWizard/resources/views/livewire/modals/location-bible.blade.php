{{-- Location Bible Modal --}}
@if($showLocationBibleModal ?? false)
<div class="vw-modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;">
    <div class="vw-modal"
         style="background: linear-gradient(135deg, rgba(30,30,45,0.98), rgba(20,20,35,0.99)); border: 1px solid rgba(139,92,246,0.3); border-radius: 1rem; width: 100%; max-width: 950px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        {{-- Header --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white; font-size: 1.1rem; font-weight: 600;">📍 {{ __('Location Bible') }}</h3>
                <p style="margin: 0.25rem 0 0 0; color: rgba(255,255,255,0.6); font-size: 0.8rem;">{{ __('Define locations for consistent environments across scenes') }}</p>
            </div>
            <button type="button" wire:click="closeLocationBibleModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>

        {{-- Content --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; gap: 1.25rem;">
            {{-- Locations List (Left Panel) --}}
            <div style="width: 240px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 1.25rem;">
                {{-- Add Location Button --}}
                <button type="button"
                        wire:click="addLocation"
                        style="width: 100%; padding: 0.6rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.5rem; color: #c4b5fd; font-size: 0.8rem; cursor: pointer; margin-bottom: 0.75rem;">
                    + {{ __('Add Location') }}
                </button>

                {{-- Auto-detect Button --}}
                <button type="button"
                        wire:click="autoDetectLocations"
                        wire:loading.attr="disabled"
                        wire:target="autoDetectLocations"
                        style="width: 100%; padding: 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                    <span wire:loading.remove wire:target="autoDetectLocations">🔍 {{ __('Auto-detect from Script') }}</span>
                    <span wire:loading wire:target="autoDetectLocations">{{ __('Detecting...') }}</span>
                </button>

                {{-- Quick Templates --}}
                <div style="margin-bottom: 1rem;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.65rem; margin-bottom: 0.5rem; text-transform: uppercase;">{{ __('Quick Templates') }}</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                        <button type="button" wire:click="applyLocationTemplate('urban')" style="padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: rgba(255,255,255,0.6); font-size: 0.65rem; cursor: pointer;">🏙️ {{ __('Urban') }}</button>
                        <button type="button" wire:click="applyLocationTemplate('forest')" style="padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: rgba(255,255,255,0.6); font-size: 0.65rem; cursor: pointer;">🌲 {{ __('Forest') }}</button>
                        <button type="button" wire:click="applyLocationTemplate('office')" style="padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: rgba(255,255,255,0.6); font-size: 0.65rem; cursor: pointer;">🏢 {{ __('Office') }}</button>
                        <button type="button" wire:click="applyLocationTemplate('studio')" style="padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.25rem; color: rgba(255,255,255,0.6); font-size: 0.65rem; cursor: pointer;">🎬 {{ __('Studio') }}</button>
                    </div>
                </div>

                {{-- Location Items List --}}
                <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 350px; overflow-y: auto;">
                    @forelse($sceneMemory['locationBible']['locations'] ?? [] as $index => $location)
                        <div wire:click="editLocation({{ $index }})"
                             style="padding: 0.75rem; background: {{ $editingLocationIndex === $index ? 'rgba(139,92,246,0.15)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $editingLocationIndex === $index ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.1)' }}; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                @if(!empty($location['referenceImage']))
                                    <img src="{{ $location['referenceImage'] }}" style="width: 35px; height: 35px; border-radius: 0.25rem; object-fit: cover;">
                                @else
                                    <div style="width: 35px; height: 35px; background: rgba(139,92,246,0.2); border-radius: 0.25rem; display: flex; align-items: center; justify-content: center; font-size: 1rem;">📍</div>
                                @endif
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; color: white; font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $location['name'] ?? __('Unnamed') }}</div>
                                    <div style="color: rgba(255,255,255,0.5); font-size: 0.65rem;">
                                        {{ ucfirst($location['type'] ?? 'exterior') }} • {{ ucfirst($location['timeOfDay'] ?? 'day') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 1.5rem; color: rgba(255,255,255,0.4); font-size: 0.75rem; text-align: center;">
                            {{ __('No locations defined yet') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Location Editor (Right Panel) --}}
            <div style="flex: 1; display: flex; flex-direction: column; gap: 1rem;">
                @if(count($sceneMemory['locationBible']['locations'] ?? []) > 0)
                    @php
                        $editIndex = $editingLocationIndex ?? 0;
                        $currentLocation = $sceneMemory['locationBible']['locations'][$editIndex] ?? null;
                    @endphp

                    @if($currentLocation)
                        {{-- Top Section: Reference Image + Basic Info --}}
                        <div style="display: flex; gap: 1rem;">
                            {{-- Reference Image Preview --}}
                            <div style="width: 200px; flex-shrink: 0;">
                                <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Reference Image') }}</label>
                                <div style="width: 200px; height: 130px; background: rgba(0,0,0,0.3); border: 1px dashed rgba(139,92,246,0.3); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                    @if(!empty($currentLocation['referenceImage']))
                                        <img src="{{ $currentLocation['referenceImage'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        <button type="button"
                                                wire:click="removeLocationReference({{ $editIndex }})"
                                                style="position: absolute; top: 0.35rem; right: 0.35rem; width: 24px; height: 24px; background: rgba(239,68,68,0.9); border: none; border-radius: 50%; color: white; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                                                title="{{ __('Remove') }}">✕</button>
                                    @else
                                        <div style="text-align: center; color: rgba(255,255,255,0.4);">
                                            <div style="font-size: 2rem; margin-bottom: 0.35rem;">🏞️</div>
                                            <div style="font-size: 0.7rem;">{{ __('No reference') }}</div>
                                        </div>
                                    @endif
                                </div>
                                <button type="button"
                                        wire:click="generateLocationReference({{ $editIndex }})"
                                        wire:loading.attr="disabled"
                                        wire:target="generateLocationReference"
                                        {{ $isGeneratingLocationRef ? 'disabled' : '' }}
                                        style="width: 100%; margin-top: 0.5rem; padding: 0.5rem; background: linear-gradient(135deg, #f59e0b, #f97316); border: none; border-radius: 0.35rem; color: white; font-size: 0.75rem; cursor: pointer; {{ $isGeneratingLocationRef ? 'opacity: 0.5;' : '' }}">
                                    <span wire:loading.remove wire:target="generateLocationReference">🎨 {{ __('Generate Reference') }}</span>
                                    <span wire:loading wire:target="generateLocationReference">{{ __('Generating...') }}</span>
                                </button>
                            </div>

                            {{-- Basic Info --}}
                            <div style="flex: 1;">
                                {{-- Location Name --}}
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Location Name') }}</label>
                                    <input type="text"
                                           wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.name"
                                           placeholder="{{ __('e.g., Downtown Office, Forest Clearing...') }}"
                                           style="width: 100%; padding: 0.6rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem;">
                                </div>

                                {{-- Type, Time, Weather --}}
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.6rem;">
                                    <div>
                                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.7rem; margin-bottom: 0.25rem;">{{ __('Type') }}</label>
                                        <select wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.type"
                                                style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.75rem;">
                                            <option value="exterior">{{ __('Exterior') }}</option>
                                            <option value="interior">{{ __('Interior') }}</option>
                                            <option value="abstract">{{ __('Abstract') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.7rem; margin-bottom: 0.25rem;">{{ __('Time of Day') }}</label>
                                        <select wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.timeOfDay"
                                                style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.75rem;">
                                            <option value="day">{{ __('Day') }}</option>
                                            <option value="night">{{ __('Night') }}</option>
                                            <option value="dawn">{{ __('Dawn') }}</option>
                                            <option value="dusk">{{ __('Dusk') }}</option>
                                            <option value="golden-hour">{{ __('Golden Hour') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.7rem; margin-bottom: 0.25rem;">{{ __('Weather') }}</label>
                                        <select wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.weather"
                                                style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.35rem; color: white; font-size: 0.75rem;">
                                            <option value="clear">{{ __('Clear') }}</option>
                                            <option value="cloudy">{{ __('Cloudy') }}</option>
                                            <option value="rainy">{{ __('Rainy') }}</option>
                                            <option value="foggy">{{ __('Foggy') }}</option>
                                            <option value="stormy">{{ __('Stormy') }}</option>
                                            <option value="snowy">{{ __('Snowy') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label style="display: block; color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-bottom: 0.35rem;">{{ __('Visual Description') }}</label>
                            <textarea wire:model.live="sceneMemory.locationBible.locations.{{ $editIndex }}.description"
                                      placeholder="{{ __('Describe the location in detail: architecture, materials, atmosphere, distinctive features, colors, textures...') }}"
                                      style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; color: white; font-size: 0.85rem; min-height: 80px; resize: vertical;"></textarea>
                        </div>

                        {{-- Scene Assignment --}}
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <label style="color: rgba(255,255,255,0.7); font-size: 0.75rem;">{{ __('Assign to Scenes') }}</label>
                                <button type="button"
                                        wire:click="applyLocationToAllScenes({{ $editIndex }})"
                                        style="padding: 0.25rem 0.5rem; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); border-radius: 0.25rem; color: #c4b5fd; font-size: 0.65rem; cursor: pointer;">
                                    {{ __('Select All') }}
                                </button>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                @foreach($storyboard['scenes'] ?? [] as $sceneIdx => $scene)
                                    @php
                                        $assignedScenes = $currentLocation['scenes'] ?? [];
                                        $isAssigned = in_array($sceneIdx, $assignedScenes);
                                    @endphp
                                    <button type="button"
                                            wire:click="toggleLocationScene({{ $editIndex }}, {{ $sceneIdx }})"
                                            style="padding: 0.35rem 0.6rem; background: {{ $isAssigned ? 'rgba(139,92,246,0.3)' : 'rgba(255,255,255,0.05)' }}; border: 1px solid {{ $isAssigned ? 'rgba(139,92,246,0.5)' : 'rgba(255,255,255,0.15)' }}; border-radius: 0.25rem; color: {{ $isAssigned ? '#c4b5fd' : 'rgba(255,255,255,0.6)' }}; font-size: 0.7rem; cursor: pointer;">
                                        S{{ $sceneIdx + 1 }}
                                    </button>
                                @endforeach
                            </div>
                            @if(empty($storyboard['scenes']))
                                <div style="color: rgba(255,255,255,0.4); font-size: 0.7rem; padding: 0.5rem;">
                                    {{ __('No scenes available yet') }}
                                </div>
                            @endif
                        </div>

                        {{-- Delete Location Button --}}
                        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                            <button type="button"
                                    wire:click="removeLocation({{ $editIndex }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this location?') }}"
                                    style="padding: 0.5rem 1rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; color: #ef4444; font-size: 0.8rem; cursor: pointer;">
                                🗑️ {{ __('Delete Location') }}
                            </button>
                        </div>
                    @endif
                @else
                    {{-- Empty State --}}
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: rgba(255,255,255,0.4);">
                        <span style="font-size: 4rem; margin-bottom: 1rem;">📍</span>
                        <p style="font-size: 1rem; margin-bottom: 0.5rem;">{{ __('No locations defined') }}</p>
                        <p style="font-size: 0.8rem;">{{ __('Add a location or auto-detect from your script') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.85rem; cursor: pointer;">
                <input type="checkbox" wire:model.live="sceneMemory.locationBible.enabled" style="accent-color: #8b5cf6;">
                {{ __('Enable Location Bible') }}
            </label>
            <div style="display: flex; gap: 0.75rem;">
                <button type="button"
                        wire:click="closeLocationBibleModal"
                        style="padding: 0.6rem 1rem; background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer;">
                    {{ __('Cancel') }}
                </button>
                <button type="button"
                        wire:click="closeLocationBibleModal"
                        style="padding: 0.6rem 1.25rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer;">
                    {{ __('Save & Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif
