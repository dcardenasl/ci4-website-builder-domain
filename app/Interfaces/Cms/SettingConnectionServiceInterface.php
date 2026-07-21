<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

/**
 * Nested-resource service for `cms_setting_connections` — connections are
 * always scoped to a parent setting, so this does not follow the generic
 * CrudServiceContract shape (index/show/store/update/destroy).
 */
interface SettingConnectionServiceInterface
{
    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function listForSetting(int $settingId): array;

    /**
     * @return array<string, mixed>
     */
    public function create(int $settingId, string $entityType, string $entityKey, ?string $usageLabel): array;

    public function delete(int $settingId, int $connectionId): void;
}
