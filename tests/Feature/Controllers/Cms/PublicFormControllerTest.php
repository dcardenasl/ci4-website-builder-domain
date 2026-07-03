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
}
