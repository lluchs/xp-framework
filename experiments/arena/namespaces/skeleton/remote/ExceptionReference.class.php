<?php
/* This class is part of the XP framework
 *
 * $Id: ExceptionReference.class.php 10977 2007-08-27 17:14:26Z friebe $ 
 */

  namespace remote;

  uses('lang.ChainedException');

  /**
   * Holds a reference to an exception
   *
   * @see      xp://remote.Serializer
   * @purpose  Exception reference
   */
  class ExceptionReference extends lang::ChainedException {
    public 
      $referencedClassname= '';

    /**
     * Constructor
     *
     * @param   string classname
     */
    public function __construct($classname) {
      parent::__construct('(null)', $cause= NULL);
      $this->referencedClassname= $classname;
    }
    
    /**
     * Return compound message of this exception.
     *
     * @return  string
     */
    public function compoundMessage() {
      return sprintf(
        'Exception %s<%s> (%s)',
        $this->getClassName(),
        $this->referencedClassname,
        $this->message
      );
    }
  }
?>
