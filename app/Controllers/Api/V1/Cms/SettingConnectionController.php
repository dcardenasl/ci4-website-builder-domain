<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\SettingConnectionCreateRequestDTO;
use App\DTO\Response\Cms\SettingConnectionResponseDTO;
use App\Entities\SettingConnectionEntity;
use App\Models\SettingConnectionModel;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class SettingConnectionController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return model(SettingConnectionModel::class);
    }

    public function index(int $settingId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context) use ($settingId): ResponseInterface {
                /** @var SettingConnectionModel $model */
                $model = model(SettingConnectionModel::class);

                /** @var SettingConnectionEntity[] $connections */
                $connections = $model->where('setting_id', $settingId)->findAll();

                $items = array_map(
                    fn (SettingConnectionEntity $conn) => (new SettingConnectionResponseDTO(
                        id: (int) $conn->id,
                        settingId: (int) $conn->setting_id,
                        entityType: (string) $conn->entity_type,
                        entityKey: (string) $conn->entity_key,
                        usageLabel: ($conn->usage_label !== null && $conn->usage_label !== '') ? (string) $conn->usage_label : null,
                        createdAt: $conn->created_at instanceof \DateTimeInterface
                            ? $conn->created_at->format('Y-m-d H:i:s')
                            : null,
                    ))->toArray(),
                    $connections
                );

                return $this->response->setJSON([
                    'ok'   => true,
                    'data' => ['items' => $items, 'total' => count($items)],
                ]);
            }
        );
    }

    public function create(int $settingId): ResponseInterface
    {
        return $this->handleRequest(
            function (SettingConnectionCreateRequestDTO $dto, SecurityContext $context) use ($settingId): ResponseInterface {
                /** @var SettingConnectionModel $model */
                $model = model(SettingConnectionModel::class);

                /** @var SettingConnectionEntity|null $existing */
                $existing = $model
                    ->where('setting_id', $settingId)
                    ->where('entity_type', $dto->entityType)
                    ->where('entity_key', $dto->entityKey)
                    ->first();

                if ($existing !== null) {
                    throw new ValidationException(
                        lang('Api.validationFailed'),
                        ['entity_key' => lang('Settings.connection_already_exists')]
                    );
                }

                $id = $model->insert([
                    'setting_id'  => $settingId,
                    'entity_type' => $dto->entityType,
                    'entity_key'  => $dto->entityKey,
                    'usage_label' => $dto->usageLabel,
                ]);

                if ($id === false) {
                    throw new ValidationException(lang('Api.validationFailed'), $model->errors());
                }

                /** @var SettingConnectionEntity $conn */
                $conn = $model->find((int) $id);

                $responseDto = new SettingConnectionResponseDTO(
                    id: (int) $conn->id,
                    settingId: (int) $conn->setting_id,
                    entityType: (string) $conn->entity_type,
                    entityKey: (string) $conn->entity_key,
                    usageLabel: ($conn->usage_label !== null && $conn->usage_label !== '') ? (string) $conn->usage_label : null,
                    createdAt: $conn->created_at instanceof \DateTimeInterface
                        ? $conn->created_at->format('Y-m-d H:i:s')
                        : null,
                );

                return $this->response->setStatusCode(201)->setJSON([
                    'ok'   => true,
                    'data' => $responseDto->toArray(),
                ]);
            },
            SettingConnectionCreateRequestDTO::class
        );
    }

    public function delete(int $settingId, int $connectionId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context) use ($settingId, $connectionId): ResponseInterface {
                /** @var SettingConnectionModel $model */
                $model = model(SettingConnectionModel::class);

                /** @var SettingConnectionEntity|null $conn */
                $conn = $model->where('id', $connectionId)->where('setting_id', $settingId)->first();

                if ($conn === null) {
                    throw new NotFoundException(lang('Api.resourceNotFound'));
                }

                $model->delete($connectionId);

                return $this->response->setJSON(['ok' => true, 'data' => null]);
            }
        );
    }
}
