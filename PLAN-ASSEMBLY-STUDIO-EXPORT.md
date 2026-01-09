# Assembly Studio & Export Implementation Plan

## Overview

Replace the outdated Assembly (Step 6) and Export (Step 7) with a modern professional video editor based on the original `video-creation-wizard.html` implementation.

---

## PHASE 1: Assembly Studio - Full Editor Interface

### 1.1 Layout Structure (Full-Screen Editor)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ HEADER BAR (50px)                                                           │
│ [Project Name] [Scenes: X] [Duration: mm:ss]          [Save] [Back] [Export]│
├────────────┬──────────────────────┬─────────────────────────────────────────┤
│ LEFT       │ MIDDLE PANEL         │ RIGHT PANEL                             │
│ SIDEBAR    │ (Tabbed Controls)    │ (Preview Player)                        │
│ (220px)    │ (340px)              │ (flex: 1)                               │
│            │                      │                                         │
│ Project    │ [TEXT][Audio][Media] │  ┌─────────────────────────────────┐   │
│ Scenes     │ [Transitions]        │  │                                 │   │
│ Captions   │                      │  │      16:9 Preview Canvas        │   │
│ Audio      │ Tab Content:         │  │                                 │   │
│ Transitions│ - Caption settings   │  │      [▶ Play Button Overlay]    │   │
│            │ - Audio mixer        │  │                                 │   │
│ ─────────  │ - Media browser      │  └─────────────────────────────────┘   │
│ QUICK      │ - Transition types   │                                         │
│ ACTIONS    │                      │  [▶/❚❚] 00:00 / 02:30  [🔊━━━━]        │
│ [Preview]  │                      │  [═══════════○═══════════════════]      │
│ [Export]   │                      │                                         │
│            │                      │                                         │
│ Duration   │                      │                                         │
│ 3m 22s     │                      │                                         │
├────────────┴──────────────────────┴─────────────────────────────────────────┤
│ TIMELINE (280px)                                                            │
│ ┌─────────┬─────────────────────────────────────────────────────────────┐  │
│ │Toolbar  │ [⏮][▶][⏭] [↩][↪] [✂][🗑] │ 00:00/02:30 │ [-][+] │ [Snap][Fit]│
│ ├─────────┼─────────────────────────────────────────────────────────────┤  │
│ │ Ruler   │ |0:00    |0:05    |0:10    |0:15    |0:20    |0:25          │  │
│ ├─────────┼─────────────────────────────────────────────────────────────┤  │
│ │📹 Video │ [Scene 1 ████████][Scene 2 ██████████][Scene 3 ████████]    │  │
│ ├─────────┼─────────────────────────────────────────────────────────────┤  │
│ │🎙 Voice │ [Voice 1 ████][Voice 2 ██████][Voice 3 ████]                │  │
│ ├─────────┼─────────────────────────────────────────────────────────────┤  │
│ │🎵 Music │ [═══════════════════Background Music═══════════════════════]│  │
│ ├─────────┼─────────────────────────────────────────────────────────────┤  │
│ │💬 Capts │ [Cap][Cap][Cap]                                             │  │
│ └─────────┴─────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Files to Create

#### Blade Views
```
modules/AppVideoWizard/resources/views/livewire/steps/
├── assembly-studio.blade.php          # Main editor view (replaces assembly.blade.php)
├── partials/
│   ├── assembly-header.blade.php      # Top header bar
│   ├── assembly-sidebar.blade.php     # Left sidebar navigation
│   ├── assembly-controls.blade.php    # Middle control panel
│   ├── assembly-preview.blade.php     # Preview player
│   └── assembly-timeline.blade.php    # Timeline component
```

#### JavaScript Files
```
modules/AppVideoWizard/resources/js/
├── video-preview-engine.js            # Canvas-based preview renderer
├── timeline-manager.js                # Timeline rendering & interaction
├── caption-renderer.js                # Caption styles & effects
└── audio-manager.js                   # Audio playback management
```

### 1.3 State Structure (Livewire Properties)

