<?php

namespace Drupal\xbbcode\Parser;

use Drupal\Core\Render\Markup;

/**
 * A node element contains other elements.
 */
abstract class NodeElement implements ElementInterface {

  /**
   * The children of this node.
   *
   * @var \Drupal\xbbcode\Parser\ElementInterface[]
   */
  protected $children = [];

  /**
   * The rendered content of this node.
   *
   * @var string
   */
  protected $content;

  /**
   * The names of every rendered tag inside this element.
   *
   * @var string[]
   */
  protected $renderedTags = [];

  /**
   * Append an element to the children of this element.
   *
   * @param \Drupal\xbbcode\Parser\ElementInterface $element
   *   The new element.
   */
  public function append(ElementInterface $element) {
    $this->children[] = $element;
  }

  /**
   * Retrieve the rendered content of the element.
   *
   * @return string
   *   The rendered content.
   */
  public function getContent() {
    if ($this->content === NULL) {
      $children = [];
      $rendered = [];
      foreach ($this->children as $child) {
        $children[] = $child->render();
        // If the child is also a node element, add its rendered tags.
        if ($child instanceof self) {
          /** @var \Drupal\xbbcode\Parser\NodeElement $child */
          $rendered[] = $child->getRenderedTags();
        }
      }
      $this->renderedTags = array_merge($this->renderedTags, ...$rendered);
      $this->content = Markup::create(implode('', $children));
    }
    return $this->content;
  }

  /**
   * Get the set of tag names rendered.
   *
   * @return string[]
   *   The set of tags, indexed by tag name.
   */
  public function getRenderedTags() {
    // Ensure that the content has been rendered.
    $this->getContent();
    return $this->renderedTags;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    $output = [];
    foreach ($this->children as $child) {
      $output[] = $child->prepare();
    }
    return implode('', $output);
  }

}
