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

        $locale = $first->locale(0);
        $this->assertSame($first->slug('entry', $locale), $second->slug('entry', $locale));
        $this->assertSame($first->text('title', $locale), $second->text('title', $locale));
        $this->assertNotSame($first->slug('entry', $locale), (new FixtureValueFactory('Other'))->slug('entry', $locale));
    }

    public function testLocaleCodesArePositionBased(): void
    {
        $factory = new FixtureValueFactory('case');

        $this->assertSame('aa', $factory->locale(0));
        $this->assertSame('ad', $factory->locale(3));
    }
}