```php
// VideoWizard.php - Assembly State
public array $assembly = [
    'status' => 'idle',
    'sceneOrder' => [],              // Ordered scene IDs
    'selectedClipId' => null,

    // Transitions
    'transitions' => [],             // [sceneId => ['type' => 'fade', 'duration' => 0.5]]
    'defaultTransition' => 'fade',

    // Music
    'music' => [
        'enabled' => false,
        'trackId' => null,
        'trackUrl' => null,
        'volume' => 30,
        'fadeIn' => 2,
        'fadeOut' => 3,
    ],

    // Captions
    'captions' => [
        'enabled' => true,
        'mode' => 'word',            // 'word' | 'sentence'
        'style' => 'karaoke',        // 'karaoke' | 'bold' | 'minimal'
        'position' => 'bottom',      // 'top' | 'center' | 'bottom'
        'size' => 1.0,               // 0.7 - 1.5
        'fontFamily' => 'Montserrat',
        'fillColor' => '#FFFFFF',
        'strokeColor' => '#000000',
        'strokeWidth' => 2,
        'effect' => 'none',          // 'none' | 'pop' | 'fade' | 'zoom' | 'bounce'
        'highlightColor' => '#FBBF24',
    ],

    // Audio Mix
    'audioMix' => [
        'voiceVolume' => 100,
        'musicVolume' => 30,
        'ducking' => true,
    ],

    // UI State
    'activeTab' => 'text',           // 'text' | 'audio' | 'media' | 'transitions'

    // Timeline
    'timeline' => [
        'zoom' => 50,                // pixels per second
        'scrollLeft' => 0,
        'playheadPosition' => 0,
        'isPlaying' => false,
        'snapEnabled' => true,
    ],
];

// Music Library (loaded from config/database)
public array $musicLibrary = [];
```

### 1.4 Livewire Methods to Add

```php
// Assembly Tab Navigation
public function setAssemblyTab(string $tab): void;

// Caption Settings
public function setCaptionEnabled(bool $enabled): void;
public function setCaptionMode(string $mode): void;
public function setCaptionStyle(string $style): void;
public function setCaptionPosition(string $position): void;
public function setCaptionFont(string $font): void;
public function setCaptionFillColor(string $color): void;
public function setCaptionStrokeColor(string $color): void;
public function setCaptionStrokeWidth(float $width): void;
public function setCaptionEffect(string $effect): void;
public function setCaptionHighlightColor(string $color): void;
public function setCaptionSize(float $size): void;

// Music Settings
public function toggleMusic(bool $enabled): void;
public function selectMusicTrack(string $trackId): void;
public function setMusicVolume(int $volume): void;
public function setMusicFadeIn(float $seconds): void;
public function setMusicFadeOut(float $seconds): void;

// Audio Mix
public function setVoiceVolume(int $volume): void;
public function setMusicMixVolume(int $volume): void;
public function toggleAudioDucking(bool $enabled): void;

// Transitions
public function setSceneTransition(string $sceneId, string $type): void;
public function setDefaultTransition(string $type): void;

// Timeline
public function setTimelineZoom(int $zoom): void;
public function selectTimelineClip(string $sceneId): void;
public function reorderScenes(array $order): void;

// Scene Management
public function splitSceneAt(string $sceneId, float $time): void;
public function deleteScene(string $sceneId): void;
```

---

## PHASE 2: Timeline Component

### 2.1 Timeline Features

1. **Time Ruler**
   - Dynamic tick intervals based on zoom
   - Clickable for seeking
   - Shows timecodes

2. **Playhead**
   - Red vertical line with head marker
   - Draggable for seeking
   - Syncs with preview

3. **Tracks**
   - **Video Track (75px)**: Scene clips with thumbnails
   - **Voice Track (50px)**: Voiceover blocks
   - **Music Track (40px)**: Background music bar
   - **Caption Track (35px)**: Caption indicators

4. **Clip Interaction**
   - Click to select
   - Drag to reorder
   - Trim handles on selected clips
   - Split at playhead
   - Delete selected

