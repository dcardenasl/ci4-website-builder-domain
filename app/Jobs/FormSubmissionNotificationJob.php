<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Entities\FormEntity;
use App\Entities\FormSubmissionEntity;
use dcardenasl\Ci4ApiCore\Queue\Job;
use Throwable;

/**
 * Dispatched after a form submission is persisted.
 *
 * Builds the admin notification email body and queues it via the Hub's
 * internal M2M endpoint (HubClient::queueEmail). Hub is the single email sender.
 */
class FormSubmissionNotificationJob extends Job
{
    public function handle(): void
    {
        $submissionId = (int) ($this->data['submission_id'] ?? 0);
        $formId       = (int) ($this->data['form_id'] ?? 0);

        if ($submissionId === 0 || $formId === 0) {
            log_message('error', '[FormSubmissionNotificationJob] Invalid payload: submission_id or form_id missing.');
            return;
        }

        try {
            /** @var \App\Models\FormSubmissionModel $submissionModel */
            $submissionModel = model(\App\Models\FormSubmissionModel::class);
            /** @var FormSubmissionEntity|null $submission */
            $submission = $submissionModel->find($submissionId);

            if ($submission === null) {
                log_message('warning', "[FormSubmissionNotificationJob] Submission #{$submissionId} not found.");
                return;
            }

            /** @var \App\Models\FormModel $formModel */
            $formModel = model(\App\Models\FormModel::class);
            /** @var FormEntity|null $form */
            $form = $formModel->find($formId);

            if ($form === null || $form->notify_email === null || $form->notify_email === '') {
                return;
            }

            $formData = $submission->getData();
            $siteName = (string) env('EMAIL_FROM_NAME', 'CMS');
            $subject  = 'Nuevo mensaje desde formulario — ' . esc($form->form_key);
            $html     = $this->buildHtml($formData, $siteName, $form->form_key);

            service('hubClient')->queueEmail($form->notify_email, $subject, $html);
        } catch (Throwable $e) {
            log_message('error', '[FormSubmissionNotificationJob] Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $formData
     */
    private function buildHtml(array $formData, string $siteName, string $formKey): string
    {
        $rows = '';
        foreach ($formData as $key => $value) {
            $label   = esc(ucfirst(str_replace('_', ' ', (string) $key)));
            // Checkbox-group fields submit as an array of selected option values.
            $valueText = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
            $val     = esc($valueText);
            $display = nl2br($val);
            $rows   .= "<tr>
                <td style=\"padding:8px 12px;font-weight:600;color:#475569;white-space:nowrap;vertical-align:top\">{$label}</td>
                <td style=\"padding:8px 12px;color:#1e293b\">{$display}</td>
              </tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family:system-ui,sans-serif;background:#f8fafc;margin:0;padding:24px">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0">
    <div style="background:#1e293b;padding:24px 28px">
      <p style="margin:0;color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:.1em">{$siteName}</p>
      <h1 style="margin:4px 0 0;color:#fff;font-size:20px">Nuevo envío — {$formKey}</h1>
    </div>
    <div style="padding:24px 28px">
      <table style="width:100%;border-collapse:collapse">{$rows}</table>
    </div>
    <div style="padding:16px 28px;background:#f1f5f9;font-size:12px;color:#94a3b8">
      Mensaje enviado desde el formulario de contacto del sitio web.
    </div>
  </div>
</body>
</html>
HTML;
    }
}
