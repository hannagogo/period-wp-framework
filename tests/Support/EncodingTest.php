<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\Encoding;

final class EncodingTest extends TestCase
{
    public function testBase64UrlEncodeAndDecodeRoundtrip(): void
    {
        $value = 'hello+world/=';

        $encoded = Encoding::base64UrlEncode($value);
        $decoded = Encoding::base64UrlDecode($encoded);

        $this->assertSame($value, $decoded);
    }

    public function testDecodeHtmlEntitiesRestoresEntities(): void
    {
        $this->assertSame('<>&"', Encoding::decodeHtmlEntities('&lt;&gt;&amp;&quot;'));
    }
}
