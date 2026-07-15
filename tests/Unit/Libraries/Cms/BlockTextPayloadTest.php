<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\BlockTextPayload;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BlockTextPayloadTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BlockTextPayload::usageCount('reset');
    }

    public function testCanonicalContentKeyIsLeftUnchanged(): void
    {
        $data = BlockTextPayload::normalize(['content' => 'Hello']);

        $this->assertSame('Hello', $data['content']);
    }

    public function testLegacyBodyKeyIsPromotedToContent(): void
    {
        $data = BlockTextPayload::normalize(['body' => 'Legacy body text']);

        $this->assertSame('Legacy body text', $data['content']);
    }

    public function testLegacyKeysAreIgnoredWhenContentAlreadyPresent(): void
    {
        $data = BlockTextPayload::normalize(['content' => 'Real content', 'body' => 'Stale legacy value']);

        $this->assertSame('Real content', $data['content']);
    }

    public function testCounterStartsAtZero(): void
    {
        $this->assertSame(0, BlockTextPayload::usageCount('read'));
    }

    public function testCanonicalContentKeyDoesNotIncrementCounter(): void
    {
        BlockTextPayload::normalize(['content' => 'Hello']);

        $this->assertSame(0, BlockTextPayload::usageCount('read'));
    }

    public function testLegacyKeyIncrementsCounter(): void
    {
        BlockTextPayload::normalize(['body' => 'Legacy body text']);

        $this->assertSame(1, BlockTextPayload::usageCount('read'));
    }

    public function testCounterAccumulatesAcrossCalls(): void
    {
        BlockTextPayload::normalize(['body' => 'One']);
        BlockTextPayload::normalize(['html' => 'Two']);
        BlockTextPayload::normalize(['content' => 'Three']);

        $this->assertSame(2, BlockTextPayload::usageCount('read'));
    }

    public function testResetClearsCounter(): void
    {
        BlockTextPayload::normalize(['body' => 'Legacy']);
        $this->assertSame(1, BlockTextPayload::usageCount('read'));

        BlockTextPayload::usageCount('reset');

        $this->assertSame(0, BlockTextPayload::usageCount('read'));
    }
}
