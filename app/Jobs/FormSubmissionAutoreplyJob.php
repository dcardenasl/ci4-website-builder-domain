<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Entities\FormEntity;
use dcardenasl\Ci4ApiCore\Queue\Job;
use Throwable;

/**
 * Sends an auto-reply email to the user after a form submission.
 *
 * Queues via Hub's internal M2M endpoint (HubClient::queueEmail).
 * Hub is the single email sender — no Symfony Mailer in Domain.
 */
class FormSubmissionAutoreplyJob extends Job
{
    public function handle(): void
    {
        $submissionId = (int) ($this->data['submission_id'] ?? 0);
        $formId       = (int) ($this->data['form_id'] ?? 0);
        $userEmail    = (string) ($this->data['user_email'] ?? '');

        if ($submissionId === 0 || $formId === 0 || $userEmail === '') {
            log_message('error', '[FormSubmissionAutoreplyJob] Invalid payload.');
            return;
        }

        try {
            /** @var \App\Models\FormModel $formModel */
            $formModel = model(\App\Models\FormModel::class);
            /** @var FormEntity|null $form */
            $form = $formModel->find($formId);

            if ($form === null || ! $form->autoreply_enabled) {
                return;
            }

            /** @var \App\Models\FormTranslationModel $transModel */
            $transModel  = model(\App\Models\FormTranslationModel::class);
            $translation = $transModel->where('form_id', $formId)->first();

            $siteName       = (string) env('EMAIL_FROM_NAME', 'CMS');
            $formName       = is_array($translation) ? (string) ($translation['name'] ?? $form->form_key) : $form->form_key;
            $successMessage = is_array($translation) ? (string) ($translation['success_message'] ?? '') : '';
            $successMessage = $successMessage !== '' ? $successMessage : 'Hemos recibido tu mensaje. Nos pondremos en contacto a la brevedad.';

            $subject = 'Gracias por contactarnos — ' . esc($siteName);
            $html    = $this->buildHtml($userEmail, $siteName, $formName, $successMessage);

            service('hubClient')->queueEmail($userEmail, $subject, $html);
        } catch (Throwable $e) {
            log_message('error', '[FormSubmissionAutoreplyJob] Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function buildHtml(string $userEmail, string $siteName, string $formName, string $successMessage): string
    {
        $safeMessage  = nl2br(esc($successMessage));

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family:system-ui,sans-serif;background:#f8fafc;margin:0;padding:24px">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0">
    <div style="background:#1e293b;padding:24px 28px">
      <p style="margin:0;color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:.1em">{$siteName}</p>
      <h1 style="margin:4px 0 0;color:#fff;font-size:20px">Gracias por escribirnos</h1>
    </div>
    <div style="padding:24px 28px;color:#334155;line-height:1.6">
      <p>{$safeMessage}</p>
    </div>
    <div style="padding:16px 28px;background:#f1f5f9;font-size:12px;color:#94a3b8">
      Por favor no respondas a este email. Este mensaje fue generado automáticamente por {$siteName}.
    </div>
  </div>
</body>
</html>
HTML;
    }
}
