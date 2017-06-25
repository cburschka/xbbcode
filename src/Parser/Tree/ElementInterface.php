<?php

namespace Drupal\xbbcode\Parser\Tree;

/**
 * An element in the parser tree.
 */
interface ElementInterface {

  /**
   * Render this element to a string.
   *
   * @return string
   *   The rendered output.
   */
  public function render();

  /**
   * Prepare this element for rendering.
   *
   * The text returned here replaces the entire element.
   *
   * @return string
   *   The escaped string.
   */
  public function prepare();

}