5. **Toolbar**
   - Transport: `[⏮] [▶/❚❚] [⏭]`
   - Edit: `[Undo] [Redo] [Split] [Delete]`
   - Time: `00:00:00 / 00:00:00`
   - Zoom: `[-] [level] [+]`
   - Options: `[Snap] [Fit]`

### 2.2 Timeline JavaScript (Alpine.js Component)

```javascript
// timeline-manager.js
document.addEventListener('alpine:init', () => {
    Alpine.data('timelineManager', () => ({
        zoom: 50,           // px per second
        scrollLeft: 0,
        playheadTime: 0,
        isPlaying: false,
        selectedClipId: null,
        snapEnabled: true,

        // Computed
        get totalDuration() { /* sum of scene durations */ },
        get timelineWidth() { return this.totalDuration * this.zoom; },

        // Methods
        seekTo(time) { /* ... */ },
        zoomIn() { this.zoom = Math.min(200, this.zoom + 10); },
        zoomOut() { this.zoom = Math.max(10, this.zoom - 10); },
        fitToView() { /* calculate zoom to fit */ },
        selectClip(sceneId) { /* ... */ },

        // Rendering
        renderRuler() { /* ... */ },
        renderTracks() { /* ... */ },
        renderPlayhead() { /* ... */ },
    }));
});
```

---

## PHASE 3: Preview Engine

### 3.1 VideoPreviewEngine Class

```javascript
// video-preview-engine.js
class VideoPreviewEngine {
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.width = options.width || 1280;
        this.height = options.height || 720;

        // Playback state
        this.scenes = [];
        this.currentTime = 0;
        this.isPlaying = false;
        this.totalDuration = 0;

        // Audio
        this.audioElements = new Map();
        this.musicElement = null;
        this.voiceVolume = 1.0;
        this.musicVolume = 0.3;

        // Captions
        this.captionsEnabled = true;
        this.captionSettings = {};
    }

    // Core methods
    async loadScenes(scenes) { /* ... */ }
    play() { /* ... */ }
    pause() { /* ... */ }
    seek(time) { /* ... */ }

    // Render loop
    _renderFrame() { /* ... */ }
    _renderScene(scene, localTime) { /* ... */ }
    _renderCaptions(scene, localTime) { /* ... */ }
    _renderTransition(fromScene, toScene, progress) { /* ... */ }

    // Caption styles
    _renderKaraokeCaption(text, words, currentWordIndex) { /* ... */ }
    _renderBoldCaption(text, words, currentWordIndex) { /* ... */ }
    _renderMinimalCaption(text) { /* ... */ }
}
```

### 3.2 Caption Rendering System

```javascript
// caption-renderer.js
const CaptionStyles = {
    karaoke: {
        render(ctx, text, words, currentIndex, settings) {
            // Word-by-word highlight with color change
        }
    },
    bold: {
        render(ctx, text, words, currentIndex, settings) {
            // Bold text with thick outline
        }
    },
    minimal: {
        render(ctx, text, words, currentIndex, settings) {
            // Clean, simple text
        }
    }
};

const CaptionEffects = {
    none: (ctx, word, x, y, scale) => { /* static */ },
    pop: (ctx, word, x, y, scale) => { /* scale animation */ },
    fade: (ctx, word, x, y, alpha) => { /* fade in */ },
    zoom: (ctx, word, x, y, scale) => { /* zoom in */ },
    bounce: (ctx, word, x, y, offset) => { /* bounce */ }
};
```

---

## PHASE 4: Control Panels

### 4.1 TEXT Tab (Captions)

