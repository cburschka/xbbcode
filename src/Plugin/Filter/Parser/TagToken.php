<?php
/**
 * Created by PhpStorm.
 * User: cburschk
 * Date: 11.11.2016
 * Time: 13:44
 */

namespace Drupal\xbbcode\Plugin\Filter\Parser;


class TagToken implements TokenInterface {
  private $open;
  private $name;
  private $extra;

  public function __construct($open, $name, $extra = '') {
    $this->open = (bool) $open;
    $this->name = (string) $name;
    $this->extra = $extra;
  }

  public function isOpen() {
    return $this->open;
  }

  public function getName() {
    return $this->name;
  }

  public function getElement() {
    return '[' . (!$this->open ? '/' : '') . $this->name . $this->extra . ']';
  }
}
