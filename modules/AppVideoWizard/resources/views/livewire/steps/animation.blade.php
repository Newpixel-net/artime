{{-- Step 5: Animation Studio Pro --}}
<style>
    /* ========================================
       ANIMATION STUDIO PRO - Full Screen Layout
       ======================================== */

    .vw-animation-studio {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, #0a0a14 0%, #141428 100%);
        z-index: 100;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Top Header Bar */
    .vw-studio-header {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.6rem 1.25rem;
        background: rgba(15, 15, 28, 0.98);
        border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        backdrop-filter: blur(10px);
    }

    .vw-studio-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-studio-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .vw-studio-title {
        font-weight: 700;
        color: white;
        font-size: 1rem;
        letter-spacing: -0.02em;
    }

    .vw-studio-subtitle {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Progress Pills */
    .vw-studio-pills {
        display: flex;
        gap: 0.5rem;
        margin-left: 1.5rem;
    }

    .vw-studio-pill {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.7rem;
        border-radius: 2rem;
        font-size: 0.7rem;
    }

    .vw-studio-pill.voiceover {
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
    }

    .vw-studio-pill.voiceover.complete {
        background: rgba(16, 185, 129, 0.15);
        border-color: rgba(16, 185, 129, 0.3);
    }

    .vw-studio-pill.voiceover .pill-value {
        color: #a78bfa;
        font-weight: 600;
    }

    .vw-studio-pill.voiceover.complete .pill-value {
        color: #10b981;
    }

    .vw-studio-pill.animated {
        background: rgba(6, 182, 212, 0.15);
        border: 1px solid rgba(6, 182, 212, 0.3);
    }

    .vw-studio-pill.animated .pill-value {
        color: #06b6d4;
        font-weight: 600;
    }

    .vw-studio-pill.ready {
        background: rgba(16, 185, 129, 0.15);
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .vw-studio-pill.ready .pill-value {
        color: #10b981;
        font-weight: 600;
    }

    /* Header Actions */
    .vw-studio-actions {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .vw-studio-btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.85rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-studio-btn.back {
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        color: rgba(255, 255, 255, 0.7);
    }

    .vw-studio-btn.back:hover {
        border-color: rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.05);
    }

    .vw-studio-btn.continue {
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.4);
    }

    .vw-studio-btn.continue.enabled {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-weight: 600;
    }

    .vw-studio-btn.continue.enabled:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    /* Main Split Panel Content */
    .vw-studio-content {
        flex: 1;
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 0;
        overflow: hidden;
    }

    @media (max-width: 900px) {
        .vw-studio-content {
            grid-template-columns: 1fr;
        }
        .vw-scene-grid-panel {
            display: none;
        }
    }

    /* ========================================
       LEFT PANEL - Scene Grid
       ======================================== */

    .vw-scene-grid-panel {
        background: rgba(15, 15, 28, 0.98);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .vw-scene-grid-header {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .vw-scene-grid-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.6rem;
    }

    .vw-scene-grid-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-scene-grid-title span {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vw-scene-grid-tools {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .vw-tool-btn {
        width: 26px;
        height: 26px;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .vw-tool-btn:hover {
        border-color: rgba(139, 92, 246, 0.4);
        background: rgba(139, 92, 246, 0.1);
        color: #a78bfa;
    }

    /* Quick Actions */
    .vw-quick-actions {
        display: flex;
        gap: 0.4rem;
    }

    .vw-quick-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        padding: 0.45rem;
        border-radius: 0.4rem;
        border: none;
        color: white;
        font-size: 0.65rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-quick-btn.voice {
        background: linear-gradient(135deg, #8b5cf6, #a855f7);
    }

    .vw-quick-btn.voice:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
    }

    .vw-quick-btn.animate {
        background: linear-gradient(135deg, #06b6d4, #10b981);
    }

    .vw-quick-btn.animate:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(6, 182, 212, 0.4);
    }

    .vw-quick-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Hint */
    .vw-scene-grid-hint {
        padding: 0.35rem 1rem;
        background: rgba(139, 92, 246, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.55rem;
        color: rgba(255, 255, 255, 0.35);
    }

    /* Scrollable Scene List */
    .vw-scene-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.5rem;
    }

    /* Scene Card */
    .vw-scene-card {
        position: relative;
        display: flex;
        gap: 0.6rem;
        padding: 0.5rem;
        margin-bottom: 0.4rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.15s;
    }

    .vw-scene-card:hover {
        border-color: rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.05);
    }

    .vw-scene-card.selected {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.5);
    }

    .vw-scene-card.processing {
        border-color: rgba(251, 191, 36, 0.4);
    }

    /* Progress Ring Container */
    .vw-progress-ring-container {
        position: relative;
        width: 60px;
        height: 60px;
        flex-shrink: 0;
    }

    .vw-progress-ring {
        transform: rotate(-90deg);
    }

    .vw-progress-ring-bg {
        fill: none;
        stroke: rgba(255, 255, 255, 0.1);
        stroke-width: 3;
    }

    .vw-progress-ring-fill {
        fill: none;
        stroke-width: 3;
        stroke-linecap: round;
        transition: stroke-dashoffset 0.5s ease;
    }

    .vw-scene-thumb-inner {
        position: absolute;
        top: 5px;
        left: 5px;
        width: 50px;
        height: 50px;
        border-radius: 0.35rem;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.3);
    }

    .vw-scene-thumb-inner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-scene-thumb-empty {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.3);
        font-size: 1rem;
    }

    .vw-scene-number {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 16px;
        height: 16px;
        background: rgba(0, 0, 0, 0.8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.55rem;
        font-weight: 600;
        color: white;
        z-index: 1;
    }

    /* Scene Info */
    .vw-scene-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .vw-scene-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 0.2rem;
    }

    .vw-scene-duration {
        font-size: 0.6rem;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 0.35rem;
    }

    .vw-scene-status {
        display: flex;
        gap: 0.25rem;
    }

    .vw-status-badge {
        font-size: 0.55rem;
        padding: 0.1rem 0.3rem;
        border-radius: 0.2rem;
        font-weight: 500;
    }

    .vw-status-badge.voice-ready {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .vw-status-badge.voice-pending {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
    }

    .vw-status-badge.animated {
        background: rgba(6, 182, 212, 0.2);
        color: #06b6d4;
    }

    .vw-status-badge.generating {
        background: rgba(139, 92, 246, 0.2);
        color: #a78bfa;
    }

    /* ========================================
       RIGHT PANEL - Detail View
       ======================================== */

    .vw-detail-panel {
        flex: 1;
        background: linear-gradient(180deg, rgba(15, 15, 28, 0.95) 0%, rgba(10, 10, 20, 0.98) 100%);
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .vw-detail-content {
        width: 100%;
        max-width: 700px;
    }

    /* Scene Preview Header */
    .vw-preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .vw-preview-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-preview-title-icon {
        font-size: 1rem;
    }

    .vw-preview-title-text {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
    }

    .vw-preview-badge {
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
        border-radius: 1rem;
        font-weight: 600;
    }

    .vw-preview-badge.ken-burns {
        background: rgba(6, 182, 212, 0.2);
        color: #06b6d4;
    }

    .vw-preview-badge.animated {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .vw-preview-badge.stock {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }

    .vw-preview-tools {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-preview-tool-btn {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.6rem;
        border-radius: 0.35rem;
        font-size: 0.65rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vw-preview-tool-btn.pip {
        border: 1px solid rgba(6, 182, 212, 0.3);
        background: rgba(6, 182, 212, 0.1);
        color: #06b6d4;
    }

    .vw-preview-tool-btn.device {
        border: 1px solid rgba(139, 92, 246, 0.3);
        background: rgba(139, 92, 246, 0.1);
        color: #a78bfa;
    }

    .vw-preview-scene-count {
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.4);
    }

    /* Main Preview Container */
    .vw-preview-container {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(20, 20, 40, 0.6));
        border-radius: 0.75rem;
        overflow: hidden;
        border: 2px solid rgba(139, 92, 246, 0.3);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        margin-bottom: 1rem;
    }

    .vw-preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-preview-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Ken Burns Animation */
    @keyframes kenBurnsPreview {
        0% { transform: scale(1) translate(0, 0); }
        50% { transform: scale(1.08) translate(-1%, -1%); }
        100% { transform: scale(1) translate(0, 0); }
    }

    .vw-ken-burns-preview {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .vw-ken-burns-preview img {
        width: 115%;
        height: 115%;
        object-fit: cover;
        object-position: center;
        animation: kenBurnsPreview 8s ease-in-out infinite alternate;
        transform-origin: center center;
    }

    /* Preview Overlays */
    .vw-preview-play-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.3);
        cursor: pointer;
        transition: background 0.2s;
    }

    .vw-preview-play-overlay:hover {
        background: rgba(0, 0, 0, 0.1);
    }

    .vw-preview-play-btn {
        width: 70px;
        height: 70px;
        background: rgba(139, 92, 246, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
        font-size: 1.75rem;
        padding-left: 4px;
    }

    .vw-preview-status-badge {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        padding: 0.35rem 0.7rem;
        border-radius: 0.35rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: white;
    }

    .vw-preview-status-badge.animated {
        background: rgba(16, 185, 129, 0.9);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .vw-preview-status-badge.generating {
        background: rgba(139, 92, 246, 0.9);
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
    }

    /* Scene Info Overlay */
    .vw-preview-info-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent);
        padding: 2rem 1rem 0.85rem;
    }

    .vw-preview-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .vw-preview-scene-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.2rem;
    }

    .vw-preview-scene-duration {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .vw-preview-quick-actions {
        display: flex;
        gap: 0.4rem;
    }

    .vw-preview-action-btn {
        padding: 0.4rem 0.7rem;
        border-radius: 0.4rem;
        border: none;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        transition: all 0.2s;
    }

    .vw-preview-action-btn.animate {
        background: rgba(6, 182, 212, 0.9);
        color: white;
    }

    .vw-preview-action-btn.animate:hover {
        background: rgba(6, 182, 212, 1);
    }

    /* Empty State */
    .vw-preview-empty {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.3);
    }

    .vw-preview-empty-content {
        text-align: center;
    }

    .vw-preview-empty-icon {
        font-size: 3.5rem;
        margin-bottom: 0.75rem;
    }

    .vw-preview-empty-text {
        font-size: 0.9rem;
        font-weight: 500;
    }

    .vw-preview-empty-hint {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.2);
        margin-top: 0.25rem;
    }

    /* Generating Overlay */
    .vw-preview-generating {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }

    .vw-preview-generating-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid rgba(6, 182, 212, 0.3);
        border-top-color: #06b6d4;
        border-radius: 50%;
        animation: vw-spin 0.8s linear infinite;
        margin-bottom: 1rem;
    }

    .vw-preview-generating-text {
        color: white;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .vw-preview-generating-hint {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    @keyframes vw-spin {
        to { transform: rotate(360deg); }
    }

    /* Mini Timeline */
    .vw-mini-timeline {
        display: flex;
        gap: 0.35rem;
        overflow-x: auto;
        padding: 0.5rem 0;
        margin-bottom: 1rem;
    }

    .vw-mini-timeline-item {
        flex-shrink: 0;
        width: 60px;
        height: 34px;
        border-radius: 0.25rem;
        overflow: hidden;
        border: 2px solid transparent;
        cursor: pointer;
        opacity: 0.5;
        transition: all 0.2s;
    }

    .vw-mini-timeline-item:hover {
        opacity: 0.8;
    }

    .vw-mini-timeline-item.active {
        border-color: #8b5cf6;
        opacity: 1;
    }

    .vw-mini-timeline-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .vw-mini-timeline-empty {
        width: 100%;
        height: 100%;
        background: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.3);
        font-size: 0.6rem;
    }

    /* Section Cards */
    .vw-section-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .vw-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .vw-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .vw-section-title-icon {
        font-size: 1rem;
    }

    .vw-section-title-text {
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
    }

    .vw-section-badge {
        font-size: 0.5rem;
        padding: 0.15rem 0.35rem;
        border-radius: 0.2rem;
        font-weight: 600;
    }

    .vw-section-badge.auto {
        background: linear-gradient(135deg, #f59e0b, #ec4899);
        color: white;
    }

    /* Alert */
    .vw-studio-alert {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.5rem;
        background: rgba(251, 191, 36, 0.15);
        border: 1px solid rgba(251, 191, 36, 0.3);
        border-radius: 0.75rem;
        color: #fbbf24;
        margin: 2rem auto;
        max-width: 500px;
    }

    .vw-studio-alert-icon {
        font-size: 1.5rem;
    }

    .vw-studio-alert-text {
        font-size: 1rem;
    }
</style>

@if(empty($script['scenes']))
    <div class="vw-studio-alert">
        <span class="vw-studio-alert-icon">⚠️</span>
        <span class="vw-studio-alert-text">{{ __('Please generate a script first before using Animation Studio.') }}</span>
    </div>
@else
    @php
        $scriptScenes = $script['scenes'] ?? [];
        $animationScenes = $animation['scenes'] ?? [];
        $storyboardScenes = $storyboard['scenes'] ?? [];
        $totalScenes = count($scriptScenes);

        // Calculate stats
        $voiceoversReady = count(array_filter($animationScenes, fn($s) => !empty($s['voiceoverUrl'])));
        $animatedCount = count(array_filter($animationScenes, fn($s) => !empty($s['videoUrl'])));
        $stockVideoCount = count(array_filter($storyboardScenes, fn($s) => ($s['source'] ?? '') === 'stock-video' && !empty($s['videoUrl'])));

        // A scene is ready if it has visual AND (voiceover OR doesn't need narration)
        $readyScenes = 0;
        foreach ($scriptScenes as $idx => $scriptScene) {
            $animScene = $animationScenes[$idx] ?? [];
            $sbScene = $storyboardScenes[$idx] ?? [];
            $hasVoiceover = !empty($animScene['voiceoverUrl']);
            $hasVisual = !empty($animScene['videoUrl']) || (($sbScene['source'] ?? '') === 'stock-video' && !empty($sbScene['videoUrl'])) || !empty($sbScene['imageUrl']);
            $hasNarration = !empty($scriptScene['narration']) && trim($scriptScene['narration']) !== '';
            $requiresVoiceover = ($scriptScene['hasNarration'] ?? true) !== false && $hasNarration;
            if ($hasVisual && ($hasVoiceover || !$requiresVoiceover)) {
                $readyScenes++;
            }
        }

        $allScenesReady = $readyScenes >= $totalScenes;
        $selectedIndex = $animation['selectedSceneIndex'] ?? 0;
        $selectedScene = $scriptScenes[$selectedIndex] ?? null;
        $selectedAnimScene = $animationScenes[$selectedIndex] ?? [];
        $selectedStoryboardScene = $storyboardScenes[$selectedIndex] ?? [];
    @endphp

    <div class="vw-animation-studio">
        {{-- TOP HEADER BAR --}}
        <div class="vw-studio-header">
            <div class="vw-studio-brand">
                <div class="vw-studio-icon">🎬</div>
                <div>
                    <div class="vw-studio-title">{{ __('Animation Studio Pro') }}</div>
                    <div class="vw-studio-subtitle">{{ __('Generate voiceovers • Create animations') }}</div>
                </div>
            </div>

            {{-- Progress Pills --}}
            <div class="vw-studio-pills">
                <div class="vw-studio-pill voiceover {{ $voiceoversReady >= $totalScenes ? 'complete' : '' }}">
                    <span>🎙️</span>
                    <span class="pill-value">{{ $voiceoversReady }}/{{ $totalScenes }}</span>
                </div>
                <div class="vw-studio-pill animated">
                    <span>🎬</span>
                    <span class="pill-value">{{ $animatedCount }} {{ __('animated') }}</span>
                </div>
                <div class="vw-studio-pill ready">
                    <span>✓</span>
                    <span class="pill-value">{{ $readyScenes }} {{ __('ready') }}</span>
                </div>
            </div>

            {{-- Header Actions --}}
            <div class="vw-studio-actions">
                <button type="button"
                        class="vw-studio-btn back"
                        wire:click="goToStep(4)">
                    <span>←</span> {{ __('Back') }}
                </button>
                <button type="button"
                        class="vw-studio-btn continue {{ $allScenesReady ? 'enabled' : '' }}"
                        wire:click="goToStep(6)"
                        {{ !$allScenesReady ? 'disabled' : '' }}>
                    {{ __('Continue') }} <span>→</span>
                </button>
            </div>
        </div>

        {{-- MAIN SPLIT-PANEL CONTENT --}}
        <div class="vw-studio-content">
            {{-- LEFT PANEL - Scene Grid --}}
            <div class="vw-scene-grid-panel">
                <div class="vw-scene-grid-header">
                    <div class="vw-scene-grid-title-row">
                        <div class="vw-scene-grid-title">
                            <span>{{ __('SCENES') }}</span>
                        </div>
                        <div class="vw-scene-grid-tools">
                            <button type="button" class="vw-tool-btn" title="{{ __('Keyboard shortcuts') }}">⌨️</button>
                            <button type="button" class="vw-tool-btn" title="{{ __('Cinema mode') }}">🎬</button>
                            <button type="button" class="vw-tool-btn" title="{{ __('Queue manager') }}">📋</button>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="vw-quick-actions">
                        <button type="button"
                                class="vw-quick-btn voice"
                                wire:click="$dispatch('generate-all-voiceovers')"
                                wire:loading.attr="disabled">
                            <span>🎙️</span> {{ __('All Voices') }}
                        </button>
                        <button type="button"
                                class="vw-quick-btn animate"
                                wire:click="$dispatch('animate-all-scenes')"
                                wire:loading.attr="disabled">
                            <span>🎬</span> {{ __('All Anim') }}
                        </button>
                    </div>
                </div>

                <div class="vw-scene-grid-hint">
                    💡 {{ __('Click a scene to edit • Shift+Click for multi-select') }}
                </div>

                {{-- Scrollable Scene List --}}
                <div class="vw-scene-list">
                    @foreach($scriptScenes as $index => $scene)
                        @php
                            $animScene = $animationScenes[$index] ?? [];
                            $sbScene = $storyboardScenes[$index] ?? [];
                            $isSelected = $selectedIndex === $index;
                            $hasVoiceover = !empty($animScene['voiceoverUrl']);
                            $hasAnimation = !empty($animScene['videoUrl']);
                            $isVoiceGenerating = ($animScene['voiceoverStatus'] ?? '') === 'generating';
                            $isAnimGenerating = ($animScene['animationStatus'] ?? '') === 'generating';
                            $isProcessing = $isVoiceGenerating || $isAnimGenerating;
                            $imageUrl = $sbScene['imageUrl'] ?? null;

                            // Calculate progress
                            $progress = 0;
                            if ($hasVoiceover && $hasAnimation) $progress = 100;
                            elseif ($hasVoiceover) $progress = 50;
                            elseif ($isProcessing) $progress = 25;

                            // Ring calculation
                            $ringRadius = 26;
                            $ringCircumference = 2 * 3.14159 * $ringRadius;
                            $ringOffset = $ringCircumference - ($progress / 100) * $ringCircumference;
                            $ringColor = $progress === 100 ? '#10b981' : ($progress >= 50 ? '#fbbf24' : ($isProcessing ? '#8b5cf6' : '#ef4444'));
                        @endphp
                        <div class="vw-scene-card {{ $isSelected ? 'selected' : '' }} {{ $isProcessing ? 'processing' : '' }}"
                             wire:click="$set('animation.selectedSceneIndex', {{ $index }})">
                            {{-- Progress Ring with Thumbnail --}}
                            <div class="vw-progress-ring-container">
                                <svg class="vw-progress-ring" width="60" height="60">
                                    <circle class="vw-progress-ring-bg" cx="30" cy="30" r="{{ $ringRadius }}"/>
                                    <circle class="vw-progress-ring-fill"
                                            cx="30" cy="30" r="{{ $ringRadius }}"
                                            stroke="{{ $ringColor }}"
                                            stroke-dasharray="{{ $ringCircumference }}"
                                            stroke-dashoffset="{{ $ringOffset }}"/>
                                </svg>
                                <div class="vw-scene-thumb-inner">
                                    @if($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="Scene {{ $index + 1 }}">
                                    @else
                                        <div class="vw-scene-thumb-empty">🎬</div>
                                    @endif
                                </div>
                                <div class="vw-scene-number">{{ $index + 1 }}</div>
                            </div>

                            {{-- Scene Info --}}
                            <div class="vw-scene-info">
                                <div class="vw-scene-name">{{ $scene['title'] ?? __('Scene') . ' ' . ($index + 1) }}</div>
                                <div class="vw-scene-duration">{{ $scene['duration'] ?? 8 }}s</div>
                                <div class="vw-scene-status">
                                    @if($isVoiceGenerating)
                                        <span class="vw-status-badge generating">⏳ {{ __('Voice...') }}</span>
                                    @elseif($hasVoiceover)
                                        <span class="vw-status-badge voice-ready">🎙️</span>
                                    @else
                                        <span class="vw-status-badge voice-pending">🎙️</span>
                                    @endif

                                    @if($isAnimGenerating)
                                        <span class="vw-status-badge generating">⏳ {{ __('Anim...') }}</span>
                                    @elseif($hasAnimation)
                                        <span class="vw-status-badge animated">🎬</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- RIGHT PANEL - Detail View --}}
            <div class="vw-detail-panel">
                <div class="vw-detail-content">
                    @if($selectedScene)
                        @php
                            $selectedImageUrl = $selectedStoryboardScene['imageUrl'] ?? null;
                            $selectedVideoUrl = $selectedAnimScene['videoUrl'] ?? null;
                            $selectedVoiceoverUrl = $selectedAnimScene['voiceoverUrl'] ?? null;
                            $selectedAnimStatus = $selectedAnimScene['animationStatus'] ?? null;
                            $selectedVoiceStatus = $selectedAnimScene['voiceoverStatus'] ?? null;
                            $isStockVideo = ($selectedStoryboardScene['source'] ?? '') === 'stock-video';
                        @endphp

                        {{-- Preview Header --}}
                        <div class="vw-preview-header">
                            <div class="vw-preview-title">
                                <span class="vw-preview-title-icon">🎬</span>
                                <span class="vw-preview-title-text">{{ __('SCENE PREVIEW') }}</span>
                                @if($selectedVideoUrl)
                                    <span class="vw-preview-badge animated">✓ {{ __('Animated') }}</span>
                                @elseif($isStockVideo)
                                    <span class="vw-preview-badge stock">📹 {{ __('Stock') }}</span>
                                @elseif($selectedImageUrl)
                                    <span class="vw-preview-badge ken-burns">{{ __('Ken Burns') }}</span>
                                @endif
                            </div>
                            <div class="vw-preview-tools">
                                <button type="button" class="vw-preview-tool-btn pip">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                                        <rect x="11" y="9" width="10" height="8" rx="1" fill="currentColor" opacity="0.3"/>
                                    </svg>
                                    {{ __('PiP') }}
                                </button>
                                <button type="button" class="vw-preview-tool-btn device">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                                        <line x1="8" y1="21" x2="16" y2="21"/>
                                        <line x1="12" y1="17" x2="12" y2="21"/>
                                    </svg>
                                    {{ __('Preview') }}
                                </button>
                                <span class="vw-preview-scene-count">{{ __('Scene') }} {{ $selectedIndex + 1 }} {{ __('of') }} {{ $totalScenes }}</span>
                            </div>
                        </div>

                        {{-- Main Preview Container --}}
                        <div class="vw-preview-container">
                            @if($selectedVideoUrl)
                                {{-- Animated Video --}}
                                <video class="vw-preview-video" src="{{ $selectedVideoUrl }}" id="preview-video-{{ $selectedIndex }}"></video>
                                <div class="vw-preview-play-overlay" onclick="document.getElementById('preview-video-{{ $selectedIndex }}').play()">
                                    <div class="vw-preview-play-btn">▶</div>
                                </div>
                                <div class="vw-preview-status-badge animated">✓ {{ __('ANIMATED') }}</div>
                            @elseif($selectedImageUrl)
                                {{-- Ken Burns Preview --}}
                                <div class="vw-ken-burns-preview">
                                    <img src="{{ $selectedImageUrl }}" alt="{{ $selectedScene['title'] ?? 'Scene' }}">
                                </div>
                                @if($selectedAnimStatus === 'generating')
                                    <div class="vw-preview-generating">
                                        <div class="vw-preview-generating-spinner"></div>
                                        <div class="vw-preview-generating-text">{{ __('Generating Animation...') }}</div>
                                        <div class="vw-preview-generating-hint">{{ __('This may take a moment') }}</div>
                                    </div>
                                @endif
                            @else
                                {{-- No Image --}}
                                <div class="vw-preview-empty">
                                    <div class="vw-preview-empty-content">
                                        <div class="vw-preview-empty-icon">🖼️</div>
                                        <div class="vw-preview-empty-text">{{ __('No storyboard image') }}</div>
                                        <div class="vw-preview-empty-hint">{{ __('Generate images in Step 4') }}</div>
                                    </div>
                                </div>
                            @endif

                            {{-- Scene Info Overlay --}}
                            @if($selectedImageUrl || $selectedVideoUrl)
                                <div class="vw-preview-info-overlay">
                                    <div class="vw-preview-info-row">
                                        <div>
                                            <div class="vw-preview-scene-title">{{ __('Scene') }} {{ $selectedIndex + 1 }}</div>
                                            <div class="vw-preview-scene-duration">{{ $selectedScene['duration'] ?? 8 }}s {{ __('duration') }}</div>
                                        </div>
                                        <div class="vw-preview-quick-actions">
                                            @if(!$selectedVideoUrl && $selectedImageUrl)
                                                <button type="button"
                                                        class="vw-preview-action-btn animate"
                                                        wire:click="$dispatch('animate-scene', { sceneIndex: {{ $selectedIndex }} })">
                                                    🎬 {{ __('Animate') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Mini Timeline --}}
                        <div class="vw-mini-timeline">
                            @foreach($scriptScenes as $idx => $s)
                                @php
                                    $sb = $storyboardScenes[$idx] ?? [];
                                    $imgUrl = $sb['imageUrl'] ?? null;
                                @endphp
                                <div class="vw-mini-timeline-item {{ $selectedIndex === $idx ? 'active' : '' }}"
                                     wire:click="$set('animation.selectedSceneIndex', {{ $idx }})">
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" alt="Scene {{ $idx + 1 }}">
                                    @else
                                        <div class="vw-mini-timeline-empty">{{ $idx + 1 }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Voiceover Section (placeholder for Phase 2) --}}
                        <div class="vw-section-card" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(236, 72, 153, 0.05)); border-color: rgba(139, 92, 246, 0.2);">
                            <div class="vw-section-header">
                                <div class="vw-section-title">
                                    <span class="vw-section-title-icon">🎙️</span>
                                    <span class="vw-section-title-text">{{ __('Voiceover Pro') }}</span>
                                </div>
                            </div>
                            <div style="padding: 1rem; text-align: center; color: rgba(255,255,255,0.5);">
                                @if($selectedVoiceoverUrl)
                                    <audio controls style="width: 100%; margin-bottom: 0.5rem;">
                                        <source src="{{ $selectedVoiceoverUrl }}" type="audio/mpeg">
                                    </audio>
                                    <button type="button"
                                            style="padding: 0.5rem 1rem; background: rgba(139,92,246,0.2); border: 1px solid rgba(139,92,246,0.4); border-radius: 0.5rem; color: #a78bfa; font-size: 0.75rem; cursor: pointer;"
                                            wire:click="$dispatch('regenerate-voiceover', { sceneIndex: {{ $selectedIndex }} })">
                                        🔄 {{ __('Regenerate') }}
                                    </button>
                                @elseif($selectedVoiceStatus === 'generating')
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 1rem;">
                                        <div style="width: 20px; height: 20px; border: 2px solid rgba(139,92,246,0.3); border-top-color: #8b5cf6; border-radius: 50%; animation: vw-spin 0.8s linear infinite;"></div>
                                        <span>{{ __('Generating voiceover...') }}</span>
                                    </div>
                                @else
                                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">♫</div>
                                    <div style="margin-bottom: 0.5rem;">{{ __('Cinematic Music Scene') }}</div>
                                    <div style="font-size: 0.75rem; margin-bottom: 1rem;">{{ __('No voiceover - relax and let images tell the story') }}</div>
                                    <button type="button"
                                            style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #8b5cf6, #06b6d4); border: none; border-radius: 0.5rem; color: white; font-size: 0.8rem; font-weight: 600; cursor: pointer;"
                                            wire:click="$dispatch('generate-voiceover', { sceneIndex: {{ $selectedIndex }}, sceneId: '{{ $selectedScene['id'] ?? '' }}' })">
                                        + {{ __('Add Voiceover') }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Animation Style Section (placeholder for Phase 2) --}}
                        <div class="vw-section-card" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(16, 185, 129, 0.05)); border-color: rgba(6, 182, 212, 0.2);">
                            <div class="vw-section-header">
                                <div class="vw-section-title">
                                    <span class="vw-section-title-icon">🎬</span>
                                    <span class="vw-section-title-text">{{ __('Animation Style') }}</span>
                                </div>
                            </div>
                            <div style="color: rgba(255,255,255,0.4); font-size: 0.8rem; text-align: center; padding: 1rem;">
                                {{ __('Animation style controls will be added in Phase 2') }}
                            </div>
                        </div>

                        {{-- Audio & Music Section (placeholder for Phase 2) --}}
                        <div class="vw-section-card" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(236, 72, 153, 0.05)); border-color: rgba(245, 158, 11, 0.25);">
                            <div class="vw-section-header">
                                <div class="vw-section-title">
                                    <span class="vw-section-title-icon">🎵</span>
                                    <span class="vw-section-title-text">{{ __('Audio & Music') }}</span>
                                    <span class="vw-section-badge auto">{{ __('AUTO') }}</span>
                                </div>
                            </div>
                            <div style="color: rgba(255,255,255,0.4); font-size: 0.8rem; text-align: center; padding: 1rem;">
                                {{ __('Audio controls will be added in Phase 2') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
