<?php

namespace Drupal\xbbcode\Parser\Tree;

class OutputElement implements OutputElementInterface {

  /**
   * @var string
   */
  private $text;

  /**
   * OutputElement constructor.
   *
   * @param string $text
   */
  public function __construct($text) {
    $this->text = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->text;
  }

}
