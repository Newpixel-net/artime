# Phase 21: Data Normalization - Research

**Researched:** 2026-01-27
**Domain:** Laravel Eloquent models, JSON-to-relational normalization, Livewire 3 lazy loading
**Confidence:** HIGH

## Summary

This research investigates how to normalize the nested array data structures (`$script['scenes']`, `$storyboard['scenes']`, `$multiShotMode['decomposedScenes']`) into proper Eloquent models (WizardScene, WizardShot) with lazy loading capabilities. The current architecture stores scenes as JSON arrays within the `script` and `storyboard` columns of `wizard_projects`, which creates performance issues when loading large projects (30+ scenes with 3-10 shots each).

The recommended approach introduces two new Eloquent models (WizardScene, WizardShot) with HasMany relationships to WizardProject. Data loads on-demand per scene using Livewire 3's `#[Lazy]` component pattern combined with Eloquent's eager loading. The migration strategy preserves backward compatibility by keeping JSON columns during transition, with a data migration command to normalize existing projects.

**Primary recommendation:** Create WizardScene and WizardShot models, implement lazy-loaded scene cards as child Livewire components, use computed properties to avoid serializing full scene arrays, and provide artisan command for data migration.

## Current Data Structure Analysis

### Problem: Nested Arrays in JSON Columns

Current architecture stores all scene data in three JSON columns:

```
wizard_projects table:
├── script JSON column (~50-500KB per project)
│   └── scenes[] - array of scene objects
│       ├── narration
│       ├── visualPrompt
│       ├── duration
│       ├── speechType
│       ├── speechSegments[] - nested array
│       │   ├── id, type, text, speaker
│       │   ├── voiceId, characterId
│       │   └── startTime, duration, audioUrl
│       ├── voiceover {}
│       └── transition
│
├── storyboard JSON column (~100KB-2MB per project)
│   └── scenes[] - array of storyboard data
│       ├── imageUrl, imageStatus
│       ├── prompt, jobId
│       └── shots[] - nested array (when multiShotMode)
│           ├── id, imagePrompt, videoPrompt
│           ├── imageUrl, videoUrl
│           ├── cameraMovement, duration
│           └── dialogue, speakingCharacters[]
│
└── animation JSON column (~50-200KB per project)
    └── scenes[] - array of animation data
        ├── videoUrl, videoStatus
        ├── voiceoverUrl, assetId
        └── shots[] - same as storyboard shots
```

### Why This Is a Problem

1. **Payload Bloat:** Every Livewire request serializes entire arrays (~500KB-2MB)
2. **No Lazy Loading:** Cannot load just one scene without loading all
3. **N+1 Query Risk:** Accessing related data (shots, speech segments) inefficient
4. **No Indexing:** Cannot query individual scenes by properties
5. **Data Integrity:** No foreign key constraints, orphaned data possible
6. **Scalability:** Large projects (100+ scenes) become unusable

## Standard Stack

The established libraries/tools for this domain:

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Eloquent | 12.x | ORM with relationships | Already in use, HasMany/BelongsTo patterns |
| livewire/livewire | 3.6.4 | Component lazy loading | #[Lazy] attribute, #[Computed] for derived data |
| MySQL 8.0 | 8.0+ | JSON column support | Virtual columns for indexing JSON if needed |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Laravel Migrations | 12.x | Schema changes | Creating new tables, data migrations |
| Eloquent Casts | 12.x | Array/JSON casting | For remaining JSON fields on models |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Separate models | Keep JSON + virtual columns | Virtual columns only help queries, not loading |
| Full normalization | Partial normalization | Could keep some data as JSON where relationships aren't needed |
| Livewire lazy components | Alpine.js fetch | Livewire has built-in hydration, Alpine needs manual |

**Installation:**
No new packages required - all tools already in Laravel/Livewire.

## Architecture Patterns

### Recommended Model Structure

