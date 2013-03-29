<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  $package= 'net.xp_framework.unittest.remote';

  uses(
    'util.cmd.Console',
    'util.log.Logger',
    'util.log.FileAppender',
    'peer.server.Server',
    'lang.archive.Archive',
    'lang.archive.ArchiveClassLoader',
    'remote.server.EascProtocol',
    'remote.server.deploy.scan.DeploymentScanner'
  );
  
  /**
   * EASC Server used by IntegrationTest. 
   *
   * Specifics
   * ~~~~~~~~~
   * <ul>
   *   <li>Server listens on a free port @ 127.0.0.1</li>
   *   <li>Deployment root is "deploye" subdirectory of this directory</li>
   *   <li>Server can be sending the protocol message #61</li>
   *   <li>On startup success, "+ Service (IP):(PORT)" is written to standard out</li>
   *   <li>On shutdown, "+ Done" is written to standard out</li>
   *   <li>On errors during any phase, "- " and the exception message are written</li>
   * </ul>
   *
   * @see   xp://net.xp_framework.unittest.remote.IntegrationTest
   */
  class net�xp_framework�unittest�remote�TestingServer extends Object {

    /**
     * Start server
     *
     * @param   string[] args
     */
    public static function main(array $args) {

      // Add shutdown message handler
      EascMessageFactory::setHandler(61, newinstance('remote.server.message.EascMessage', array(), '{
        public function getType() { 
          return 61; 
        }
        public function handle($protocol, $data) {
          Logger::getInstance()->getCategory()->debug("Shutting down");
          $protocol->server->terminate= TRUE; 
        }
      }')->getClass());
      
      $s= new Server('127.0.0.1', 0);
      try {
        $protocol= new EascProtocol(newinstance('remote.server.deploy.scan.DeploymentScanner', array(), '{
          private $changed= TRUE;

          public function scanDeployments() {
            $changed= $this->changed;
            $this->changed= FALSE;
            return $changed;
          }

          public function getDeployments() {
            $res= "net/xp_framework/unittest/remote/deploy/beans.test.CalculatorBean.xar";

            with ($d= new Deployment($res)); {
              $d->setClassLoader(new ArchiveClassLoader(new Archive(ClassLoader::getDefault()->getResourceAsStream($res))));
              $d->setImplementation("beans.test.CalculatorBeanImpl");
              $d->setInterface("beans.test.Calculator");
              $d->setDirectoryName("xp/test/Calculator");

              return array($d);
            }
          }
        }'));
        $protocol->initialize();

        $s->setProtocol($protocol);
        $s->init();
        Console::writeLinef('+ Service %s:%d', $s->socket->host, $s->socket->port);
        $s->service();
        Console::writeLine('+ Done');
      } catch (Throwable $e) {
        Console::writeLine('- ', $e->getMessage());
      }
    }
  }
?>
