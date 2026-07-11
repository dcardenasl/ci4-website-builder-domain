<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PublicFormControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langEsId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_form_field_translations`");
        $this->db->query("DELETE FROM `cms_form_fields`");
        $this->db->query("DELETE FROM `cms_form_translations`");
        $this->db->query("DELETE FROM `cms_forms`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        // Seed language
        $this->db->table('cms_languages')->insert([
            'code'       => 'es',
            'name'       => 'Spanish',
            'is_default' => 1,
            'is_active'  => 1,
        ]);
        $this->langEsId = $this->db->insertID();
    }

    public function testGetPublicFormDefinitionSuccess(): void
    {
        $this->db->table('cms_forms')->insert([
            'form_key'              => 'contact',
            'is_active'             => 1,
            'has_captcha'           => 0,
            'notify_email'          => 'admin@example.com',
            'autoreply_enabled'     => 1,
            'autoreply_email_field' => 'email',
        ]);
        $formId = $this->db->insertID();

        $this->db->table('cms_form_translations')->insert([
            'form_id'         => $formId,
            'language_id'     => $this->langEsId,
            'name'            => 'Formulario de Contacto',
            'submit_label'    => 'Enviar mensaje',
            'success_message' => '¡Gracias por escribirnos!',
            'error_message'   => 'Hubo un error.',
        ]);

        $this->db->table('cms_form_fields')->insert([
            'form_id'       => $formId,
            'field_key'     => 'name',
            'field_type'    => 'text',
            'display_order' => 10,
            'is_required'   => 1,
            'is_active'     => 1,
        ]);
        $fieldId = $this->db->insertID();

        $this->db->table('cms_form_field_translations')->insert([
            'form_field_id'  => $fieldId,
            'language_id'    => $this->langEsId,
            'label'          => 'Nombre completo',
            'placeholder'    => 'Su nombre',
            'error_required' => 'El nombre es obligatorio.',
        ]);

        $result = $this->get('/api/v1/public/es/forms/contact');

        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('contact', $body['data']['form_key']);
        $this->assertSame('Formulario de Contacto', $body['data']['name']);
        $this->assertSame('Enviar mensaje', $body['data']['submit_label']);
        $this->assertCount(1, $body['data']['fields']);
        $this->assertSame('name', $body['data']['fields'][0]['field_key']);
        $this->assertSame('Nombre completo', $body['data']['fields'][0]['label']);
    }

    public function testGetPublicFormDefinitionNotFound(): void
    {
        $result = $this->get('/api/v1/public/es/forms/no-exists');
        $result->assertStatus(404);
    }

    /**
     * Regression test: resolveFormTranslation()/resolveFieldTranslation() used
     * to (array)-cast the LanguageEntity instead of calling toArray(), which
     * always resolved $languageId to 0. The language_id lookup then silently
     * fell through to "first translation for this row" — invisible with only
     * one language seeded (the original testGetPublicFormDefinitionSuccess
     * above), because that fallback happened to match by coincidence. With two
     * languages present, every locale rendered identically until fixed.
     *
     * Also covers the select field's translatable option labels: the stable
     * value list on cms_form_fields.options combined with the per-language
     * option_labels map on cms_form_field_translations must resolve to the
     * requested locale's labels, not the other language's.
     */
    public function testGetPublicFormDefinitionResolvesRequestedLocaleNotJustTheFirstOne(): void
    {
        $langEnId = $this->db->table('cms_languages')->insert([
            'code'       => 'en',
            'name'       => 'English',
            'is_default' => 0,
            'is_active'  => 1,
        ]) ? $this->db->insertID() : 0;

        $this->db->table('cms_forms')->insert([
            'form_key'   => 'contact',
            'is_active'  => 1,
            'has_captcha' => 0,
        ]);
        $formId = $this->db->insertID();

        $this->db->table('cms_form_translations')->insert([
            'form_id'      => $formId,
            'language_id'  => $this->langEsId,
            'name'         => 'Formulario de Contacto',
            'submit_label' => 'Enviar mensaje',
        ]);
        $this->db->table('cms_form_translations')->insert([
            'form_id'      => $formId,
            'language_id'  => $langEnId,
            'name'         => 'Contact Form',
            'submit_label' => 'Send message',
        ]);

        $this->db->table('cms_form_fields')->insert([
            'form_id'       => $formId,
            'field_key'     => 'hours',
            'field_type'    => 'select',
            'options'       => json_encode(['1-hora', '2-horas'], JSON_UNESCAPED_UNICODE),
            'display_order' => 10,
            'is_required'   => 1,
            'is_active'     => 1,
        ]);
        $fieldId = $this->db->insertID();

        $this->db->table('cms_form_field_translations')->insert([
            'form_field_id' => $fieldId,
            'language_id'   => $this->langEsId,
            'label'         => 'Cantidad de Horas',
            'option_labels' => json_encode(['1-hora' => '1 Hora', '2-horas' => '2 Horas'], JSON_UNESCAPED_UNICODE),
        ]);
        $this->db->table('cms_form_field_translations')->insert([
            'form_field_id' => $fieldId,
            'language_id'   => $langEnId,
            'label'         => 'Number of Hours',
            'option_labels' => json_encode(['1-hora' => '1 Hour', '2-horas' => '2 Hours'], JSON_UNESCAPED_UNICODE),
        ]);

        $es = json_decode($this->get('/api/v1/public/es/forms/contact')->getJSON(), true);
        $en = json_decode($this->get('/api/v1/public/en/forms/contact')->getJSON(), true);

        $this->assertSame('Formulario de Contacto', $es['data']['name']);
        $this->assertSame('Contact Form', $en['data']['name']);
        $this->assertSame('Cantidad de Horas', $es['data']['fields'][0]['label']);
        $this->assertSame('Number of Hours', $en['data']['fields'][0]['label']);
        $this->assertSame(
            [['value' => '1-hora', 'label' => '1 Hora'], ['value' => '2-horas', 'label' => '2 Horas']],
            $es['data']['fields'][0]['options']
        );
        $this->assertSame(
            [['value' => '1-hora', 'label' => '1 Hour'], ['value' => '2-horas', 'label' => '2 Hours']],
            $en['data']['fields'][0]['options']
        );
    }
}
