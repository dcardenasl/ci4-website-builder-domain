<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/** @internal */
final class ResourceAuthorizationServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testDirectGrantIsolatedFromAnotherUser(): void
    {
        $pageId = $this->insertPage();
        $this->grant('page', $pageId, 10, 'read');
        $service = Services::resourceAuthorization(false);

        $service->assertCan('page', $pageId, 'read', new SecurityContext(user_id: 10));

        $this->expectException(NotFoundException::class);
        $service->assertCan('page', $pageId, 'read', new SecurityContext(user_id: 11));
    }

    public function testCollectionGrantIsInheritedByEntries(): void
    {
        $collectionId = $this->insertCollection();
        $entryId = $this->insertEntry($collectionId);
        $this->grant('collection', $collectionId, 10, 'write');

        Services::resourceAuthorization(false)->assertCan(
            'entry',
            $entryId,
            'write',
            new SecurityContext(user_id: 10),
        );
        self::assertTrue(true);
    }

    public function testScopeCriteriaReturnsOnlyGrantedIds(): void
    {
        $allowed = $this->insertPage();
        $hidden = $this->insertPage();
        $this->grant('page', $allowed, 9010, 'read');

        $criteria = Services::resourceAuthorization(false)->scopeCriteria(
            'page',
            ['page' => 1],
            new SecurityContext(user_id: 9010),
        );

        self::assertSame([$allowed], $criteria['scope_ids']);
        self::assertNotContains($hidden, $criteria['scope_ids']);
    }

    public function testTransferRemovesOldOwnershipAndAssignsNewOwner(): void
    {
        $pageId = $this->insertPage();
        $this->grant('page', $pageId, 10, 'admin');
        $context = new SecurityContext(user_id: 10);

        Services::resourceAuthorization(false)->transferOwnership('page', $pageId, 11, $context);

        $service = Services::resourceAuthorization(false);
        try {
            $service->assertCan('page', $pageId, 'admin', new SecurityContext(user_id: 10));
            self::fail('The previous owner must lose admin access after transfer.');
        } catch (NotFoundException) {
            self::assertTrue(true);
        }
        $service->assertCan('page', $pageId, 'admin', new SecurityContext(user_id: 11));
    }

    public function testSuperadminBypassesResourceGrantAndAResourceCannotBeOrphaned(): void
    {
        $pageId = $this->insertPage();
        $service = Services::resourceAuthorization(false);
        $superadmin = new SecurityContext(user_id: 99, permissions: ['iam.superadmin-access']);

        $service->grant('page', $pageId, 10, 'admin', $superadmin);
        $service->grant('page', $pageId, 11, 'read', $superadmin);
        self::assertCount(2, $service->listGrants('page', $pageId, $superadmin));

        $this->expectException(ValidationException::class);
        $service->revoke('page', $pageId, 10, $superadmin);
    }

    public function testResourceAdminCanManageReadAndWriteGrantsWithoutGlobalPermission(): void
    {
        $pageId = $this->insertPage();
        $this->grant('page', $pageId, 10, 'admin');
        $service = Services::resourceAuthorization(false);
        $owner = new SecurityContext(user_id: 10);

        $service->grant('page', $pageId, 11, 'write', $owner);
        $service->assertCan('page', $pageId, 'write', new SecurityContext(user_id: 11));
        $service->revoke('page', $pageId, 11, $owner);

        $this->expectException(NotFoundException::class);
        $service->assertCan('page', $pageId, 'read', new SecurityContext(user_id: 11));
    }

    public function testOnlySuperadminCanDelegateResourceAdmin(): void
    {
        $pageId = $this->insertPage();
        $this->grant('page', $pageId, 10, 'admin');
        $service = Services::resourceAuthorization(false);

        $this->expectException(AuthorizationException::class);
        $service->grant('page', $pageId, 11, 'admin', new SecurityContext(user_id: 10));
    }

    public function testDeletedResourceIsNotAccessibleEvenToSuperadmin(): void
    {
        $pageId = $this->insertPage();
        Database::connect()->table('cms_pages')->where('id', $pageId)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        $this->expectException(NotFoundException::class);
        Services::resourceAuthorization(false)->assertCan(
            'page',
            $pageId,
            'read',
            new SecurityContext(user_id: 99, permissions: ['iam.superadmin-access']),
        );
    }

    private function insertPage(): int
    {
        $db = Database::connect();
        $db->table('cms_pages')->insert(['page_type' => 'generic', 'status' => 'draft', 'sort_order' => 0]);

        return (int) $db->insertID();
    }

    private function insertCollection(): int
    {
        $db = Database::connect();
        $db->table('cms_collections')->insert([
            'collection_key' => 'scope-' . bin2hex(random_bytes(4)),
            'collection_type' => 'article',
            'sort_order' => 0,
        ]);

        return (int) $db->insertID();
    }

    private function insertEntry(int $collectionId): int
    {
        $db = Database::connect();
        $db->table('cms_entries')->insert([
            'collection_id' => $collectionId,
            'workflow_status' => 'draft',
            'view_count' => 0,
            'sort_order' => 0,
            'is_in_sitemap' => 1,
        ]);

        return (int) $db->insertID();
    }

    private function grant(string $type, int $resourceId, int $userId, string $level): void
    {
        Database::connect()->table('cms_resource_access')->insert([
            'resource_type' => $type,
            'resource_id' => $resourceId,
            'user_id' => $userId,
            'access_level' => $level,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
