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
  /**
   * Construct an empty root element.
   *
   * This object serves only as a container for the tag tree.
   */
  public function __construct() {
    parent::__construct('', '', 0, 0, '', NULL);
  }

}
