<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'unittest.TestCase',
    'io.streams.TextReader',
    'io.streams.MemoryInputStream'
  );

  /**
   * TestCase
   *
   * @see      xp://io.streams.TextReader
   */
  class TextReaderTest extends TestCase {
  
    /**
     * Returns a text reader for a given input string.
     *
     * @param   string str
     * @param   string charset
     * @return  io.streams.TextReader
     */
    protected function newReader($str, $charset= 'iso-8859-1') {
      return new TextReader(new MemoryInputStream($str), $charset);
    }

    /**
     * Test reading
     *
     */
    #[@test]
    public function readOne() {
      $this->assertEquals('H', $this->newReader('Hello')->read(1));
    }

    /**
     * Test reading
     *
     */
    #[@test]
    public function readOneUtf8() {
      $this->assertEquals('�', $this->newReader('Übercoder', 'utf-8')->read(1));
    }

    /**
     * Test reading
     *
     */
    #[@test]
    public function readLength() {
      $this->assertEquals('Hello', $this->newReader('Hello')->read(5));
    }

    /**
     * Test reading
     *
     */
    #[@test]
    public function readLengthUtf8() {
      $this->assertEquals('�bercoder', $this->newReader('Übercoder', 'utf-8')->read(9));
    }

    /**
     * Test reading
     *
     */
    #[@test, @expect('lang.FormatException')]
    public function readBrokenUtf8() {
      $this->newReader('Hello �|', 'utf-8')->read(0x1000);
    }

    /**
     * Test reading
     *
     */
    #[@test, @expect('lang.FormatException')]
    public function readMalformedUtf8() {
      $this->newReader('Hello �bercoder', 'utf-8')->read(0x1000);
    }

    /**
     * Test reading
     *
     */
    #[@test]
    public function readingDoesNotContinueAfterBrokenCharacters() {
      $r= $this->newReader("Hello �bercoder\n".str_repeat('*', 512), 'utf-8');
      try {
        $r->read(1);
        $this->fail('No exception caught', NULL, 'lang.FormatException');
      } catch (FormatException $expected) {
        // OK
      }
      $this->assertNull($r->read(512));
    }

    /**
     * Test reading "'�:ina" which contains two characters not convertible
     * to iso-8859-1, our internal encoding.
     *
     * @see     http://de.wikipedia.org/wiki/China (the word in the first square brackets on this page).
     */
    #[@test, @expect('lang.FormatException')]
    public function readUnconvertible() {
      $this->newReader('ˈçiːna', 'utf-8')->read();
    }

    /**
     * Test reading
     *
     */
    #[@test]
    public function read() {
      $this->assertEquals('Hello', $this->newReader('Hello')->read());
    }

    /**
     * Test reading a source returning encoded bytes only (no US-ASCII inbetween!)
     *
     */
    #[@test]
    public function encodedBytesOnly() {
      $this->assertEquals(
        str_repeat('�', 1024), 
        $this->newReader(str_repeat('Ü', 1024), 'utf-8')->read(1024)
      );
    }

    /**
     * Test reading after EOF
     *
     */
    #[@test]
    public function readAfterEnd() {
      $r= $this->newReader('Hello');
      $this->assertEquals('Hello', $r->read(5));
      $this->assertNull($r->read());
    }

    /**
     * Test reading after EOF
     *
     */
    #[@test]
    public function readMultipleAfterEnd() {
      $r= $this->newReader('Hello');
      $this->assertEquals('Hello', $r->read(5));
      $this->assertNull($r->read());
      $this->assertNull($r->read());
    }

    /**
     * Test reading after EOF
     *
     */
    #[@test]
    public function readLineAfterEnd() {
      $r= $this->newReader('Hello');
      $this->assertEquals('Hello', $r->read(5));
      $this->assertNull($r->readLine());
    }

    /**
     * Test reading after EOF
     *
     */
    #[@test]
    public function readLineMultipleAfterEnd() {
      $r= $this->newReader('Hello');
      $this->assertEquals('Hello', $r->read(5));
      $this->assertNull($r->readLine());
      $this->assertNull($r->readLine());
    }

    /**
     * Test reading
     *
     */
    #[@test]
    public function readZero() {
      $this->assertEquals('', $this->newReader('Hello')->read(0));
    }
        
    /**
     * Test reading lines separated by "\n"
     *
     */
    #[@test]
    public function readLinesSeparatedByLineFeed() {
      $r= $this->newReader("Hello\nWorld");
      $this->assertEquals('Hello', $r->readLine());
      $this->assertEquals('World', $r->readLine());
      $this->assertNull($r->readLine());
    }
        
    /**
     * Test reading lines separated by "\r"
     *
     */
    #[@test]
    public function readLinesSeparatedByCarriageReturn() {
      $r= $this->newReader("Hello\rWorld");
      $this->assertEquals('Hello', $r->readLine());
      $this->assertEquals('World', $r->readLine());
      $this->assertNull($r->readLine());
    }
        
    /**
     * Test reading lines separated by "\r\n"
     *
     */
    #[@test]
    public function readLinesSeparatedByCRLF() {
      $r= $this->newReader("Hello\r\nWorld");
      $this->assertEquals('Hello', $r->readLine());
      $this->assertEquals('World', $r->readLine());
      $this->assertNull($r->readLine());
    }

    /**
     * Test reading an empty line
     *
     */
    #[@test]
    public function readEmptyLine() {
      $r= $this->newReader("Hello\n\nWorld");
      $this->assertEquals('Hello', $r->readLine());
      $this->assertEquals('', $r->readLine());
      $this->assertEquals('World', $r->readLine());
      $this->assertNull($r->readLine());
    }

    /**
     * Test reading lines
     *
     */
    #[@test]
    public function readLinesUtf8() {
      $r= $this->newReader("Über\nCoder", 'utf-8');
      $this->assertEquals('�ber', $r->readLine());
      $this->assertEquals('Coder', $r->readLine());
      $this->assertNull($r->readLine());
    }
    
    /**
     * Test reading lines w/ autodetected encoding at iso-8859-1
     *
     */
    #[@test]
    public function readLinesAutodetectIso88591() {
      $r= $this->newReader('�bercoder', NULL);
      $this->assertEquals('�bercoder', $r->readLine());
    }
    
    /**
     * Test reading from an encoding-autodetected stream when length of
     * data does is insufficient for autodetection.
     *
     */
    #[@test]
    public function readShortLinesAutodetectIso88591() {
      $r= $this->newReader('�', NULL);
      $this->assertEquals('�', $r->readLine());
    }
    
    
    /**
     * Test reading lines w/ autodetected encoding at utf-8
     *
     */
    #[@test]
    public function readLinesAutodetectUtf8() {
      $r= $this->newReader("\357\273\277\303\234bercoder", NULL);
      $this->assertEquals('�bercoder', $r->readLine());
    }

    /**
     * Test reading lines w/ autodetected encoding at utf-8
     *
     */
    #[@test]
    public function autodetectUtf8() {
      $r= $this->newReader("\357\273\277\303\234bercoder", NULL);
      $this->assertEquals('utf-8', $r->charset());
    }

    /**
     * Test reading lines w/ autodetected encoding at utf-16be
     *
     */
    #[@test]
    public function readLinesAutodetectUtf16BE() {
      $r= $this->newReader("\376\377\000\334\000b\000e\000r\000c\000o\000d\000e\000r", NULL);
      $this->assertEquals('�bercoder', $r->readLine());
    }

    /**
     * Test reading lines w/ autodetected encoding at utf-16be
     *
     */
    #[@test]
    public function autodetectUtf16Be() {
      $r= $this->newReader("\376\377\000\334\000b\000e\000r\000c\000o\000d\000e\000r", NULL);
      $this->assertEquals('utf-16be', $r->charset());
    }
    
    /**
     * Test reading lines w/ autodetected encoding at utf-16le
     *
     */
    #[@test]
    public function readLinesAutodetectUtf16Le() {
      $r= $this->newReader("\377\376\334\000b\000e\000r\000c\000o\000d\000e\000r\000", NULL);
      $this->assertEquals('�bercoder', $r->readLine());
    }

    /**
     * Test reading lines w/ autodetected encoding at utf-16le
     *
     */
    #[@test]
    public function autodetectUtf16Le() {
      $r= $this->newReader("\377\376\334\000b\000e\000r\000c\000o\000d\000e\000r\000", NULL);
      $this->assertEquals('utf-16le', $r->charset());
    }

    /**
     * Test reading lines w/ autodetected encoding at iso-8859-1
     *
     */
    #[@test]
    public function defaultCharsetIsIso88591() {
      $r= $this->newReader('�bercoder', NULL);
      $this->assertEquals('iso-8859-1', $r->charset());
    }

    /**
     * Test reading
     *
     */
    #[@test]
    public function bufferProblem() {
      $r= $this->newReader("Hello\rX");
      $this->assertEquals('Hello', $r->readLine());
      $this->assertEquals('X', $r->readLine());
      $this->assertNull($r->readLine());
    }

    /**
     * Test closing a reader twice has no effect.
     *
     * @see   xp://lang.Closeable#close
     */
    #[@test]
    public function closingTwice() {
      $r= $this->newReader('');
      $r->close();
      $r->close();
    }

    /**
     * Test resetting a reader
     *
     */
    #[@test]
    public function reset() {
      $r= $this->newReader('ABC');
      $this->assertEquals('ABC', $r->read(3));
      $r->reset();
      $this->assertEquals('ABC', $r->read(3));

    }
    /**
     * Test resetting a reader
     *
     */
    #[@test]
    public function resetWithBuffer() {
      $r= $this->newReader("Line 1\rLine 2");
      $this->assertEquals('Line 1', $r->readLine());    // We have "\n" in the buffer
      $r->reset();
      $this->assertEquals('Line 1', $r->readLine());
      $this->assertEquals('Line 2', $r->readLine());
    }

    /**
     * Test resetting a reader
     *
     */
    #[@test]
    public function resetUtf8() {
      $r= $this->newReader("\357\273\277ABC", NULL);
      $this->assertEquals('ABC', $r->read(3));
      $r->reset();
      $this->assertEquals('ABC', $r->read(3));
    }

    /**
     * Test resetting a reader
     *
     */
    #[@test]
    public function resetUtf8WithoutBOM() {
      $r= $this->newReader('ABC', 'utf-8');
      $this->assertEquals('ABC', $r->read(3));
      $r->reset();
      $this->assertEquals('ABC', $r->read(3));
    }

    /**
     * Test resetting a reader
     *
     */
    #[@test]
    public function resetUtf16Le() {
      $r= $this->newReader("\377\376A\000B\000C\000", NULL);
      $this->assertEquals('ABC', $r->read(3));
      $r->reset();
      $this->assertEquals('ABC', $r->read(3));
    }

    /**
     * Test resetting a reader
     *
     */
    #[@test]
    public function resetUtf16LeWithoutBOM() {
      $r= $this->newReader("A\000B\000C\000", 'utf-16le');
      $this->assertEquals('ABC', $r->read(3));
      $r->reset();
      $this->assertEquals('ABC', $r->read(3));
    }

    /**
     * Test resetting a reader
     *
     */
    #[@test]
    public function resetUtf16Be() {
      $r= $this->newReader("\376\377\000A\000B\000C", NULL);
      $this->assertEquals('ABC', $r->read(3));
      $r->reset();
      $this->assertEquals('ABC', $r->read(3));
    }

    /**
     * Test resetting a reader
     *
     */
    #[@test]
    public function resetUtf16BeWithoutBOM() {
      $r= $this->newReader("\000A\000B\000C", 'utf-16be');
      $this->assertEquals('ABC', $r->read(3));
      $r->reset();
      $this->assertEquals('ABC', $r->read(3));
    }

    /**
     * Test resetting a reader
     *
     */
    #[@test, @expect(class= 'io.IOException', withMessage= 'Underlying stream does not support seeking')]
    public function resetUnseekable() {
      $r= new TextReader(newinstance('io.streams.InputStream', array(), '{
        public function read($size= 8192) { return NULL; }
        public function available() { return 0; }
        public function close() { }
      }'));
      $r->reset();
    }
  }
?>
