<?php

namespace Drupal\xbbcode\Parser\Tree;

/**
 * An element in the parser tree.
 */
interface ElementInterface {

  /**
   * Render this element to a string.
   *
   * @return string|\Drupal\xbbcode\Parser\Tree\OutputElementInterface
   *   The rendered output.
   */
  public function render();

}
