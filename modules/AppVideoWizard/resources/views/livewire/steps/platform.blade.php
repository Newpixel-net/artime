{{-- Step 1: Platform & Format Selection --}}
<div class="fade-in space-y-6">
    {{-- Video Format Card --}}
    <div class="content-card bg-base-content/5 border border-base-content/10 rounded-3xl p-6">
        <div class="content-card-header flex items-center gap-3 mb-6">
            <div class="content-card-icon w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500/20 to-cyan-500/20 flex items-center justify-center text-2xl">
                📐
            </div>
            <div>
                <div class="content-card-title text-xl font-bold">{{ __('Video Format') }}</div>
                <div class="content-card-subtitle text-sm text-base-content/50">{{ __('Choose your aspect ratio') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($formats as $id => $formatConfig)
                @php
                    $isSelected = $format === $id;
                @endphp
                <div wire:click="selectFormat('{{ $id }}')"
                     class="format-option cursor-pointer p-4 rounded-xl text-center transition-all duration-200
                            {{ $isSelected ? 'bg-purple-500/30 border-2 border-purple-500' : 'bg-base-content/5 border-2 border-transparent hover:bg-base-content/10' }}">
                    <div class="text-3xl mb-2">
                        @switch($id)
                            @case('widescreen')
                                🖥️
                                @break
                            @case('vertical')
                                📱
                                @break
                            @case('square')
                                ⬜
                                @break
                            @case('tall')
                                📐
                                @break
                            @default
                                <i class="{{ $formatConfig['icon'] ?? 'fa-solid fa-video' }}"></i>
                        @endswitch
                    </div>
                    <div class="font-semibold text-sm">{{ $formatConfig['name'] }}</div>
                    <div class="text-xs text-base-content/50">{{ $formatConfig['aspectRatio'] }}</div>
                    <div class="text-xs text-base-content/40 mt-1">{{ $formatConfig['description'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Production Type Card --}}
    <div class="content-card bg-base-content/5 border border-base-content/10 rounded-3xl p-6">
        <div class="content-card-header flex items-center gap-3 mb-6">
            <div class="content-card-icon w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500/20 to-cyan-500/20 flex items-center justify-center text-2xl">
                🎬
            </div>
            <div>
                <div class="content-card-title text-xl font-bold">{{ __('What are you creating?') }}</div>
                <div class="content-card-subtitle text-sm text-base-content/50">{{ __('Select your production type') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @foreach($productionTypes as $typeId => $type)
                @php
                    $isSelected = $productionType === $typeId;
                @endphp
                <div wire:click="selectProductionType('{{ $typeId }}')"
                     class="production-type-card cursor-pointer p-5 rounded-xl text-center transition-all duration-200
                            {{ $isSelected ? 'bg-purple-500/30 border-2 border-purple-500' : 'bg-base-content/5 border-2 border-transparent hover:bg-base-content/10' }}">
                    <div class="text-3xl mb-2">
                        @switch($typeId)
                            @case('social')
                                📱
                                @break
                            @case('movie')
                                🎬
                                @break
                            @case('series')
                                📺
                                @break
                            @case('educational')
                                🎓
                                @break
                            @case('music')
                                🎵
                                @break
                            @case('commercial')
                                📢
                                @break
                            @default
                                <i class="{{ $type['icon'] ?? 'fa-solid fa-film' }}"></i>
                        @endswitch
                    </div>
                    <div class="font-semibold">{{ $type['name'] }}</div>
                    <div class="text-xs text-base-content/50 mt-1">{{ $type['description'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Sub-type Selection --}}
        @if($productionType && isset($productionTypes[$productionType]['subTypes']))
            <div class="mt-6 pt-6 border-t border-base-content/10">
                <div class="flex items-center gap-2 mb-4 text-sm text-base-content/70">
                    <span>{{ $productionTypes[$productionType]['icon'] ?? '🎬' }}</span>
                    <span>{{ __('Select :name Style:', ['name' => $productionTypes[$productionType]['name']]) }}</span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($productionTypes[$productionType]['subTypes'] as $subId => $subType)
                        @php
                            $isSubSelected = $productionSubtype === $subId;
                        @endphp
                        <div wire:click="selectProductionType('{{ $productionType }}', '{{ $subId }}')"
                             class="subtype-card cursor-pointer p-3 rounded-lg transition-all duration-200
                                    {{ $isSubSelected ? 'bg-purple-500/25 border border-purple-500' : 'bg-base-content/5 border border-base-content/10 hover:bg-base-content/10' }}">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ $subType['icon'] ?? '🎯' }}</span>
                                <span class="font-medium text-sm">{{ $subType['name'] }}</span>
                            </div>
                            <div class="text-xs text-base-content/40 mt-1 ml-7">{{ $subType['description'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Target Duration Card --}}
    @if($productionType && $productionSubtype)
        <div class="content-card bg-base-content/5 border border-base-content/10 rounded-3xl p-6">
            <div class="content-card-header flex items-center gap-3 mb-6">
                <div class="content-card-icon w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500/20 to-cyan-500/20 flex items-center justify-center text-2xl">
                    ⏱️
                </div>
                <div>
                    <div class="content-card-title text-xl font-bold">{{ __('Target Duration') }}</div>
                    <div class="content-card-subtitle text-sm text-base-content/50">
                        {{ __('Recommended for :type', ['type' => $productionTypes[$productionType]['subTypes'][$productionSubtype]['name'] ?? $productionTypes[$productionType]['name']]) }}
                    </div>
                </div>
            </div>

            <div class="duration-container">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm text-base-content/60">{{ __('Video Length') }}</span>
                    <span class="badge badge-lg badge-primary">
                        @if($targetDuration >= 60)
                            {{ floor($targetDuration / 60) }}:{{ str_pad($targetDuration % 60, 2, '0', STR_PAD_LEFT) }}
                        @else
                            {{ $targetDuration }}s
                        @endif
                    </span>
                </div>

                <input type="range"
                       wire:model.live="targetDuration"
                       min="{{ $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['min'] ?? 15 }}"
                       max="{{ $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['max'] ?? 300 }}"
                       class="range range-primary w-full" />

                <div class="flex justify-between text-xs text-base-content/50 mt-2">
                    <span>{{ $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['min'] ?? 15 }}s</span>
                    <span>{{ $productionTypes[$productionType]['subTypes'][$productionSubtype]['suggestedDuration']['max'] ?? 300 }}s</span>
                </div>
            </div>
        </div>
    @endif
</div>