```
modules/AppVideoWizard/app/Models/
├── WizardProject.php (existing - add relationships)
├── WizardScene.php (NEW)
│   ├── BelongsTo: WizardProject
│   ├── HasMany: WizardShot
│   └── HasMany: WizardSpeechSegment
├── WizardShot.php (NEW)
│   ├── BelongsTo: WizardScene
│   └── Casts: metadata as array
└── WizardSpeechSegment.php (NEW)
    └── BelongsTo: WizardScene
```

### Pattern 1: WizardScene Model

**What:** Eloquent model representing a single scene in the wizard
**When to use:** Any access to scene data (script, storyboard, animation)
**Example:**
```php
// Source: https://laravel.com/docs/12.x/eloquent-relationships

namespace Modules\AppVideoWizard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WizardScene extends Model
{
    protected $table = 'wizard_scenes';

    protected $fillable = [
        'project_id',
        'order',
        'narration',
        'visual_prompt',
        'duration',
        'speech_type',
        'transition',
        // Storyboard fields
        'image_url',
        'image_status',
        'image_prompt',
        'image_job_id',
        // Animation fields
        'video_url',
        'video_status',
        'voiceover_url',
        // Metadata
        'scene_metadata',
    ];

    protected $casts = [
        'order' => 'integer',
        'duration' => 'integer',
        'scene_metadata' => 'array', // For less-frequently accessed data
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(WizardProject::class, 'project_id');
    }

    public function shots(): HasMany
    {
        return $this->hasMany(WizardShot::class, 'scene_id')
            ->orderBy('order');
    }

    public function speechSegments(): HasMany
    {
        return $this->hasMany(WizardSpeechSegment::class, 'scene_id')
            ->orderBy('order');
    }
}
```

### Pattern 2: WizardShot Model

**What:** Eloquent model for multi-shot decomposition data
**When to use:** Hollywood-style shot-based workflows
**Example:**
```php
// Source: https://laravel.com/docs/12.x/eloquent-relationships

namespace Modules\AppVideoWizard\Models;

class WizardShot extends Model
{
    protected $table = 'wizard_shots';

    protected $fillable = [
        'scene_id',
        'order',
        'image_prompt',
        'video_prompt',
        'camera_movement',
        'duration',
        'duration_class',
        'image_url',
        'image_status',
        'video_url',
        'video_status',
        'dialogue',
        'shot_metadata',
    ];

    protected $casts = [
        'order' => 'integer',
        'duration' => 'integer',
        'shot_metadata' => 'array', // speaking_characters, etc.
    ];

    public function scene(): BelongsTo
    {
        return $this->belongsTo(WizardScene::class, 'scene_id');
    }
}
```

### Pattern 3: Livewire Lazy-Loaded Scene Card

**What:** Child component that loads scene data on-demand
**When to use:** Storyboard step displaying multiple scenes
**Example:**
```php
// Source: https://livewire.laravel.com/docs/3.x/lazy

namespace Modules\AppVideoWizard\Livewire\Components;

use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use Livewire\Component;

#[Lazy]
class SceneCard extends Component
{
    #[Locked]
    public int $sceneId;

    #[Locked]
    public int $projectId;

    // Scene data loaded lazily via computed property
    #[Computed]
    public function scene(): ?WizardScene
    {
        return WizardScene::with(['shots', 'speechSegments'])
            ->find($this->sceneId);
    }

    public function placeholder()
    {
        return view('appvideowizard::livewire.components.scene-card-placeholder');
    }

    public function render()
    {
        return view('appvideowizard::livewire.components.scene-card');
    }
}

// In parent Storyboard component blade:
@foreach($sceneIds as $sceneId)
    <livewire:app-video-wizard::components.scene-card
        :scene-id="$sceneId"
        :project-id="$projectId"
        lazy
        wire:key="scene-card-{{ $sceneId }}"
    />
@endforeach
```

### Pattern 4: Eager Loading for Scene Inspector

**What:** Load single scene with all relations for detailed view
**When to use:** Scene inspector modal, editing scene
**Example:**
```php
// Source: https://laravel.com/docs/12.x/eloquent-relationships

// In VideoWizard.php
#[Computed]
public function inspectorScene(): ?WizardScene
{
    if ($this->inspectorSceneId === null) {
        return null;
    }

    return WizardScene::with([
        'shots' => fn ($q) => $q->orderBy('order'),
        'speechSegments' => fn ($q) => $q->orderBy('order'),
    ])->find($this->inspectorSceneId);
}

// Access in template
{{ $this->inspectorScene?->narration }}
@foreach($this->inspectorScene?->shots ?? [] as $shot)
    {{ $shot->image_url }}
@endforeach
```

