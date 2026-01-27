<?php

namespace Modules\AppVideoWizard\Livewire\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Modules\AppVideoWizard\Services\ReferenceImageStorageService;
use Modules\AppVideoWizard\Services\CharacterLookService;
use Modules\AppVideoWizard\Services\BibleOrderingService;

/**
 * Character Bible Modal - Livewire Child Component
 *
 * Manages the Character Bible modal as an isolated child component.
 * Uses event-based communication with parent VideoWizard for:
 * - Data synchronization (characterBible updates)
 * - Portrait generation (dispatched to parent for long-running jobs)
 * - Modal state management
 *
 * This follows Livewire 3's "island architecture" pattern where child
 * components manage their own requests while coordinating with parents.
 *
 * @package Modules\AppVideoWizard\Livewire\Modals
 */
class CharacterBibleModal extends Component
{
    use WithFileUploads;

    // =========================================================================
    // PROPS FROM PARENT (via wire:model or attributes)
    // =========================================================================

    /**
     * Character Bible data - two-way bound with parent via wire:model
     */
    #[Modelable]
    public array $characterBible = [
        'enabled' => false,
        'characters' => [],
    ];

    /**
     * Project ID for storage operations
     */
    #[Locked]
    public ?string $projectId = null;

    /**
     * Visual mode for generation prompts (cinematic-realistic, etc.)
     */
    #[Locked]
    public string $visualMode = 'cinematic-realistic';

    /**
     * Content language for generation prompts
     */
    #[Locked]
    public string $contentLanguage = 'en';

    /**
     * Script scenes for character assignment
     */
    public array $scriptScenes = [];

    /**
     * Story Bible characters for sync feature
     */
    public array $storyBibleCharacters = [];

    /**
     * Story Bible status for sync feature
     */
    public string $storyBibleStatus = '';

    // =========================================================================
    // LOCAL STATE
    // =========================================================================

    /**
     * Modal visibility - controlled via events
     */
    public bool $show = false;

    /**
     * Currently editing character index
     */
    public int $editingCharacterIndex = 0;

    /**
     * File upload for character portraits
     */
    public $characterImageUpload = null;

    /**
     * Portrait generation in progress
     */
    public bool $isGeneratingPortrait = false;

    /**
     * Syncing from Story Bible in progress
     */
    public bool $isSyncingCharacterBible = false;

    /**
     * Emotion for voice preview
     */
    public ?string $previewEmotion = null;

    /**
     * Error message to display
     */
    public ?string $error = null;

    // =========================================================================
    // LIFECYCLE
    // =========================================================================

    public function mount(
        array $characterBible = [],
        ?string $projectId = null,
        string $visualMode = 'cinematic-realistic',
        string $contentLanguage = 'en',
        array $scriptScenes = [],
        array $storyBibleCharacters = [],
        string $storyBibleStatus = ''
    ): void {
        $this->characterBible = $characterBible ?: [
            'enabled' => false,
            'characters' => [],
        ];
        $this->projectId = $projectId;
        $this->visualMode = $visualMode;
        $this->contentLanguage = $contentLanguage;
        $this->scriptScenes = $scriptScenes;
        $this->storyBibleCharacters = $storyBibleCharacters;
        $this->storyBibleStatus = $storyBibleStatus;
    }

    public function render()
    {
        return view('appvideowizard::livewire.modals.character-bible-modal');
    }

    // =========================================================================
    // EVENT LISTENERS (from parent)
    // =========================================================================

    /**
     * Open the Character Bible modal
     */
    #[On('open-character-bible')]
    public function openModal(): void
    {
        $this->show = true;
        $this->editingCharacterIndex = 0;
        $this->error = null;

        // Auto-sync from Story Bible if available
        if (!empty($this->storyBibleCharacters) && $this->storyBibleStatus === 'ready') {
            $this->isSyncingCharacterBible = true;
            $this->syncFromStoryBible();
            $this->isSyncingCharacterBible = false;
        }

        Log::debug('CharacterBibleModal: Opened');
    }

