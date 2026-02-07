<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TcpdiParserDecodeStreamTest extends TestCase
{
    private \tcpdi_parser $parser;

    protected function setUp(): void
    {
        $pdf = $this->buildMinimalPdf();
        $this->parser = new \tcpdi_parser($pdf, 'decode-test');
    }

    /**
     * Bug 2: /Length with PDF_TYPE_NUMERIC should truncate the stream.
     * Before fix, the /Length check was inside if ($v[0] == PDF_TYPE_TOKEN),
     * so PDF_TYPE_NUMERIC values were never checked.
     */
    public function testLengthTruncatesStream(): void
    {
        $method = new \ReflectionMethod(\tcpdi_parser::class, 'decodeStream');
        $method->setAccessible(true);

        $sdic = [
            '/Length' => [PDF_TYPE_NUMERIC, 5],
        ];
        $stream = 'HelloWorld'; // 10 chars, should be truncated to 5

        $result = $method->invoke($this->parser, $sdic, $stream);
        $this->assertSame('Hello', $result[0]);
    }

    /**
     * /Length should not truncate if declared length >= actual length
     */
    public function testLengthDoesNotExtendStream(): void
    {
        $method = new \ReflectionMethod(\tcpdi_parser::class, 'decodeStream');
        $method->setAccessible(true);

        $sdic = [
            '/Length' => [PDF_TYPE_NUMERIC, 100],
        ];
        $stream = 'Hello';

        $result = $method->invoke($this->parser, $sdic, $stream);
        $this->assertSame('Hello', $result[0]);
    }

    /**
     * /Filter as TOKEN (single filter) should be collected.
     */
    public function testFilterAsToken(): void
    {
        $method = new \ReflectionMethod(\tcpdi_parser::class, 'decodeStream');
        $method->setAccessible(true);

        // Use a filter name that won't be decoded (not in available filters)
        // so it ends up in remaining_filters
        $sdic = [
            '/Filter' => [PDF_TYPE_TOKEN, 'SomeUnknownFilter'],
        ];
        $stream = 'data';

        $result = $method->invoke($this->parser, $sdic, $stream);
        $this->assertSame('data', $result[0]);
        $this->assertContains('SomeUnknownFilter', $result[1]);
    }

    /**
     * Bug 2: /Filter as ARRAY should be collected.
     * Before fix, the /Filter ARRAY check was inside if ($v[0] == PDF_TYPE_TOKEN),
     * so arrays were never processed.
     */
    public function testFilterAsArray(): void
    {
        $method = new \ReflectionMethod(\tcpdi_parser::class, 'decodeStream');
        $method->setAccessible(true);

        $sdic = [
            '/Filter' => [
                PDF_TYPE_ARRAY,
                [
                    [PDF_TYPE_TOKEN, 'UnknownFilter1'],
                    [PDF_TYPE_TOKEN, 'UnknownFilter2'],
                ],
            ],
        ];
        $stream = 'data';

        $result = $method->invoke($this->parser, $sdic, $stream);
        $this->assertSame('data', $result[0]);
        $this->assertContains('UnknownFilter1', $result[1]);
        $this->assertContains('UnknownFilter2', $result[1]);
    }

    /**
     * Empty stream returns empty result
     */
    public function testEmptyStreamReturnsEmpty(): void
    {
        $method = new \ReflectionMethod(\tcpdi_parser::class, 'decodeStream');
        $method->setAccessible(true);

        $result = $method->invoke($this->parser, [], '');
        $this->assertSame('', $result[0]);
        $this->assertEmpty($result[1]);
    }

    private function buildMinimalPdf(): string
    {
        return "%PDF-1.4\n" .
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n" .
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n" .
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\n" .
            "xref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n" .
            "trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n186\n%%EOF";
    }
}