### Pattern 5: Data Migration from JSON to Models

**What:** Artisan command to migrate existing JSON data to normalized tables
**When to use:** After models and migrations are created
**Example:**
```php
// Source: Laravel documentation on commands and migrations

namespace Modules\AppVideoWizard\Console;

use Illuminate\Console\Command;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardScene;
use Modules\AppVideoWizard\Models\WizardShot;
use Modules\AppVideoWizard\Models\WizardSpeechSegment;

class NormalizeProjectData extends Command
{
    protected $signature = 'wizard:normalize-data {--project=} {--dry-run}';
    protected $description = 'Migrate JSON scene data to normalized tables';

    public function handle()
    {
        $projectId = $this->option('project');
        $dryRun = $this->option('dry-run');

        $query = WizardProject::query();
        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        foreach ($projects as $project) {
            $this->normalizeProject($project, $dryRun);
        }
    }

    protected function normalizeProject(WizardProject $project, bool $dryRun): void
    {
        $script = $project->script ?? [];
        $storyboard = $project->storyboard ?? [];

        foreach ($script['scenes'] ?? [] as $index => $sceneData) {
            $storyboardData = $storyboard['scenes'][$index] ?? [];

            // Create WizardScene
            $sceneAttributes = [
                'project_id' => $project->id,
                'order' => $index,
                'narration' => $sceneData['narration'] ?? null,
                'visual_prompt' => $sceneData['visualPrompt'] ?? null,
                'duration' => $sceneData['duration'] ?? 8,
                'speech_type' => $sceneData['speechType'] ?? 'voiceover',
                'transition' => $sceneData['transition'] ?? 'cut',
                'image_url' => $storyboardData['imageUrl'] ?? null,
                'image_status' => $storyboardData['status'] ?? 'pending',
                'image_prompt' => $storyboardData['prompt'] ?? null,
            ];

            if (!$dryRun) {
                $scene = WizardScene::create($sceneAttributes);

                // Create shots if multi-shot mode
                foreach ($storyboardData['shots'] ?? [] as $shotIndex => $shotData) {
                    WizardShot::create([
                        'scene_id' => $scene->id,
                        'order' => $shotIndex,
                        'image_prompt' => $shotData['imagePrompt'] ?? null,
                        'video_prompt' => $shotData['videoPrompt'] ?? null,
                        'camera_movement' => $shotData['cameraMovement'] ?? null,
                        'duration' => $shotData['duration'] ?? 5,
                        'image_url' => $shotData['imageUrl'] ?? null,
                        'video_url' => $shotData['videoUrl'] ?? null,
                    ]);
                }

                // Create speech segments
                foreach ($sceneData['speechSegments'] ?? [] as $segIndex => $segData) {
                    WizardSpeechSegment::create([
                        'scene_id' => $scene->id,
                        'order' => $segIndex,
                        'type' => $segData['type'] ?? 'narrator',
                        'text' => $segData['text'] ?? '',
                        'speaker' => $segData['speaker'] ?? null,
                        'voice_id' => $segData['voiceId'] ?? null,
                    ]);
                }
            }

            $this->info("Scene {$index} normalized for project {$project->id}");
        }
    }
}
```

### Anti-Patterns to Avoid