    /**
     * Handle portrait generated event from parent
     */
    #[On('character-portrait-generated')]
    public function handlePortraitGenerated(int $characterIndex, ?string $imageUrl, ?string $error = null): void
    {
        $this->isGeneratingPortrait = false;

        if ($error) {
            $this->error = $error;
            Log::warning('CharacterBibleModal: Portrait generation failed', [
                'characterIndex' => $characterIndex,
                'error' => $error,
            ]);
            return;
        }

        if (isset($this->characterBible['characters'][$characterIndex])) {
            $this->characterBible['characters'][$characterIndex]['referenceImage'] = $imageUrl;
            $this->characterBible['characters'][$characterIndex]['referenceImageStatus'] = 'ready';
            $this->notifyParentOfUpdate();
        }

        Log::info('CharacterBibleModal: Portrait generated', [
            'characterIndex' => $characterIndex,
        ]);
    }

    /**
     * Update script scenes when parent changes them
     */
    #[On('update-script-scenes')]
    public function updateScriptScenes(array $scenes): void
    {
        $this->scriptScenes = $scenes;
    }

    // =========================================================================
    // MODAL CONTROL
    // =========================================================================

    /**
     * Close the modal and notify parent
     */
    public function closeModal(): void
    {
        $this->show = false;
        $this->notifyParentOfUpdate();
        $this->dispatch('character-bible-closed');

        Log::debug('CharacterBibleModal: Closed');
    }

    // =========================================================================
    // CHARACTER CRUD OPERATIONS
    // =========================================================================

    /**
     * Add a new character to the Character Bible
     */
    public function addCharacter(string $name = '', string $description = ''): void
    {
        $this->characterBible['characters'][] = [
            'id' => uniqid('char_'),
            'name' => $name,
            'description' => $description,
            'role' => 'Supporting',
            'scenes' => [],
            'traits' => [],
            'defaultExpression' => '',
            'attire' => '',
            'referenceImage' => null,
            'referenceImageBase64' => null,
            'referenceImageMimeType' => null,
            'referenceImageStatus' => 'none',
            'voice' => [
                'id' => null,
                'gender' => null,
                'style' => 'natural',
                'speed' => 1.0,
                'pitch' => 'medium',
            ],
            'isNarrator' => false,
            'speakingRole' => 'dialogue',
            'hair' => [
                'style' => '',
                'color' => '',
                'length' => '',
                'texture' => '',
            ],
            'wardrobe' => [
                'outfit' => '',
                'colors' => '',
                'style' => '',
                'footwear' => '',
            ],
            'makeup' => [
                'style' => '',
                'details' => '',
            ],
            'accessories' => [],
        ];

        $this->editingCharacterIndex = count($this->characterBible['characters']) - 1;
        $this->notifyParentOfUpdate();

        Log::info('CharacterBibleModal: Character added', [
            'name' => $name,
            'index' => $this->editingCharacterIndex,
        ]);
    }

    /**
     * Remove a character from the Character Bible
     */
    public function removeCharacter(int $index): void
    {
        if (isset($this->characterBible['characters'][$index])) {
            $name = $this->characterBible['characters'][$index]['name'] ?? 'Unknown';
            unset($this->characterBible['characters'][$index]);
            $this->characterBible['characters'] = array_values($this->characterBible['characters']);

            // Reset editing index if needed
            $count = count($this->characterBible['characters']);
            if ($this->editingCharacterIndex >= $count) {
                $this->editingCharacterIndex = max(0, $count - 1);
            }

            $this->notifyParentOfUpdate();

            Log::info('CharacterBibleModal: Character removed', [
                'name' => $name,
                'index' => $index,
            ]);
        }
    }

    /**
     * Set the character to edit
     */
    public function editCharacter(int $index): void
    {
        $this->editingCharacterIndex = $index;
    }

    // =========================================================================
    // CHARACTER TRAITS MANAGEMENT
    // =========================================================================

    /**
     * Add a trait to a character
     */
    public function addCharacterTrait(int $characterIndex, string $trait): void
    {
        $trait = trim($trait);
        if (empty($trait)) {
            return;
        }

        if (!isset($this->characterBible['characters'][$characterIndex])) {
            return;
        }

        // Initialize traits array if not exists
        if (!isset($this->characterBible['characters'][$characterIndex]['traits'])) {
            $this->characterBible['characters'][$characterIndex]['traits'] = [];
        }

        // Avoid duplicates (case-insensitive)
        $existingTraits = array_map('strtolower', $this->characterBible['characters'][$characterIndex]['traits']);
        if (in_array(strtolower($trait), $existingTraits)) {
            return;
        }

        $this->characterBible['characters'][$characterIndex]['traits'][] = $trait;
        $this->notifyParentOfUpdate();
    }

