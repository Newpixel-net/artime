{{-- Step: Collage Builder - Unified Collage-First Workflow --}}

{{-- Embedded CSS for Collage Step --}}
<style>
    .vw-collage-step {
        width: 100%;
    }

    .vw-collage-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-collage-header {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        margin-bottom: 1.25rem !important;
    }

    .vw-collage-icon {
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%) !important;
        border-radius: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.5rem !important;
    }

    .vw-collage-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-collage-subtitle {
        font-size: 0.85rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.25rem !important;
    }

    /* Tabs */
    .vw-collage-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0.75rem;
    }

    .vw-collage-tab {
        padding: 0.6rem 1.25rem;
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-collage-tab:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(139, 92, 246, 0.4);
    }

    .vw-collage-tab.active {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(236, 72, 153, 0.2));
        border-color: #8b5cf6;
        color: white;
    }

    .vw-collage-tab-icon {
        font-size: 1rem;
    }

    /* Image Upload Zone */
    .vw-upload-zone {
        border: 2px dashed rgba(139, 92, 246, 0.4);
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        background: rgba(139, 92, 246, 0.05);
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 1.5rem;
    }

    .vw-upload-zone:hover {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-upload-zone.dragging {
        border-color: #ec4899;
        background: rgba(236, 72, 153, 0.1);
        transform: scale(1.02);
    }

    .vw-upload-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .vw-upload-text {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }

    .vw-upload-hint {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.8rem;
    }

    /* Image Grid */
    .vw-image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-image-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 0.75rem;
        overflow: hidden;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-image-item:hover {
        border-color: rgba(139, 92, 246, 0.5);
        transform: translateY(-2px);
    }

    .vw-image-item.selected {
        border-color: #8b5cf6;
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.4);
    }

    .vw-image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-image-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.8) 100%);
        opacity: 0;
        transition: opacity 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 0.75rem;
    }

    .vw-image-item:hover .vw-image-overlay {
        opacity: 1;
    }

    .vw-image-number {
        position: absolute;
        top: 0.5rem;
        left: 0.5rem;
        width: 24px;
        height: 24px;
        background: rgba(0, 0, 0, 0.7);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-image-actions {
        display: flex;
        gap: 0.5rem;
    }

    .vw-image-action-btn {
        padding: 0.35rem 0.6rem;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 0.35rem;
        color: white;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-image-action-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .vw-image-action-btn.delete {
        background: rgba(239, 68, 68, 0.4);
    }

    .vw-image-action-btn.delete:hover {
        background: rgba(239, 68, 68, 0.6);
    }

    /* Layout Selection */
    .vw-layout-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-layout-card {
        background: rgba(255, 255, 255, 0.03);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-layout-card:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-layout-card.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.2);
        box-shadow: 0 0 15px rgba(139, 92, 246, 0.3);
    }

    .vw-layout-card.disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .vw-layout-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .vw-layout-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.25rem;
    }

    .vw-layout-desc {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-layout-images {
        font-size: 0.65rem;
        color: rgba(139, 92, 246, 0.8);
        margin-top: 0.35rem;
    }

    /* Animation Style Selection */
    .vw-animation-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-animation-card {
        background: rgba(255, 255, 255, 0.03);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-animation-card:hover {
        border-color: rgba(236, 72, 153, 0.4);
        background: rgba(236, 72, 153, 0.1);
    }

    .vw-animation-card.selected {
        border-color: #ec4899;
        background: rgba(236, 72, 153, 0.2);
        box-shadow: 0 0 15px rgba(236, 72, 153, 0.3);
    }

    .vw-animation-name {
        font-size: 0.95rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.35rem;
    }

    .vw-animation-desc {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.5rem;
    }

    .vw-animation-specs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .vw-animation-spec {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 0.25rem;
        color: rgba(255, 255, 255, 0.7);
    }

    /* Preview Area */
    .vw-preview-container {
        background: #000;
        border-radius: 1rem;
        aspect-ratio: 16/9;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        margin-bottom: 1.5rem;
    }

    .vw-preview-placeholder {
        text-align: center;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-preview-placeholder-icon {
        font-size: 3rem;
        margin-bottom: 0.75rem;
    }

    .vw-collage-preview-grid {
        display: grid;
        width: 100%;
        height: 100%;
        padding: 8px;
        gap: 8px;
    }

    .vw-collage-preview-item {
        overflow: hidden;
        border-radius: 4px;
    }

    .vw-collage-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Stats Bar */
    .vw-stats-bar {
        display: flex;
        gap: 1.5rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .vw-stat-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-stat-icon {
        font-size: 1.25rem;
    }

    .vw-stat-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
    }

    .vw-stat-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Action Buttons */
    .vw-collage-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .vw-action-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-action-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .vw-action-btn-primary {
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: white;
    }

    .vw-action-btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-action-btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .vw-action-btn-secondary:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .vw-action-btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .vw-action-btn-success:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }

    /* Empty State */
    .vw-empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .vw-empty-state-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 0.5rem;
    }

    .vw-empty-state-desc {
        font-size: 0.85rem;
    }

    /* Settings Panel */
    .vw-settings-panel {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .vw-settings-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-settings-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .vw-settings-row:last-child {
        border-bottom: none;
    }

    .vw-settings-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-settings-input {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 0.35rem;
        padding: 0.4rem 0.6rem;
        color: white;
        font-size: 0.8rem;
        width: 80px;
        text-align: center;
    }

    .vw-settings-select {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 0.35rem;
        padding: 0.4rem 0.6rem;
        color: white;
        font-size: 0.8rem;
    }

    /* Badge */
    .vw-collage-badge {
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.5rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vw-collage-badge-new {
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: white;
    }

    .vw-collage-badge-ready {
        background: rgba(16, 185, 129, 0.2);
        color: #6ee7b7;
    }

    .vw-collage-badge-draft {
        background: rgba(251, 191, 36, 0.2);
        color: #fcd34d;
    }
</style>

<div class="vw-collage-step">
    {{-- Header Card --}}
    <div class="vw-collage-card">
        <div class="vw-collage-header">
            <div class="vw-collage-icon">
                <i class="fa-solid fa-images"></i>
            </div>
            <div>
                <h2 class="vw-collage-title">
                    {{ __('Collage Builder') }}
                    <span class="vw-collage-badge vw-collage-badge-new">NEW</span>
                </h2>
                <p class="vw-collage-subtitle">{{ __('Create stunning visual collages from your images') }}</p>
            </div>
            <div style="margin-left: auto;">
                @if($collage['status'] === 'ready')
                    <span class="vw-collage-badge vw-collage-badge-ready">{{ __('Ready') }}</span>
                @elseif(count($collage['images']) > 0)
                    <span class="vw-collage-badge vw-collage-badge-draft">{{ __('Draft') }}</span>
                @endif
            </div>
        </div>

        {{-- Stats Bar --}}
        @if(count($collage['images']) > 0)
        <div class="vw-stats-bar">
            <div class="vw-stat-item">
                <span class="vw-stat-icon">🖼️</span>
                <div>
                    <div class="vw-stat-value">{{ count($collage['images']) }}</div>
                    <div class="vw-stat-label">{{ __('Images') }}</div>
                </div>
            </div>
            <div class="vw-stat-item">
                <span class="vw-stat-icon">📐</span>
                <div>
                    <div class="vw-stat-value">{{ ucfirst($collage['layout']['type']) }}</div>
                    <div class="vw-stat-label">{{ __('Layout') }}</div>
                </div>
            </div>
            <div class="vw-stat-item">
                <span class="vw-stat-icon">🎬</span>
                <div>
                    <div class="vw-stat-value">{{ ucfirst($collage['animation']['style']) }}</div>
                    <div class="vw-stat-label">{{ __('Animation') }}</div>
                </div>
            </div>
            <div class="vw-stat-item">
                <span class="vw-stat-icon">⏱️</span>
                <div>
                    @php $duration = $this->calculateCollageDuration(); @endphp
                    <div class="vw-stat-value">{{ $duration > 0 ? round($duration) . 's' : '--' }}</div>
                    <div class="vw-stat-label">{{ __('Duration') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Tabs --}}
        <div class="vw-collage-tabs">
            <button wire:click="setCollageTab('images')"
                    class="vw-collage-tab {{ $collageTab === 'images' ? 'active' : '' }}">
                <span class="vw-collage-tab-icon">🖼️</span>
                {{ __('Images') }}
                @if(count($collage['images']) > 0)
                    <span style="background: rgba(139, 92, 246, 0.3); padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.7rem;">
                        {{ count($collage['images']) }}
                    </span>
                @endif
            </button>
            <button wire:click="setCollageTab('layout')"
                    class="vw-collage-tab {{ $collageTab === 'layout' ? 'active' : '' }}">
                <span class="vw-collage-tab-icon">📐</span>
                {{ __('Layout') }}
            </button>
            <button wire:click="setCollageTab('animation')"
                    class="vw-collage-tab {{ $collageTab === 'animation' ? 'active' : '' }}">
                <span class="vw-collage-tab-icon">✨</span>
                {{ __('Animation') }}
            </button>
            <button wire:click="setCollageTab('preview')"
                    class="vw-collage-tab {{ $collageTab === 'preview' ? 'active' : '' }}">
                <span class="vw-collage-tab-icon">👁️</span>
                {{ __('Preview') }}
            </button>
        </div>

        {{-- Tab Content --}}
        @if($collageTab === 'images')
            {{-- Images Tab --}}
            <div class="vw-tab-content">
                {{-- Upload Zone --}}
                <div class="vw-upload-zone"
                     x-data="{ dragging: false }"
                     x-on:dragover.prevent="dragging = true"
                     x-on:dragleave.prevent="dragging = false"
                     x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                     :class="{ 'dragging': dragging }">
                    <input type="file"
                           wire:model="collageImageUpload"
                           accept="image/*"
                           class="hidden"
                           x-ref="fileInput"
                           id="collageImageUpload">
                    <label for="collageImageUpload" style="cursor: pointer; display: block;">
                        <span class="vw-upload-icon">📷</span>
                        <p class="vw-upload-text">{{ __('Drag & drop images here or click to browse') }}</p>
                        <p class="vw-upload-hint">{{ __('Supports JPG, PNG, WebP - Max 10MB per image') }}</p>
                    </label>
                </div>

                {{-- Upload Progress --}}
                @if($isUploadingCollageImage)
                <div style="text-align: center; padding: 1rem; color: rgba(255,255,255,0.7);">
                    <span style="animation: vw-pulse 1s infinite;">{{ __('Uploading image...') }}</span>
                </div>
                @endif

                {{-- Image Grid --}}
                @if(count($collage['images']) > 0)
                <div class="vw-image-grid">
                    @foreach($collage['images'] as $index => $image)
                    <div class="vw-image-item {{ $collageSelectedImageIndex === $index ? 'selected' : '' }}"
                         wire:click="selectCollageImage({{ $index }})">
                        <span class="vw-image-number">{{ $index + 1 }}</span>
                        <img src="{{ $image['url'] }}" alt="Collage image {{ $index + 1 }}">
                        <div class="vw-image-overlay">
                            <div class="vw-image-actions">
                                <button class="vw-image-action-btn" wire:click.stop="selectCollageImage({{ $index }})" title="{{ __('Edit') }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="vw-image-action-btn delete" wire:click.stop="removeCollageImage({{ $index }})" title="{{ __('Remove') }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Clear All Button --}}
                <div style="margin-top: 1rem;">
                    <button wire:click="clearCollageImages"
                            wire:confirm="{{ __('Are you sure you want to remove all images?') }}"
                            class="vw-action-btn vw-action-btn-secondary">
                        <i class="fa-solid fa-trash"></i>
                        {{ __('Clear All Images') }}
                    </button>
                </div>
                @else
                {{-- Empty State --}}
                <div class="vw-empty-state">
                    <div class="vw-empty-state-icon">🖼️</div>
                    <div class="vw-empty-state-title">{{ __('No images yet') }}</div>
                    <div class="vw-empty-state-desc">{{ __('Upload images to start building your collage') }}</div>
                </div>
                @endif

                {{-- Import from Storyboard --}}
                @if(!empty($this->storyboard['scenes']))
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <button wire:click="importStoryboardImagesToCollage"
                            class="vw-action-btn vw-action-btn-secondary">
                        <i class="fa-solid fa-download"></i>
                        {{ __('Import from Storyboard') }}
                    </button>
                    <p style="font-size: 0.75rem; color: rgba(255,255,255,0.4); margin-top: 0.5rem;">
                        {{ __('Import generated images from your storyboard') }}
                    </p>
                </div>
                @endif
            </div>

        @elseif($collageTab === 'layout')
            {{-- Layout Tab --}}
            <div class="vw-tab-content">
                <div class="vw-layout-grid">
                    @foreach(\Modules\AppVideoWizard\Livewire\VideoWizard::COLLAGE_LAYOUTS as $layoutId => $layout)
                    @php
                        $imageCount = count($collage['images']);
                        $isDisabled = $imageCount > 0 && $imageCount < $layout['minImages'];
                    @endphp
                    <div class="vw-layout-card {{ $collage['layout']['type'] === $layoutId ? 'selected' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                         @if(!$isDisabled)
                         wire:click="setCollageLayout('{{ $layoutId }}')"
                         @endif>
                        <div class="vw-layout-icon">{{ $layout['icon'] }}</div>
                        <div class="vw-layout-name">{{ $layout['name'] }}</div>
                        <div class="vw-layout-desc">{{ $layout['description'] }}</div>
                        <div class="vw-layout-images">{{ $layout['minImages'] }}-{{ $layout['maxImages'] }} images</div>
                    </div>
                    @endforeach
                </div>

                {{-- Layout Settings --}}
                @if($collage['layout']['type'] === 'grid')
                <div class="vw-settings-panel">
                    <div class="vw-settings-title">
                        <i class="fa-solid fa-sliders"></i>
                        {{ __('Grid Settings') }}
                    </div>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Columns') }}</span>
                        <select class="vw-settings-select" wire:model.live="collage.layout.columns">
                            @for($i = 2; $i <= 6; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Gap (px)') }}</span>
                        <input type="number" class="vw-settings-input" wire:model.live="collage.layout.gap" min="0" max="32">
                    </div>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Border Radius (px)') }}</span>
                        <input type="number" class="vw-settings-input" wire:model.live="collage.layout.borderRadius" min="0" max="24">
                    </div>
                </div>
                @endif

                {{-- Background Settings --}}
                <div class="vw-settings-panel">
                    <div class="vw-settings-title">
                        <i class="fa-solid fa-fill-drip"></i>
                        {{ __('Background') }}
                    </div>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Style') }}</span>
                        <select class="vw-settings-select" wire:model.live="collage.layout.backgroundStyle">
                            <option value="solid">{{ __('Solid Color') }}</option>
                            <option value="gradient">{{ __('Gradient') }}</option>
                            <option value="blur">{{ __('Blur') }}</option>
                        </select>
                    </div>
                    @if($collage['layout']['backgroundStyle'] === 'solid')
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Color') }}</span>
                        <input type="color" wire:model.live="collage.layout.backgroundColor" style="width: 60px; height: 32px; border: none; cursor: pointer;">
                    </div>
                    @endif
                </div>
            </div>

        @elseif($collageTab === 'animation')
            {{-- Animation Tab --}}
            <div class="vw-tab-content">
                <div class="vw-animation-grid">
                    @foreach(\Modules\AppVideoWizard\Livewire\VideoWizard::COLLAGE_ANIMATION_STYLES as $styleId => $style)
                    <div class="vw-animation-card {{ $collage['animation']['style'] === $styleId ? 'selected' : '' }}"
                         wire:click="setCollageAnimationStyle('{{ $styleId }}')">
                        <div class="vw-animation-name">{{ $style['name'] }}</div>
                        <div class="vw-animation-desc">{{ $style['description'] }}</div>
                        <div class="vw-animation-specs">
                            <span class="vw-animation-spec">{{ $style['imageDuration'] }}s/image</span>
                            <span class="vw-animation-spec">{{ $style['transitionType'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Animation Settings --}}
                <div class="vw-settings-panel">
                    <div class="vw-settings-title">
                        <i class="fa-solid fa-cog"></i>
                        {{ __('Animation Settings') }}
                    </div>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Image Duration (seconds)') }}</span>
                        <input type="number" class="vw-settings-input" wire:model.live="collage.animation.imageDuration" min="1" max="30">
                    </div>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Transition Duration (seconds)') }}</span>
                        <input type="number" class="vw-settings-input" wire:model.live="collage.animation.transitionDuration" min="0" max="5" step="0.5">
                    </div>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Transition Type') }}</span>
                        <select class="vw-settings-select" wire:model.live="collage.animation.transitionType">
                            <option value="crossfade">{{ __('Crossfade') }}</option>
                            <option value="slide">{{ __('Slide') }}</option>
                            <option value="zoom">{{ __('Zoom') }}</option>
                            <option value="wipe">{{ __('Wipe') }}</option>
                            <option value="cut">{{ __('Cut') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Narrative Generation --}}
                <div class="vw-settings-panel">
                    <div class="vw-settings-title">
                        <i class="fa-solid fa-comment-dots"></i>
                        {{ __('AI Narrative') }}
                        <span class="vw-collage-badge vw-collage-badge-new" style="margin-left: 0.5rem;">BETA</span>
                    </div>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-bottom: 1rem;">
                        {{ __('Generate a voiceover narrative from your images using AI') }}
                    </p>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Style') }}</span>
                        <select class="vw-settings-select" wire:model.live="collage.narrative.style">
                            <option value="documentary">{{ __('Documentary') }}</option>
                            <option value="story">{{ __('Storytelling') }}</option>
                            <option value="poetic">{{ __('Poetic') }}</option>
                            <option value="informative">{{ __('Informative') }}</option>
                        </select>
                    </div>
                    <div class="vw-settings-row">
                        <span class="vw-settings-label">{{ __('Tone') }}</span>
                        <select class="vw-settings-select" wire:model.live="collage.narrative.tone">
                            <option value="neutral">{{ __('Neutral') }}</option>
                            <option value="dramatic">{{ __('Dramatic') }}</option>
                            <option value="uplifting">{{ __('Uplifting') }}</option>
                            <option value="nostalgic">{{ __('Nostalgic') }}</option>
                        </select>
                    </div>
                    <div style="margin-top: 1rem;">
                        <button wire:click="generateCollageNarrative"
                                wire:loading.attr="disabled"
                                wire:target="generateCollageNarrative"
                                class="vw-action-btn vw-action-btn-secondary"
                                {{ count($collage['images']) < 2 ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="generateCollageNarrative">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                {{ __('Generate Narrative') }}
                            </span>
                            <span wire:loading wire:target="generateCollageNarrative">
                                {{ __('Generating...') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

        @elseif($collageTab === 'preview')
            {{-- Preview Tab --}}
            <div class="vw-tab-content">
                @if(count($collage['images']) > 0)
                <div class="vw-preview-container"
                     style="aspect-ratio: {{ str_replace(':', '/', $aspectRatio) }};">
                    @php
                        $layout = $collage['layout'];
                        $gridStyle = "grid-template-columns: repeat({$layout['columns']}, 1fr); gap: {$layout['gap']}px;";
                        if ($layout['backgroundStyle'] === 'solid') {
                            $bgStyle = "background-color: {$layout['backgroundColor']};";
                        } else {
                            $bgStyle = "background-color: #000;";
                        }
                    @endphp
                    <div class="vw-collage-preview-grid" style="{{ $gridStyle }} {{ $bgStyle }}">
                        @foreach($collage['images'] as $image)
                        <div class="vw-collage-preview-item" style="border-radius: {{ $layout['borderRadius'] }}px;">
                            <img src="{{ $image['url'] }}" alt="Preview">
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Preview Info --}}
                <div class="vw-stats-bar">
                    <div class="vw-stat-item">
                        <span class="vw-stat-icon">📐</span>
                        <div>
                            <div class="vw-stat-value">{{ $aspectRatio }}</div>
                            <div class="vw-stat-label">{{ __('Aspect Ratio') }}</div>
                        </div>
                    </div>
                    <div class="vw-stat-item">
                        <span class="vw-stat-icon">⏱️</span>
                        <div>
                            @php $duration = $this->calculateCollageDuration(); @endphp
                            <div class="vw-stat-value">{{ gmdate($duration >= 60 ? "i:s" : "0:s", $duration) }}</div>
                            <div class="vw-stat-label">{{ __('Total Duration') }}</div>
                        </div>
                    </div>
                    <div class="vw-stat-item">
                        <span class="vw-stat-icon">🎞️</span>
                        <div>
                            <div class="vw-stat-value">{{ $collage['output']['resolution'] }}</div>
                            <div class="vw-stat-label">{{ __('Resolution') }}</div>
                        </div>
                    </div>
                </div>
                @else
                <div class="vw-preview-container">
                    <div class="vw-preview-placeholder">
                        <div class="vw-preview-placeholder-icon">🎬</div>
                        <p>{{ __('Add images to see preview') }}</p>
                    </div>
                </div>
                @endif
            </div>
        @endif

        {{-- Footer Actions --}}
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <div class="vw-collage-actions">
                @if(count($collage['images']) > 0 && $collage['status'] !== 'ready')
                <button wire:click="finalizeCollage" class="vw-action-btn vw-action-btn-success">
                    <i class="fa-solid fa-check"></i>
                    {{ __('Finalize Collage') }}
                </button>
                @endif

                @if($collage['status'] === 'ready')
                <button wire:click="convertCollageToScenes" class="vw-action-btn vw-action-btn-primary">
                    <i class="fa-solid fa-arrow-right"></i>
                    {{ __('Convert to Video Scenes') }}
                </button>
                @endif

                <button wire:click="toggleCollageMode" class="vw-action-btn vw-action-btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ __('Back to Video Mode') }}
                </button>
            </div>
        </div>
    </div>
</div>
