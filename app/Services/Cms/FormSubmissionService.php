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
use dcardenasl\Ci4ApiCore\Queue\QueueManagerInterface;

class FormSubmissionService
{
    public function __construct(
        private FormSubmissionModel $model,
        private QueueManagerInterface $queueManager,
    ) {
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
        // Validate CAPTCHA if the form requires it
        if ($dto->form_id !== null) {
            /** @var \App\Models\FormModel $formModel */
            $formModel = model(\App\Models\FormModel::class);
            /** @var \App\Entities\FormEntity|null $form */
            $form = $formModel->find($dto->form_id);

            if ($form !== null && $form->has_captcha) {
                if ($dto->captcha_token === null || ! $this->verifyRecaptcha($dto->captcha_token)) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\ValidationException(
                        lang('FormSubmissions.captcha_failed'),
                        ['captcha_token' => lang('FormSubmissions.captcha_failed')]
                    );
                }
            }
        }

        $dataJson = json_encode($dto->form_data, JSON_UNESCAPED_UNICODE) ?: '{}';

        $id = $this->model->insert([
            'form_id'     => $dto->form_id,
            'form_key'    => $dto->form_key,
            'page_id'     => $dto->page_id,
            'language_id' => $dto->language_id,
            'data_json'   => $dataJson,
            'status'      => 'new',
            'ip_address'  => $dto->ip_address,
            'user_agent'  => $dto->user_agent,
        ], true);

        $submission = $this->get((int) $id);

        // Dispatch email notification jobs if a form definition exists
        if ($dto->form_id !== null) {
            $this->dispatchEmailJobs((int) $id, $dto->form_id, $dto->form_data);
        }

        return $submission;
    }

    private function verifyRecaptcha(string $token): bool
    {
        $secret = $this->recaptchaSecretKey();
        if ($secret === '' || $token === '') {
            return false;
        }

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => 'https://www.google.com/recaptcha/api/siteverify',
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret, 'response' => $token]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $response = curl_exec($curl);
            curl_close($curl);

            if (! is_string($response)) {
                return false;
            }

            $data = json_decode($response, true);

            return ($data['success'] ?? false) === true
                && (float) ($data['score'] ?? 0.0) >= 0.5;
        } catch (\Throwable) {
            return false;
        }
    }

    private function recaptchaSecretKey(): string
    {
        /** @var \App\Models\SettingModel $settingModel */
        $settingModel = model(\App\Models\SettingModel::class);
        $setting = $settingModel
            ->where('setting_key', 'recaptcha_secret_key')
            ->where('is_active', 1)
            ->first();

        if ($setting instanceof \App\Entities\SettingEntity) {
            $value = trim((string) ($setting->setting_value ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return trim((string) (config('App')->recaptchaSecretKey ?? ''));
    }

    /**
     * @param array<string, mixed> $formData
     */
    private function dispatchEmailJobs(int $submissionId, int $formId, array $formData): void
    {
        try {
            /** @var \App\Models\FormModel $formModel */
            $formModel = model(\App\Models\FormModel::class);
            /** @var \App\Entities\FormEntity|null $form */
            $form = $formModel->find($formId);

            if ($form === null) {
                return;
            }

            if ($form->notify_email !== null && $form->notify_email !== '') {
                $this->queueManager->push(\App\Jobs\FormSubmissionNotificationJob::class, [
                    'submission_id' => $submissionId,
                    'form_id'       => $formId,
                ], 'emails');
            }

            if ($form->autoreply_enabled && $form->autoreply_email_field !== null) {
                $userEmail = (string) ($formData[$form->autoreply_email_field] ?? '');
                if ($userEmail !== '' && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->queueManager->push(\App\Jobs\FormSubmissionAutoreplyJob::class, [
                        'submission_id' => $submissionId,
                        'form_id'       => $formId,
                        'user_email'    => $userEmail,
                    ], 'emails');
                }
            }
        } catch (\Throwable $e) {
            log_message('error', '[FormSubmissionService] Failed to dispatch email jobs: ' . $e->getMessage());
        }
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