    /**
     * Remove a trait from a character
     */
    public function removeCharacterTrait(int $characterIndex, int $traitIndex): void
    {
        if (!isset($this->characterBible['characters'][$characterIndex]['traits'][$traitIndex])) {
            return;
        }

        unset($this->characterBible['characters'][$characterIndex]['traits'][$traitIndex]);
        $this->characterBible['characters'][$characterIndex]['traits'] = array_values(
            $this->characterBible['characters'][$characterIndex]['traits']
        );
        $this->notifyParentOfUpdate();
    }

    // =========================================================================
    // CHARACTER ACCESSORIES MANAGEMENT
    // =========================================================================

    /**
     * Add an accessory to a character
     */
    public function addCharacterAccessory(int $characterIndex, string $accessory): void
    {
        $accessory = trim($accessory);
        if (empty($accessory)) {
            return;
        }

        if (!isset($this->characterBible['characters'][$characterIndex])) {
            return;
        }

        // Initialize accessories array if not exists
        if (!isset($this->characterBible['characters'][$characterIndex]['accessories'])) {
            $this->characterBible['characters'][$characterIndex]['accessories'] = [];
        }

        // Avoid duplicates (case-insensitive)
        $existingAccessories = array_map('strtolower', $this->characterBible['characters'][$characterIndex]['accessories']);
        if (in_array(strtolower($accessory), $existingAccessories)) {
            return;
        }

        $this->characterBible['characters'][$characterIndex]['accessories'][] = $accessory;
        $this->notifyParentOfUpdate();
    }

    /**
     * Remove an accessory from a character
     */
    public function removeCharacterAccessory(int $characterIndex, int $accessoryIndex): void
    {
        if (!isset($this->characterBible['characters'][$characterIndex]['accessories'][$accessoryIndex])) {
            return;
        }

        unset($this->characterBible['characters'][$characterIndex]['accessories'][$accessoryIndex]);
        $this->characterBible['characters'][$characterIndex]['accessories'] = array_values(
            $this->characterBible['characters'][$characterIndex]['accessories']
        );
        $this->notifyParentOfUpdate();
    }

    // =========================================================================
    // CHARACTER VOICE MANAGEMENT
    // =========================================================================

    /**
     * Apply a voice preset to a character
     */
    public function applyCharacterVoicePreset(int $characterIndex, string $preset): void
    {
        if (!isset($this->characterBible['characters'][$characterIndex])) {
            return;
        }

        $voicePresets = [
            'hero-male' => ['id' => 'onyx', 'gender' => 'male', 'style' => 'confident', 'speed' => 1.0, 'pitch' => 'medium'],
            'hero-female' => ['id' => 'nova', 'gender' => 'female', 'style' => 'confident', 'speed' => 1.0, 'pitch' => 'medium'],
            'villain-male' => ['id' => 'echo', 'gender' => 'male', 'style' => 'intense', 'speed' => 0.9, 'pitch' => 'low'],
            'villain-female' => ['id' => 'shimmer', 'gender' => 'female', 'style' => 'intense', 'speed' => 0.9, 'pitch' => 'medium'],
            'mentor' => ['id' => 'fable', 'gender' => 'neutral', 'style' => 'warm', 'speed' => 0.95, 'pitch' => 'medium'],
            'narrator' => ['id' => 'fable', 'gender' => 'neutral', 'style' => 'storytelling', 'speed' => 1.0, 'pitch' => 'medium'],
            'young-male' => ['id' => 'alloy', 'gender' => 'male', 'style' => 'energetic', 'speed' => 1.1, 'pitch' => 'medium'],
            'young-female' => ['id' => 'nova', 'gender' => 'female', 'style' => 'energetic', 'speed' => 1.1, 'pitch' => 'high'],
            'professional' => ['id' => 'alloy', 'gender' => 'neutral', 'style' => 'authoritative', 'speed' => 1.0, 'pitch' => 'medium'],
            'documentary' => ['id' => 'onyx', 'gender' => 'male', 'style' => 'authoritative', 'speed' => 0.95, 'pitch' => 'low'],
            'child' => ['id' => 'nova', 'gender' => 'neutral', 'style' => 'energetic', 'speed' => 1.15, 'pitch' => 'high'],
            'elder' => ['id' => 'fable', 'gender' => 'neutral', 'style' => 'warm', 'speed' => 0.85, 'pitch' => 'low'],
        ];

        if (!isset($voicePresets[$preset])) {
            return;
        }

        $this->characterBible['characters'][$characterIndex]['voice'] = $voicePresets[$preset];
        $this->notifyParentOfUpdate();
    }

