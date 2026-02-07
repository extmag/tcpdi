<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TcpdiImageIntegrationTest extends TestCase
{
    private static string $sourcePdf;
    private static string $pngPath;
    private static string $gifPath;

    public static function setUpBeforeClass(): void
    {
        // Generate a single-page PDF via TCPDF
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Image integration test page', 0, 1);
        self::$sourcePdf = $pdf->Output('', 'S');

        // Create PNG 4x4 red
        $png = imagecreatetruecolor(4, 4);
        $red = imagecolorallocate($png, 255, 0, 0);
        imagefill($png, 0, 0, $red);
        self::$pngPath = tempnam(sys_get_temp_dir(), 'tcpdi_png_') . '.png';
        imagepng($png, self::$pngPath);
        imagedestroy($png);

        // Create GIF 4x4 blue
        $gif = imagecreatetruecolor(4, 4);
        $blue = imagecolorallocate($gif, 0, 0, 255);
        imagefill($gif, 0, 0, $blue);
        self::$gifPath = tempnam(sys_get_temp_dir(), 'tcpdi_gif_') . '.gif';
        imagegif($gif, self::$gifPath);
        imagedestroy($gif);
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$pngPath)) {
            unlink(self::$pngPath);
        }
        // Clean up the tempnam file without extension that was also created
        $pngBase = preg_replace('/\.png$/', '', self::$pngPath);
        if ($pngBase !== self::$pngPath && file_exists($pngBase)) {
            unlink($pngBase);
        }

        if (file_exists(self::$gifPath)) {
            unlink(self::$gifPath);
        }
        $gifBase = preg_replace('/\.gif$/', '', self::$gifPath);
        if ($gifBase !== self::$gifPath && file_exists($gifBase)) {
            unlink($gifBase);
        }
    }

    private function assertValidPdf(string $data, int $expectedPages): void
    {
        $this->assertStringStartsWith('%PDF-', $data, 'PDF must start with %PDF-');
        $this->assertStringContainsString('startxref', $data, 'PDF must contain startxref');
        $trimmed = rtrim($data);
        $this->assertStringEndsWith('%%EOF', $trimmed, 'PDF must end with %%EOF');

        $parser = new \tcpdi_parser($data, 'img-validation-' . uniqid());
        $this->assertSame($expectedPages, $parser->getPageCount(), "PDF should have $expectedPages page(s)");
    }

    // ================================================================
    // Tests
    // ================================================================

    public function testImportPageAndAddPngImage(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$sourcePdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $tcpdi->useTemplate($tplId);
        $tcpdi->Image(self::$pngPath, 10, 10, 20, 20, 'PNG');

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 1);
    }

    public function testImportPageAndAddGifImage(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$sourcePdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $tcpdi->useTemplate($tplId);
        $tcpdi->Image(self::$gifPath, 10, 10, 20, 20, 'GIF');

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 1);
    }

    public function testImportPageAndAddMultipleImages(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$sourcePdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $tcpdi->useTemplate($tplId);
        $tcpdi->Image(self::$pngPath, 10, 10, 20, 20, 'PNG');
        $tcpdi->Image(self::$gifPath, 40, 10, 20, 20, 'GIF');

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 1);
    }

    public function testOutputPdfStructuralValidity(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$sourcePdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $tcpdi->useTemplate($tplId);
        $tcpdi->Image(self::$pngPath, 10, 10, 20, 20, 'PNG');

        $output = $tcpdi->Output('', 'S');

        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertStringContainsString('startxref', $output);
        $this->assertStringContainsString('/XObject', $output);
        $trimmed = rtrim($output);
        $this->assertStringEndsWith('%%EOF', $trimmed);
    }

    public function testImageOnMultiplePages(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$sourcePdf);

        for ($i = 0; $i < 2; $i++) {
            $tcpdi->AddPage();
            $tplId = $tcpdi->importPage(1);
            $tcpdi->useTemplate($tplId);
            $tcpdi->Image(self::$pngPath, 10, 10, 20, 20, 'PNG');
        }

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 2);
    }

    public function testImageDoesNotCorruptTemplate(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$sourcePdf);

        // Page 1: template only, no image
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $tcpdi->useTemplate($tplId);

        // Page 2: template + image
        $tcpdi->AddPage();
        $tcpdi->useTemplate($tplId);
        $tcpdi->Image(self::$pngPath, 10, 10, 20, 20, 'PNG');

        $output = $tcpdi->Output('', 'S');
        $this->assertValidPdf($output, 2);
    }

    public function testOutputContainsImageResource(): void
    {
        $tcpdi = new \TCPDI();
        $tcpdi->setSourceData(self::$sourcePdf);
        $tcpdi->AddPage();
        $tplId = $tcpdi->importPage(1);
        $tcpdi->useTemplate($tplId);
        $tcpdi->Image(self::$pngPath, 10, 10, 20, 20, 'PNG');

        $output = $tcpdi->Output('', 'S');
        $this->assertStringContainsString('/Subtype /Image', $output);
    }
}
