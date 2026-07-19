<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures\FixtureValueFactory;

/** @internal */
final class FixtureValueFactoryTest extends TestCase
{
    public function testValuesAreDeterministicAndScoped(): void
    {
        $first = new FixtureValueFactory('Tests\\Feature\\Cms');
        $second = new FixtureValueFactory('Tests\\Feature\\Cms');

        $this->assertSame($first->slug('entry', 'l01'), $second->slug('entry', 'l01'));
        $this->assertSame($first->text('title', 'l01'), $second->text('title', 'l01'));
        $this->assertNotSame($first->slug('entry', 'l01'), (new FixtureValueFactory('Other'))->slug('entry', 'l01'));
    }

    public function testLocaleCodesArePositionBased(): void
    {
        $factory = new FixtureValueFactory('case');

        $this->assertSame('l01', $factory->locale(0));
        $this->assertSame('l04', $factory->locale(3));
    }
}