    // =========================================================================
    // CHARACTER LOOK PRESETS
    // =========================================================================

    /**
     * Apply a complete look preset to a character
     */
    public function applyCharacterLookPreset(int $characterIndex, string $preset): void
    {
        if (!isset($this->characterBible['characters'][$characterIndex])) {
            return;
        }

        $lookPresets = [
            'corporate-female' => [
                'hair' => ['style' => 'sleek professional blowout', 'color' => 'dark brown', 'length' => 'shoulder-length', 'texture' => 'straight polished'],
                'wardrobe' => ['outfit' => 'tailored charcoal blazer over white silk blouse, fitted dark trousers', 'colors' => 'charcoal, white, navy accents', 'style' => 'corporate professional', 'footwear' => 'black pointed-toe heels'],
                'makeup' => ['style' => 'polished professional', 'details' => 'neutral eyeshadow, defined brows, nude-pink lip, subtle contour'],
                'accessories' => ['pearl stud earrings', 'silver wristwatch', 'thin gold necklace'],
            ],
            'corporate-male' => [
                'hair' => ['style' => 'short tapered business cut', 'color' => 'dark brown', 'length' => 'short', 'texture' => 'neat styled'],
                'wardrobe' => ['outfit' => 'navy blue tailored suit, white dress shirt, dark tie', 'colors' => 'navy, white, silver accents', 'style' => 'corporate professional', 'footwear' => 'polished black oxford shoes'],
                'makeup' => ['style' => 'none', 'details' => 'clean groomed appearance'],
                'accessories' => ['silver wristwatch', 'wedding band', 'subtle cufflinks'],
            ],
            'tech-female' => [
                'hair' => ['style' => 'modern asymmetric bob', 'color' => 'black with subtle highlights', 'length' => 'chin-length', 'texture' => 'straight sleek'],
                'wardrobe' => ['outfit' => 'fitted black jacket over dark tech t-shirt, slim dark jeans', 'colors' => 'black, charcoal, electric blue accents', 'style' => 'tech-casual', 'footwear' => 'white minimalist sneakers'],
                'makeup' => ['style' => 'minimal modern', 'details' => 'subtle wing eyeliner, natural lip, dewy skin'],
                'accessories' => ['smart watch with black band', 'small geometric earrings', 'thin-framed glasses'],
            ],
            'tech-male' => [
                'hair' => ['style' => 'textured modern cut', 'color' => 'dark brown', 'length' => 'medium-short', 'texture' => 'slightly tousled'],
                'wardrobe' => ['outfit' => 'gray zip-up hoodie over dark t-shirt, dark slim jeans', 'colors' => 'gray, black, subtle blue', 'style' => 'tech-casual', 'footwear' => 'clean white sneakers'],
                'makeup' => ['style' => 'none', 'details' => 'natural groomed'],
                'accessories' => ['smart watch', 'wireless earbuds case clipped to belt'],
            ],
            'action-hero-female' => [
                'hair' => ['style' => 'practical ponytail or braided', 'color' => 'dark', 'length' => 'long pulled back', 'texture' => 'natural'],
                'wardrobe' => ['outfit' => 'fitted tactical vest over dark compression top, cargo pants with utility belt', 'colors' => 'black, olive, tactical tan', 'style' => 'tactical combat', 'footwear' => 'black tactical boots'],
                'makeup' => ['style' => 'minimal combat-ready', 'details' => 'smudge-proof subtle eye, natural lip, matte skin'],
                'accessories' => ['tactical watch', 'dog tags', 'utility belt pouches'],
            ],
            'action-hero-male' => [
                'hair' => ['style' => 'short military-style or rugged', 'color' => 'dark', 'length' => 'short', 'texture' => 'natural'],
                'wardrobe' => ['outfit' => 'fitted tactical jacket, dark henley shirt, military cargo pants', 'colors' => 'black, olive drab, tactical gray', 'style' => 'tactical combat', 'footwear' => 'worn combat boots'],
                'makeup' => ['style' => 'none', 'details' => 'weathered rugged appearance, possible stubble'],
                'accessories' => ['tactical watch', 'dog tags', 'weapon holster'],
            ],
            'scientist-female' => [
                'hair' => ['style' => 'practical bun or neat ponytail', 'color' => 'natural brown', 'length' => 'medium-long tied back', 'texture' => 'natural'],
                'wardrobe' => ['outfit' => 'white lab coat over smart casual blouse, dark trousers', 'colors' => 'white, navy, muted tones', 'style' => 'academic professional', 'footwear' => 'sensible closed-toe flats'],
                'makeup' => ['style' => 'natural minimal', 'details' => 'light natural makeup, clear lip balm'],
                'accessories' => ['reading glasses', 'ID badge on lanyard', 'simple stud earrings'],
            ],
            'scientist-male' => [
                'hair' => ['style' => 'neat professional cut', 'color' => 'graying at temples', 'length' => 'short', 'texture' => 'neat'],
                'wardrobe' => ['outfit' => 'white lab coat over button-down shirt, khaki trousers', 'colors' => 'white, light blue, khaki', 'style' => 'academic professional', 'footwear' => 'brown leather shoes'],
                'makeup' => ['style' => 'none', 'details' => 'clean professional appearance'],
                'accessories' => ['wire-framed glasses', 'ID badge', 'pen in lab coat pocket'],
            ],
            'cyberpunk' => [
                'hair' => ['style' => 'edgy undercut or neon-streaked', 'color' => 'black with neon highlights', 'length' => 'asymmetric', 'texture' => 'styled spiky or sleek'],
                'wardrobe' => ['outfit' => 'leather jacket with LED accents, tech-wear bodysuit, tactical pants', 'colors' => 'black, neon cyan, magenta accents', 'style' => 'cyberpunk streetwear', 'footwear' => 'platform tech boots'],
                'makeup' => ['style' => 'cyber-glam', 'details' => 'neon eyeliner, holographic highlights, dark lip'],
                'accessories' => ['cyber-implant earpiece', 'LED wrist display', 'holographic jewelry'],
            ],
            'fantasy-warrior' => [
                'hair' => ['style' => 'long braided warrior style', 'color' => 'natural or silver', 'length' => 'long', 'texture' => 'thick braided'],
                'wardrobe' => ['outfit' => 'leather armor with metal pauldrons, worn tunic, belted', 'colors' => 'brown leather, silver metal, earth tones', 'style' => 'medieval warrior', 'footwear' => 'worn leather boots'],
                'makeup' => ['style' => 'battle-worn', 'details' => 'natural weathered look, possible war paint'],
                'accessories' => ['sword sheath on back', 'leather bracers', 'tribal pendant'],
            ],
        ];

        if (!isset($lookPresets[$preset])) {
            return;
        }

        $presetData = $lookPresets[$preset];
        $this->characterBible['characters'][$characterIndex]['hair'] = $presetData['hair'];
        $this->characterBible['characters'][$characterIndex]['wardrobe'] = $presetData['wardrobe'];
        $this->characterBible['characters'][$characterIndex]['makeup'] = $presetData['makeup'];
        $this->characterBible['characters'][$characterIndex]['accessories'] = $presetData['accessories'];
        $this->notifyParentOfUpdate();
    }