```blade
{{-- Caption Controls --}}
<div class="assembly-tab-content" x-show="activeTab === 'text'">
    {{-- Enable Toggle --}}
    <div class="control-row">
        <span>Show Captions</span>
        <x-toggle wire:model.live="assembly.captions.enabled" />
    </div>

    {{-- Caption Mode --}}
    <div class="control-group">
        <label>Caption Style</label>
        <div class="button-group">
            <button wire:click="setCaptionMode('word')"
                    class="{{ $assembly['captions']['mode'] === 'word' ? 'active' : '' }}">
                WORD LEVEL
            </button>
            <button wire:click="setCaptionMode('sentence')"
                    class="{{ $assembly['captions']['mode'] === 'sentence' ? 'active' : '' }}">
                SENTENCE LEVEL
            </button>
        </div>
    </div>

    {{-- Font Selection --}}
    <div class="control-group">
        <label>Font</label>
        <select wire:model.live="assembly.captions.fontFamily">
            <option value="Montserrat">Montserrat</option>
            <option value="Poppins">Poppins</option>
            <option value="Roboto">Roboto</option>
            <option value="Inter">Inter</option>
            <option value="Oswald">Oswald</option>
            <option value="Bebas Neue">Bebas Neue</option>
        </select>
    </div>

    {{-- Colors --}}
    <div class="control-row">
        <span>Fill Color</span>
        <input type="color" wire:model.live="assembly.captions.fillColor">
    </div>
    <div class="control-row">
        <span>Stroke Color</span>
        <input type="color" wire:model.live="assembly.captions.strokeColor">
    </div>
    <div class="control-group">
        <label>Stroke Width: {{ $assembly['captions']['strokeWidth'] }}px</label>
        <input type="range" min="0" max="5" step="0.5"
               wire:model.live="assembly.captions.strokeWidth">
    </div>

    {{-- Effects --}}
    <div class="control-group">
        <label>Effects</label>
        <div class="effect-grid">
            @foreach(['none' => '—', 'pop' => '💥', 'fade' => '🌫️', 'zoom' => '🔍', 'bounce' => '⚡'] as $effect => $icon)
                <button wire:click="setCaptionEffect('{{ $effect }}')"
                        class="{{ $assembly['captions']['effect'] === $effect ? 'active' : '' }}">
                    {{ $icon }} {{ ucfirst($effect) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Style Presets --}}
    <div class="control-group">
        <label>Preset Styles</label>
        <div class="preset-grid">
            <button wire:click="setCaptionStyle('karaoke')">Karaoke</button>
            <button wire:click="setCaptionStyle('bold')">Bold</button>
            <button wire:click="setCaptionStyle('minimal')">Minimal</button>
        </div>
    </div>

    {{-- Highlight Color --}}
    <div class="control-row">
        <span>Highlight Color</span>
        <input type="color" wire:model.live="assembly.captions.highlightColor">
    </div>
</div>
```

### 4.2 AUDIO Tab

```blade
{{-- Audio Controls --}}
<div class="assembly-tab-content" x-show="activeTab === 'audio'">
    {{-- Background Music --}}
    <div class="section">
        <div class="section-header">
            <span>🎵 Background Music</span>
            <x-toggle wire:model.live="assembly.music.enabled" />
        </div>

        @if($assembly['music']['enabled'])
            {{-- Track Selection --}}
            <div class="music-tracks">
                @foreach($musicLibrary as $track)
                    <div wire:click="selectMusicTrack('{{ $track['id'] }}')"
                         class="music-track {{ $assembly['music']['trackId'] === $track['id'] ? 'selected' : '' }}">
                        <span>{{ $track['icon'] }}</span>
                        <div>
                            <div class="track-name">{{ $track['name'] }}</div>
                            <div class="track-genre">{{ $track['genre'] }}</div>
                        </div>
                        <button class="preview-btn">▶</button>
                    </div>
                @endforeach
            </div>

            {{-- Volume --}}
            <div class="control-group">
                <label>Volume: {{ $assembly['music']['volume'] }}%</label>
                <input type="range" min="0" max="100"
                       wire:model.live="assembly.music.volume">
            </div>

            {{-- Fade Settings --}}
            <div class="control-row">
                <div>
                    <label>Fade In</label>
                    <input type="number" min="0" max="5" step="0.5"
                           wire:model.live="assembly.music.fadeIn"> s
                </div>
                <div>
                    <label>Fade Out</label>
                    <input type="number" min="0" max="5" step="0.5"
                           wire:model.live="assembly.music.fadeOut"> s
                </div>
            </div>
        @endif
    </div>

    {{-- Audio Mix --}}
    <div class="section">
        <div class="section-header">🎚️ Audio Mix</div>

        <div class="control-group">
            <label>🎙 Voice: {{ $assembly['audioMix']['voiceVolume'] }}%</label>
            <input type="range" min="0" max="100"
                   wire:model.live="assembly.audioMix.voiceVolume">
        </div>

        <div class="control-group">
            <label>🎵 Music: {{ $assembly['audioMix']['musicVolume'] }}%</label>
            <input type="range" min="0" max="100"
                   wire:model.live="assembly.audioMix.musicVolume">
        </div>

        <div class="control-row">
            <span>Auto-Ducking</span>
            <x-toggle wire:model.live="assembly.audioMix.ducking" />
        </div>
    </div>
</div>
```

