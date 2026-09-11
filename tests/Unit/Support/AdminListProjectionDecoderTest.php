<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\AdminListProjectionDecoder;
use CodeIgniter\Test\CIUnitTestCase;

/** @internal */
final class AdminListProjectionDecoderTest extends CIUnitTestCase
{
    public function testDecodesHexEncodedTranslationsAndIgnoresMalformedRows(): void
    {
        $encoded = '1:4361746567:C3A1746567|broken|2:4361746567:ZZ';

        $this->assertSame([
            [
                'language_id' => 1,
                'name' => 'Categ',
                'slug' => 'áteg',
            ],
        ], AdminListProjectionDecoder::translations($encoded, ['name', 'slug']));
    }

    public function testEmptyHexValuesBecomeNull(): void
    {
        $this->assertSame([
            ['language_id' => 1, 'name' => null],
        ], AdminListProjectionDecoder::translations('1:', ['name']));
    }
}