    /**
     * Apply a character template (for quick setup)
     */
    public function applyCharacterTemplate(int $characterIndex, string $template): void
    {
        if (!isset($this->characterBible['characters'][$characterIndex])) {
            return;
        }

        $templates = [
            'action-hero' => [
                'traits' => ['brave', 'determined', 'skilled', 'protective'],
                'description' => 'Athletic build, confident posture, focused expression',
            ],
            'tech-pro' => [
                'traits' => ['intelligent', 'analytical', 'innovative', 'curious'],
                'description' => 'Modern attire, alert expression, tech-savvy appearance',
            ],
            'mysterious' => [
                'traits' => ['enigmatic', 'perceptive', 'secretive', 'calculating'],
                'description' => 'Dark clothing, piercing gaze, subtle demeanor',
            ],
            'narrator' => [
                'traits' => ['wise', 'knowledgeable', 'articulate', 'observant'],
                'description' => 'Professional appearance, warm expression, authoritative presence',
                'speakingRole' => 'narrator',
                'isNarrator' => true,
            ],
        ];

        if (!isset($templates[$template])) {
            return;
        }

        $templateData = $templates[$template];
        $this->characterBible['characters'][$characterIndex]['traits'] = $templateData['traits'];

        if (empty($this->characterBible['characters'][$characterIndex]['description'])) {
            $this->characterBible['characters'][$characterIndex]['description'] = $templateData['description'];
        }

        if (isset($templateData['speakingRole'])) {
            $this->characterBible['characters'][$characterIndex]['speakingRole'] = $templateData['speakingRole'];
        }

        if (isset($templateData['isNarrator'])) {
            $this->characterBible['characters'][$characterIndex]['isNarrator'] = $templateData['isNarrator'];
        }

        $this->notifyParentOfUpdate();
    }

