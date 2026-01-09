{{--
    Audio Tab Content
    Voice, music, and audio mix settings
--}}

<div class="vw-audio-tab">
    {{-- Audio Mix Visualization --}}
    <div class="vw-audio-viz">
        <div class="vw-viz-label">{{ __('Audio Mix') }}</div>
        <div class="vw-viz-bars">
            <div class="vw-bar" style="height: 70%;"></div>
            <div class="vw-bar" style="height: 85%;"></div>
            <div class="vw-bar" style="height: 60%;"></div>
            <div class="vw-bar alt" style="height: 90%;"></div>
            <div class="vw-bar alt" style="height: 75%;"></div>
            <div class="vw-bar" style="height: 65%;"></div>
            <div class="vw-bar alt" style="height: 80%;"></div>
            <div class="vw-bar" style="height: 55%;"></div>
        </div>
        <div class="vw-viz-legend">
            <span class="vw-legend-voice">● {{ __('Voice') }} {{ $assembly['audioMix']['voiceVolume'] ?? 100 }}%</span>
            <span class="vw-legend-music">● {{ __('Music') }} {{ ($assembly['music']['enabled'] ?? false) ? ($assembly['music']['volume'] ?? 30) . '%' : 'Off' }}</span>
        </div>
    </div>

    {{-- Voiceover Settings --}}
    <div class="vw-setting-section">
        <div class="vw-section-header">
            <span>🎙️</span> {{ __('Voiceover') }}
        </div>
        <div class="vw-setting-row">
            <span class="vw-setting-label">{{ __('Volume') }}</span>
            <span class="vw-setting-value">{{ $assembly['audioMix']['voiceVolume'] ?? 100 }}%</span>
        </div>
        <input
            type="range"
            wire:model.live="assembly.audioMix.voiceVolume"
            min="0" max="100"
            class="vw-range-input voice"
        >
    </div>

    {{-- Background Music Section --}}
    <div class="vw-setting-section">
        <div class="vw-section-header">
            <span>🎵</span> {{ __('Background Music') }}
        </div>

        {{-- Enable Toggle --}}
        <div class="vw-setting-row toggle-row">
            <span class="vw-setting-label">{{ __('Enable Music') }}</span>
            <label class="vw-toggle-switch">
                <input
                    type="checkbox"
                    wire:model.live="assembly.music.enabled"
                    x-on:change="updateMusicSetting('enabled', $event.target.checked)"
                    {{ ($assembly['music']['enabled'] ?? false) ? 'checked' : '' }}
                >
                <span class="vw-toggle-slider"></span>
            </label>
        </div>

        <div class="vw-music-controls" :class="{ 'disabled': !musicEnabled }">
            {{-- Track Selection --}}
            <div class="vw-setting-group">
                <div class="vw-setting-row">
                    <label class="vw-setting-label">{{ __('Select Track') }}</label>
                    <button type="button" @click="$dispatch('open-music-browser')" class="vw-browse-btn">
                        🔍 {{ __('Browse') }}
                    </button>
                </div>
                <select
                    wire:model.live="assembly.music.trackId"
                    class="vw-select-input"
                >
                    <option value="">-- {{ __('Select a track') }} --</option>
                    <option value="upbeat-corporate-1">Corporate Uplift</option>
                    <option value="cinematic-epic-1">Epic Cinematic</option>
                    <option value="chill-ambient-1">Chill Ambient</option>
                    <option value="energetic-pop-1">Energetic Pop</option>
                </select>
            </div>

            {{-- Volume --}}
            <div class="vw-setting-group">
                <div class="vw-setting-row">
                    <span class="vw-setting-label">{{ __('Volume') }}</span>
                    <span class="vw-setting-value">{{ $assembly['music']['volume'] ?? 30 }}%</span>
                </div>
                <input
                    type="range"
                    wire:model.live="assembly.music.volume"
                    x-on:input="updateMusicSetting('volume', parseInt($event.target.value))"
                    min="0" max="100" step="5"
                    class="vw-range-input music"
                >
            </div>

            {{-- Audio Ducking --}}
            <div class="vw-setting-row toggle-row small">
                <span class="vw-setting-label">{{ __('Auto-duck during voice') }}</span>
                <label class="vw-toggle-switch small">
                    <input
                        type="checkbox"
                        wire:model.live="assembly.audioMix.ducking"
                        {{ ($assembly['audioMix']['ducking'] ?? true) ? 'checked' : '' }}
                    >
                    <span class="vw-toggle-slider"></span>
                </label>
            </div>

            {{-- Fade Settings --}}
            <div class="vw-fade-settings">
                <div class="vw-fade-group">
                    <label>{{ __('Fade In') }}</label>
                    <select wire:model.live="assembly.music.fadeIn" class="vw-select-input small">
                        <option value="0">{{ __('None') }}</option>
                        <option value="1">1s</option>
                        <option value="2">2s</option>
                        <option value="3">3s</option>
                    </select>
                </div>
                <div class="vw-fade-group">
                    <label>{{ __('Fade Out') }}</label>
                    <select wire:model.live="assembly.music.fadeOut" class="vw-select-input small">
                        <option value="0">{{ __('None') }}</option>
                        <option value="2">2s</option>
                        <option value="3">3s</option>
                        <option value="5">5s</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Audio Tips --}}
    <div class="vw-audio-tips">
        <div class="vw-tip-icon">💡</div>
        <div class="vw-tip-text">
            {{ __('Keep music volume around 20-30% for clear voiceover. Enable auto-duck to automatically lower music during speech.') }}
        </div>
    </div>
