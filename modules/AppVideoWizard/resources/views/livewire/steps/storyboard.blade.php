{{-- Step 4: Storyboard --}}
<style>
    .vw-storyboard-step {
        width: 100%;
    }

    .vw-storyboard-card {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.95) 0%, rgba(20, 20, 35, 0.98) 100%) !important;
        border: 1px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 1rem !important;
        padding: 1.5rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }

    .vw-storyboard-header {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        margin-bottom: 1rem !important;
    }

    .vw-storyboard-icon {
        width: 42px !important;
        height: 42px !important;
        min-width: 42px !important;
        background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
        border-radius: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.25rem !important;
    }

    .vw-storyboard-title {
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        margin: 0 !important;
    }

    .vw-storyboard-subtitle {
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.5) !important;
        margin-top: 0.15rem !important;
    }

    /* AI Model Selector */
    .vw-model-selector {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-model-selector-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-model-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .vw-model-btn {
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        font-size: 0.75rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.15rem;
        transition: all 0.2s;
    }

    .vw-model-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
    }

    .vw-model-btn.selected {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.2);
        color: white;
    }

    .vw-model-btn-name {
        font-weight: 600;
    }

    .vw-model-btn-cost {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-model-btn.selected .vw-model-btn-cost {
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-model-description {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 0.35rem;
    }

    /* Visual Style Controls */
    .vw-visual-style-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-visual-style-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .vw-visual-style-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-pro-badge {
        font-size: 0.55rem;
        padding: 0.15rem 0.4rem;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        border-radius: 0.75rem;
        color: white;
        font-weight: 600;
    }

    .vw-new-badge {
        font-size: 0.55rem;
        padding: 0.15rem 0.4rem;
        background: linear-gradient(135deg, #10b981, #06b6d4);
        border-radius: 0.75rem;
        color: white;
        font-weight: 600;
    }

    .vw-style-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }

    @media (max-width: 768px) {
        .vw-style-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .vw-style-select-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .vw-style-select-label {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-style-select {
        width: 100%;
        padding: 0.4rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.35rem;
        color: white;
        font-size: 0.7rem;
    }

    .vw-style-hint {
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.6rem;
        margin-top: 0.4rem;
    }

    /* Scene Memory Section */
    .vw-scene-memory-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-scene-memory-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .vw-scene-memory-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-memory-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .vw-memory-cards {
            grid-template-columns: 1fr;
        }
    }

    .vw-memory-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        padding: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-memory-card-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-memory-card-icon {
        font-size: 1rem;
    }

    .vw-memory-card-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }

    .vw-memory-card-desc {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-memory-card-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-memory-edit-btn {
        padding: 0.25rem 0.5rem;
        background: rgba(139, 92, 246, 0.2);
        border: 1px solid rgba(139, 92, 246, 0.4);
        border-radius: 0.25rem;
        color: #c4b5fd;
        font-size: 0.65rem;
        cursor: pointer;
    }

    .vw-memory-toggle {
        width: 16px;
        height: 16px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 0.25rem;
        background: transparent;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vw-memory-toggle.active {
        background: #8b5cf6;
        border-color: #8b5cf6;
    }

    /* Storyboard Grid */
    .vw-storyboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    /* Scene Card */
    .vw-scene-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        overflow: hidden;
        transition: all 0.2s;
    }

    .vw-scene-card:hover {
        border-color: rgba(139, 92, 246, 0.3);
    }

    /* Scene Image Container */
    .vw-scene-image-container {
        position: relative;
        aspect-ratio: 16/9;
        background: rgba(0, 0, 0, 0.3);
    }

    .vw-scene-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-scene-status-badge {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .vw-scene-status-badge.ai {
        background: rgba(139, 92, 246, 0.9);
        color: white;
    }

    .vw-scene-status-badge.stock {
        background: rgba(16, 185, 129, 0.9);
        color: white;
    }

    .vw-scene-status-badge.generating {
        background: rgba(251, 191, 36, 0.9);
        color: white;
    }

    /* Empty State */
    .vw-scene-empty {
        height: 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        border: 2px dashed rgba(255, 255, 255, 0.2);
        border-radius: 0.5rem;
    }

    .vw-scene-empty-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .vw-scene-empty-buttons {
        display: flex;
        gap: 0.5rem;
        width: 100%;
    }

    .vw-scene-empty-btn {
        flex: 1;
        padding: 0.6rem 0.5rem;
        border-radius: 0.5rem;
        border: 1px solid;
        color: white;
        cursor: pointer;
        font-size: 0.7rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.2s;
    }

    .vw-scene-empty-btn.ai {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(6, 182, 212, 0.3));
        border-color: rgba(139, 92, 246, 0.4);
    }

    .vw-scene-empty-btn.ai:hover {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.4), rgba(6, 182, 212, 0.4));
    }

    .vw-scene-empty-btn.stock {
        background: rgba(16, 185, 129, 0.2);
        border-color: rgba(16, 185, 129, 0.4);
    }

    .vw-scene-empty-btn.stock:hover {
        background: rgba(16, 185, 129, 0.3);
    }

    .vw-scene-empty-btn-icon {
        font-size: 1.1rem;
    }

    .vw-scene-empty-btn-cost {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-scene-empty-btn.stock .vw-scene-empty-btn-cost {
        color: rgba(16, 185, 129, 0.8);
    }

    /* Generating State */
    .vw-scene-generating {
        height: 160px;
        background: rgba(139, 92, 246, 0.1);
        border-radius: 0.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    .vw-spinner {
        width: 2rem;
        height: 2rem;
        border: 3px solid rgba(139, 92, 246, 0.3);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        margin-bottom: 0.75rem;
        animation: vw-spin 0.8s linear infinite;
    }

    .vw-generating-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.8rem;
    }

    /* Scene Info */
    .vw-scene-info {
        padding: 0.75rem 1rem;
    }

    .vw-scene-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border-radius: 50%;
        font-size: 0.7rem;
        font-weight: 600;
        color: white;
        margin-right: 0.5rem;
    }

    .vw-scene-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-scene-desc {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 0.35rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .vw-scene-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        flex-wrap: wrap;
    }

    .vw-scene-duration {
        font-size: 0.65rem;
        padding: 0.15rem 0.35rem;
        background: rgba(6, 182, 212, 0.2);
        color: #67e8f9;
        border-radius: 0.2rem;
    }

    .vw-scene-transition {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.4);
    }

    /* Scene Actions */
    .vw-scene-actions {
        padding: 0.5rem 1rem 1rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .vw-scene-action-btn {
        flex: 1;
        padding: 0.4rem 0.5rem;
        border-radius: 0.35rem;
        font-size: 0.7rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        transition: all 0.2s;
    }

    .vw-scene-action-btn.regenerate {
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
        color: #c4b5fd;
    }

    .vw-scene-action-btn.regenerate:hover {
        background: rgba(139, 92, 246, 0.25);
    }

    .vw-scene-action-btn.edit {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-scene-action-btn.edit:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    /* Progress Stats */
    .vw-progress-stats {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-progress-stat {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-progress-stat-icon {
        font-size: 0.9rem;
    }

    .vw-progress-stat-value {
        font-size: 0.85rem;
        font-weight: 600;
        color: #10b981;
    }

    .vw-progress-stat-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Bulk Actions */
    .vw-bulk-actions {
        display: flex;
        gap: 0.5rem;
        margin-left: auto;
    }

    .vw-bulk-action-btn {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-bulk-action-btn.primary {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        color: white;
    }

    .vw-bulk-action-btn.primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
    }

    .vw-bulk-action-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Alert */
    .vw-alert {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-alert.warning {
        background: rgba(251, 191, 36, 0.15);
        border: 1px solid rgba(251, 191, 36, 0.3);
        color: #fbbf24;
    }

    .vw-alert.error {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #f87171;
    }

    .vw-alert-icon {
        font-size: 1.25rem;
    }

    .vw-alert-text {
        font-size: 0.9rem;
    }

    /* Modal Styles */
    .vw-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
    }

    .vw-modal {
        background: linear-gradient(135deg, rgba(30, 30, 45, 0.98) 0%, rgba(20, 20, 35, 0.99) 100%);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 1rem;
        width: 100%;
        max-width: 700px;
        max-height: 80vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .vw-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .vw-modal-title {
        font-size: 1rem;
        font-weight: 600;
        color: white;
    }

    .vw-modal-close {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.5);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }

    .vw-modal-close:hover {
        color: white;
    }

    .vw-modal-body {
        padding: 1.5rem;
        overflow-y: auto;
        flex: 1;
    }

    .vw-modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    /* Stock Browser Styles */
    .vw-stock-search-bar {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .vw-stock-search-input {
        flex: 1;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        color: white;
        font-size: 0.9rem;
    }

    .vw-stock-search-btn {
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        border-radius: 0.5rem;
        color: white;
        font-weight: 600;
        cursor: pointer;
    }

    .vw-stock-search-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .vw-stock-filters {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .vw-stock-filter-select {
        padding: 0.5rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.35rem;
        color: white;
        font-size: 0.8rem;
    }

    .vw-stock-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.75rem;
    }

    .vw-stock-item {
        position: relative;
        aspect-ratio: 16/9;
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s;
    }

    .vw-stock-item:hover {
        border-color: #8b5cf6;
        transform: scale(1.02);
    }

    .vw-stock-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-stock-item-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
        display: flex;
        align-items: flex-end;
        padding: 0.5rem;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .vw-stock-item:hover .vw-stock-item-overlay {
        opacity: 1;
    }

    .vw-stock-item-author {
        color: white;
        font-size: 0.65rem;
    }

    /* Edit Prompt Styles */
    .vw-prompt-textarea {
        width: 100%;
        min-height: 150px;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.5rem;
        color: white;
        font-size: 0.9rem;
        resize: vertical;
        line-height: 1.5;
    }

    .vw-prompt-hint {
        margin-top: 0.5rem;
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.75rem;
    }

    .vw-modal-btn {
        padding: 0.6rem 1.25rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
    }

    .vw-modal-btn.secondary {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }

    .vw-modal-btn.primary {
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border: none;
        color: white;
    }
</style>

<div class="vw-storyboard-step">
    {{-- Error Alert --}}
    @if($error)
        <div class="vw-alert error">
            <span class="vw-alert-icon">X</span>
            <span class="vw-alert-text">{{ $error }}</span>
        </div>
    @endif

    @if(empty($script['scenes']))
        <div class="vw-alert warning">
            <span class="vw-alert-icon">!</span>
            <span class="vw-alert-text">{{ __('Please generate a script first before creating the storyboard.') }}</span>
        </div>
    @else
        {{-- Main Card --}}
        <div class="vw-storyboard-card">
            {{-- Header --}}
            <div class="vw-storyboard-header">
                <div class="vw-storyboard-icon">*</div>
                <div style="flex: 1;">
                    <h2 class="vw-storyboard-title">{{ __('Storyboard') }}</h2>
                    <p class="vw-storyboard-subtitle">
                        {{ __('Visual preview of each scene') }} -
                        {{ count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl']))) }}/{{ count($script['scenes']) }}
                        {{ __('images ready') }}
                    </p>
                </div>
            </div>

            {{-- AI Model Selector --}}
            <div class="vw-model-selector">
                <div class="vw-model-selector-label">
                    <span>*</span>
                    <span>{{ __('AI Model for Image Generation:') }}</span>
                </div>
                <div class="vw-model-buttons">
                    @php
                        $imageModels = [
                            'hidream' => ['name' => 'HiDream', 'cost' => 2, 'desc' => 'Artistic & cinematic style'],
                            'nanobanana-pro' => ['name' => 'NanoBanana Pro', 'cost' => 3, 'desc' => 'High quality, fast generation'],
                            'nanobanana' => ['name' => 'NanoBanana', 'cost' => 1, 'desc' => 'Quick drafts, lower cost'],
                        ];
                        $selectedModel = $storyboard['imageModel'] ?? 'nanobanana-pro';
                    @endphp
                    @foreach($imageModels as $modelId => $model)
                        <button type="button"
                                class="vw-model-btn {{ $selectedModel === $modelId ? 'selected' : '' }}"
                                wire:click="$set('storyboard.imageModel', '{{ $modelId }}')">
                            <span class="vw-model-btn-name">{{ $model['name'] }}</span>
                            <span class="vw-model-btn-cost">{{ $model['cost'] }} {{ __('tokens') }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="vw-model-description">
                    {{ $imageModels[$selectedModel]['desc'] ?? '' }}
                </div>
            </div>

            {{-- Visual Style Controls --}}
            <div class="vw-visual-style-section">
                <div class="vw-visual-style-header">
                    <div class="vw-visual-style-label">
                        <span>*</span>
                        <span>{{ __('Visual Style') }}</span>
                    </div>
                    <span class="vw-pro-badge">PRO</span>
                </div>
                <div class="vw-style-grid">
                    {{-- Mood --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Mood') }}</span>
                        <select class="vw-style-select" wire:model.live="storyboard.visualStyle.mood">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="epic">{{ __('Epic') }}</option>
                            <option value="intimate">{{ __('Intimate') }}</option>
                            <option value="mysterious">{{ __('Mysterious') }}</option>
                            <option value="energetic">{{ __('Energetic') }}</option>
                            <option value="contemplative">{{ __('Contemplative') }}</option>
                            <option value="tense">{{ __('Tense') }}</option>
                            <option value="hopeful">{{ __('Hopeful') }}</option>
                            <option value="professional">{{ __('Professional') }}</option>
                        </select>
                    </div>
                    {{-- Lighting --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Lighting') }}</span>
                        <select class="vw-style-select" wire:model.live="storyboard.visualStyle.lighting">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="natural">{{ __('Natural') }}</option>
                            <option value="golden-hour">{{ __('Golden Hour') }}</option>
                            <option value="blue-hour">{{ __('Blue Hour') }}</option>
                            <option value="high-key">{{ __('High Key') }}</option>
                            <option value="low-key">{{ __('Low Key/Noir') }}</option>
                            <option value="neon">{{ __('Neon') }}</option>
                        </select>
                    </div>
                    {{-- Colors --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Colors') }}</span>
                        <select class="vw-style-select" wire:model.live="storyboard.visualStyle.colorPalette">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="teal-orange">{{ __('Teal/Orange') }}</option>
                            <option value="warm-tones">{{ __('Warm') }}</option>
                            <option value="cool-tones">{{ __('Cool') }}</option>
                            <option value="desaturated">{{ __('Desaturated') }}</option>
                            <option value="vibrant">{{ __('Vibrant') }}</option>
                            <option value="pastel">{{ __('Pastel') }}</option>
                        </select>
                    </div>
                    {{-- Shot --}}
                    <div class="vw-style-select-wrapper">
                        <span class="vw-style-select-label">{{ __('Shot') }}</span>
                        <select class="vw-style-select" wire:model.live="storyboard.visualStyle.composition">
                            <option value="">{{ __('Auto') }}</option>
                            <option value="wide">{{ __('Wide') }}</option>
                            <option value="medium">{{ __('Medium') }}</option>
                            <option value="close-up">{{ __('Close-up') }}</option>
                            <option value="extreme-close-up">{{ __('Extreme CU') }}</option>
                            <option value="low-angle">{{ __('Low Angle') }}</option>
                            <option value="birds-eye">{{ __("Bird's Eye") }}</option>
                        </select>
                    </div>
                </div>
                <div class="vw-style-hint">
                    * {{ __('"Auto" uses genre-appropriate defaults based on your content settings') }}
                </div>
            </div>

            {{-- Scene Memory Section --}}
            <div class="vw-scene-memory-section">
                <div class="vw-scene-memory-header">
                    <div class="vw-scene-memory-label">
                        <span>*</span>
                        <span>{{ __('Scene Memory') }}</span>
                    </div>
                    <span class="vw-new-badge">NEW</span>
                </div>
                <p style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-bottom: 0.75rem;">
                    {{ __('Visual consistency with Style, Character & Location Bibles') }}
                </p>
                <div class="vw-memory-cards">
                    {{-- Style Bible --}}
                    <div class="vw-memory-card">
                        <div class="vw-memory-card-info">
                            <span class="vw-memory-card-icon">*</span>
                            <div>
                                <div class="vw-memory-card-title">{{ __('Style Bible') }}</div>
                                <div class="vw-memory-card-desc">{{ __('Visual DNA') }}</div>
                            </div>
                        </div>
                        <div class="vw-memory-card-actions">
                            <button class="vw-memory-edit-btn" wire:click="toggleStyleBible">
                                {{ __('Edit') }}
                            </button>
                            <button class="vw-memory-toggle {{ ($sceneMemory['styleBible']['enabled'] ?? false) ? 'active' : '' }}"
                                    wire:click="toggleStyleBible">
                                @if($sceneMemory['styleBible']['enabled'] ?? false)
                                    <span style="color: white; font-size: 10px;">V</span>
                                @endif
                            </button>
                        </div>
                    </div>

                    {{-- Character Bible --}}
                    <div class="vw-memory-card">
                        <div class="vw-memory-card-info">
                            <span class="vw-memory-card-icon">*</span>
                            <div>
                                <div class="vw-memory-card-title">{{ __('Character Bible') }}</div>
                                <div class="vw-memory-card-desc">{{ __('Consistent faces') }}</div>
                            </div>
                        </div>
                        <div class="vw-memory-card-actions">
                            <button class="vw-memory-edit-btn" wire:click="toggleCharacterBible">
                                {{ __('Edit') }}
                            </button>
                            <button class="vw-memory-toggle {{ ($sceneMemory['characterBible']['enabled'] ?? false) ? 'active' : '' }}"
                                    wire:click="toggleCharacterBible">
                                @if($sceneMemory['characterBible']['enabled'] ?? false)
                                    <span style="color: white; font-size: 10px;">V</span>
                                @endif
                            </button>
                        </div>
                    </div>

                    {{-- Location Bible --}}
                    <div class="vw-memory-card">
                        <div class="vw-memory-card-info">
                            <span class="vw-memory-card-icon">*</span>
                            <div>
                                <div class="vw-memory-card-title">{{ __('Location Bible') }}</div>
                                <div class="vw-memory-card-desc">{{ __('Consistent environments') }}</div>
                            </div>
                        </div>
                        <div class="vw-memory-card-actions">
                            <button class="vw-memory-edit-btn" wire:click="toggleLocationBible">
                                {{ __('Edit') }}
                            </button>
                            <button class="vw-memory-toggle {{ ($sceneMemory['locationBible']['enabled'] ?? false) ? 'active' : '' }}"
                                    wire:click="toggleLocationBible">
                                @if($sceneMemory['locationBible']['enabled'] ?? false)
                                    <span style="color: white; font-size: 10px;">V</span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress Stats & Bulk Actions --}}
        <div class="vw-progress-stats">
            <div class="vw-progress-stat">
                <span class="vw-progress-stat-icon">*</span>
                <span class="vw-progress-stat-value">{{ count(array_filter($storyboard['scenes'] ?? [], fn($s) => !empty($s['imageUrl']))) }}</span>
                <span class="vw-progress-stat-label">{{ __('images') }}</span>
            </div>
            <div class="vw-progress-stat">
                <span class="vw-progress-stat-icon">*</span>
                <span class="vw-progress-stat-value">{{ count($script['scenes']) }}</span>
                <span class="vw-progress-stat-label">{{ __('scenes') }}</span>
            </div>
            <div class="vw-bulk-actions">
                <button class="vw-bulk-action-btn primary"
                        wire:click="$dispatch('generate-all-images')"
                        wire:loading.attr="disabled"
                        wire:target="generateAllImages">
                    <span wire:loading.remove wire:target="generateAllImages">
                        * {{ __('Generate All Images') }}
                    </span>
                    <span wire:loading wire:target="generateAllImages">
                        <svg style="width: 16px; height: 16px; animation: vw-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                        {{ __('Generating...') }}
                    </span>
                </button>
            </div>
        </div>

        {{-- Storyboard Grid --}}
        <div class="vw-storyboard-grid">
            @foreach($script['scenes'] as $index => $scene)
                @php
                    $storyboardScene = $storyboard['scenes'][$index] ?? null;
                    $imageUrl = $storyboardScene['imageUrl'] ?? null;
                    $status = $storyboardScene['status'] ?? 'pending';
                    $source = $storyboardScene['source'] ?? 'ai';
                @endphp
                <div class="vw-scene-card">
                    {{-- Image Container --}}
                    <div class="vw-scene-image-container">
                        @if($status === 'generating' || ($isLoading && !$imageUrl))
                            {{-- Generating State --}}
                            <div class="vw-scene-generating">
                                <div class="vw-spinner"></div>
                                <span class="vw-generating-text">{{ __('Generating...') }}</span>
                            </div>
                        @elseif($imageUrl)
                            {{-- Image Ready --}}
                            <img src="{{ $imageUrl }}" alt="{{ $scene['title'] ?? 'Scene ' . ($index + 1) }}" class="vw-scene-image">
                            <div class="vw-scene-status-badge {{ $source === 'stock' ? 'stock' : 'ai' }}">
                                @if($source === 'stock')
                                    * {{ __('Stock') }}
                                @else
                                    * {{ __('AI') }}
                                @endif
                            </div>
                        @else
                            {{-- Empty State --}}
                            <div class="vw-scene-empty">
                                <div class="vw-scene-empty-text">{{ __('Choose image source:') }}</div>
                                <div class="vw-scene-empty-buttons">
                                    <button type="button"
                                            class="vw-scene-empty-btn ai"
                                            wire:click="$dispatch('generate-image', { sceneIndex: {{ $index }}, sceneId: '{{ $scene['id'] }}' })"
                                            wire:loading.attr="disabled">
                                        <span class="vw-scene-empty-btn-icon">*</span>
                                        <span>{{ __('AI Generate') }}</span>
                                        <span class="vw-scene-empty-btn-cost">{{ $imageModels[$selectedModel]['cost'] ?? 2 }} {{ __('tokens') }}</span>
                                    </button>
                                    <button type="button"
                                            class="vw-scene-empty-btn stock"
                                            wire:click="openStockBrowser({{ $index }})">
                                        <span class="vw-scene-empty-btn-icon">*</span>
                                        <span>{{ __('Stock Media') }}</span>
                                        <span class="vw-scene-empty-btn-cost">{{ __('FREE') }}</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Scene Info --}}
                    <div class="vw-scene-info">
                        <div>
                            <span class="vw-scene-number">{{ $index + 1 }}</span>
                            <span class="vw-scene-title">{{ $scene['title'] ?? __('Scene') . ' ' . ($index + 1) }}</span>
                        </div>
                        <p class="vw-scene-desc">{{ Str::limit($scene['visualDescription'] ?? $scene['narration'] ?? '', 80) }}</p>
                        <div class="vw-scene-meta">
                            <span class="vw-scene-duration">{{ $scene['duration'] ?? 8 }}s</span>
                            <span class="vw-scene-transition">{{ $scene['transition'] ?? 'cut' }}</span>
                        </div>
                    </div>

                    {{-- Actions (only show if image exists) --}}
                    @if($imageUrl)
                        <div class="vw-scene-actions">
                            <button type="button"
                                    class="vw-scene-action-btn regenerate"
                                    wire:click="$dispatch('regenerate-image', { sceneIndex: {{ $index }} })"
                                    wire:loading.attr="disabled">
                                * {{ __('Regenerate') }}
                            </button>
                            <button type="button"
                                    class="vw-scene-action-btn edit"
                                    wire:click="openEditPrompt({{ $index }})">
                                * {{ __('Edit Prompt') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Stock Media Browser Modal --}}
    @if($showStockBrowser)
        <div class="vw-modal-overlay" wire:click.self="closeStockBrowser">
            <div class="vw-modal">
                <div class="vw-modal-header">
                    <h3 class="vw-modal-title">* {{ __('Stock Media Browser') }}</h3>
                    <button class="vw-modal-close" wire:click="closeStockBrowser">&times;</button>
                </div>
                <div class="vw-modal-body">
                    {{-- Search Bar --}}
                    <div class="vw-stock-search-bar">
                        <input type="text"
                               class="vw-stock-search-input"
                               placeholder="{{ __('Search for images...') }}"
                               wire:model="stockSearchQuery"
                               wire:keydown.enter="searchStockMedia">
                        <button class="vw-stock-search-btn"
                                wire:click="searchStockMedia"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="searchStockMedia">{{ __('Search') }}</span>
                            <span wire:loading wire:target="searchStockMedia">...</span>
                        </button>
                    </div>

                    {{-- Filters --}}
                    <div class="vw-stock-filters">
                        <select class="vw-stock-filter-select" wire:model.live="stockOrientation">
                            <option value="landscape">{{ __('Landscape') }}</option>
                            <option value="portrait">{{ __('Portrait') }}</option>
                            <option value="square">{{ __('Square') }}</option>
                        </select>
                        <select class="vw-stock-filter-select" wire:model.live="stockMediaType">
                            <option value="image">{{ __('Images') }}</option>
                            <option value="video">{{ __('Videos') }}</option>
                        </select>
                    </div>

                    {{-- Results Grid --}}
                    @if(count($stockSearchResults) > 0)
                        <div class="vw-stock-grid">
                            @foreach($stockSearchResults as $mediaIndex => $media)
                                <div class="vw-stock-item" wire:click="selectStockMedia({{ $mediaIndex }})">
                                    <img src="{{ $media['thumbnail'] }}" alt="{{ $media['author'] ?? 'Stock' }}">
                                    <div class="vw-stock-item-overlay">
                                        <span class="vw-stock-item-author">{{ $media['author'] ?? 'Pexels' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($stockSearchQuery && !$stockSearching)
                        <div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.5);">
                            {{ __('No results found. Try a different search term.') }}
                        </div>
                    @else
                        <div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.5);">
                            {{ __('Search for stock images above') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Prompt Modal --}}
    @if($showEditPromptModal)
        <div class="vw-modal-overlay" wire:click.self="closeEditPrompt">
            <div class="vw-modal">
                <div class="vw-modal-header">
                    <h3 class="vw-modal-title">* {{ __('Edit Image Prompt') }}</h3>
                    <button class="vw-modal-close" wire:click="closeEditPrompt">&times;</button>
                </div>
                <div class="vw-modal-body">
                    <textarea class="vw-prompt-textarea"
                              wire:model="editPromptText"
                              placeholder="{{ __('Describe the image you want to generate...') }}"></textarea>
                    <p class="vw-prompt-hint">
                        * {{ __('Be specific about lighting, mood, composition, and style for best results.') }}
                    </p>
                </div>
                <div class="vw-modal-footer">
                    <button class="vw-modal-btn secondary" wire:click="closeEditPrompt">
                        {{ __('Cancel') }}
                    </button>
                    <button class="vw-modal-btn primary" wire:click="saveAndRegeneratePrompt">
                        {{ __('Save & Regenerate') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