- **Loading all scenes for pagination:** Use database LIMIT/OFFSET, not PHP array_slice
- **Serializing WizardScene models in Livewire state:** Use IDs and computed properties
- **Eager loading relations not needed:** Only load shots when multiShotMode is enabled
- **Removing JSON columns immediately:** Keep for backward compatibility during transition
- **N+1 queries in loops:** Always use eager loading with `with()` for relations

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Lazy loading scenes | Custom AJAX fetch | Livewire #[Lazy] components | Built-in placeholder, viewport detection |
| Caching scene data | Manual caching | #[Computed(persist: true)] | Livewire handles cache invalidation |
| Ordering scenes/shots | Array sorting | Eloquent orderBy + order column | Database handles it efficiently |
| JSON field access | Manual decode | Eloquent $casts array | Automatic serialization/deserialization |
| Parent model access | Manual queries | BelongsTo relation with chaperone() | Prevents N+1 when accessing parent |
| Data migration | Manual SQL | Artisan command with progress | Safer, reversible, can dry-run |

**Key insight:** Livewire 3's computed properties with caching (`persist: true`) combined with Eloquent's eager loading solve the lazy-load problem without building custom infrastructure.

## Common Pitfalls

### Pitfall 1: Livewire Model Serialization Overhead

**What goes wrong:** Storing WizardScene models in public properties causes them to be serialized/deserialized every request
**Why it happens:** Livewire hydrates models by re-querying from database
**How to avoid:** Store only IDs in public properties, use #[Computed] for actual model access
**Warning signs:** Slow component updates, high database queries per request

### Pitfall 2: Breaking Existing Project Load

**What goes wrong:** Old projects with JSON data fail to load after migration
**Why it happens:** Code expects normalized data but project hasn't been migrated
**How to avoid:** Keep JSON columns, add fallback logic to check both sources
**Warning signs:** Errors on old projects, empty scene lists

```php
// Safe fallback pattern
#[Computed]
public function scenes(): Collection
{
    // Check normalized tables first
    $normalizedScenes = $this->project->scenes()->get();
    if ($normalizedScenes->isNotEmpty()) {
        return $normalizedScenes;
    }

    // Fall back to JSON (legacy)
    return collect($this->project->script['scenes'] ?? [])
        ->map(fn ($s, $i) => (object) array_merge($s, ['id' => $i]));
}
```

### Pitfall 3: Circular Relationship Loading

**What goes wrong:** Scene loads shots, shot tries to access scene.project
**Why it happens:** Missing chaperone() or wrong eager loading
**How to avoid:** Use chaperone() on HasMany relationships that need parent access
**Warning signs:** N+1 queries when iterating shots and accessing parent

### Pitfall 4: Livewire Key Collisions

**What goes wrong:** Scene cards re-render incorrectly when scenes are reordered
**Why it happens:** Using index as key instead of stable ID
**How to avoid:** Use `wire:key="scene-{{ $scene->id }}"` with database ID
**Warning signs:** Wrong scene data displayed after drag-drop reorder

### Pitfall 5: Over-Eager Loading

**What goes wrong:** Loading all shots for all scenes even when only viewing one
**Why it happens:** Eager loading at project level instead of scene level
**How to avoid:** Load relations only when accessing specific scene
**Warning signs:** Large initial page load, memory spikes

## Code Examples

Verified patterns from official sources:

### Database Migration for WizardScene

```php
// Source: https://laravel.com/docs/12.x/migrations

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('wizard_projects')
                ->onDelete('cascade');

            // Order within project
            $table->unsignedInteger('order')->default(0);

            // Script data
            $table->text('narration')->nullable();
            $table->text('visual_prompt')->nullable();
            $table->unsignedSmallInteger('duration')->default(8);
            $table->string('speech_type', 20)->default('voiceover');
            $table->string('transition', 20)->default('cut');

            // Storyboard data
            $table->string('image_url', 500)->nullable();
            $table->string('image_status', 20)->default('pending');
            $table->text('image_prompt')->nullable();
            $table->string('image_job_id', 100)->nullable();

            // Animation data
            $table->string('video_url', 500)->nullable();
            $table->string('video_status', 20)->default('pending');
            $table->string('voiceover_url', 500)->nullable();

            // Flexible metadata (JSON for less-frequent fields)
            $table->json('scene_metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'order']);
            $table->index('image_status');
            $table->index('video_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_scenes');
    }
};
```

### Database Migration for WizardShot

