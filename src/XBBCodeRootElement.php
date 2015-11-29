<?php

namespace Drupal\xbbcode;

/**
 * @file
 * Contains \Drupal\xbbcode\XBBCodeRootElement.
 */

/**
 * The root element of the tag tree.
 */
class XBBCodeRootElement extends XBBCodeTagMatch {
  function __construct() {
    parent::__construct();
    $this->offset = 0;
    $this->start = 0;
    $this->content = '';
  }
}