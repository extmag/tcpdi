<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TcpdiUnescapeTest extends TestCase
{
    private \TCPDI $tcpdi;

    protected function setUp(): void
    {
        $this->tcpdi = new \TCPDI();
    }

    private function unescape(string $s): string
    {
        $method = new \ReflectionMethod(\TCPDI::class, '_unescape');
        $method->setAccessible(true);
        return $method->invoke($this->tcpdi, $s);
    }

    public function testSimpleEscapes(): void
    {
        $this->assertSame('(', $this->unescape('\\('));
        $this->assertSame(')', $this->unescape('\\)'));
        $this->assertSame('\\', $this->unescape('\\\\'));
        $this->assertSame("\n", $this->unescape('\\n'));
        $this->assertSame("\r", $this->unescape('\\r'));
        $this->assertSame("\t", $this->unescape('\\t'));
    }

    public function testOctalInMiddleOfString(): void
    {
        // \101 = 'A' (octal 101 = decimal 65)
        $this->assertSame('xAy', $this->unescape('x\\101y'));
    }

    /**
     * Bug 7: Octal at end of string should not cause out-of-bounds access
     */
    public function testOctalAtEndOfString(): void
    {
        // \101 = 'A' at the very end
        $result = $this->unescape('x\\101');
        $this->assertSame('xA', $result);
    }

    /**
     * Bug 7: Single octal digit at end of string
     */
    public function testSingleOctalDigitAtEnd(): void
    {
        // \1 at end of string
        $result = $this->unescape('x\\1');
        $this->assertSame('x' . chr(1), $result);
    }

    /**
     * Bug 7: Two octal digits at end of string
     */
    public function testTwoOctalDigitsAtEnd(): void
    {
        // \12 at end of string (octal 12 = decimal 10 = newline)
        $result = $this->unescape('x\\12');
        $this->assertSame("x\n", $result);
    }

    /**
     * Bug 7: Trailing backslash should be output as-is
     */
    public function testTrailingBackslash(): void
    {
        $result = $this->unescape('hello\\');
        $this->assertSame('hello\\', $result);
    }

    public function testNoEscapes(): void
    {
        $this->assertSame('hello world', $this->unescape('hello world'));
    }

    public function testEmptyString(): void
    {
        $this->assertSame('', $this->unescape(''));
    }
}