### 4.3 TRANSITIONS Tab

```blade
{{-- Transitions --}}
<div class="assembly-tab-content" x-show="activeTab === 'transitions'">
    <div class="section">
        <div class="section-header">Default Transition</div>
        <div class="transition-grid">
            @foreach(['cut' => '✂️', 'fade' => '🌫️', 'dissolve' => '💫', 'slide' => '➡️', 'zoom' => '🔍'] as $type => $icon)
                <button wire:click="setDefaultTransition('{{ $type }}')"
                        class="{{ $assembly['defaultTransition'] === $type ? 'active' : '' }}">
                    {{ $icon }} {{ ucfirst($type) }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="section">
        <div class="section-header">Per-Scene Transitions</div>
        @foreach($script['scenes'] as $index => $scene)
            @if($index > 0)
                <div class="scene-transition">
                    <span>Scene {{ $index }} → {{ $index + 1 }}</span>
                    <select wire:model.live="assembly.transitions.{{ $scene['id'] }}.type">
                        <option value="cut">Cut</option>
                        <option value="fade">Fade</option>
                        <option value="dissolve">Dissolve</option>
                        <option value="slide">Slide</option>
                    </select>
                </div>
            @endif
        @endforeach
    </div>
</div>
```

---

## PHASE 5: Export System

### 5.1 Export State

```php
public array $export = [
    'status' => 'idle',          // 'idle' | 'exporting' | 'completed' | 'failed'
    'jobId' => null,
    'progress' => 0,             // 0-100
    'currentStage' => '',
    'outputUrl' => null,
    'error' => null,

    // Settings
    'quality' => '1080p',        // '480p' | '720p' | '1080p' | '4K'
    'format' => 'mp4',
    'platform' => 'youtube',     // Target platform

    // Scene progress
    'scenesTotal' => 0,
    'scenesCompleted' => 0,
    'sceneStatuses' => [],       // [sceneId => 'queued'|'rendering'|'complete'|'failed']
];
```

### 5.2 Export Modal

```blade
{{-- Export Modal --}}
@if($showExportModal)
<div class="export-modal-overlay">
    <div class="export-modal">
        {{-- Header --}}
        <div class="export-header">
            <span>🚀</span>
            <span>Export Video</span>
            <button wire:click="closeExportModal">×</button>
        </div>

        {{-- Content based on status --}}
        <div class="export-content">
            @if($export['status'] === 'idle')
                {{-- Config State --}}
                @include('appvideowizard::livewire.partials.export-config')
            @elseif($export['status'] === 'exporting')
                {{-- Progress State --}}
                @include('appvideowizard::livewire.partials.export-progress')
            @elseif($export['status'] === 'completed')
                {{-- Complete State --}}
                @include('appvideowizard::livewire.partials.export-complete')
            @elseif($export['status'] === 'failed')
                {{-- Failed State --}}
                @include('appvideowizard::livewire.partials.export-failed')
            @endif
        </div>
    </div>
</div>
@endif
```

### 5.3 Export Config Panel

