<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Jobs\CacheInvalidationJob;
use App\Libraries\Cms\CacheInvalidationClient;
use dcardenasl\Ci4ApiCore\Queue\QueueManagerInterface;
use dcardenasl\Ci4ApiCore\Queue\SyncQueueManager;
use PHPUnit\Framework\TestCase;

final class CacheInvalidationClientTest extends TestCase
{
    public function testQueueDriverDispatchesUsingTheSharedDefaultQueue(): void
    {
        $queue = $this->createMock(QueueManagerInterface::class);
        $queue->expects($this->once())
            ->method('push')
            ->with(CacheInvalidationJob::class, ['scopes' => ['menus']], 'default')
            ->willReturn(42);

        (new CacheInvalidationClient(queueManager: $queue))->invalidate(['menus']);
    }

    public function testSyncQueueExecutesTheSameJobImmediately(): void
    {
        $queue = new SyncQueueManager();

        (new CacheInvalidationClient(queueManager: $queue))->invalidate(['menus']);

        $this->addToAssertionCount(1);
    }
}