    /**
     * Apply trait presets
     */
    public function applyTraitPreset(int $characterIndex, string $preset): void
    {
        if (!isset($this->characterBible['characters'][$characterIndex])) {
            return;
        }

        $traitPresets = [
            'hero' => ['brave', 'determined', 'selfless', 'strong'],
            'villain' => ['cunning', 'ambitious', 'ruthless', 'intelligent'],
            'mentor' => ['wise', 'patient', 'experienced', 'supportive'],
            'professional' => ['competent', 'reliable', 'focused', 'organized'],
            'mysterious' => ['enigmatic', 'secretive', 'perceptive', 'calculating'],
            'comic' => ['witty', 'charismatic', 'quick-thinking', 'playful'],
            'leader' => ['authoritative', 'decisive', 'inspiring', 'strategic'],
        ];

        if (!isset($traitPresets[$preset])) {
            return;
        }

        $this->characterBible['characters'][$characterIndex]['traits'] = $traitPresets[$preset];
        $this->notifyParentOfUpdate();
    }

    // =========================================================================
    // SCENE ASSIGNMENT
    // =========================================================================

    /**
     * Toggle character scene assignment
     */
    public function toggleCharacterScene(int $charIndex, int $sceneIndex): void
    {
        if (!isset($this->characterBible['characters'][$charIndex])) {
            return;
        }

        // Support both new field name 'scenes' and legacy 'appliedScenes'
        $scenes = $this->characterBible['characters'][$charIndex]['scenes']
            ?? $this->characterBible['characters'][$charIndex]['appliedScenes']
            ?? [];

        $key = array_search($sceneIndex, $scenes);
        if ($key !== false) {
            unset($scenes[$key]);
            $scenes = array_values($scenes);
        } else {
            $scenes[] = $sceneIndex;
            sort($scenes);
        }

        $this->characterBible['characters'][$charIndex]['scenes'] = $scenes;
        $this->notifyParentOfUpdate();
    }

    /**
     * Apply character to all scenes
     */
    public function applyCharacterToAllScenes(int $charIndex): void
    {
        if (!isset($this->characterBible['characters'][$charIndex])) {
            return;
        }

        $allSceneIndices = array_keys($this->scriptScenes);
        $this->characterBible['characters'][$charIndex]['scenes'] = $allSceneIndices;
        $this->notifyParentOfUpdate();
    }

    // =========================================================================
    // PORTRAIT MANAGEMENT
    // =========================================================================

    /**
     * Request portrait generation (dispatch to parent)
     */
    public function generateCharacterPortrait(int $characterIndex): void
    {
        if (!isset($this->characterBible['characters'][$characterIndex])) {
            return;
        }

        $this->isGeneratingPortrait = true;
        $this->characterBible['characters'][$characterIndex]['referenceImageStatus'] = 'generating';
        $this->notifyParentOfUpdate();

        // Dispatch to parent for actual generation
        $this->dispatch('generate-character-portrait', characterIndex: $characterIndex);

        Log::info('CharacterBibleModal: Portrait generation requested', [
            'characterIndex' => $characterIndex,
            'characterName' => $this->characterBible['characters'][$characterIndex]['name'] ?? 'Unknown',
        ]);
    }

    /**
     * Generate all missing character portraits
     */
    public function generateAllMissingCharacterReferences(): void
    {
        $toGenerate = [];
        foreach ($this->characterBible['characters'] as $index => $char) {
            $hasPortrait = !empty($char['referenceImageStorageKey']) || !empty($char['referenceImageBase64']) || !empty($char['referenceImage']);
            if (!$hasPortrait || ($char['referenceImageStatus'] ?? '') !== 'ready') {
                $toGenerate[] = $index;
            }
        }

        if (empty($toGenerate)) {
            return;
        }

        // Dispatch to parent for batch generation
        $this->dispatch('generate-all-character-portraits', characterIndices: $toGenerate);

        Log::info('CharacterBibleModal: Batch portrait generation requested', [
            'count' => count($toGenerate),
        ]);
    }