```blade
{{-- export-config.blade.php --}}
<div class="export-config">
    {{-- Video Summary --}}
    <div class="summary-card">
        <div class="summary-row">
            <span>📹 Scenes</span>
            <span>{{ count($script['scenes']) }}</span>
        </div>
        <div class="summary-row">
            <span>⏱️ Duration</span>
            <span>{{ $this->getTotalDuration() }}</span>
        </div>
        <div class="summary-row">
            <span>🖼️ Images</span>
            <span>{{ $this->getImageCount() }}</span>
        </div>
        <div class="summary-row">
            <span>🎬 Videos</span>
            <span>{{ $this->getAnimatedCount() }}</span>
        </div>
    </div>

    {{-- Platform Selection --}}
    <div class="section">
        <label>Target Platform</label>
        <div class="platform-grid">
            @foreach(['youtube' => ['📺', 'YouTube', '16:9'], 'shorts' => ['📱', 'YT Shorts', '9:16'], 'tiktok' => ['🎵', 'TikTok', '9:16'], 'reels' => ['📸', 'Reels', '9:16'], 'linkedin' => ['💼', 'LinkedIn', '16:9']] as $id => [$icon, $name, $ratio])
                <button wire:click="setExportPlatform('{{ $id }}')"
                        class="{{ $export['platform'] === $id ? 'active' : '' }}">
                    <span>{{ $icon }}</span>
                    <span>{{ $name }}</span>
                    <span class="ratio">{{ $ratio }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Quality Selection --}}
    <div class="section">
        <label>Quality</label>
        <div class="quality-grid">
            @foreach(['480p' => '📱', '720p' => '📺', '1080p' => '🎬', '4K' => '🎥'] as $quality => $icon)
                <button wire:click="setExportQuality('{{ $quality }}')"
                        class="{{ $export['quality'] === $quality ? 'active' : '' }}">
                    {{ $icon }} {{ $quality }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Export Button --}}
    <button wire:click="startExport" class="export-btn primary">
        🚀 Start Export
    </button>
</div>
```

### 5.4 Export Progress Panel

```blade
{{-- export-progress.blade.php --}}
<div class="export-progress">
    {{-- Animated Icon --}}
    <div class="progress-icon spinning">🎬</div>

    {{-- Progress Bar --}}
    <div class="progress-bar-container">
        <div class="progress-bar" style="width: {{ $export['progress'] }}%"></div>
    </div>
    <div class="progress-text">{{ $export['progress'] }}%</div>

    {{-- Current Stage --}}
    <div class="stage-text">{{ $export['currentStage'] }}</div>

    {{-- Scene Progress Grid --}}
    <div class="scene-progress-grid">
        @foreach($export['sceneStatuses'] as $sceneId => $status)
            <div class="scene-dot {{ $status }}">
                @if($status === 'complete') ✓
                @elseif($status === 'failed') ✗
                @elseif($status === 'rendering') ⏳
                @endif
            </div>
        @endforeach
    </div>

    {{-- Cancel Button --}}
    <button wire:click="cancelExport" class="cancel-btn">
        Cancel Export
    </button>
</div>
```

### 5.5 Export Service

```php
// ExportService.php
class VideoExportService
{
    public function startExport(WizardProject $project, array $options): array
    {
        // Create export job
        $job = ExportJob::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'status' => 'queued',
            'quality' => $options['quality'],
            'format' => $options['format'],
            'platform' => $options['platform'],
        ]);

        // Dispatch to queue
        ProcessVideoExport::dispatch($job);

        return [
            'jobId' => $job->id,
            'status' => 'queued',
        ];
    }

    public function checkStatus(string $jobId): array
    {
        $job = ExportJob::findOrFail($jobId);

        return [
            'status' => $job->status,
            'progress' => $job->progress,
            'currentStage' => $job->current_stage,
            'outputUrl' => $job->output_url,
            'scenesTotal' => $job->scenes_total,
            'scenesCompleted' => $job->scenes_completed,
            'sceneStatuses' => $job->scene_statuses,
        ];
    }
}
```

---

## PHASE 6: Backend Services

### 6.1 New Services to Create

```
app/Services/
├── VideoExportService.php         # Export job management
├── VideoRenderService.php         # FFmpeg rendering
└── MusicLibraryService.php        # Music track management

app/Jobs/
└── ProcessVideoExport.php         # Background export job
```

### 6.2 Database Migrations

