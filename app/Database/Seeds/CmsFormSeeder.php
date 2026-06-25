<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the initial contact form with bilingual translations and default fields.
 * Idempotent: safe to run multiple times.
 */
class CmsFormSeeder extends Seeder
{
    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);

        if (! isset($langIds['es'])) {
            echo "CmsFormSeeder: language 'es' not found. Run CmsLanguageSeeder first.\n";
            return;
        }

        $formId = $this->upsertForm([
            'form_key'              => 'contact',
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
                'field_key'    => 'phone',
                'field_type'   => 'phone',
                'display_order' => 30,
                'is_required'  => 0,
                'translations' => [
                    'es' => ['label' => 'Teléfono', 'placeholder' => '+56 9 1234 5678', 'help_text' => 'Opcional', 'error_required' => null, 'error_invalid' => null],
                    'en' => ['label' => 'Phone', 'placeholder' => '+1 555 0100', 'help_text' => 'Optional', 'error_required' => null, 'error_invalid' => null],
                ],
            ],
            [
                'field_key'    => 'message',
                'field_type'   => 'textarea',
                'display_order' => 40,
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

        echo "CmsFormSeeder: contact form seeded (form_id={$formId}).\n";
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
        $existing = $this->db->table('cms_forms')
            ->where('form_key', $data['form_key'])
            ->get()
            ->getRowArray();

        $payload = array_diff_key($data, ['form_key' => null]);

        if ($existing === null) {
            $this->db->table('cms_forms')->insert(array_merge(['form_key' => $data['form_key']], $payload));
            return (int) $this->db->insertID();
        }

        $this->db->table('cms_forms')->where('id', (int) $existing['id'])->update($payload);
        return (int) $existing['id'];
    }

    /** @param array<string, mixed> $data */
    private function upsertFormTranslation(int $formId, int $languageId, array $data): void
    {
        $existing = $this->db->table('cms_form_translations')
            ->where('form_id', $formId)
            ->where('language_id', $languageId)
            ->get()
            ->getRowArray();

        if ($existing === null) {
            $this->db->table('cms_form_translations')->insert(array_merge(
                ['form_id' => $formId, 'language_id' => $languageId],
                $data
            ));
            return;
        }

        $this->db->table('cms_form_translations')
            ->where('id', (int) $existing['id'])
            ->update($data);
    }

    /** @param array<string, mixed> $data */
    private function upsertFormField(int $formId, array $data): int
    {
        $existing = $this->db->table('cms_form_fields')
            ->where('form_id', $formId)
            ->where('field_key', $data['field_key'])
            ->get()
            ->getRowArray();

        $payload = [
            'field_type'    => $data['field_type'],
            'display_order' => $data['display_order'],
            'is_required'   => $data['is_required'],
            'is_active'     => 1,
        ];

        if ($existing === null) {
            $this->db->table('cms_form_fields')->insert(array_merge(
                ['form_id' => $formId, 'field_key' => $data['field_key']],
                $payload
            ));
            return (int) $this->db->insertID();
        }

        $this->db->table('cms_form_fields')
            ->where('id', (int) $existing['id'])
            ->update($payload);

        return (int) $existing['id'];
    }

    /** @param array<string, mixed> $data */
    private function upsertFormFieldTranslation(int $fieldId, int $languageId, array $data): void
    {
        $existing = $this->db->table('cms_form_field_translations')
            ->where('form_field_id', $fieldId)
            ->where('language_id', $languageId)
            ->get()
            ->getRowArray();

        if ($existing === null) {
            $this->db->table('cms_form_field_translations')->insert(array_merge(
                ['form_field_id' => $fieldId, 'language_id' => $languageId],
                $data
            ));
            return;
        }

        $this->db->table('cms_form_field_translations')
            ->where('id', (int) $existing['id'])
            ->update($data);
    }
}
