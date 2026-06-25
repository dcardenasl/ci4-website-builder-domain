<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterCmsFormSubmissionsAddFormId extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('cms_form_submissions', [
            'form_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'id',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE cms_form_submissions ADD CONSTRAINT fk_form_submissions_form_id
             FOREIGN KEY (form_id) REFERENCES cms_forms(id) ON DELETE SET NULL'
        );
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE cms_form_submissions DROP FOREIGN KEY fk_form_submissions_form_id');
        $this->forge->dropColumn('cms_form_submissions', 'form_id');
    }
}