```php
// create_export_jobs_table
Schema::create('wizard_export_jobs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('project_id')->constrained('wizard_projects');
    $table->foreignId('user_id')->constrained();
    $table->string('status');           // queued, processing, completed, failed
    $table->integer('progress')->default(0);
    $table->string('current_stage')->nullable();
    $table->string('quality');
    $table->string('format');
    $table->string('platform')->nullable();
    $table->string('output_path')->nullable();
    $table->string('output_url')->nullable();
    $table->integer('scenes_total')->default(0);
    $table->integer('scenes_completed')->default(0);
    $table->json('scene_statuses')->nullable();
    $table->text('error')->nullable();
    $table->timestamps();
});
```

### 6.3 Music Library Config

```php
// config/appvideowizard.php
'music_library' => [
    [
        'id' => 'epic-cinematic',
        'name' => 'Epic Cinematic',
        'genre' => 'Cinematic',
        'icon' => '🎬',
        'url' => '/audio/library/epic-cinematic.mp3',
        'duration' => 180,
    ],
    [
        'id' => 'upbeat-corporate',
        'name' => 'Upbeat Corporate',
        'genre' => 'Corporate',
        'icon' => '💼',
        'url' => '/audio/library/upbeat-corporate.mp3',
        'duration' => 120,
    ],
    // ... more tracks
],
```

---

## PHASE 7: Implementation Order

### Week 1: Foundation
1. Create `assembly-studio.blade.php` layout structure
2. Implement basic Livewire state and methods
3. Create sidebar and tab navigation

### Week 2: Timeline
4. Build timeline component HTML/CSS
5. Implement timeline JavaScript (Alpine.js)
6. Add track rendering (video, voice, music, captions)
7. Implement playhead and seeking

### Week 3: Preview Engine
8. Create VideoPreviewEngine JavaScript class
9. Implement canvas rendering
10. Add audio playback sync
11. Build caption rendering system

### Week 4: Control Panels
12. Build TEXT tab (caption controls)
13. Build AUDIO tab (music & mix)
14. Build TRANSITIONS tab
15. Implement all Livewire methods

### Week 5: Export
16. Create export modal
17. Build ExportService
18. Create export job processor
19. Implement progress polling
20. Add download functionality

### Week 6: Polish
21. Testing and bug fixes
22. Performance optimization
23. Mobile responsiveness
24. Documentation

---

## Key Differences from Original

| Feature | Original (HTML) | Laravel Version |
|---------|-----------------|-----------------|
| State | JavaScript object | Livewire properties |
| Reactivity | Manual render() | Livewire auto-sync |
| Backend | Firebase Functions | Laravel Jobs |
| Storage | Firebase Storage | Laravel Storage |
| Auth | Firebase Auth | Laravel Auth |
| Music | Firebase hosted | Local/S3 |

---

## Files Summary

### New Files to Create
- `resources/views/livewire/steps/assembly-studio.blade.php`
- `resources/views/livewire/partials/export-*.blade.php` (4 files)
- `resources/js/video-preview-engine.js`
- `resources/js/timeline-manager.js`
- `resources/js/caption-renderer.js`
- `app/Services/VideoExportService.php`
- `app/Services/VideoRenderService.php`
- `app/Jobs/ProcessVideoExport.php`
- `database/migrations/*_create_wizard_export_jobs_table.php`

### Files to Modify
- `VideoWizard.php` - Add assembly & export state/methods
- `config/appvideowizard.php` - Add music library

### Files to Replace
- `assembly.blade.php` → `assembly-studio.blade.php`
- Current Step 7 (Export) → New export modal system

---

## Notes

1. **Preview Engine**: The VideoPreviewEngine renders to canvas for real-time preview. For actual export, use server-side FFmpeg.

2. **Caption Timing**: Word-level captions require voiceover duration and word count to calculate timing. Store this in scene metadata.

3. **Music Library**: Start with 5-10 royalty-free tracks. Can expand later with user uploads or API integration.

4. **Export Queue**: Use Laravel Horizon or native queue for background processing. Export can take 1-5 minutes depending on video length.

5. **Progressive Loading**: Load scene thumbnails and audio progressively to avoid blocking UI.
