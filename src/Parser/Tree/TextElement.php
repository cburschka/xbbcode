<?php

namespace Drupal\xbbcode\Parser\Tree;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;

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
    // Escape unsafe markup.
    if (!($this->text instanceof MarkupInterface)) {
      // Be permissive; restricting HTML should be up to the format settings.
      $this->text = Markup::create(Xss::filterAdmin($this->text));
    }
    return $this->text;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    return $this->text;
  }

}
