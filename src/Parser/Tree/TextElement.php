<?php

namespace Drupal\xbbcode\Parser\Tree;

/**
 * An element representing a text fragment.
 */
class TextElement implements ElementInterface {

  /**
   * The text.
   *
   * @var string
   */
  protected $text;

  /**
   * TextElement constructor.
   *
   * @param string $text
   *   The text.
   */
  public function __construct($text) {
    $this->setText($text);
  }

  /**
   * @return string
   */
  public function getText(): string {
    return $this->text;
  }

  /**
   * @param string $text
   *
   * @return $this
   */
  public function setText($text): self {
    $this->text = $text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): string {
    return $this->getText();
  }

}
