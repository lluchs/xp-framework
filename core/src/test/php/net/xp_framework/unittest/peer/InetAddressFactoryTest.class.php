<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  uses(
    'unittest.TestCase',
    'peer.InetAddressFactory'
  );

  /**
   * Test class
   *
   * 
   */
  class InetAddressFactoryTest extends TestCase {
    protected
      $cut  = NULL;

    /**
     * SetUp
     *
     */
    public function setUp() {
      $this->cut= new InetAddressFactory();
    }

    /**
     * Parse 127.0.0.1
     *
     */
    #[@test]
    public function createLocalhostV4() {
      $this->assertInstanceOf('peer.Inet4Address', $this->cut->parse('127.0.0.1'));
    }

    /**
     * Parse invalid address that matches a valid one
     *
     */
    #[@test, @expect('lang.FormatException')]
    public function parseInvalidAddressThatLooksLikeV4() {
      $this->cut->parse('3.33.333.333');
    }

    /**
     * Parse invalid address that matches a valid one
     *
     */
    #[@test, @expect('lang.FormatException')]
    public function parseInvalidAddressThatAlsoLooksLikeV4() {
      $this->cut->parse('10..3.3');
    }    

    /**
     * Parse localhost address
     * 
     */
    #[@test]
    public function parseLocalhostV6() {
      $this->assertInstanceOf('peer.Inet6Address', $this->cut->parse('::1'));
    }

    /**
     * Parse address
     *
     */
    #[@test]
    public function parseV6() {
      $this->assertInstanceOf('peer.Inet6Address', $this->cut->parse('fe80::a6ba:dbff:fefe:7755'));
    }

    /**
     * Parse address
     *
     */
    #[@test, @expect('lang.FormatException')]
    public function parseThatLooksLikeV6() {
      $this->cut->parse('::ffffff:::::a');
    }
  }
?>