```php
// Source: https://laravel.com/docs/12.x/migrations

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_shots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')
                ->constrained('wizard_scenes')
                ->onDelete('cascade');

            // Order within scene
            $table->unsignedTinyInteger('order')->default(0);

            // Prompts
            $table->text('image_prompt')->nullable();
            $table->text('video_prompt')->nullable();

            // Technical specs
            $table->string('camera_movement', 50)->nullable();
            $table->unsignedTinyInteger('duration')->default(5);
            $table->string('duration_class', 20)->default('short');

            // Generated assets
            $table->string('image_url', 500)->nullable();
            $table->string('image_status', 20)->default('pending');
            $table->string('video_url', 500)->nullable();
            $table->string('video_status', 20)->default('pending');

            // Dialogue/speech for this shot
            $table->text('dialogue')->nullable();

            // Flexible metadata
            $table->json('shot_metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['scene_id', 'order']);
            $table->index('image_status');
            $table->index('video_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_shots');
    }
};
```

### Database Migration for WizardSpeechSegment

```php
// Source: https://laravel.com/docs/12.x/migrations

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_speech_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')
                ->constrained('wizard_scenes')
                ->onDelete('cascade');

            // Order within scene
            $table->unsignedTinyInteger('order')->default(0);

            // Segment data (from SpeechSegment service class)
            $table->string('type', 20)->default('narrator');
            $table->text('text');
            $table->string('speaker', 100)->nullable();
            $table->string('character_id', 50)->nullable();
            $table->string('voice_id', 50)->nullable();

            // Timing (set after audio generation)
            $table->float('start_time')->nullable();
            $table->float('duration')->nullable();

            // Generated audio
            $table->string('audio_url', 500)->nullable();

            // Additional attributes
            $table->string('emotion', 50)->nullable();
            $table->boolean('needs_lip_sync')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['scene_id', 'order']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_speech_segments');
    }
};
```

### WizardProject Relationship Updates

```php
// Source: https://laravel.com/docs/12.x/eloquent-relationships

// Add to existing WizardProject.php
public function scenes(): HasMany
{
    return $this->hasMany(WizardScene::class, 'project_id')
        ->orderBy('order');
}

/**
 * Get scene count (computed from relationship or JSON fallback).
 */
public function getSceneCount(): int
{
    // Try normalized data first
    $count = $this->scenes()->count();
    if ($count > 0) {
        return $count;
    }

    // Fallback to JSON
    return count($this->script['scenes'] ?? []);
}

/**
 * Check if project uses normalized data.
 */
public function usesNormalizedData(): bool
{
    return $this->scenes()->exists();
}
```

### VideoWizard Component Updates