    /**
     * Remove character portrait
     */
    public function removeCharacterPortrait(int $characterIndex): void
    {
        if (!isset($this->characterBible['characters'][$characterIndex])) {
            return;
        }

        $this->characterBible['characters'][$characterIndex]['referenceImage'] = null;
        $this->characterBible['characters'][$characterIndex]['referenceImageBase64'] = null;
        $this->characterBible['characters'][$characterIndex]['referenceImageMimeType'] = null;
        $this->characterBible['characters'][$characterIndex]['referenceImageStatus'] = 'none';
        $this->characterBible['characters'][$characterIndex]['referenceImageStorageKey'] = null;
        $this->notifyParentOfUpdate();
    }

    /**
     * Handle portrait upload
     */
    public function uploadCharacterPortrait(int $characterIndex): void
    {
        if (!$this->characterImageUpload) {
            return;
        }

        if (!isset($this->characterBible['characters'][$characterIndex])) {
            $this->characterImageUpload = null;
            return;
        }

        try {
            $storageService = app(ReferenceImageStorageService::class);
            $storageKey = $storageService->storeUploadedImage(
                $this->characterImageUpload,
                $this->projectId,
                'character',
                $characterIndex
            );

            $this->characterBible['characters'][$characterIndex]['referenceImageStorageKey'] = $storageKey;
            $this->characterBible['characters'][$characterIndex]['referenceImageStatus'] = 'ready';
            $this->characterBible['characters'][$characterIndex]['referenceImageSource'] = 'upload';

            // Load the image URL for display
            $imageData = $storageService->loadImage($storageKey);
            if ($imageData) {
                $this->characterBible['characters'][$characterIndex]['referenceImage'] = $imageData['url'] ?? null;
            }

            $this->notifyParentOfUpdate();

            Log::info('CharacterBibleModal: Portrait uploaded', [
                'characterIndex' => $characterIndex,
                'storageKey' => $storageKey,
            ]);
        } catch (\Exception $e) {
            $this->error = 'Failed to upload image: ' . $e->getMessage();
            Log::error('CharacterBibleModal: Portrait upload failed', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->characterImageUpload = null;
    }

    /**
     * Extract DNA from portrait image
     */
    public function extractDNAFromPortrait(int $characterIndex): void
    {
        // Dispatch to parent for AI processing
        $this->dispatch('extract-character-dna', characterIndex: $characterIndex);
    }

    // =========================================================================
    // AUTO-DETECTION
    // =========================================================================

    /**
     * Auto-detect characters from script (dispatch to parent)
     */
    public function autoDetectCharacters(): void
    {
        $this->dispatch('auto-detect-characters');
    }

    // =========================================================================
    // STORY BIBLE SYNC
    // =========================================================================

    /**
     * Sync from Story Bible
     */
    public function syncFromStoryBible(): void
    {
        $this->dispatch('sync-story-bible-to-character-bible');
    }

    /**
     * Handle sync completion from parent
     */
    #[On('story-bible-synced')]
    public function handleStoryBibleSynced(array $characterBible): void
    {
        $this->characterBible = $characterBible;
        $this->isSyncingCharacterBible = false;
    }

    // =========================================================================
    // VOICE PREVIEW
    // =========================================================================

    /**
     * Preview voice with emotion (dispatch to parent)
     */
    public function previewVoiceWithEmotion(int $characterIndex, ?string $emotion): void
    {
        $this->dispatch('preview-character-voice', characterIndex: $characterIndex, emotion: $emotion);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Notify parent of Character Bible updates
     */
    protected function notifyParentOfUpdate(): void
    {
        $this->dispatch('character-bible-updated', characterBible: $this->characterBible);
    }

    /**
     * Get count of characters needing portraits
     */
    public function getCharactersNeedingPortraitsCount(): int
    {
        $count = 0;
        foreach ($this->characterBible['characters'] ?? [] as $char) {
            $hasPortrait = !empty($char['referenceImageBase64']) || !empty($char['referenceImage']);
            if (!$hasPortrait || ($char['referenceImageStatus'] ?? '') !== 'ready') {
                $count++;
            }
        }
        return $count;
    }
}
