@extends('layouts.app')

@push('styles')
<style>
/* ========================================
   SCROLLABLE TABS
   ======================================== */
.nav-tabs-scrollable {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    padding-bottom: 2px;
    gap: 0.25rem;
}
.nav-tabs-scrollable::-webkit-scrollbar {
    height: 4px;
}
.nav-tabs-scrollable::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 2px;
}
.nav-tabs-scrollable .nav-item {
    flex-shrink: 0;
}
.nav-tabs-scrollable .nav-link {
    white-space: nowrap;
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
}
.nav-tabs-scrollable .nav-link .badge {
    font-size: 0.65rem;
}

/* ========================================
   TWO-COLUMN LAYOUT
   ======================================== */
.settings-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -12px;
}
.settings-row > .col-md-6 {
    padding: 0 12px;
    margin-bottom: 24px !important;
}

/* ========================================
   SETTING CARD
   ======================================== */
.setting-card {
    background: #fff;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px;
    padding: 20px !important;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.setting-card.bg-light {
    background: #f9fafb !important;
}

/* ========================================
   SETTING HEADER (Title + Icon)
   ======================================== */
.setting-card .setting-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 8px;
}
.setting-card .form-label {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0;
    line-height: 1.4;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 6px;
}
.setting-card .form-label i {
    color: #6b7280;
    font-size: 0.8125rem;
    width: 16px;
    text-align: center;
}
.setting-card .form-label .badge {
    font-size: 0.625rem;
    padding: 2px 6px;
    font-weight: 500;
}

/* ========================================
   DESCRIPTION TEXT
   ======================================== */
.setting-card .setting-description {
    font-size: 0.8125rem;
    line-height: 1.6;
    color: #6b7280;
    margin-bottom: 16px;
}

/* ========================================
   INPUT FIELDS - Unified Styling
   ======================================== */
.setting-card .form-control,
.setting-card .form-select {
    height: 40px;
    padding: 8px 12px;
    font-size: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background-color: #fff;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
.setting-card .form-control:focus,
.setting-card .form-select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
.setting-card textarea.form-control {
    height: auto;
    min-height: 64px;
    resize: vertical;
    line-height: 1.5;
}

/* Input Group */
.setting-card .input-group {
    display: flex;
    align-items: stretch;
}
.setting-card .input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}
.setting-card .input-group-text {
    height: 40px;
    padding: 8px 12px;
    font-size: 0.75rem;
    color: #6b7280;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-left: none;
    border-radius: 0 6px 6px 0;
    display: flex;
    align-items: center;
    white-space: nowrap;
}

/* ========================================
   TOGGLE SWITCH
   ======================================== */
.setting-card .form-check.form-switch {
    padding-left: 3rem;
    min-height: 40px;
    display: flex;
    align-items: center;
    margin: 0;
}
.setting-card .form-check-input {
    width: 44px;
    height: 24px;
    margin-left: -3rem;
    margin-top: 0;
    cursor: pointer;
}
.setting-card .form-check-label {
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
    padding-left: 8px;
    line-height: 24px;
}

/* ========================================
   SELECT / DROPDOWN
   ======================================== */
.setting-card .form-select {
    padding-right: 36px;
    background-position: right 12px center;
    background-size: 12px;
}

/* ========================================
   HELP TEXT
   ======================================== */
.setting-card .setting-help {
    display: block;
    margin-top: 8px;
    font-size: 0.75rem;
    line-height: 1.5;
    color: #9ca3af;
}
.setting-card .setting-help i {
    margin-right: 4px;
}
.setting-card .setting-help.text-success {
    color: #10b981 !important;
}
.setting-card .setting-help.text-warning {
    color: #f59e0b !important;
}

/* Default value indicator */
.setting-card .setting-default {
    display: block;
    margin-top: 8px;
    padding: 6px 10px;
    font-size: 0.75rem;
    line-height: 1.4;
    color: #92400e;
    background-color: #fef3c7;
    border-radius: 4px;
}
.setting-card .setting-default i {
    margin-right: 4px;
}

