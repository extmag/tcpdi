TCPDI
=====

PDF importer for [TCPDF](http://www.tcpdf.org/), based on [FPDI](http://www.setasign.de/products/pdf-php-solutions/fpdi/).
Includes [tcpdi_parser](https://github.com/pauln/tcpdi_parser) and [FPDF_TPL](http://www.setasign.de/products/pdf-php-solutions/fpdi/downloads/).

Requirements
------------

- PHP >= 7.4 (compatible with PHP 8.0 — 8.5)
- TCPDF ^6.2.1

Installation
------------

```bash
composer require faradey/tcpdi
```

Usage
-----

Usage is essentially the same as FPDI, except importing TCPDI rather than FPDI. It also has a `setSourceData()` function which accepts raw PDF data, for cases where the file does not reside on disk or is not readable by TCPDI.

```php
// Create new PDF document.
$pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Add a page from a PDF by file path.
$pdf->AddPage();
$pdf->setSourceFile('/path/to/file-to-import.pdf');
$idx = $pdf->importPage(1);
$pdf->useTemplate($idx);

// Or load from raw PDF data.
$pdfdata = file_get_contents('/path/to/other-file.pdf');
$pagecount = $pdf->setSourceData($pdfdata);
for ($i = 1; $i <= $pagecount; $i++) {
    $tplidx = $pdf->importPage($i);
    $pdf->AddPage();
    $pdf->useTemplate($tplidx);
}
```

### Annotations

TCPDI includes functionality for handling PDF annotations. As annotations are positioned relative to the bleed box rather than the crop box, you'll need to import the full bleed box. A `setPageFormatFromTemplatePage()` function sets the page format from the imported page:

```php
$pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->setSourceFile('/path/to/file-to-import.pdf');

// Import the bleed box (default is crop box) for page 1.
$tplidx = $pdf->importPage(1, '/BleedBox');
$size = $pdf->getTemplateSize($tplidx);
$orientation = ($size['w'] > $size['h']) ? 'L' : 'P';

$pdf->AddPage($orientation);
$pdf->setPageFormatFromTemplatePage(1, $orientation);
$pdf->useTemplate($tplidx);
$pdf->importAnnotations(1);
```

### Page size adjustment

Use `adjustPageSize` to automatically match the output page size to the imported template:

```php
$pdf = new TCPDI();
$pdf->setSourceFile('/path/to/file.pdf');
$pdf->AddPage();
$tplidx = $pdf->importPage(1);
$pdf->useTemplate($tplidx, null, null, 0, 0, true);
```

### Adding images on imported pages

You can overlay images (PNG, GIF, JPEG) on imported pages using TCPDF's `Image()` method:

```php
$pdf = new TCPDI();
$pdf->setSourceData($pdfdata);
$pdf->AddPage();
$tplidx = $pdf->importPage(1);
$pdf->useTemplate($tplidx);
$pdf->Image('/path/to/image.png', 10, 10, 50, 50, 'PNG');
$pdf->Output('/path/to/output.pdf', 'F');
```

Error handling
--------------

Parser errors throw `\RuntimeException` instead of calling `die()`:

```php
try {
    $pdf = new TCPDI();
    $pdf->setSourceData($pdfdata);
    $tplidx = $pdf->importPage(1);
} catch (\RuntimeException $e) {
    // Handle invalid/corrupted PDF
    echo $e->getMessage();
}
```

Testing
-------

```bash
composer install
./vendor/bin/phpunit
```

TCPDI_PARSER
============

Parser for use with TCPDI, based on TCPDF_PARSER. Supports PDFs up to v1.7.
