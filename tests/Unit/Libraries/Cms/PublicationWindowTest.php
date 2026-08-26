<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\PublicationWindow;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PublicationWindowTest extends CIUnitTestCase
{
    private const NOW = '2026-08-26 12:00:00';

    public function testDraftIsNeverOpen(): void
    {
        $this->assertFalse(PublicationWindow::isOpen('draft', null, null, self::NOW));
        $this->assertFalse(PublicationWindow::isOpen('archived', null, null, self::NOW));
        $this->assertFalse(PublicationWindow::isOpen(null, null, null, self::NOW));
    }

    public function testPublishedWithNoDatesIsOpen(): void
    {
        $this->assertTrue(PublicationWindow::isOpen('published', null, null, self::NOW));
    }

    public function testEmptyDatesCountAsUnset(): void
    {
        $this->assertTrue(PublicationWindow::isOpen('published', '', '   ', self::NOW));
    }

    public function testFuturePublishedAtKeepsWindowClosed(): void
    {
        $this->assertFalse(
            PublicationWindow::isOpen('published', '2026-08-26 12:00:01', null, self::NOW)
        );
    }

    public function testFutureScheduledAtKeepsWindowClosed(): void
    {
        $this->assertFalse(
            PublicationWindow::isOpen('published', null, '2026-08-26 12:00:01', self::NOW)
        );
    }

    public function testPastDatesOpenTheWindow(): void
    {
        $this->assertTrue(
            PublicationWindow::isOpen('published', '2026-08-26 11:59:59', '2026-01-01 00:00:00', self::NOW)
        );
    }

    public function testBoundaryIsInclusive(): void
    {
        // `apply()` uses `<=` in SQL; the row-level check must agree exactly,
        // otherwise a page flips visibility depending on which path loaded it.
        $this->assertTrue(
            PublicationWindow::isOpen('published', self::NOW, self::NOW, self::NOW)
        );
    }

    public function testBothDatesMustHaveElapsed(): void
    {
        $this->assertFalse(
            PublicationWindow::isOpen('published', '2026-01-01 00:00:00', '2026-12-31 00:00:00', self::NOW)
        );
    }
}