/* ========================================
   RESPONSIVE ADJUSTMENTS
   ======================================== */
@media (max-width: 768px) {
    .settings-row > .col-md-6 {
        margin-bottom: 16px !important;
    }
    .setting-card {
        padding: 16px !important;
    }
}
</style>
@endpush

@section('content')
<div class="border-bottom mb-1 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.video-wizard.index') }}">{{ __('Video Creator') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Dynamic Settings') }}</li>
                    </ol>
                </nav>
                <div class="fw-7 fs-20 text-primary-700">{{ __('Video Wizard Settings') }}</div>
                <p class="text-muted mb-0 small">{{ __('Configure AI providers, API endpoints, credit costs, shot intelligence, animation, and more') }}</p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('admin.video-wizard.dynamic-settings.seed-defaults') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('{{ __('This will add any missing default settings. Existing values will be preserved. Continue?') }}')">
                        <i class="fa fa-database me-1"></i> {{ __('Seed Defaults') }}
                    </button>
                </form>
                <form action="{{ route('admin.video-wizard.dynamic-settings.reset-all') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('{{ __('Reset ALL settings to their default values? This cannot be undone.') }}')">
                        <i class="fa fa-undo me-1"></i> {{ __('Reset All') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fa fa-cog fa-lg text-primary"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['total'] }}</div>
                        <div class="text-muted small">{{ __('Total Settings') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fa fa-check fa-lg text-success"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['active'] }}</div>
                        <div class="text-muted small">{{ __('Active Settings') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 rounded-3 p-3 me-3">
                        <i class="fa fa-layer-group fa-lg text-info"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats['categories'] }}</div>
                        <div class="text-muted small">{{ __('Categories') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form action="{{ route('admin.video-wizard.dynamic-settings.update') }}" method="POST">
        @csrf

        <!-- Category Tabs (Scrollable) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-2">
                <ul class="nav nav-tabs nav-tabs-scrollable border-0" id="settingsTabs" role="tablist">
                    @foreach($categories as $categorySlug => $categoryName)
                        @if(isset($settingsByCategory[$categorySlug]) && $settingsByCategory[$categorySlug]->count() > 0)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                        id="tab-{{ $categorySlug }}"
                                        data-bs-toggle="tab"
                                        data-bs-target="#content-{{ $categorySlug }}"
                                        type="button"
                                        role="tab">
                                    <i class="{{ $categoryIcons[$categorySlug] ?? 'fa fa-cog' }} me-1"></i>
                                    {{ $categoryName }}
                                    <span class="badge bg-secondary ms-1">{{ $settingsByCategory[$categorySlug]->count() }}</span>
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="settingsTabContent">
            @foreach($categories as $categorySlug => $categoryName)
                @if(isset($settingsByCategory[$categorySlug]) && $settingsByCategory[$categorySlug]->count() > 0)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                         id="content-{{ $categorySlug }}"
                         role="tabpanel">

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="{{ $categoryIcons[$categorySlug] ?? 'fa fa-cog' }} me-2 text-muted"></i>
                                        {{ $categoryName }}
                                    </h5>
                                </div>
                                <form action="{{ route('admin.video-wizard.dynamic-settings.reset-category', $categorySlug) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('{{ __('Reset all settings in this category to defaults?') }}')">
                                        <i class="fa fa-undo me-1"></i> {{ __('Reset Category') }}
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <div class="row settings-row">
                                    @foreach($settingsByCategory[$categorySlug] as $setting)
                                        <div class="col-md-6">
                                            <div class="setting-card {{ $setting->is_system ? 'bg-light' : '' }}">
                                                <div class="setting-header">
                                                    <label class="form-label" for="setting-{{ $setting->slug }}">
                                                        @if($setting->icon)
                                                            <i class="{{ $setting->icon }}"></i>
                                                        @endif
                                                        {{ $setting->name }}
                                                        @if($setting->is_system)
                                                            <span class="badge bg-secondary" title="{{ __('System setting') }}">
                                                                <i class="fa fa-lock"></i>
                                                            </span>
                                                        @endif
                                                    </label>
                                                </div>

                                                @if($setting->description)
                                                    <p class="setting-description">{{ $setting->description }}</p>
                                                @endif

                                                @php
                                                    $currentValue = $setting->getTypedValue() ?? $setting->getTypedDefaultValue();
                                                @endphp

                                                {{-- Input based on type --}}
                                                @switch($setting->input_type)
                                                    @case('checkbox')
                                                        <div class="form-check form-switch">
                                                            <input type="hidden" name="settings[{{ $setting->slug }}]" value="0">
                                                            <input class="form-check-input"
                                                                   type="checkbox"
                                                                   id="setting-{{ $setting->slug }}"
                                                                   name="settings[{{ $setting->slug }}]"
                                                                   value="1"
                                                                   {{ $currentValue ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="setting-{{ $setting->slug }}">
                                                                {{ $currentValue ? __('Enabled') : __('Disabled') }}
                                                            </label>
                                                        </div>
                                                        @break

                                                    @case('select')
                                                        <select class="form-select"
                                                                id="setting-{{ $setting->slug }}"
                                                                name="settings[{{ $setting->slug }}]">
                                                            @php
                                                                $allowedValues = $setting->allowed_values;
                                                                if (is_string($allowedValues)) {
                                                                    $allowedValues = json_decode($allowedValues, true) ?? [];
                                                                }
                                                            @endphp
                                                            @if(!empty($allowedValues) && is_array($allowedValues))
                                                                @foreach($allowedValues as $option)
                                                                    <option value="{{ $option }}" {{ $currentValue == $option ? 'selected' : '' }}>
                                                                        {{ is_string($option) ? ucfirst($option) : $option }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                        @break

                                                    @case('number')
                                                        <div class="input-group">
                                                            <input type="number"
                                                                   class="form-control"
                                                                   id="setting-{{ $setting->slug }}"
                                                                   name="settings[{{ $setting->slug }}]"
                                                                   value="{{ $currentValue }}"
                                                                   @if($setting->min_value !== null) min="{{ $setting->min_value }}" @endif
                                                                   @if($setting->max_value !== null) max="{{ $setting->max_value }}" @endif
                                                                   placeholder="{{ $setting->input_placeholder }}">
                                                            @if($setting->min_value !== null && $setting->max_value !== null)
                                                                <span class="input-group-text text-muted small">
                                                                    {{ $setting->min_value }}-{{ $setting->max_value }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @break

                                                    @case('password')
                                                        <div class="input-group">
                                                            <input type="password"
                                                                   class="form-control"
                                                                   id="setting-{{ $setting->slug }}"
                                                                   name="settings[{{ $setting->slug }}]"
                                                                   value="{{ $currentValue }}"
                                                                   placeholder="{{ $setting->input_placeholder ?: '••••••••' }}"
                                                                   autocomplete="new-password">
                                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('setting-{{ $setting->slug }}')" title="{{ __('Toggle visibility') }}">
                                                                <i class="fa fa-eye" id="eye-setting-{{ $setting->slug }}"></i>
                                                            </button>
                                                        </div>
                                                        @if($currentValue)
                                                            <small class="setting-help text-success">
                                                                <i class="fa fa-check-circle"></i>
                                                                {{ __('API key is configured') }}
                                                            </small>
                                                        @else
                                                            <small class="setting-help text-warning">
                                                                <i class="fa fa-exclamation-triangle"></i>
                                                                {{ __('Not configured') }}
                                                            </small>
                                                        @endif
                                                        @break

                                                    @case('textarea')
                                                        <textarea class="form-control font-monospace"
                                                                  id="setting-{{ $setting->slug }}"
                                                                  name="settings[{{ $setting->slug }}]"
                                                                  rows="6"
                                                                  placeholder="{{ $setting->input_placeholder }}">{{ is_array($currentValue) ? json_encode($currentValue, JSON_PRETTY_PRINT) : $currentValue }}</textarea>
                                                        @break

                                                    @case('json_editor')
                                                        <textarea class="form-control font-monospace"
                                                                  id="setting-{{ $setting->slug }}"
                                                                  name="settings[{{ $setting->slug }}]"
                                                                  rows="3"
                                                                  placeholder="{{ $setting->input_placeholder }}">{{ is_array($currentValue) ? json_encode($currentValue, JSON_PRETTY_PRINT) : $currentValue }}</textarea>
                                                        <small class="setting-help">{{ __('Enter valid JSON') }}</small>
                                                        @break

                                                    @default
                                                        @if(is_array($currentValue))
                                                            {{-- Array value - show as JSON textarea --}}
                                                            <textarea class="form-control font-monospace"
                                                                      id="setting-{{ $setting->slug }}"
                                                                      name="settings[{{ $setting->slug }}]"
                                                                      rows="4"
                                                                      placeholder="{{ $setting->input_placeholder }}">{{ json_encode($currentValue, JSON_PRETTY_PRINT) }}</textarea>
                                                            <small class="setting-help">{{ __('JSON format') }}</small>
                                                        @elseif(is_string($currentValue) && (strlen($currentValue) > 40 || str_contains($currentValue, ',')))
                                                            {{-- Long text or comma-separated values - show as textarea --}}
                                                            <textarea class="form-control"
                                                                      id="setting-{{ $setting->slug }}"
                                                                      name="settings[{{ $setting->slug }}]"
                                                                      rows="2"
                                                                      placeholder="{{ $setting->input_placeholder }}">{{ $currentValue }}</textarea>
                                                            @if(str_contains($currentValue ?? '', ','))
                                                                <small class="setting-help">{{ __('Comma-separated list') }}</small>
                                                            @endif
                                                        @else
                                                            <input type="text"
                                                                   class="form-control"
                                                                   id="setting-{{ $setting->slug }}"
                                                                   name="settings[{{ $setting->slug }}]"
                                                                   value="{{ $currentValue }}"
                                                                   placeholder="{{ $setting->input_placeholder }}">
                                                        @endif
                                                @endswitch

                                                @if($setting->input_help)
                                                    <small class="setting-help">
                                                        <i class="fa fa-info-circle"></i>
                                                        {{ $setting->input_help }}
                                                    </small>
                                                @endif

                                                @if($setting->default_value && $setting->value !== $setting->default_value)
                                                    <small class="setting-default">
                                                        <i class="fa fa-exclamation-triangle"></i>
                                                        {{ __('Default:') }} {{ is_array($setting->getTypedDefaultValue()) ? json_encode($setting->getTypedDefaultValue()) : $setting->getTypedDefaultValue() }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Save Button -->
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    <i class="fa fa-info-circle me-1"></i>
                    {{ __('Changes take effect immediately after saving. Caches will be automatically cleared.') }}
                </div>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-save me-2"></i>
                    {{ __('Save All Settings') }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Toggle checkbox label text
    document.querySelectorAll('.form-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (label) {
                label.textContent = this.checked ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}';
            }
        });
    });

    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const eyeIcon = document.getElementById('eye-' + inputId);
        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }

    // JSON validation for json_editor inputs
    document.querySelectorAll('textarea[id^="setting-"]').forEach(textarea => {
        if (textarea.closest('.col-md-6')?.querySelector('small')?.textContent.includes('JSON')) {
            textarea.addEventListener('blur', function() {
                try {
                    if (this.value.trim()) {
                        JSON.parse(this.value);
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                } catch (e) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        }
    });
</script>
@endpush
@endsection
