<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Story Bible First Architecture Migration
 *
 * Adds the story_bible column to wizard_projects table.
 * The Story Bible is generated BEFORE the script and acts as the
 * "DNA" that constrains all subsequent generation (script, images, etc.)
 *
 * Story Bible Structure:
 * {
 *   "enabled": true,
 *   "status": "pending|generating|ready",
 *   "generatedAt": "2026-01-13T12:00:00Z",
 *
 *   // Core Story Elements
 *   "title": "The Video Title",
 *   "logline": "One-sentence story summary",
 *   "theme": "Core theme/message",
 *   "tone": "engaging|dramatic|educational|etc",
 *   "genre": "thriller|documentary|comedy|etc",
 *
 *   // Three-Act Structure
 *   "acts": [
 *     {
 *       "actNumber": 1,
 *       "name": "Setup",
 *       "description": "Introduce characters, setting, and conflict",
 *       "turningPoint": "The inciting incident",
 *       "duration": 30,
 *       "percentage": 25
 *     }
 *   ],
 *
 *   // Character Profiles (3-5+ characters)
 *   "characters": [
 *     {
 *       "id": "char_1",
 *       "name": "Sarah",
 *       "role": "protagonist|antagonist|supporting|narrator",
 *       "description": "Detailed visual description for AI consistency",
 *       "arc": "Character's journey/transformation",
 *       "traits": ["determined", "resourceful"],
 *       "appearsInActs": [1, 2, 3]
 *     }
 *   ],
 *
 *   // Location Index
 *   "locations": [
 *     {
 *       "id": "loc_1",
 *       "name": "Corporate Office",
 *       "type": "interior|exterior",
 *       "description": "Detailed visual description for AI consistency",
 *       "timeOfDay": "day|night|dawn|dusk",
 *       "atmosphere": "tense|peaceful|energetic",
 *       "appearsInActs": [1, 3]
 *     }
 *   ],
 *
 *   // Visual Style Definition
 *   "visualStyle": {
 *     "mode": "cinematic-realistic|stylized-animation|mixed-hybrid",
 *     "colorPalette": "Color scheme description",
 *     "lighting": "Lighting style description",
 *     "cameraLanguage": "Camera movement preferences",
 *     "references": "Style references (e.g., 'Blade Runner meets The Social Network')"
 *   },
 *
 *   // Pacing & Rhythm
 *   "pacing": {
 *     "overall": "fast|balanced|contemplative",
 *     "tensionCurve": [10, 30, 50, 80, 100, 70, 90],
 *     "emotionalBeats": ["curiosity", "tension", "revelation", "resolution"]
 *   }
 * }
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wizard_projects', function (Blueprint $table) {
            // Story Bible - the source of truth for all generation
            // Generated BEFORE script, constrains all subsequent AI calls
            $table->json('story_bible')->nullable()->after('content_config');

            // Script generation config - tracks which prompts/settings were used
            $table->json('script_generation_config')->nullable()->after('script');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wizard_projects', function (Blueprint $table) {
            $table->dropColumn('story_bible');
            $table->dropColumn('script_generation_config');
        });
    }
};
