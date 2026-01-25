---
phase: 19
plan: 03
subsystem: livewire-performance
tags: [livewire, performance, file-storage, base64, lazy-loading]
dependency-graph:
  requires: ["19-01"]
  provides: ["file-based-image-storage", "lazy-loading-api"]
  affects: ["image-generation", "face-correction", "character-portraits"]
tech-stack:
  added: []
  patterns: ["file-storage", "lazy-loading", "runtime-caching"]
key-files:
  created:
    - modules/AppVideoWizard/app/Services/ReferenceImageStorageService.php
  modified:
    - modules/AppVideoWizard/app/Livewire/VideoWizard.php
decisions:
  - id: base64-storage-path
    choice: "video-wizard/reference-images/{projectId}/"
    reason: "Organized by project for easy cleanup"
  - id: runtime-cache
    choice: "#[Locked] protected $loadedBase64Cache"
    reason: "Avoid repeated disk reads during single request"
metrics:
  duration: "~15 minutes"
  completed: "2026-01-25"
---

# Phase 19 Plan 03: Base64 Storage Migration Summary

File-based storage for reference images with lazy loading to eliminate base64 data from Livewire state serialization.

## One-liner

ReferenceImageStorageService stores base64 images as files, VideoWizard uses lazy loading for API calls only.

## Objectives Achieved

1. Created ReferenceImageStorageService with storeBase64(), loadBase64(), deleteBase64(), deleteProjectImages()
2. Added lazy loading methods: getCharacterPortraitBase64(), getLocationReferenceBase64(), getStyleReferenceBase64()
3. Updated all SET operations to store images to files instead of component state
4. Updated all READ operations (API calls) to use lazy loading
5. Added backward compatibility migration for legacy base64 data
6. Updated sceneMemory initialization to use referenceImageStorageKey instead of referenceImageBase64

## Commits

| Hash | Type | Description |
|------|------|-------------|
| 4830334 | feat | Create ReferenceImageStorageService for file-based image storage |
| 03279c3 | feat | Integrate file-based reference image storage in VideoWizard |

## Key Implementations

### ReferenceImageStorageService

New service at `modules/AppVideoWizard/app/Services/ReferenceImageStorageService.php`:

```php
class ReferenceImageStorageService
{
    protected string $disk = 'local';
    protected string $basePath = 'video-wizard/reference-images';

    public function storeBase64(int $projectId, string $type, string|int $identifier, string $base64Data, ?string $mimeType): string
    public function loadBase64(string $storageKey): ?string
    public function deleteBase64(string $storageKey): bool
    public function deleteProjectImages(int $projectId): bool
    public function exists(string $storageKey): bool
}
```

Storage pattern: `storage/app/video-wizard/reference-images/{projectId}/{type}-{identifier}-{random}.{ext}`

### VideoWizard Changes

1. **New property for runtime caching:**
```php
#[Locked]
protected array $loadedBase64Cache = [];
```

2. **Helper methods added:**
```php
protected function storeReferenceImage(string $type, string|int $identifier, string $base64Data, ?string $mimeType): ?string
protected function loadReferenceImage(?string $storageKey): ?string
protected function deleteStoredReferenceImage(?string $storageKey): bool
public function getCharacterPortraitBase64(int $index): ?string
public function getLocationReferenceBase64(int $index): ?string
public function getStyleReferenceBase64(): ?string
protected function migrateBase64ToStorage(): void
```

3. **Updated methods (SET operations):**
- generateCharacterPortrait() - stores to file
- uploadCharacterPortrait() - stores to file
- generateLocationReference() - stores to file
- uploadLocationReference() - stores to file
- generateStyleReference() - stores to file
- uploadStyleReference() - stores to file
- updateCharacterWithReference() - stores to file
- updateLocationWithReference() - stores to file
- collage portrait extraction - stores to file

4. **Updated methods (READ operations):**
- getCharacterReferenceImages() - uses lazy loading
- applyFaceCorrection() - uses lazy loading
- ShotFaceCorrection - uses lazy loading
- extractDNAFromPortrait() - uses lazy loading

5. **Updated methods (DELETE operations):**
- removeCharacterPortrait() - deletes stored file
- removeLocationReference() - deletes stored file
- removeStyleReference() - deletes stored file

## Performance Impact

### Before

- Each character portrait: 100-500KB base64 in component state
- Each location reference: 100-500KB base64 in component state
- Style reference: 100-500KB base64 in component state
- **Total with 5 characters + 3 locations + style: 0.9MB - 4.5MB per request**

### After

- **Component state: 0 bytes for reference images**
- Images stored on disk, loaded only when API calls need them
- Runtime cache prevents repeated disk reads during single request
- **Serialization payload reduced by 0.9MB - 4.5MB**

## Backward Compatibility

Legacy projects with inline base64 data are automatically migrated:

1. On first access via `getCharacterPortraitBase64()`, `getLocationReferenceBase64()`, or `getStyleReferenceBase64()`
2. The lazy loading method detects legacy base64, stores it to file, updates the storage key, and returns the data
3. Next project save persists the new storage key, clearing the legacy base64 field

Batch migration available via `migrateBase64ToStorage()` for proactive migration.

## Deviations from Plan

None - plan executed exactly as written.

## Files Changed

### Created
- `modules/AppVideoWizard/app/Services/ReferenceImageStorageService.php` (198 lines)

### Modified
- `modules/AppVideoWizard/app/Livewire/VideoWizard.php` (+510/-73 lines)
  - Added ReferenceImageStorageService import
  - Added #[Locked] $loadedBase64Cache property
  - Added 6 helper/lazy-loading methods
  - Updated 15+ methods to use file storage
  - Updated 2 sceneMemory initializations

## Success Criteria Verification

| Criteria | Status |
|----------|--------|
| ReferenceImageStorageService exists with storeBase64(), loadBase64(), deleteBase64() | PASS |
| VideoWizard no longer stores base64 in component state | PASS |
| Images stored in storage/app/video-wizard/reference-images/{projectId}/ | PASS |
| Images lazy-loaded only when needed for API calls | PASS |
| Backward compatibility: existing projects with base64 migrated on first access | PASS |
| Character portrait, location reference, style reference functionality works | PASS |

## Next Phase Readiness

Plan 19-03 is complete. All success criteria met.

The file-based storage system is ready for:
- Character portraits (up to 10+ per project)
- Location references (up to 10+ per project)
- Style references (1 per project)

Estimated payload reduction: **0.9MB - 4.5MB per Livewire request** for typical projects with multiple reference images.
