<?php

namespace Drupal\xbbcode\Parser\Tree;

use Drupal\Core\Render\Markup;

/**
 * A node element contains other elements.
 */
abstract class NodeElement implements NodeElementInterface {

  /**
   * The children of this node.
   *
   * @var \Drupal\xbbcode\Parser\Tree\ElementInterface[]
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
   * {@inheritdoc}
   */
  public function append(ElementInterface $element) {
    $this->children[] = $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    if ($this->content === NULL) {
      $children = [];
      $rendered = [];
      foreach ($this->children as $child) {
        $children[] = $child->render();
        // If the child is also a node element, add its rendered tags.
        if ($child instanceof NodeElementInterface) {
          $rendered[] = $child->getRenderedTags();
        }
      }
      $this->renderedTags = array_merge($this->renderedTags, ...$rendered);
      $this->content = Markup::create(implode('', $children));
    }
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendants() {
    foreach ($this->children as $child) {
      yield $child;
      if ($child instanceof NodeElementInterface) {
        // TODO: PHP 7+ has yield from.
        foreach ($child->getDescendants() as $descendant) {
          yield $descendant;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
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
