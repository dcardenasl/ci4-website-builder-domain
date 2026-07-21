<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Response\Cms\SettingConnectionResponseDTO;
use App\Entities\SettingConnectionEntity;
use App\Interfaces\Cms\SettingConnectionServiceInterface;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

class SettingConnectionService implements SettingConnectionServiceInterface
{
    /**
     * @param RepositoryInterface<SettingConnectionEntity> $settingConnectionRepository
     */
    public function __construct(
        private readonly RepositoryInterface $settingConnectionRepository
    ) {
    }

    public function listForSetting(int $settingId): array
    {
        /** @var list<SettingConnectionEntity> $connections */
        $connections = $this->settingConnectionRepository->getModel()
            ->where('setting_id', $settingId)
            ->findAll();

        $items = array_map(
            static fn (SettingConnectionEntity $connection): array => self::toResponseArray($connection),
            $connections
        );

        return ['items' => $items, 'total' => count($items)];
    }

    public function create(int $settingId, string $entityType, string $entityKey, ?string $usageLabel): array
    {
        $existing = $this->settingConnectionRepository->getModel()
            ->where('setting_id', $settingId)
            ->where('entity_type', $entityType)
            ->where('entity_key', $entityKey)
            ->first();

        if ($existing !== null) {
            throw new ValidationException(
                lang('Api.validationFailed'),
                ['entity_key' => lang('Settings.connection_already_exists')]
            );
        }

        $id = $this->settingConnectionRepository->insert([
            'setting_id'  => $settingId,
            'entity_type' => $entityType,
            'entity_key'  => $entityKey,
            'usage_label' => $usageLabel,
        ]);

        if ($id === false) {
            throw new ValidationException(lang('Api.validationFailed'), $this->settingConnectionRepository->errors());
        }

        /** @var SettingConnectionEntity $connection */
        $connection = $this->settingConnectionRepository->find((int) $id);

        return self::toResponseArray($connection);
    }

    public function delete(int $settingId, int $connectionId): void
    {
        $connection = $this->settingConnectionRepository->getModel()
            ->where('id', $connectionId)
            ->where('setting_id', $settingId)
            ->first();

        if ($connection === null) {
            throw new NotFoundException(lang('Api.resourceNotFound'));
        }

        $this->settingConnectionRepository->delete($connectionId);
    }

    /**
     * @return array<string, mixed>
     */
    private static function toResponseArray(SettingConnectionEntity $connection): array
    {
        return (new SettingConnectionResponseDTO(
            id: (int) $connection->id,
            settingId: (int) $connection->setting_id,
            entityType: (string) $connection->entity_type,
            entityKey: (string) $connection->entity_key,
            usageLabel: ($connection->usage_label !== null && $connection->usage_label !== '') ? (string) $connection->usage_label : null,
            createdAt: $connection->created_at instanceof \DateTimeInterface
                ? $connection->created_at->format('Y-m-d H:i:s')
                : null,
        ))->toArray();
    }
}
