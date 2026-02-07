<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TcpdiParserErrorTest extends TestCase
{
    /**
     * Bug 3: Error() should throw RuntimeException, not die()
     */
    public function testErrorThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TCPDI_PARSER ERROR');

        new \tcpdi_parser('', 'test-id');
    }

    public function testErrorMessageContainsUniqueId(): void
    {
        $minimalPdf = $this->buildMinimalPdf();
        $parser = new \tcpdi_parser($minimalPdf, 'my-unique-id');

        // Call Error directly to verify uniqueid is included
        try {
            $parser->Error('test message');
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('my-unique-id', $e->getMessage());
            $this->assertStringContainsString('test message', $e->getMessage());
        }
    }

    public function testErrorMessageHasNoHtmlTags(): void
    {
        try {
            new \tcpdi_parser('', 'test-id');
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('<strong>', $e->getMessage());
            $this->assertStringNotContainsString('</strong>', $e->getMessage());
        }
    }

    /**
     * Bug 6: Error() in getIndirectObject should receive $obj_ref (string), not $obj (array)
     */
    public function testInvalidObjRefErrorContainsString(): void
    {
        $minimalPdf = $this->buildMinimalPdf();
        $parser = new \tcpdi_parser($minimalPdf, 'test');

        // Use reflection to call getIndirectObject with an invalid obj_ref
        $method = new \ReflectionMethod(\tcpdi_parser::class, 'getIndirectObject');
        $method->setAccessible(true);

        try {
            $method->invoke($parser, 'invalid_ref_format', 0);
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            // The message should contain the obj_ref string, not "Array"
            $this->assertStringContainsString('invalid_ref_format', $e->getMessage());
            $this->assertStringNotContainsString('Array', $e->getMessage());
        }
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
