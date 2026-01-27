<?php

use Illuminate\Database\Migrations\Migration;
use Modules\AppVideoWizard\Models\VwSetting;

return new class extends Migration
{
    public function up(): void
    {
        VwSetting::create([
            'slug' => 'hollywood_expansion_enabled',
            'name' => 'Hollywood Prompt Expansion',
            'category' => 'production_intelligence',
            'description' => 'Enable AI-enhanced prompts for complex shots. When enabled, shots with multiple characters, unusual settings, or high emotional complexity automatically route to LLM expansion for Hollywood-quality prompts.',
            'value_type' => 'boolean',
            'value' => 'true',
            'default_value' => 'true',
            'input_type' => 'checkbox',
            'input_help' => 'Disable for faster generation with template-only prompts',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function down(): void
    {
        VwSetting::where('slug', 'hollywood_expansion_enabled')->delete();
    }
};
