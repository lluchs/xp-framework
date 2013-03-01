<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  $package= 'net.xp_framework.unittest.peer.ftp';

  uses(
    'util.cmd.Console',
    'util.log.Logger',
    'util.log.FileAppender',
    'peer.server.Server',
    'peer.ftp.server.FtpProtocol',
    'net.xp_framework.unittest.peer.ftp.TestingStorage'
  );
  
  /**
   * FTP Server used by IntegrationTest. 
   *
   * Specifics
   * ~~~~~~~~~
   * <ul>
   *   <li>Server listens on a free port @ 127.0.0.1</li>
   *   <li>Authentication requires "test" / "test" as credentials</li>
   *   <li>Storage is inside an "ftproot" subdirectory of this directory</li>
   *   <li>Server can be shut down by issuing the "SHUTDOWN" command</li>
   *   <li>On startup success, "+ Service (IP):(PORT)" is written to standard out</li>
   *   <li>On shutdown, "+ Done" is written to standard out</li>
   *   <li>On errors during any phase, "- " and the exception message are written</li>
   * </ul>
   *
   * @see   xp://net.xp_framework.unittest.peer.ftp.IntegrationTest
   */
  class net�xp_framework�unittest�peer�ftp�TestingServer extends Object {
    const FTPROOT= 'net.xp_framework.unittest.peer.ftp.ftproot';

    /**
     * Start server
     *
     * @param   string[] args
     */
    public static function main(array $args) {
      $stor= new TestingStorage();
      $stor->entries['/']= new TestingCollection('/', $stor);
      $stor->entries['/.trash']= new TestingCollection('/.trash', $stor);
      $stor->entries['/.trash/do-not-remove.txt']= new TestingElement('/.trash/do-not-remove.txt', $stor);
      $stor->entries['/htdocs']= new TestingCollection('/htdocs', $stor);
      $stor->entries['/htdocs/file with whitespaces.html']= new TestingElement('/htdocs/file with whitespaces.html', $stor);
      $stor->entries['/htdocs/index.html']= new TestingElement('/htdocs/index.html', $stor, "<html/>\n");
      $stor->entries['/outer']= new TestingCollection('/outer', $stor);
      $stor->entries['/outer/inner']= new TestingCollection('/outer/inner', $stor);
      $stor->entries['/outer/inner/index.html']= new TestingElement('/outer/inner/index.html', $stor);

      $auth= newinstance('lang.Object', array(), '{
        public function authenticate($user, $password) {
          return ("testtest" == $user.$password);
        }
      }');

      $protocol= newinstance('peer.ftp.server.FtpProtocol', array($stor, $auth), '{
        public function onShutdown($socket, $params) {
          $this->answer($socket, 200, "Shutting down");
          $this->server->terminate= TRUE;
        }
      }');

      $args[0]= 'C:\cygwin\home\friebe\devel\xp.thekid.core\debug';
      isset($args[0]) && $protocol->setTrace(Logger::getInstance()
        ->getCategory()
        ->withAppender(new FileAppender($args[0]))
      );
      
      $s= new Server('127.0.0.1', 0);
      try {
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
