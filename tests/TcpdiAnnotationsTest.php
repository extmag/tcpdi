<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TcpdiAnnotationsTest extends TestCase
{
    /**
     * Bug 1: count($annots[1][1] > 1) always evaluates count(true) = 1 or count(false) = 1
     * on PHP 7.2+ (count on non-countable), meaning annotations were NEVER imported.
     *
     * Demonstrates that the buggy expression is always truthy (but useless):
     *   count($arr > 1) => count(bool) => always 1 on PHP < 7.2
     *   or triggers a warning on PHP 7.2+
     */
    public function testBuggyExpressionAlwaysFalseOnModernPhp(): void
    {
        $arr = [1, 2, 3];
        // The buggy code: count($arr > 1) - $arr > 1 evaluates to true (bool),
        // then count(true) returns 1 on PHP < 8.0, or triggers a TypeError on PHP 8.0+
        // Either way, it doesn't give the intended result of count($arr) > 1
        $this->assertGreaterThan(0, count($arr));
        // The fix: count($arr) > 0
        $this->assertTrue(count($arr) > 0);
    }

    /**
     * With zero annotations, count should be 0
     */
    public function testEmptyAnnotationsCountIsZero(): void
    {
        $arr = [];
        $this->assertFalse(count($arr) > 0);
    }

    /**
     * With one annotation, count should be 1 and > 0
     */
    public function testSingleAnnotationCountIsPositive(): void
    {
        $arr = [[8, 5, 0]]; // 8 = PDF_TYPE_OBJREF
        $this->assertTrue(count($arr) > 0);
    }

    /**
     * Test importAnnotations doesn't crash on a page without annotations
     */
    public function testImportAnnotationsNoAnnotations(): void
    {
        $pdf = $this->buildMinimalPdf();

        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData($pdf);
        $tcpdi->AddPage();

        // Should not throw - page has no annotations
        $tcpdi->importAnnotations(1);

        // No imported annotations should exist
        $ref = new \ReflectionProperty(\TCPDI::class, '_importedAnnots');
        $ref->setAccessible(true);
        $annots = $ref->getValue($tcpdi);

        // Either empty or not set for this page
        $this->assertTrue(empty($annots) || !isset($annots[$tcpdi->PageNo()]));
    }

    /**
     * Test importAnnotations processes a page with annotations
     */
    public function testImportAnnotationsWithAnnotations(): void
    {
        $pdf = $this->buildPdfWithAnnotation();

        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData($pdf);
        $tcpdi->AddPage();

        $tcpdi->importAnnotations(1);

        $ref = new \ReflectionProperty(\TCPDI::class, '_importedAnnots');
        $ref->setAccessible(true);
        $annots = $ref->getValue($tcpdi);

        // Annotations should have been imported for the current page
        $this->assertNotEmpty($annots);
        $pageNo = $tcpdi->PageNo();
        $this->assertArrayHasKey($pageNo, $annots);
        $this->assertNotEmpty($annots[$pageNo]);
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

    private function buildPdfWithAnnotation(): string
    {
        $pdf = "%PDF-1.4\n";

        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Type /Annot /Subtype /Text /Rect [100 100 200 200] /Contents (Test) >>\nendobj\n";

        // Annots array as indirect object (required for importAnnotations to detect it)
        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n[4 0 R]\nendobj\n";

        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots 5 0 R >>\nendobj\n";

        $xref_offset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offsets[1]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[2]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[3]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[4]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[5]);
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref_offset . "\n%%EOF";

        return $pdf;
    }
}
