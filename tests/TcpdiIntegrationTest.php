<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TcpdiIntegrationTest extends TestCase
{
    private static string $singlePagePdf;
    private static string $multiPagePdf;
    private static string $annotatedPdf;
    private static string $rotated90Pdf;
    private static string $rotated180Pdf;
    private static string $rotated270Pdf;
    private static string $pageBoxesPdf;
    private static string $tempFilePath;

    public static function setUpBeforeClass(): void
    {
        // --- TCPDF-generated fixtures ---

        // Single page PDF
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Single page test content', 0, 1);
        self::$singlePagePdf = $pdf->Output('', 'S');

        // Multi page PDF (3 pages)
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        for ($i = 1; $i <= 3; $i++) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, "Page $i content", 0, 1);
        }
        self::$multiPagePdf = $pdf->Output('', 'S');

        // Annotated PDF
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Annotated page', 0, 1);
        $pdf->Annotation(10, 20, 50, 30, 'Test annotation', ['Subtype' => 'Text']);
        self::$annotatedPdf = $pdf->Output('', 'S');

        // --- Hand-crafted PDF fixtures ---

        // Rotated 90
        self::$rotated90Pdf = self::buildRotatedPdf(90);
        // Rotated 180
        self::$rotated180Pdf = self::buildRotatedPdf(180);
        // Rotated 270
        self::$rotated270Pdf = self::buildRotatedPdf(270);

        // Page boxes PDF (MediaBox + CropBox + TrimBox)
        self::$pageBoxesPdf = self::buildPageBoxesPdf();

        // Temp file for setSourceFile tests
        self::$tempFilePath = tempnam(sys_get_temp_dir(), 'tcpdi_test_');
        file_put_contents(self::$tempFilePath, self::$singlePagePdf);
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$tempFilePath)) {
            unlink(self::$tempFilePath);
        }
    }

    // ---- Helper: build rotated PDF ----
    private static function buildRotatedPdf(int $angle): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Rotate $angle /Resources << /ProcSet [/PDF] >> >>\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 4\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offsets[1]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[2]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[3]);
        $pdf .= "trailer\n<< /Size 4 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    // ---- Helper: build page boxes PDF ----
    private static function buildPageBoxesPdf(): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R"
            . " /MediaBox [0 0 612 792]"
            . " /CropBox [10 10 602 782]"
            . " /TrimBox [20 20 592 772]"
            . " /Resources << /ProcSet [/PDF] >>"
            . " >>\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 4\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offsets[1]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[2]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[3]);
        $pdf .= "trailer\n<< /Size 4 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    // ---- Helper: build PDF with annotation ----
    private static function buildPdfWithAnnotation(): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Type /Annot /Subtype /Text /Rect [100 100 200 200] /Contents (Test) >>\nendobj\n";

        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n[4 0 R]\nendobj\n";

        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots 5 0 R /Resources << /ProcSet [/PDF] >> >>\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offsets[1]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[2]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[3]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[4]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[5]);
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    // ---- Helper: build minimal PDF without CropBox ----
    private static function buildMinimalPdf(): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 4\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offsets[1]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[2]);
        $pdf .= sprintf("%010d 00000 n \n", $offsets[3]);
        $pdf .= "trailer\n<< /Size 4 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    // ---- Validation helper ----
    private function assertValidPdf(string $data, int $expectedPages): void
    {
        $this->assertStringStartsWith('%PDF-', $data, 'PDF must start with %PDF-');
        $this->assertStringContainsString('startxref', $data, 'PDF must contain startxref');
        $trimmed = rtrim($data);
        $this->assertStringEndsWith('%%EOF', $trimmed, 'PDF must end with %%EOF');

        // Parse with tcpdi_parser — should not throw
        $parser = new \tcpdi_parser($data, 'validation-' . uniqid());
        $this->assertSame($expectedPages, $parser->getPageCount(), "PDF should have $expectedPages page(s)");
    }

    // ================================================================
    // Import single-page PDF
    // ================================================================

    public function testImportSinglePagePdfPageCount(): void
    {
        $tcpdi = new \TCPDI();
        $pageCount = $tcpdi->setSourceData(self::$singlePagePdf);
        $this->assertSame(1, $pageCount);

        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $this->assertIsInt($tplId);
        $this->assertGreaterThan(0, $tplId);
    }

    public function testImportSinglePagePdfOutput(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$singlePagePdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $tcpdi->useTemplate($tplId);

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 1);
    }

    // ================================================================
    // Import multi-page PDF
    // ================================================================

    public function testImportMultiPagePdfPageCount(): void
    {
        $tcpdi = new \TCPDI();
        $pageCount = $tcpdi->setSourceData(self::$multiPagePdf);
        $this->assertSame(3, $pageCount);
    }

    public function testImportMultiPageAllPages(): void
    {
        $tcpdi = new \TCPDI();
        $pageCount = $tcpdi->setSourceData(self::$multiPagePdf);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tcpdi->AddPage();
            $tplId = $tcpdi->importPage($i);
            $tcpdi->useTemplate($tplId);
        }

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 3);
    }

    public function testImportMultiPageSelectivePage(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$multiPagePdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(2);
        $tcpdi->useTemplate($tplId);

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 1);
    }

    // ================================================================
    // Annotations
    // ================================================================

    public function testImportAnnotations(): void
    {
        // Use a hand-crafted PDF with an annotation structure the parser can detect
        $pdf = self::buildPdfWithAnnotation();

        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData($pdf);
        $tcpdi->AddPage();
        $tcpdi->importPage(1);
        $tcpdi->importAnnotations(1);

        $ref = new \ReflectionProperty(\TCPDI::class, '_importedAnnots');
        $ref->setAccessible(true);
        $annots = $ref->getValue($tcpdi);

        $this->assertNotEmpty($annots);
        $pageNo = $tcpdi->PageNo();
        $this->assertArrayHasKey($pageNo, $annots);
        $this->assertNotEmpty($annots[$pageNo]);
    }

    public function testImportAnnotationsOutputValid(): void
    {
        $pdf = self::buildPdfWithAnnotation();

        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData($pdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $tcpdi->useTemplate($tplId);
        $tcpdi->importAnnotations(1);

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 1);
    }

    // ================================================================
    // Rotation (hand-crafted PDFs with /Rotate)
    // ================================================================

    public function testImportRotated90Page(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$rotated90Pdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1, '/MediaBox');

        $tplsRef = new \ReflectionProperty(\FPDF_TPL::class, 'tpls');
        $tplsRef->setAccessible(true);
        $tpls = $tplsRef->getValue($tcpdi);
        $tpl = $tpls[$tplId];

        // 90 degree rotation swaps w/h
        // Original MediaBox: 612x792 (in points). After /k conversion these are in user units.
        // w and h should be swapped
        $this->assertSame(-90, $tpl['_rotationAngle']);
        // 90° rotation of portrait page (612x792) swaps w/h, so w becomes > h
        $this->assertGreaterThan($tpl['h'], $tpl['w'], 'For 90deg rotation of portrait page, w should become > h');
    }

    public function testImportRotated180Page(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$rotated180Pdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1, '/MediaBox');

        $tplsRef = new \ReflectionProperty(\FPDF_TPL::class, 'tpls');
        $tplsRef->setAccessible(true);
        $tpls = $tplsRef->getValue($tcpdi);
        $tpl = $tpls[$tplId];

        $this->assertSame(-180, $tpl['_rotationAngle']);
        // 180 degree rotation does NOT swap w/h
        $this->assertLessThan($tpl['h'], $tpl['w'], 'For 180deg rotation, w < h (portrait stays portrait)');
    }

    public function testImportRotated270Page(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$rotated270Pdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1, '/MediaBox');

        $tplsRef = new \ReflectionProperty(\FPDF_TPL::class, 'tpls');
        $tplsRef->setAccessible(true);
        $tpls = $tplsRef->getValue($tcpdi);
        $tpl = $tpls[$tplId];

        $this->assertSame(-270, $tpl['_rotationAngle']);
        // 270° rotation of portrait page swaps w/h (same as 90), so w becomes > h
        $this->assertGreaterThan($tpl['h'], $tpl['w'], 'For 270deg rotation of portrait page, w should become > h');
    }

    public function testImportRotatedPagesOutput(): void
    {
        $tcpdi = new \TCPDI();

        // Page 1: 90 deg
        $tcpdi->setSourceData(self::$rotated90Pdf);
        $tcpdi->AddPage();
        $tpl1 = $tcpdi->importPage(1, '/MediaBox');
        $tcpdi->useTemplate($tpl1);

        // Page 2: 180 deg
        $tcpdi->setSourceData(self::$rotated180Pdf);
        $tcpdi->AddPage();
        $tpl2 = $tcpdi->importPage(1, '/MediaBox');
        $tcpdi->useTemplate($tpl2);

        // Page 3: 270 deg
        $tcpdi->setSourceData(self::$rotated270Pdf);
        $tcpdi->AddPage();
        $tpl3 = $tcpdi->importPage(1, '/MediaBox');
        $tcpdi->useTemplate($tpl3);

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 3);
    }

    // ================================================================
    // Page Boxes
    // ================================================================

    public function testImportWithMediaBox(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$pageBoxesPdf);
        $tcpdi->AddPage();
        $tcpdi->importPage(1, '/MediaBox');

        $this->assertSame('/MediaBox', $tcpdi->getLastUsedPageBox());
    }

    public function testImportWithCropBox(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$pageBoxesPdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1, '/CropBox');

        $this->assertSame('/CropBox', $tcpdi->getLastUsedPageBox());

        // Verify CropBox dimensions correspond to [10 10 602 782]
        $size = $tcpdi->getTemplateSize($tplId);
        // CropBox width = 602-10 = 592 points, height = 782-10 = 772 points
        // Default unit is mm, k = 72/25.4
        $k = 72 / 25.4;
        $expectedW = 592 / $k;
        $expectedH = 772 / $k;
        $this->assertEqualsWithDelta($expectedW, $size['w'], 0.1);
        $this->assertEqualsWithDelta($expectedH, $size['h'], 0.1);
    }

    public function testImportWithTrimBox(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$pageBoxesPdf);
        $tcpdi->AddPage();
        $tcpdi->importPage(1, '/TrimBox');

        $this->assertSame('/TrimBox', $tcpdi->getLastUsedPageBox());
    }

    public function testCropBoxFallbackToMediaBox(): void
    {
        // Use minimal PDF that has only MediaBox, no CropBox
        $pdf = self::buildMinimalPdf();
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData($pdf);
        $tcpdi->AddPage();
        $tcpdi->importPage(1, '/CropBox');

        $this->assertSame('/MediaBox', $tcpdi->getLastUsedPageBox());
    }

    // ================================================================
    // Caching
    // ================================================================

    public function testImportSamePageTwiceReturnsSameId(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$singlePagePdf);
        $tcpdi->AddPage();

        $tplId1 = $tcpdi->importPage(1);
        $tplId2 = $tcpdi->importPage(1);

        $this->assertSame($tplId1, $tplId2);
    }

    public function testImportSamePageDifferentBoxReturnsDifferentId(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$pageBoxesPdf);
        $tcpdi->AddPage();

        $tplMedia = $tcpdi->importPage(1, '/MediaBox');
        $tplCrop = $tcpdi->importPage(1, '/CropBox');

        $this->assertNotSame($tplMedia, $tplCrop);
    }

    // ================================================================
    // setSourceFile vs setSourceData
    // ================================================================

    public function testSetSourceFilePageCount(): void
    {
        $tcpdi = new \TCPDI();
        $pageCount = $tcpdi->setSourceFile(self::$tempFilePath);
        $this->assertSame(1, $pageCount);
    }

    public function testSetSourceDataVsSetSourceFile(): void
    {
        // Load via setSourceData
        $tcpdi1 = new \TCPDI();
        $tcpdi1->setSourceData(self::$singlePagePdf);
        $tcpdi1->AddPage();
        $tpl1 = $tcpdi1->importPage(1);
        $size1 = $tcpdi1->getTemplateSize($tpl1);

        // Load via setSourceFile
        $tcpdi2 = new \TCPDI();
        $tcpdi2->setSourceFile(self::$tempFilePath);
        $tcpdi2->AddPage();
        $tpl2 = $tcpdi2->importPage(1);
        $size2 = $tcpdi2->getTemplateSize($tpl2);

        $this->assertEqualsWithDelta($size1['w'], $size2['w'], 0.01);
        $this->assertEqualsWithDelta($size1['h'], $size2['h'], 0.01);
    }

    // ================================================================
    // Miscellaneous
    // ================================================================

    public function testUseTemplateAdjustPageSize(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$singlePagePdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);

        $tplSize = $tcpdi->getTemplateSize($tplId);
        $tcpdi->useTemplate($tplId, null, null, 0, 0, true);

        // After adjustPageSize, page dimensions should match template
        $pageW = $tcpdi->getPageWidth();
        $pageH = $tcpdi->getPageHeight();
        $this->assertEqualsWithDelta($tplSize['w'], $pageW, 0.5);
        $this->assertEqualsWithDelta($tplSize['h'], $pageH, 0.5);
    }

    public function testMultipleSourceFiles(): void
    {
        // Create a second temp file with multi-page PDF
        $tempFile2 = tempnam(sys_get_temp_dir(), 'tcpdi_test2_');
        file_put_contents($tempFile2, self::$multiPagePdf);

        try {
            $tcpdi = new \TCPDI();

            // Import page from first file
            $tcpdi->setSourceFile(self::$tempFilePath);
            $tcpdi->AddPage();
            $tpl1 = $tcpdi->importPage(1);
            $tcpdi->useTemplate($tpl1);

            // Import page from second file
            $tcpdi->setSourceFile($tempFile2);
            $tcpdi->AddPage();
            $tpl2 = $tcpdi->importPage(1);
            $tcpdi->useTemplate($tpl2);

            $output = $tcpdi->Output('', 'S');
            $this->assertValidPdf($output, 2);
        } finally {
            unlink($tempFile2);
        }
    }
}
