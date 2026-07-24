<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SlugRedirectRecorder
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    /**
     * @param BaseConnection<mixed, mixed>|null $db
     */
    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Setea una redirección si el slug ha cambiado.
     *
     * @param string $entityType El tipo de entidad ('page', 'entry', 'category', 'tag', 'collection')
     * @param int $entityId El ID de la entidad
     * @param int $languageId El ID del idioma
     * @param string $oldSlug El slug anterior
     * @param string $newSlug El slug nuevo
     * @param string $oldFullPath El path anterior completo (ej: 'blog/antiguo-post' o 'antigua-pagina')
     */
    public function record(
        string $entityType,
        int $entityId,
        int $languageId,
        string $oldSlug,
        string $newSlug,
        string $oldFullPath
    ): void {
        if ($oldSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        // Limpiar barras iniciales/finales
        $oldFullPath = trim($oldFullPath, '/');

        // Validar si ya existe este redirect exacto para evitar duplicados
        $exists = $this->db->table('cms_slug_redirects')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('language_id', $languageId)
            ->where('old_slug', $oldSlug)
            ->countAllResults() > 0;

        if (!$exists) {
            $this->db->table('cms_slug_redirects')->insert([
                'entity_type'   => $entityType,
                'entity_id'     => $entityId,
                'language_id'   => $languageId,
                'old_slug'      => $oldSlug,
                'old_full_path' => $oldFullPath,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