```php
// Source: https://livewire.laravel.com/docs/3.x/computed-properties

// Replace array-based scene access with model-based
#[Computed(persist: true, seconds: 300)]
public function sceneIds(): array
{
    if ($this->project->usesNormalizedData()) {
        return $this->project->scenes()->pluck('id')->toArray();
    }

    // Fallback for legacy projects
    return array_keys($this->script['scenes'] ?? []);
}

#[Computed]
public function paginatedSceneIds(): array
{
    $allIds = $this->sceneIds;
    $offset = ($this->storyboardPage - 1) * $this->storyboardPerPage;
    return array_slice($allIds, $offset, $this->storyboardPerPage);
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| JSON columns for all data | Normalized models + JSON for metadata | Laravel 5.7+ | Better queries, relations |
| Load all scenes at once | Lazy-loaded scene components | Livewire 3.0 | Viewport-based loading |
| Array state in Livewire | IDs + computed properties | Livewire 3.0 | Reduced payload |
| Manual pagination in PHP | Database LIMIT/OFFSET | Always better | Performance |
| Direct JSON column queries | Eloquent relations with eager load | Laravel 8+ | Type safety, caching |

**Deprecated/outdated:**
- Storing binary/base64 in JSON columns (use file storage + URLs)
- Full model binding in Livewire (use IDs + computed)
- Manual array slicing for pagination (use database)

## Open Questions

Things that couldn't be fully resolved:

1. **Multi-shot enabled check timing**
   - What we know: multiShotMode['enabled'] determines if shots exist
   - What's unclear: Should shots table only be populated when multi-shot enabled?
   - Recommendation: Always create WizardScene, create WizardShot only when multi-shot enabled

2. **Scene DNA integration**
   - What we know: sceneDNA is per-scene computed data from Bibles
   - What's unclear: Should sceneDNA be stored in wizard_scenes or remain computed?
   - Recommendation: Keep as computed (derived data), possibly cache in scene_metadata JSON

3. **Backward compatibility duration**
   - What we know: Need to support both JSON and normalized data
   - What's unclear: How long to maintain dual support?
   - Recommendation: Keep JSON columns indefinitely, add deprecation warning after 3 months

4. **Real-time collaboration**
   - What we know: Current arrays don't support concurrent editing
   - What's unclear: Do normalized models need optimistic locking?
   - Recommendation: Defer - not in current requirements

## Sources

### Primary (HIGH confidence)
- [Laravel 12.x Eloquent Relationships](https://laravel.com/docs/12.x/eloquent-relationships) - HasMany, BelongsTo, eager loading
- [Livewire 3.x Lazy Loading](https://livewire.laravel.com/docs/3.x/lazy) - #[Lazy] attribute, placeholders
- [Livewire 3.x Computed Properties](https://livewire.laravel.com/docs/3.x/computed-properties) - #[Computed], persist, cache
- [Laravel 12.x Migrations](https://laravel.com/docs/12.x/migrations) - Schema building, foreign keys

### Secondary (MEDIUM confidence)
- [Laravel Daily: JSON Column Type](https://laraveldaily.com/lesson/structuring-databases-laravel/json-column-type) - When to use JSON vs relations
- [Livewire GitHub Discussion #5586](https://github.com/livewire/livewire/discussions/5586) - Model binding best practices
- [Medium: Eager Loading Saved 1.2M Queries](https://inspector.dev/save-1-2-million-queries-per-day-with-laravel-eager-loading/) - Performance impact

### Tertiary (LOW confidence)
- Phase 19-20 prior work (internal) - Pattern precedents in this codebase

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Laravel/Livewire core features, well documented
- Architecture patterns: HIGH - Based on official documentation and prior phases
- Pitfalls: MEDIUM - Based on community experience, some codebase-specific
- Migration strategy: MEDIUM - Depends on existing data volume and complexity

**Research date:** 2026-01-27
**Valid until:** 2026-02-27 (30 days - stable patterns, Laravel/Livewire unlikely to change)

---

## Appendix: Data Size Estimation

### Current JSON Size Analysis

Based on typical VideoWizard projects:

| Project Size | Scenes | Shots/Scene | JSON Size | Est. After Normalization |
|--------------|--------|-------------|-----------|--------------------------|
| Small | 5-10 | 3 | ~100KB | ~5KB (IDs only) |
| Medium | 15-30 | 5 | ~500KB | ~15KB (IDs only) |
| Large | 50-100 | 8 | ~2MB | ~50KB (IDs only) |
| Feature | 100+ | 10 | ~5MB+ | ~100KB (IDs only) |

### Livewire Payload Impact

- **Before:** Full scene arrays serialized (~500KB-5MB per request)
- **After:** Scene IDs only + lazy-loaded components (~50KB per request)
- **Estimated improvement:** 90% reduction in payload size

### Database Query Pattern

- **Before:** 1 query loads everything, PHP processes arrays
- **After:** 1 query for IDs + N queries for visible scenes (lazy)
- **Net effect:** Better for large projects, same for small projects

## Appendix: Migration Order

Recommended implementation sequence:

1. **Phase 21-01:** Create migrations (wizard_scenes, wizard_shots, wizard_speech_segments)
2. **Phase 21-02:** Create Eloquent models with relationships
3. **Phase 21-03:** Create data migration command (artisan wizard:normalize-data)
4. **Phase 21-04:** Update VideoWizard to support both JSON and normalized data
5. **Phase 21-05:** Create lazy-loaded SceneCard component
6. **Phase 21-06:** Update storyboard blade to use SceneCard components
7. **Phase 21-07:** Run data migration on existing projects (gradual)
8. **Phase 21-08:** Performance benchmarking and optimization
