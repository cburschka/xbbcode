<?php

namespace Drupal\xbbcode\Parser\Tree;

/**
 * Represent a rendered element in the parse tree.
 */
class OutputElement implements OutputElementInterface {

  /**
   * The output.
   *
   * @var string
   */
  private $text;

  /**
   * OutputElement constructor.
   *
   * @param string $text
   *   The output.
   */
  public function __construct($text) {
    $this->text = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return $this->text;
  }

}
