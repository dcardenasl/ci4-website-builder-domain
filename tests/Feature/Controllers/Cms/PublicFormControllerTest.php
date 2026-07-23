<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Fixtures\CmsFixtureFactory;
use Tests\Support\Traits\WithWebAppKeyTrait;

/**
 * @internal
 */
final class PublicFormControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use WithWebAppKeyTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private CmsFixtureFactory $fixtures;

    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages;

    private string $formKey;

    private string $fieldKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebAppKey();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_form_field_translations`");
        $this->db->query("DELETE FROM `cms_form_fields`");
        $this->db->query("DELETE FROM `cms_form_translations`");
        $this->db->query("DELETE FROM `cms_forms`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        $this->fixtures = new CmsFixtureFactory($this->db, self::class);
        $this->languages = $this->fixtures->languages(3);
        $this->formKey = $this->fixtures->slug('form');
        $this->fieldKey = $this->fixtures->slug('field');
    }

    protected function tearDown(): void
    {
        $this->restoreWebAppKey();
        parent::tearDown();
    }

    public function testGetPublicFormDefinitionSuccess(): void
    {
        $language = $this->languages[0];
        $form = $this->fixtures->form([[
            'language_id' => $language['id'],
            'name' => $this->fixtures->text('form-name', $language['code']),
            'submit_label' => $this->fixtures->text('form-submit', $language['code']),
            'success_message' => $this->fixtures->text('form-success', $language['code']),
            'error_message' => $this->fixtures->text('form-error', $language['code']),
        ]], ['form_key' => $this->formKey]);
        $translation = $form['translations'][0];

        $this->db->table('cms_form_fields')->insert([
            'form_id' => $form['id'],
            'field_key' => $this->fieldKey,
            'field_type' => 'text',
            'display_order' => 10,
            'is_required' => 1,
            'is_active' => 1,
        ]);
        $fieldId = (int) $this->db->insertID();
        $fieldTranslation = [
            'form_field_id' => $fieldId,
            'language_id' => $language['id'],
            'label' => $this->fixtures->text('field-label', $language['code']),
            'placeholder' => $this->fixtures->text('field-placeholder', $language['code']),
            'error_required' => $this->fixtures->text('field-error', $language['code']),
        ];
        $this->db->table('cms_form_field_translations')->insert($fieldTranslation);

        $result = $this->get($this->formPath($this->formKey, $language['code']));

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame($form['key'], $body['data']['form_key']);
        $this->assertSame($translation['name'], $body['data']['name']);
        $this->assertSame($translation['submit_label'], $body['data']['submit_label']);
        $this->assertCount(1, $body['data']['fields']);
        $this->assertSame($this->fieldKey, $body['data']['fields'][0]['field_key']);
        $this->assertSame($fieldTranslation['label'], $body['data']['fields'][0]['label']);
    }

    public function testGetPublicFormDefinitionNotFound(): void
    {
        $result = $this->get($this->formPath($this->fixtures->slug('missing-form'), $this->languages[0]['code']));

        $result->assertStatus(404);
    }

    public function testGetPublicFormDefinitionResolvesRequestedLocaleNotJustTheFirstOne(): void
    {
        $first = $this->languages[0];
        $second = $this->languages[1];
        $form = $this->fixtures->form([
            [
                'language_id' => $first['id'],
                'name' => $this->fixtures->text('form-name', $first['code']),
                'submit_label' => $this->fixtures->text('form-submit', $first['code']),
            ],
            [
                'language_id' => $second['id'],
                'name' => $this->fixtures->text('form-name', $second['code']),
                'submit_label' => $this->fixtures->text('form-submit', $second['code']),
            ],
        ], ['form_key' => $this->formKey]);

        $optionValues = [
            $this->fixtures->slug('option', 'first'),
            $this->fixtures->slug('option', 'second'),
        ];
        $this->db->table('cms_form_fields')->insert([
            'form_id' => $form['id'],
            'field_key' => $this->fieldKey,
            'field_type' => 'select',
            'options' => json_encode($optionValues, JSON_THROW_ON_ERROR),
            'display_order' => 10,
            'is_required' => 1,
            'is_active' => 1,
        ]);
        $fieldId = (int) $this->db->insertID();

        $labels = [];
        foreach ([$first, $second] as $language) {
            $labels[$language['code']] = [
                $optionValues[0] => $this->fixtures->text('option-label', $language['code'] . '-first'),
                $optionValues[1] => $this->fixtures->text('option-label', $language['code'] . '-second'),
            ];
            $this->db->table('cms_form_field_translations')->insert([
                'form_field_id' => $fieldId,
                'language_id' => $language['id'],
                'label' => $this->fixtures->text('field-label', $language['code']),
                'option_labels' => json_encode($labels[$language['code']], JSON_THROW_ON_ERROR),
            ]);
        }

        $responses = [];
        foreach ([$first, $second] as $language) {
            $responses[$language['code']] = json_decode(
                $this->get($this->formPath($this->formKey, $language['code']))->getJSON(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        }

        $this->assertSame(
            $form['translations'][0]['name'],
            $responses[$first['code']]['data']['name'],
        );
        $this->assertSame(
            $form['translations'][1]['name'],
            $responses[$second['code']]['data']['name'],
        );

        foreach ([$first, $second] as $language) {
            $expectedOptions = array_map(
                fn (string $value): array => ['value' => $value, 'label' => $labels[$language['code']][$value]],
                $optionValues,
            );
            $this->assertSame($expectedOptions, $responses[$language['code']]['data']['fields'][0]['options']);
        }
    }

    private function formPath(string $key, string $locale): string
    {
        return '/api/v1/public/' . $locale . '/forms/' . $key;
    }
}
