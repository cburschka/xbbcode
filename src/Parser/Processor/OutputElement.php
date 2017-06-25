<?php

namespace Drupal\xbbcode\Parser\Processor;

use Drupal\xbbcode\Parser\Tree\OutputElementInterface;

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
