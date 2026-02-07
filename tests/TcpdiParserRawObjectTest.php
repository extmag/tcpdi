<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TcpdiParserRawObjectTest extends TestCase
{
    /**
     * Bug 8: Short data (fewer than 4 chars) should not trigger
     * undefined offset warnings when building the $frag variable.
     */
    public function testShortDataDoesNotCauseError(): void
    {
        $pdf = $this->buildMinimalPdf();
        $parser = new \tcpdi_parser($pdf, 'raw-test');

        $method = new \ReflectionMethod(\tcpdi_parser::class, 'getRawObject');
        $method->setAccessible(true);

        // Test with data shorter than 4 bytes - should not emit any warnings
        // 'n' is 1 byte, gets matched as numeric or falls through
        $result = $method->invoke($parser, 0, 'n');
        $this->assertIsArray($result);
    }

    /**
     * Parsing 'null' should return PDF_TYPE_NULL
     */
    public function testParseNull(): void
    {
        $pdf = $this->buildMinimalPdf();
        $parser = new \tcpdi_parser($pdf, 'raw-test');

        $method = new \ReflectionMethod(\tcpdi_parser::class, 'getRawObject');
        $method->setAccessible(true);

        $result = $method->invoke($parser, 0, 'null ');
        $this->assertSame(PDF_TYPE_NULL, $result[0][0]);
    }

    /**
     * Parsing 'true' should return PDF_TYPE_BOOLEAN with value true
     */
    public function testParseTrue(): void
    {
        $pdf = $this->buildMinimalPdf();
        $parser = new \tcpdi_parser($pdf, 'raw-test');

        $method = new \ReflectionMethod(\tcpdi_parser::class, 'getRawObject');
        $method->setAccessible(true);

        $result = $method->invoke($parser, 0, 'true ');
        $this->assertSame(PDF_TYPE_BOOLEAN, $result[0][0]);
        $this->assertTrue($result[0][1]);
    }

    /**
     * Parsing 'false' should return PDF_TYPE_BOOLEAN with value false
     */
    public function testParseFalse(): void
    {
        $pdf = $this->buildMinimalPdf();
        $parser = new \tcpdi_parser($pdf, 'raw-test');

        $method = new \ReflectionMethod(\tcpdi_parser::class, 'getRawObject');
        $method->setAccessible(true);

        $result = $method->invoke($parser, 0, 'false ');
        $this->assertSame(PDF_TYPE_BOOLEAN, $result[0][0]);
        $this->assertFalse($result[0][1]);
    }

    /**
     * Two-char data should work without warnings
     */
    public function testTwoCharDataNoWarning(): void
    {
        $pdf = $this->buildMinimalPdf();
        $parser = new \tcpdi_parser($pdf, 'raw-test');

        $method = new \ReflectionMethod(\tcpdi_parser::class, 'getRawObject');
        $method->setAccessible(true);

        $result = $method->invoke($parser, 0, '42');
        $this->assertIsArray($result);
        // Should parse as numeric
        $this->assertSame(PDF_TYPE_NUMERIC, $result[0][0]);
        $this->assertSame('42', $result[0][1]);
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
