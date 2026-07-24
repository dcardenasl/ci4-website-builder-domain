<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePageViewsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'page_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'referrer' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
            ],
            'referrer_domain' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'utm_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'utm_medium' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'utm_campaign' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'device_type' => [
                'type'       => 'ENUM',
                'constraint' => ['desktop', 'mobile', 'tablet', 'bot', 'unknown'],
                'default'    => 'unknown',
            ],
            'browser' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'os' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'session_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
                'default'    => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['url', 'created_at']);
        $this->forge->addKey('created_at');
        $this->forge->addKey(['referrer_domain', 'created_at']);
        $this->forge->addKey(['device_type', 'created_at']);
        $this->forge->addKey(['session_id', 'created_at']);

        $this->forge->createTable('page_views', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('page_views', true);
    }
}
