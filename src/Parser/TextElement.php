<?php

namespace Drupal\xbbcode\Parser;

/**
 * An element representing a text fragment.
 */
class TextElement implements ElementInterface {

  /**
   * The text.
   *
   * @var string
   */
  private $text;

  /**
   * TextElement constructor.
   *
   * @param string $text
   *   The text.
   */
  public function __construct($text) {
    $this->text = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return $this->text;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    return $this->text;
  }

}
