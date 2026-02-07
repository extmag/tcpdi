<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TcpdiRequireTest extends TestCase
{
    /**
     * Bug 9: require_once should use __DIR__ for reliable path resolution
     */
    public function testRequireUsesDir(): void
    {
        $source = file_get_contents(__DIR__ . '/../tcpdi.php');

        // Should use __DIR__ based paths
        $this->assertStringContainsString("require_once(__DIR__ . '/fpdf_tpl.php')", $source);
        $this->assertStringContainsString("require_once(__DIR__ . '/tcpdi_parser.php')", $source);

        // Should NOT use bare relative paths
        $this->assertStringNotContainsString("require_once('fpdf_tpl.php')", $source);
        $this->assertStringNotContainsString("require_once('tcpdi_parser.php')", $source);
    }
}
