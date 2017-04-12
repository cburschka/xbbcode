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
   * @param bool $prepared
   *   Whether the text has already been parsed and escaped before.
   *
   * @return \Drupal\xbbcode\Parser\ElementInterface
   *   The element representing the root of the tree.
   */
  public function parse($text, $prepared = FALSE);

}
