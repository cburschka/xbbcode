<?php

namespace Drupal\xbbcode\Parser\Tree;

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
   * The rendered children of this node.
   *
   * @var \Drupal\xbbcode\Parser\Tree\OutputElementInterface[]
   */
  protected $output;

  /**
   * {@inheritdoc}
   */
  public function append(ElementInterface $element) {
    $this->children[] = $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren() {
    return $this->children;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return implode('', $this->getRenderedChildren());
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedChildren() {
    if ($this->output === NULL) {
      $this->output = [];
      foreach ($this->children as $child) {
        $this->output[] = $child->render();
      }
    }
    return $this->output;
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

}
