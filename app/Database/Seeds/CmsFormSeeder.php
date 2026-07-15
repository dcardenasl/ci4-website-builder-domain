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
    private const GDPR_FORM_KEY    = 'gdpr_rights';

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);

        if (! isset($langIds['es'])) {
            echo "CmsFormSeeder: language 'es' not found. Seed CmsLanguageSeeder first.\n";
            return;
        }

        // ── 1. Contact Form ───────────────────────────────────────────────────
        $contactFormId = $this->upsertForm([
            'form_key'              => self::CONTACT_FORM_KEY,
            'is_active'             => 1,
            'has_captcha'           => 0,
            'notify_email'          => null,
            'autoreply_enabled'     => 1,
            'autoreply_email_field' => 'email',
        ]);

        $contactTranslations = [
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

        foreach ($contactTranslations as $code => $trans) {
            $langId = $langIds[$code] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->upsertFormTranslation($contactFormId, $langId, $trans);
        }

        $contactFields = [
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

        foreach ($contactFields as $fieldDef) {
            $fieldId = $this->upsertFormField($contactFormId, $fieldDef);

            foreach ($fieldDef['translations'] as $code => $trans) {
                $langId = $langIds[$code] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->upsertFormFieldTranslation($fieldId, $langId, $trans);
            }
        }

        echo "CmsFormSeeder: contact form seeded.\n";

        // ── 2. GDPR/ARCO Rights Form ──────────────────────────────────────────
        $gdprFormId = $this->upsertForm([
            'form_key'              => self::GDPR_FORM_KEY,
            'is_active'             => 1,
            'has_captcha'           => 0,
            'notify_email'          => null,
            'autoreply_enabled'     => 1,
            'autoreply_email_field' => 'email',
        ]);

        $gdprTranslations = [
            'es' => [
                'name'            => 'Solicitud de Derechos de Datos (ARCO)',
                'description'     => 'Utilice este formulario para ejercer sus derechos de protección de datos personales de acuerdo con el RGPD.',
                'submit_label'    => 'Enviar solicitud',
                'success_message' => '¡Su solicitud ha sido recibida! Responderemos a su requerimiento en el plazo legal.',
                'error_message'   => 'Ocurrió un error al enviar su solicitud. Por favor inténtelo de nuevo.',
            ],
            'en' => [
                'name'            => 'Data Subject Rights Request (GDPR)',
                'description'     => 'Use this form to exercise your personal data protection rights in accordance with GDPR.',
                'submit_label'    => 'Submit request',
                'success_message' => 'Your request has been received! We will respond within the legally required timeframe.',
                'error_message'   => 'There was an error submitting your request. Please try again.',
            ],
        ];

        foreach ($gdprTranslations as $code => $trans) {
            $langId = $langIds[$code] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->upsertFormTranslation($gdprFormId, $langId, $trans);
        }

        $gdprFields = [
            [
                'field_key'     => 'fullname',
                'field_type'    => 'text',
                'display_order' => 10,
                'is_required'   => 1,
                'translations'  => [
                    'es' => ['label' => 'Nombre completo', 'placeholder' => 'Su nombre y apellidos', 'help_text' => null, 'error_required' => 'Por favor ingrese su nombre.', 'error_invalid' => null],
                    'en' => ['label' => 'Full name', 'placeholder' => 'Your first and last name', 'help_text' => null, 'error_required' => 'Please enter your full name.', 'error_invalid' => null],
                ],
            ],
            [
                'field_key'     => 'email',
                'field_type'    => 'email',
                'display_order' => 20,
                'is_required'   => 1,
                'translations'  => [
                    'es' => ['label' => 'Correo electrónico', 'placeholder' => 'correo@ejemplo.com', 'help_text' => null, 'error_required' => 'Por favor ingrese su email.', 'error_invalid' => 'Ingrese un correo electrónico válido.'],
                    'en' => ['label' => 'Email address', 'placeholder' => 'you@example.com', 'help_text' => null, 'error_required' => 'Please enter your email.', 'error_invalid' => 'Enter a valid email address.'],
                ],
            ],
            [
                'field_key'     => 'right_type',
                'field_type'    => 'select',
                'display_order' => 30,
                'is_required'   => 1,
                'options'       => ['access', 'rectification', 'erasure', 'objection', 'portability'],
                'translations'  => [
                    'es' => [
                        'label'          => 'Derecho a ejercer',
                        'placeholder'    => 'Seleccione una opción',
                        'help_text'      => 'Seleccione el derecho que desea ejercitar sobre sus datos.',
                        'error_required' => 'Por favor seleccione una opción.',
                        'error_invalid'  => null,
                        'option_labels'  => [
                            'access'        => 'Acceso (Conocer qué datos tenemos)',
                            'rectification' => 'Rectificación (Corregir datos incorrectos)',
                            'erasure'       => 'Supresión / Olvido (Eliminar mis datos)',
                            'objection'     => 'Oposición (Rechazar ciertos tratamientos)',
                            'portability'   => 'Portabilidad (Recibir mis datos en formato estructurado)',
                        ],
                    ],
                    'en' => [
                        'label'          => 'Right to exercise',
                        'placeholder'    => 'Select an option',
                        'help_text'      => 'Select the right you wish to exercise over your data.',
                        'error_required' => 'Please select an option.',
                        'error_invalid'  => null,
                        'option_labels'  => [
                            'access'        => 'Access (Know what data we have)',
                            'rectification' => 'Rectification (Correct incorrect data)',
                            'erasure'       => 'Erasure (Delete my data)',
                            'objection'     => 'Objection (Object to certain treatments)',
                            'portability'   => 'Portability (Receive my data in structured format)',
                        ],
                    ],
                ],
            ],
            [
                'field_key'     => 'details',
                'field_type'    => 'textarea',
                'display_order' => 40,
                'is_required'   => 1,
                'translations'  => [
                    'es' => ['label' => 'Detalles de la solicitud', 'placeholder' => 'Describa detalladamente su petición...', 'help_text' => 'Especifique la información afectada o el cambio requerido.', 'error_required' => 'Por favor especifique los detalles.', 'error_invalid' => null],
                    'en' => ['label' => 'Details of the request', 'placeholder' => 'Describe your request in detail...', 'help_text' => 'Specify the affected information or the required change.', 'error_required' => 'Please specify request details.', 'error_invalid' => null],
                ],
            ],
        ];

        foreach ($gdprFields as $fieldDef) {
            $fieldId = $this->upsertFormField($gdprFormId, $fieldDef);

            foreach ($fieldDef['translations'] as $code => $trans) {
                $langId = $langIds[$code] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->upsertFormFieldTranslation($fieldId, $langId, $trans);
            }
        }

        echo "CmsFormSeeder: GDPR/ARCO rights form seeded.\n";
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
            'options'       => isset($data['options']) ? json_encode($data['options'], JSON_UNESCAPED_UNICODE) : null,
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
        ], [
            'label'          => $data['label'] ?? null,
            'placeholder'    => $data['placeholder'] ?? null,
            'help_text'      => $data['help_text'] ?? null,
            'error_required' => $data['error_required'] ?? null,
            'error_invalid'  => $data['error_invalid'] ?? null,
            'option_labels'  => isset($data['option_labels']) ? json_encode($data['option_labels'], JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
