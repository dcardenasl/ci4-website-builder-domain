<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds the default contact form used by the starter site.
 * Idempotent: safe to run multiple times.
 */
class CmsFormSeeder extends Seeder
{
    use IdempotentSeederSupport;

    private const CONTACT_FORM_KEY = 'contact';

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);

        if (! isset($langIds['es'])) {
            echo "CmsFormSeeder: language 'es' not found. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $formId = $this->upsertForm([
            'form_key'              => self::CONTACT_FORM_KEY,
            'is_active'             => 1,
            'has_captcha'           => 0,
            'notify_email'          => null,
            'autoreply_enabled'     => 1,
            'autoreply_email_field' => 'email',
        ]);

        $translations = [
            'es' => [
                'name'            => 'Formulario de Contacto',
                'description'     => null,
                'submit_label'    => 'Enviar mensaje',
                'success_message' => '¡Gracias por escribirnos! Te responderemos a la brevedad.',
                'error_message'   => 'Ocurrió un error al enviar tu mensaje. Por favor inténtalo de nuevo.',
            ],
            'en' => [
                'name'            => 'Contact Form',
                'description'     => null,
                'submit_label'    => 'Send message',
                'success_message' => 'Thank you for reaching out! We will get back to you shortly.',
                'error_message'   => 'There was an error submitting your message. Please try again.',
            ],
        ];

        foreach ($translations as $code => $trans) {
            $langId = $langIds[$code] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->upsertFormTranslation($formId, $langId, $trans);
        }

        $fields = [
            [
                'field_key'    => 'name',
                'field_type'   => 'text',
                'display_order' => 10,
                'is_required'  => 1,
                'translations' => [
                    'es' => ['label' => 'Nombre completo', 'placeholder' => 'Su nombre completo', 'help_text' => null, 'error_required' => 'Por favor ingresa tu nombre.', 'error_invalid' => null],
                    'en' => ['label' => 'Full name', 'placeholder' => 'Your full name', 'help_text' => null, 'error_required' => 'Please enter your name.', 'error_invalid' => null],
                ],
            ],
            [
                'field_key'    => 'email',
                'field_type'   => 'email',
                'display_order' => 20,
                'is_required'  => 1,
                'translations' => [
                    'es' => ['label' => 'Email', 'placeholder' => 'correo@ejemplo.com', 'help_text' => null, 'error_required' => 'Por favor ingresa tu email.', 'error_invalid' => 'Ingresa un email válido.'],
                    'en' => ['label' => 'Email', 'placeholder' => 'you@example.com', 'help_text' => null, 'error_required' => 'Please enter your email.', 'error_invalid' => 'Enter a valid email address.'],
                ],
            ],
            [
                'field_key'    => 'message',
                'field_type'   => 'textarea',
                'display_order' => 30,
                'is_required'  => 1,
                'translations' => [
                    'es' => ['label' => 'Mensaje', 'placeholder' => 'Escribe tu mensaje aquí...', 'help_text' => null, 'error_required' => 'Por favor escribe tu mensaje.', 'error_invalid' => null],
                    'en' => ['label' => 'Message', 'placeholder' => 'Write your message here...', 'help_text' => null, 'error_required' => 'Please write your message.', 'error_invalid' => null],
                ],
            ],
        ];

        foreach ($fields as $fieldDef) {
            $fieldId = $this->upsertFormField($formId, $fieldDef);

            foreach ($fieldDef['translations'] as $code => $trans) {
                $langId = $langIds[$code] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->upsertFormFieldTranslation($fieldId, $langId, $trans);
            }
        }

        echo "CmsFormSeeder: contact form seeded (form_id={$formId}, form_key=" . self::CONTACT_FORM_KEY . ").\n";
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** @param array<int, string> $codes @return array<string, int> */
    private function langIds(array $codes): array
    {
        $rows = $this->db->table('cms_languages')
            ->whereIn('code', $codes)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['code']] = (int) $row['id'];
        }

        return $map;
    }

    /** @param array<string, mixed> $data */
    private function upsertForm(array $data): int
    {
        $formId = $this->upsertRecord('cms_forms', [
            'form_key' => $data['form_key'],
        ], [
            'is_active'             => $data['is_active'],
            'has_captcha'           => $data['has_captcha'],
            'notify_email'          => $data['notify_email'],
            'autoreply_enabled'     => $data['autoreply_enabled'],
            'autoreply_email_field' => $data['autoreply_email_field'],
        ]);

        if ($formId === null) {
            throw new \RuntimeException('CmsFormSeeder: unable to seed contact form.');
        }

        return $formId;
    }

    /** @param array<string, mixed> $data */
    private function upsertFormTranslation(int $formId, int $languageId, array $data): void
    {
        $this->upsertRecord('cms_form_translations', [
            'form_id'     => $formId,
            'language_id' => $languageId,
        ], $data);
    }

    /** @param array<string, mixed> $data */
    private function upsertFormField(int $formId, array $data): int
    {
        $fieldId = $this->upsertRecord('cms_form_fields', [
            'form_id'   => $formId,
            'field_key' => $data['field_key'],
        ], [
            'field_type'    => $data['field_type'],
            'display_order' => $data['display_order'],
            'is_required'   => $data['is_required'],
            'is_active'     => 1,
        ]);

        if ($fieldId === null) {
            throw new \RuntimeException(sprintf(
                'CmsFormSeeder: unable to seed form field "%s".',
                (string) $data['field_key']
            ));
        }

        return $fieldId;
    }

    /** @param array<string, mixed> $data */
    private function upsertFormFieldTranslation(int $fieldId, int $languageId, array $data): void
    {
        $this->upsertRecord('cms_form_field_translations', [
            'form_field_id' => $fieldId,
            'language_id'   => $languageId,
        ], $data);
    }
}
