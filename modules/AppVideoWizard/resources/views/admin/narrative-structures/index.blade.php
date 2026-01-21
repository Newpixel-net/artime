@extends('layouts.app')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">{{ __('Video Creator') }}</div>
                    <h2 class="page-title">{{ __('Narrative Structures') }}</h2>
                    <p class="text-muted small mb-0">{{ __('Hollywood-level storytelling configuration for script generation') }}</p>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('admin.video-wizard.narrative.export') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-download me-1"></i>{{ __('Export JSON') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-xl">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="fa fa-check me-2"></i></div>
                    <div>{{ session('success') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="narrativeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="narrative-tab" data-bs-toggle="tab" data-bs-target="#narrative-content" type="button" role="tab">
                    <i class="fa fa-book me-2"></i>{{ __('Narrative Structure') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cinematography-tab" data-bs-toggle="tab" data-bs-target="#cinematography-content" type="button" role="tab">
                    <i class="fa fa-video me-2"></i>{{ __('Cinematography') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="engagement-tab" data-bs-toggle="tab" data-bs-target="#engagement-content" type="button" role="tab">
                    <i class="fa fa-bolt me-2"></i>{{ __('Engagement') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced-content" type="button" role="tab">
                    <i class="fa fa-magic me-2"></i>{{ __('Advanced') }}
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="narrativeTabsContent">
            <!-- Narrative Structure Tab -->
            <div class="tab-pane fade show active" id="narrative-content" role="tabpanel">
                <div class="row row-deck row-cards mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-purple bg-opacity-10 rounded p-2">
                                        <i class="fa fa-project-diagram text-purple fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Story Arcs') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_arcs'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-primary bg-opacity-10 rounded p-2">
                                        <i class="fa fa-tv text-primary fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Presets') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_presets'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-success bg-opacity-10 rounded p-2">
                                        <i class="fa fa-chart-line text-success fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Tension Curves') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_curves'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-danger bg-opacity-10 rounded p-2">
                                        <i class="fa fa-theater-masks text-danger fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Emotional Journeys') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_journeys'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cinematography Tab -->
            <div class="tab-pane fade" id="cinematography-content" role="tabpanel">
                <div class="row row-deck row-cards mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-info bg-opacity-10 rounded p-2">
                                        <i class="fa fa-camera text-info fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Shot Types') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_shot_types'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-warning bg-opacity-10 rounded p-2">
                                        <i class="fa fa-lightbulb text-warning fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Lighting Styles') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_lighting_styles'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-pink bg-opacity-10 rounded p-2">
                                        <i class="fa fa-palette text-pink fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Color Grades') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_color_grades'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-indigo bg-opacity-10 rounded p-2">
                                        <i class="fa fa-th text-indigo fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Compositions') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_compositions'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Engagement Tab -->
            <div class="tab-pane fade" id="engagement-content" role="tabpanel">
                <div class="row row-deck row-cards mb-4">
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-orange bg-opacity-10 rounded p-2">
                                        <i class="fa fa-anchor text-orange fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Retention Hooks') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_retention_hooks'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-teal bg-opacity-10 rounded p-2">
                                        <i class="fa fa-music text-teal fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Scene Beats') }}</div>
                                        <div class="h2 mb-0">{{ count($sceneBeats ?? []) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-purple bg-opacity-10 rounded p-2">
                                        <i class="fa fa-random text-purple fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Transitions') }}</div>
                                        <div class="h2 mb-0">{{ count($transitions ?? []) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Tab -->
            <div class="tab-pane fade" id="advanced-content" role="tabpanel">
                <div class="row row-deck row-cards mb-4">
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-success bg-opacity-10 rounded p-2">
                                        <i class="fa fa-paint-brush text-success fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Visual Styles') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_visual_styles'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-pink bg-opacity-10 rounded p-2">
                                        <i class="fa fa-music text-pink fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Music Moods') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_music_moods'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-info bg-opacity-10 rounded p-2">
                                        <i class="fa fa-tachometer-alt text-info fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Pacing Profiles') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_pacing_profiles'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-warning bg-opacity-10 rounded p-2">
                                        <i class="fa fa-film text-warning fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Genre Templates') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_genre_templates'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-lime bg-opacity-10 rounded p-2">
                                        <i class="fa fa-image text-lime fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Visual Themes') }}</div>
                                        <div class="h2 mb-0">{{ $stats['total_visual_themes'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Default Settings Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('Default Settings') }}</h3>
                <p class="card-subtitle">{{ __('Configure default selections for new projects') }}</p>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.video-wizard.narrative.update-settings') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Default Preset') }}</label>
                            <select name="default_preset" class="form-select">
                                <option value="">{{ __('None') }}</option>
                                @foreach($narrativePresets as $key => $preset)
                                    <option value="{{ $key }}" {{ ($settings['default_preset'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $preset['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Default Story Arc') }}</label>
                            <select name="default_arc" class="form-select">
                                <option value="">{{ __('None') }}</option>
                                @foreach($storyArcs as $key => $arc)
                                    <option value="{{ $key }}" {{ ($settings['default_arc'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $arc['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Default Tension Curve') }}</label>
                            <select name="default_curve" class="form-select">
                                <option value="">{{ __('None') }}</option>
                                @foreach($tensionCurves as $key => $curve)
                                    <option value="{{ $key }}" {{ ($settings['default_curve'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $curve['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Default Emotional Journey') }}</label>
                            <select name="default_journey" class="form-select">
                                <option value="">{{ __('None') }}</option>
                                @foreach($emotionalJourneys as $key => $journey)
                                    <option value="{{ $key }}" {{ ($settings['default_journey'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $journey['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" name="show_advanced_by_default" value="1" class="form-check-input"
                                       {{ ($settings['show_advanced_by_default'] ?? false) ? 'checked' : '' }}>
                                <span class="form-check-label">{{ __('Show advanced options by default in wizard') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>{{ __('Save Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Narrative Presets -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-tv me-2"></i>{{ __('Narrative Presets') }}</h3>
                <p class="card-subtitle">{{ __('Platform-optimized storytelling formulas') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($narrativePresets as $key => $preset)
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 class="card-title mb-0">{{ $preset['name'] }}</h4>
                                        <button onclick="toggleItem('preset', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm toggle-btn" data-type="preset" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_presets'] ?? []))
                                                <i class="fa fa-eye-slash text-muted"></i>
                                            @else
                                                <i class="fa fa-eye text-success"></i>
                                            @endif
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-2">{{ $preset['description'] ?? '' }}</p>
                                    @if(!empty($preset['tips']))
                                        <p class="text-purple small fst-italic mb-2">{{ $preset['tips'] }}</p>
                                    @endif
                                    <div class="d-flex flex-wrap gap-1">
                                        @if(!empty($preset['defaultArc']))
                                            <span class="badge bg-secondary">{{ $storyArcs[$preset['defaultArc']]['name'] ?? $preset['defaultArc'] }}</span>
                                        @endif
                                        @if(!empty($preset['defaultTension']))
                                            <span class="badge bg-secondary">{{ $tensionCurves[$preset['defaultTension']]['name'] ?? $preset['defaultTension'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Story Arcs -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-project-diagram me-2"></i>{{ __('Story Arcs') }}</h3>
                <p class="card-subtitle">{{ __('Narrative structure frameworks') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($storyArcs as $key => $arc)
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 class="card-title mb-0">{{ $arc['name'] }}</h4>
                                        <button onclick="toggleItem('arc', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm toggle-btn" data-type="arc" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_arcs'] ?? []))
                                                <i class="fa fa-eye-slash text-muted"></i>
                                            @else
                                                <i class="fa fa-eye text-success"></i>
                                            @endif
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-2">{{ $arc['description'] ?? '' }}</p>
                                    @if(!empty($arc['beats']))
                                        <div class="small text-primary">
                                            {{ implode(' → ', array_map(fn($b) => ucwords(str_replace('_', ' ', $b)), array_slice($arc['beats'], 0, 4))) }}...
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Tension Curves -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-chart-line me-2"></i>{{ __('Tension Curves') }}</h3>
                <p class="card-subtitle">{{ __('Pacing dynamics for engagement') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($tensionCurves as $key => $curve)
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 class="card-title mb-0">{{ $curve['name'] }}</h4>
                                        <button onclick="toggleItem('curve', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm toggle-btn" data-type="curve" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_curves'] ?? []))
                                                <i class="fa fa-eye-slash text-muted"></i>
                                            @else
                                                <i class="fa fa-eye text-success"></i>
                                            @endif
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-2">{{ $curve['description'] ?? '' }}</p>
                                    @if(!empty($curve['curve']))
                                        <div class="d-flex align-items-end gap-1" style="height: 32px;">
                                            @foreach($curve['curve'] as $value)
                                                <div class="flex-fill bg-success rounded-top" style="height: {{ $value }}%; min-height: 2px;"></div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Emotional Journeys -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-theater-masks me-2"></i>{{ __('Emotional Journeys') }}</h3>
                <p class="card-subtitle">{{ __('Viewer feeling arcs') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($emotionalJourneys as $key => $journey)
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 class="card-title mb-0">{{ $journey['name'] }}</h4>
                                        <button onclick="toggleItem('journey', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm toggle-btn" data-type="journey" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_journeys'] ?? []))
                                                <i class="fa fa-eye-slash text-muted"></i>
                                            @else
                                                <i class="fa fa-eye text-success"></i>
                                            @endif
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-2">{{ $journey['description'] ?? '' }}</p>
                                    @if(!empty($journey['emotionArc']))
                                        <div class="small text-danger mb-1">
                                            {{ implode(' → ', array_map(fn($e) => ucfirst($e), $journey['emotionArc'])) }}
                                        </div>
                                    @endif
                                    @if(!empty($journey['endFeeling']))
                                        <div class="small text-muted">
                                            {{ __('End feeling') }}: <span class="text-danger">{{ $journey['endFeeling'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Shot Types -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h3 class="card-title mb-0"><i class="fa fa-camera me-2"></i>{{ __('Shot Types') }}</h3>
                    <span class="badge bg-info ms-2">{{ __('Cinematography') }}</span>
                </div>
                <p class="card-subtitle">{{ __('Camera framing options for visual descriptions') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($shotTypes ?? [] as $key => $shot)
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="card card-sm h-100">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="fw-medium small">{{ $shot['name'] }}</span>
                                        <button onclick="toggleItem('shot_type', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm p-0 toggle-btn" data-type="shot_type" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_shot_types'] ?? []))
                                                <i class="fa fa-eye-slash text-muted small"></i>
                                            @else
                                                <i class="fa fa-eye text-success small"></i>
                                            @endif
                                        </button>
                                    </div>
                                    <span class="badge bg-info-lt small">{{ $shot['abbrev'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Lighting Styles -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h3 class="card-title mb-0"><i class="fa fa-lightbulb me-2"></i>{{ __('Lighting Styles') }}</h3>
                    <span class="badge bg-warning ms-2">{{ __('Cinematography') }}</span>
                </div>
                <p class="card-subtitle">{{ __('Lighting atmosphere for scene visuals') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($lightingStyles ?? [] as $key => $lighting)
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="card card-sm h-100">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="fw-medium small">{{ $lighting['name'] }}</span>
                                        <button onclick="toggleItem('lighting', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm p-0 toggle-btn" data-type="lighting" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_lightings'] ?? []))
                                                <i class="fa fa-eye-slash text-muted small"></i>
                                            @else
                                                <i class="fa fa-eye text-success small"></i>
                                            @endif
                                        </button>
                                    </div>
                                    @if(!empty($lighting['mood']))
                                        <span class="badge bg-warning-lt small">{{ $lighting['mood'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Color Grades -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h3 class="card-title mb-0"><i class="fa fa-palette me-2"></i>{{ __('Color Grades') }}</h3>
                    <span class="badge bg-danger ms-2">{{ __('Cinematography') }}</span>
                </div>
                <p class="card-subtitle">{{ __('Color grading styles for visual mood') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($colorGrades ?? [] as $key => $grade)
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="card card-sm h-100">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="fw-medium small">{{ $grade['name'] }}</span>
                                        <button onclick="toggleItem('color_grade', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm p-0 toggle-btn" data-type="color_grade" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_color_grades'] ?? []))
                                                <i class="fa fa-eye-slash text-muted small"></i>
                                            @else
                                                <i class="fa fa-eye text-success small"></i>
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Retention Hooks -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h3 class="card-title mb-0"><i class="fa fa-anchor me-2"></i>{{ __('Retention Hooks') }}</h3>
                    <span class="badge bg-orange ms-2">{{ __('Engagement') }}</span>
                </div>
                <p class="card-subtitle">{{ __('Engagement elements to maintain viewer attention') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($retentionHooks ?? [] as $key => $hook)
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h4 class="card-title mb-0">{{ $hook['name'] }}</h4>
                                            @if(!empty($hook['insertAfter']))
                                                <span class="text-orange small">@{{ $hook['insertAfter'] }}s</span>
                                            @endif
                                        </div>
                                        <button onclick="toggleItem('retention_hook', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm toggle-btn" data-type="retention_hook" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_retention_hooks'] ?? []))
                                                <i class="fa fa-eye-slash text-muted"></i>
                                            @else
                                                <i class="fa fa-eye text-success"></i>
                                            @endif
                                        </button>
                                    </div>
                                    @if(!empty($hook['templates']))
                                        @foreach(array_slice($hook['templates'], 0, 2) as $template)
                                            <p class="text-muted small fst-italic mb-1">"{{ $template }}"</p>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Transitions -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h3 class="card-title mb-0"><i class="fa fa-random me-2"></i>{{ __('Transitions') }}</h3>
                    <span class="badge bg-purple ms-2">{{ __('Engagement') }}</span>
                </div>
                <p class="card-subtitle">{{ __('Scene transition effects') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($transitions ?? [] as $key => $transition)
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 class="card-title mb-0">{{ $transition['name'] }}</h4>
                                        <button onclick="toggleItem('transition', '{{ $key }}')"
                                                class="btn btn-ghost-secondary btn-sm toggle-btn" data-type="transition" data-key="{{ $key }}">
                                            @if(in_array($key, $settings['disabled_transitions'] ?? []))
                                                <i class="fa fa-eye-slash text-muted"></i>
                                            @else
                                                <i class="fa fa-eye text-success"></i>
                                            @endif
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-2">{{ $transition['description'] ?? '' }}</p>
                                    @if(!empty($transition['duration']))
                                        <span class="badge bg-purple-lt">{{ $transition['duration'] }}ms</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
function toggleItem(type, key) {
    fetch('{{ route('admin.video-wizard.narrative.toggle') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type, key })
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const btn = document.querySelector(`[data-type="${type}"][data-key="${key}"]`);
            const icon = btn.querySelector('i');
            if (data.enabled) {
                icon.className = 'fa fa-eye text-success';
            } else {
                icon.className = 'fa fa-eye-slash text-muted';
            }
        }
    })
    .catch(error => {
        console.error('Toggle failed:', error);
    });
}
</script>
@endpush
