<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Back-fill input_type for existing cms_settings rows that were created
 * before the input_type column existed (added in 2026-06-27-100001).
 * Those rows landed with the column default of 'text'.
 *
 * Rules applied:
 *   - setting_type = 'file_id'           → input_type = 'image'
 *   - setting_key  in (description keys) → input_type = 'textarea'
 *   - all others remain 'text' (already correct)
 */
class SetInputTypeForIdentitySettings extends Migration
{
    private const TEXTAREA_KEYS = [
        'site_description',
        'site_tagline',
        'site_copyright',
    ];

    public function up(): void
    {
        // file_id settings → image picker
        $this->db->table('cms_settings')
            ->where('setting_type', 'file_id')
            ->where('input_type', 'text')
            ->update(['input_type' => 'image']);

        // long-text settings → textarea
        if (!empty(self::TEXTAREA_KEYS)) {
            $this->db->table('cms_settings')
                ->whereIn('setting_key', self::TEXTAREA_KEYS)
                ->where('input_type', 'text')
                ->update(['input_type' => 'textarea']);
        }
    }

    public function down(): void
    {
        // Revert to 'text' for the rows we changed.
        $this->db->table('cms_settings')
            ->where('setting_type', 'file_id')
            ->where('input_type', 'image')
            ->update(['input_type' => 'text']);

        if (!empty(self::TEXTAREA_KEYS)) {
            $this->db->table('cms_settings')
                ->whereIn('setting_key', self::TEXTAREA_KEYS)
                ->where('input_type', 'textarea')
                ->update(['input_type' => 'text']);
        }
    }
}
