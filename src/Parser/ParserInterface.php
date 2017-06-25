<?php

namespace Drupal\xbbcode\Parser;

/**
 * An interface for parsers.
 */
interface ParserInterface {

  /**
   * Parse a text and build an element tree.
   *
   * @param string $text
   *   The source text.
   *
   * @return \Drupal\xbbcode\Parser\Tree\NodeElementInterface
   *   The element representing the root of the tree.
   */
  public function parse($text);

}
