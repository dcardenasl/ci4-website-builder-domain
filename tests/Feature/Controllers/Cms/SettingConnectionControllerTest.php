<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use App\Libraries\Hub\HubClient;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\Client\IntrospectResult;
use Tests\Support\ApiTestCase;

/**
 * Characterization coverage for SettingConnectionController, written before
 * moving its model() access into SettingConnectionService (DOM-115) — locks
 * in the current `{ok, data}` response shape and behavior so the refactor
 * cannot silently change it.
 *
 * @internal
 */
final class SettingConnectionControllerTest extends ApiTestCase
{
    private int $settingId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateRequest();
        $this->settingId = $this->seedSetting();
    }

    private function authenticateRequest(): void
    {
        $stub = new class (new IntrospectResult(
            valid: true,
            uid: 1,
            permissions: ['cms.settings.read', 'cms.settings.write'],
            exp: time() + 3600,
            error: null
        )) extends HubClient {
            public function __construct(private readonly IntrospectResult $result)
            {
            }

            public function introspect(string $token): IntrospectResult
            {
                return $this->result;
            }
        };

        Services::injectMock('hubClient', $stub);
        $this->setTestRequestHeaders(['Authorization' => 'Bearer fake-test-token']);
    }

    private function seedSetting(): int
    {
        $id = $this->db->table('cms_settings')->insert([
            'setting_key' => 'test_setting_' . uniqid(),
            'setting_value' => 'value',
            'setting_type' => 'string',
            'input_type' => 'text',
        ]);

        return (int) $this->db->insertID();
    }

    public function testIndexReturnsEmptyListInitially(): void
    {
        $result = $this->get("api/v1/cms/settings/{$this->settingId}/connections");

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);

        $this->assertTrue($body['ok']);
        $this->assertSame([], $body['data']['items']);
        $this->assertSame(0, $body['data']['total']);
    }

    public function testCreateThenIndexReturnsTheConnection(): void
    {
        $create = $this->post("api/v1/cms/settings/{$this->settingId}/connections", [
            'entity_type' => 'block_type',
            'entity_key' => 'hero_banner',
            'usage_label' => 'Logo shown in hero banner',
        ]);

        $create->assertStatus(201);
        $createBody = json_decode((string) $create->response()->getBody(), true);

        $this->assertTrue($createBody['ok']);
        $this->assertSame($this->settingId, $createBody['data']['setting_id']);
        $this->assertSame('block_type', $createBody['data']['entity_type']);
        $this->assertSame('hero_banner', $createBody['data']['entity_key']);
        $this->assertSame('Logo shown in hero banner', $createBody['data']['usage_label']);

        $this->resetRequest();
        $index = $this->get("api/v1/cms/settings/{$this->settingId}/connections");
        $indexBody = json_decode((string) $index->response()->getBody(), true);

        $this->assertSame(1, $indexBody['data']['total']);
        $this->assertSame('hero_banner', $indexBody['data']['items'][0]['entity_key']);
    }

    public function testCreateDuplicateReturnsValidationError(): void
    {
        $this->post("api/v1/cms/settings/{$this->settingId}/connections", [
            'entity_type' => 'block_type',
            'entity_key' => 'hero_banner',
        ]);

        $this->resetRequest();
        $duplicate = $this->post("api/v1/cms/settings/{$this->settingId}/connections", [
            'entity_type' => 'block_type',
            'entity_key' => 'hero_banner',
        ]);

        $duplicate->assertStatus(422);
    }

    public function testDeleteRemovesTheConnection(): void
    {
        $create = $this->post("api/v1/cms/settings/{$this->settingId}/connections", [
            'entity_type' => 'form',
            'entity_key' => 'contact_form',
        ]);
        $createBody = json_decode((string) $create->response()->getBody(), true);
        $connectionId = (int) $createBody['data']['id'];

        $this->resetRequest();
        $delete = $this->delete("api/v1/cms/settings/{$this->settingId}/connections/{$connectionId}");
        $delete->assertStatus(200);

        $this->resetRequest();
        $index = $this->get("api/v1/cms/settings/{$this->settingId}/connections");
        $indexBody = json_decode((string) $index->response()->getBody(), true);

        $this->assertSame(0, $indexBody['data']['total']);
    }

    public function testDeleteMissingConnectionReturnsNotFound(): void
    {
        $result = $this->delete("api/v1/cms/settings/{$this->settingId}/connections/999999");

        $result->assertStatus(404);
    }
}
