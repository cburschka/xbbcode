<?php

namespace Drupal\xbbcode\Parser\Tree;

/**
 * An output element must be convertible to a string.
 */
interface OutputElementInterface {

  /**
   * Convert to string.
   *
   * @return string
   */
  public function __toString();

}
