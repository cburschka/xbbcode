<?php

namespace Drupal\xbbcode\Parser\Tree;

/**
 * Interface for node elements.
 */
interface NodeElementInterface extends ElementInterface {

  /**
   * Append an element to the children of this element.
   *
   * @param \Drupal\xbbcode\Parser\Tree\ElementInterface $element
   *   The new element.
   */
  public function append(ElementInterface $element): void;

  /**
   * Get all children of the element.
   *
   * @return \Drupal\xbbcode\Parser\Tree\ElementInterface[]
   *   The children.
   */
  public function getChildren(): array;

  /**
   * Retrieve the rendered content of the element.
   *
   * @return string|mixed
   *   The rendered content.
   */
  public function getContent();

  /**
   * Retrieve the rendered output of each child.
   *
   * @return \Drupal\xbbcode\Parser\Tree\OutputElementInterface[]
   *   The sequence of rendered outputs.
   */
  public function getRenderedChildren(): array;

  /**
   * Retrieve the descendants of the node.
   *
   * @return \Drupal\xbbcode\Parser\Tree\ElementInterface[]|\iterable
   *   Every descendant of the node.
   */
  public function getDescendants();

}
