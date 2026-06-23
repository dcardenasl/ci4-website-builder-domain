<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\FormSubmissionCreateRequestDTO;
use App\DTO\Request\Cms\FormSubmissionIndexRequestDTO;
use App\DTO\Request\Cms\FormSubmissionUpdateStatusRequestDTO;
use App\DTO\Response\Cms\FormSubmissionResponseDTO;
use App\Entities\FormSubmissionEntity;
use App\Models\FormSubmissionModel;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;

class FormSubmissionService
{
    public function __construct(private FormSubmissionModel $model)
    {
    }

    /**
     * List submissions (admin) with optional status/form_key filter.
     *
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function list(FormSubmissionIndexRequestDTO $dto): array
    {
        $builder = $this->model->orderBy('created_at', 'DESC');

        if ($dto->status !== null) {
            $builder->where('status', $dto->status);
        }

        if ($dto->form_key !== null) {
            $builder->where('form_key', $dto->form_key);
        }

        $total  = (int) $builder->countAllResults(false);
        $offset = ($dto->page - 1) * $dto->per_page;
        /** @var list<FormSubmissionEntity> */
        $rows   = $builder->findAll($dto->per_page, $offset);

        $data = array_map(
            fn (FormSubmissionEntity $e) => FormSubmissionResponseDTO::fromArray($e->toArray())->toArray(),
            $rows
        );

        return [
            'data'     => $data,
            'total'    => $total,
            'page'     => $dto->page,
            'per_page' => $dto->per_page,
        ];
    }

    /**
     * Get a single submission by ID.
     */
    public function get(int $id): FormSubmissionResponseDTO
    {
        $entity = $this->model->find($id);

        if (!$entity instanceof FormSubmissionEntity) {
            throw new NotFoundException(lang('FormSubmissions.not_found'));
        }

        return FormSubmissionResponseDTO::fromArray($entity->toArray());
    }

    /**
     * Create a new form submission from a public form POST.
     */
    public function create(FormSubmissionCreateRequestDTO $dto): FormSubmissionResponseDTO
    {
        $dataJson = json_encode($dto->form_data, JSON_UNESCAPED_UNICODE) ?: '{}';

        $id = $this->model->insert([
            'form_key'    => $dto->form_key,
            'page_id'     => $dto->page_id,
            'language_id' => $dto->language_id,
            'data_json'   => $dataJson,
            'status'      => 'new',
            'ip_address'  => $dto->ip_address,
            'user_agent'  => $dto->user_agent,
        ], true);

        return $this->get((int) $id);
    }

    /**
     * Update the status of a submission (admin action).
     */
    public function updateStatus(int $id, FormSubmissionUpdateStatusRequestDTO $dto): FormSubmissionResponseDTO
    {
        $entity = $this->model->find($id);

        if (!$entity instanceof FormSubmissionEntity) {
            throw new NotFoundException(lang('FormSubmissions.not_found'));
        }

        $this->model->update($id, ['status' => $dto->status]);

        return $this->get($id);
    }

    /**
     * Count submissions by status (for badge counters in admin).
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $db = \Config\Database::connect();
        $result = $db->table('cms_form_submissions')
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();
        $rows = $result ? $result->getResultArray() : [];

        $counts = ['new' => 0, 'read' => 0, 'replied' => 0, 'spam' => 0, 'archived' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