</div>

<style>
    .vw-audio-tab {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .vw-audio-viz {
        padding: 1rem;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.5rem;
        text-align: center;
    }

    .vw-viz-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.75rem;
    }

    .vw-viz-bars {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        gap: 4px;
        height: 40px;
        margin-bottom: 0.75rem;
    }

    .vw-bar {
        width: 6px;
        background: linear-gradient(to top, #8b5cf6, #a78bfa);
        border-radius: 3px;
        animation: pulse 1.5s ease-in-out infinite;
    }

    .vw-bar.alt {
        background: linear-gradient(to top, #06b6d4, #22d3ee);
        animation-delay: 0.2s;
    }

    @keyframes pulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
    }

    .vw-viz-legend {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        font-size: 0.75rem;
    }

    .vw-legend-voice {
        color: #a78bfa;
    }

    .vw-legend-music {
        color: #22d3ee;
    }

    .vw-setting-section {
        padding: 0.75rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
    }

    .vw-section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.75rem;
    }

    .vw-setting-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .vw-setting-row.toggle-row {
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 0.4rem;
    }

    .vw-setting-row.toggle-row.small {
        padding: 0.4rem 0.5rem;
    }

    .vw-setting-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-setting-value {
        font-size: 0.8rem;
        color: #8b5cf6;
        font-weight: 600;
    }

    .vw-toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }

    .vw-toggle-switch.small {
        width: 36px;
        height: 20px;
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

    .vw-toggle-switch.small .vw-toggle-slider:before {
        height: 14px;
        width: 14px;
    }

    .vw-toggle-switch input:checked + .vw-toggle-slider {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
    }

    .vw-toggle-switch input:checked + .vw-toggle-slider:before {
        transform: translateX(20px);
    }

    .vw-toggle-switch.small input:checked + .vw-toggle-slider:before {
        transform: translateX(16px);
    }

    .vw-music-controls {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 0.75rem;
        transition: opacity 0.3s;
    }

    .vw-music-controls.disabled {
        opacity: 0.4;
        pointer-events: none;
    }

    .vw-setting-group {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .vw-browse-btn {
        padding: 0.35rem 0.6rem;
        border-radius: 0.35rem;
        border: 1px solid rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.1);
        color: #a78bfa;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-browse-btn:hover {
        background: rgba(139, 92, 246, 0.2);
    }

    .vw-select-input {
        width: 100%;
        padding: 0.5rem 0.6rem;
        border-radius: 0.4rem;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(0, 0, 0, 0.3);
        color: white;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .vw-select-input.small {
        padding: 0.4rem 0.5rem;
        font-size: 0.75rem;
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

    .vw-range-input.voice::-webkit-slider-thumb {
        background: #8b5cf6;
    }

    .vw-range-input.music::-webkit-slider-thumb {
        background: #06b6d4;
    }

    .vw-fade-settings {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        padding-top: 0.5rem;
    }

    .vw-fade-group {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .vw-fade-group label {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-audio-tips {
        display: flex;
        gap: 0.75rem;
        padding: 0.75rem;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 0.5rem;
    }

    .vw-tip-icon {
        font-size: 1rem;
    }

    .vw-tip-text {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.4;
    }
</style>
