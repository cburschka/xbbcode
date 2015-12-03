<?php

/**
 * @file
 * Contains \Drupal\xbbcode\RootElement.
 */

namespace Drupal\xbbcode;

/**
 * The root element of the tag tree.
 */
class RootElement extends Element {
  function __construct() {
    parent::__construct();
    $this->offset = 0;
    $this->start = 0;
    $this->content = '';
  }
}