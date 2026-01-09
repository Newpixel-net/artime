{{--
    Text/Captions Tab Content
    Caption style, font, colors, effects settings
--}}

<div class="vw-text-tab">
    {{-- Section Header --}}
    <div class="vw-section-header">
        <span class="vw-section-icon">💬</span>
        <span class="vw-section-title">{{ __('CAPTIONS') }}</span>
    </div>

    {{-- Enable Toggle --}}
    <div class="vw-setting-row toggle-row">
        <span class="vw-setting-label">{{ __('Show Captions') }}</span>
        <label class="vw-toggle-switch">
            <input
                type="checkbox"
                wire:model.live="assembly.captions.enabled"
                x-on:change="updateCaptionSetting('enabled', $event.target.checked)"
                {{ ($assembly['captions']['enabled'] ?? true) ? 'checked' : '' }}
            >
            <span class="vw-toggle-slider"></span>
        </label>
    </div>

    {{-- Caption Settings (shown when enabled) --}}
    <div class="vw-caption-settings" :class="{ 'disabled': !captionsEnabled }">
        {{-- Caption Mode --}}
        <div class="vw-setting-group">
            <label class="vw-setting-label">{{ __('Caption Style') }}</label>
            <div class="vw-button-group dual">
                <button
                    type="button"
                    @click="$wire.set('assembly.captions.mode', 'word'); updateCaptionSetting('mode', 'word')"
                    :class="{ 'active': '{{ $assembly['captions']['mode'] ?? 'word' }}' === 'word' }"
                    class="vw-mode-btn"
                >
                    {{ __('WORD LEVEL') }}
                </button>
                <button
                    type="button"
                    @click="$wire.set('assembly.captions.mode', 'sentence'); updateCaptionSetting('mode', 'sentence')"
                    :class="{ 'active': '{{ $assembly['captions']['mode'] ?? 'word' }}' === 'sentence' }"
                    class="vw-mode-btn"
                >
                    {{ __('SENTENCE LEVEL') }}
                </button>
            </div>
        </div>

        {{-- Font Selection --}}
        <div class="vw-setting-group">
            <label class="vw-setting-label">{{ __('Font') }}</label>
            <select
                wire:model.live="assembly.captions.fontFamily"
                x-on:change="updateCaptionSetting('fontFamily', $event.target.value)"
                class="vw-select-input"
            >
                @foreach(['Montserrat', 'Poppins', 'Roboto', 'Inter', 'Oswald', 'Bebas Neue', 'Anton', 'Playfair Display'] as $font)
                    <option value="{{ $font }}" style="font-family: {{ $font }};">{{ $font }}</option>
                @endforeach
            </select>
        </div>

        {{-- Fill Color --}}
        <div class="vw-setting-row color-row">
            <span class="vw-setting-label">{{ __('Fill Color') }}</span>
            <div class="vw-color-input-wrapper">
                <input
                    type="color"
                    wire:model.live="assembly.captions.fillColor"
                    x-on:change="updateCaptionSetting('fillColor', $event.target.value)"
                    value="{{ $assembly['captions']['fillColor'] ?? '#FFFFFF' }}"
                    class="vw-color-input"
                >
                <span class="vw-color-value">{{ $assembly['captions']['fillColor'] ?? '#FFFFFF' }}</span>
            </div>
        </div>

        {{-- Stroke Color --}}
        <div class="vw-setting-row color-row">
            <span class="vw-setting-label">{{ __('Stroke Color') }}</span>
            <input
                type="color"
                wire:model.live="assembly.captions.strokeColor"
                x-on:change="updateCaptionSetting('strokeColor', $event.target.value)"
                value="{{ $assembly['captions']['strokeColor'] ?? '#000000' }}"
                class="vw-color-input small"
            >
        </div>

        {{-- Stroke Width --}}
        <div class="vw-setting-group">
            <div class="vw-setting-row">
                <span class="vw-setting-label">{{ __('Stroke Width') }}</span>
                <span class="vw-setting-value">{{ $assembly['captions']['strokeWidth'] ?? 2 }}px</span>
            </div>
            <input
                type="range"
                wire:model.live="assembly.captions.strokeWidth"
                x-on:input="updateCaptionSetting('strokeWidth', parseFloat($event.target.value))"
                min="0" max="5" step="0.5"
                class="vw-range-input"
            >
        </div>

        {{-- Effects Section --}}
        <div class="vw-setting-group">
            <div class="vw-section-label with-icon">
                <span>✨</span> {{ __('EFFECTS') }}
            </div>
            <div class="vw-effect-grid">
                @php
                    $effects = [
                        ['id' => 'none', 'name' => 'None', 'icon' => '—'],
                        ['id' => 'pop', 'name' => 'Pop', 'icon' => '💥'],
                        ['id' => 'fade', 'name' => 'Fade', 'icon' => '🌫️'],
                        ['id' => 'zoom', 'name' => 'Zoom', 'icon' => '🔍'],
                        ['id' => 'bounce', 'name' => 'Bounce', 'icon' => '⚡'],
                    ];
                @endphp
                @foreach($effects as $effect)
                    <button
                        type="button"
                        wire:click="$set('assembly.captions.effect', '{{ $effect['id'] }}')"
                        @click="updateCaptionSetting('effect', '{{ $effect['id'] }}')"
                        class="vw-effect-btn {{ ($assembly['captions']['effect'] ?? 'none') === $effect['id'] ? 'active' : '' }}"
                    >
                        <div class="vw-effect-icon">{{ $effect['icon'] }}</div>
                        <div class="vw-effect-name">{{ $effect['name'] }}</div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Text Style Presets --}}
        <div class="vw-setting-group">
            <label class="vw-setting-label">{{ __('Text Style') }}</label>
            <div class="vw-preset-grid">
                @php
                    $styles = [
                        ['id' => 'karaoke', 'name' => 'Karaoke'],
                        ['id' => 'beasty', 'name' => 'Bold'],
                        ['id' => 'hormozi', 'name' => 'Box'],
                        ['id' => 'ali', 'name' => 'Glow'],
                        ['id' => 'minimal', 'name' => 'Minimal'],
                    ];
                @endphp
                @foreach($styles as $style)
                    <button
                        type="button"
                        wire:click="$set('assembly.captions.style', '{{ $style['id'] }}')"
                        @click="updateCaptionSetting('style', '{{ $style['id'] }}')"
                        class="vw-preset-btn {{ ($assembly['captions']['style'] ?? 'karaoke') === $style['id'] ? 'active' : '' }}"
                    >
                        {{ $style['name'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Position --}}
        <div class="vw-setting-group">
            <label class="vw-setting-label">{{ __('Position') }}</label>
            <select
                wire:model.live="assembly.captions.position"
                x-on:change="updateCaptionSetting('position', $event.target.value)"
                class="vw-select-input"
            >
                <option value="top">{{ __('Top') }}</option>
                <option value="middle">{{ __('Middle') }}</option>
                <option value="bottom">{{ __('Bottom') }}</option>
            </select>
        </div>

        {{-- Size --}}
        <div class="vw-setting-group">
            <div class="vw-setting-row">
                <span class="vw-setting-label">{{ __('Size') }}</span>
                <span class="vw-setting-value">{{ number_format($assembly['captions']['size'] ?? 1, 1) }}x</span>
            </div>
            <input
                type="range"
                wire:model.live="assembly.captions.size"
                x-on:input="updateCaptionSetting('size', parseFloat($event.target.value))"
                min="0.5" max="2" step="0.1"
                class="vw-range-input"
            >
        </div>

        {{-- Highlight Color (for Karaoke) --}}
        <div class="vw-setting-group highlight-section">
            <div class="vw-setting-row">
                <span class="vw-setting-label">{{ __('Highlight Color') }}</span>
                <div class="vw-color-input-wrapper">
                    <input
                        type="color"
                        wire:model.live="assembly.captions.highlightColor"
                        x-on:change="updateCaptionSetting('highlightColor', $event.target.value)"
                        value="{{ $assembly['captions']['highlightColor'] ?? '#FBBF24' }}"
                        class="vw-color-input"
                    >
                    <span class="vw-karaoke-badge">KARAOKE</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .vw-text-tab {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .vw-setting-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .vw-setting-row.toggle-row {
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .vw-setting-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }

    .vw-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .vw-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.1);
        transition: 0.3s;
        border-radius: 24px;
    }

    .vw-toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }

    .vw-toggle-switch input:checked + .vw-toggle-slider {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
    }

    .vw-toggle-switch input:checked + .vw-toggle-slider:before {
        transform: translateX(20px);
    }

    .vw-caption-settings {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        transition: opacity 0.3s;
    }

    .vw-caption-settings.disabled {
        opacity: 0.4;
        pointer-events: none;
    }

    .vw-setting-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .vw-button-group.dual {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }

    .vw-mode-btn {
        padding: 0.6rem;
        border-radius: 0.4rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(0, 0, 0, 0.2);
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-mode-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-mode-btn.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
        color: #a78bfa;
    }

    .vw-select-input {
        width: 100%;
        padding: 0.6rem 0.75rem;
        border-radius: 0.4rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(0, 0, 0, 0.3);
        color: white;
        font-size: 0.85rem;
        cursor: pointer;
    }

    .vw-select-input:focus {
        outline: none;
        border-color: rgba(139, 92, 246, 0.5);
    }

    .vw-color-input-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-color-input {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 0.35rem;
        cursor: pointer;
        padding: 0;
        background: transparent;
    }

    .vw-color-input.small {
        width: 28px;
        height: 28px;
    }

    .vw-color-value {
        font-size: 0.75rem;
        font-family: monospace;
        color: rgba(255, 255, 255, 0.6);
    }

    .vw-range-input {
        width: 100%;
        height: 6px;
        -webkit-appearance: none;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        cursor: pointer;
    }

    .vw-range-input::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        cursor: pointer;
        border: 2px solid white;
    }

    .vw-setting-value {
        font-size: 0.8rem;
        color: #8b5cf6;
        font-weight: 600;
    }

    .vw-section-label.with-icon {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.5rem;
    }

    .vw-effect-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 0.4rem;
    }

    .vw-effect-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem 0.25rem;
        border-radius: 0.4rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-effect-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-effect-btn.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
        color: white;
    }

    .vw-effect-icon {
        font-size: 1rem;
    }

    .vw-effect-name {
        font-size: 0.6rem;
    }

    .vw-preset-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.4rem;
    }

    .vw-preset-btn {
        padding: 0.5rem;
        border-radius: 0.35rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-preset-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-preset-btn.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
        color: #a78bfa;
    }

    .vw-karaoke-badge {
        font-size: 0.55rem;
        padding: 0.15rem 0.35rem;
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
        border-radius: 0.2rem;
        font-weight: 600;
    }

    .highlight-section {
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
</style>
