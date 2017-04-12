<?php

namespace Drupal\xbbcode\Parser;

/**
 * Interface for node elements.
 */
interface NodeElementInterface extends ElementInterface {

  /**
   * Append an element to the children of this element.
   *
   * @param \Drupal\xbbcode\Parser\ElementInterface $element
   *   The new element.
   */
  public function append(ElementInterface $element);

  /**
   * Retrieve the rendered content of the element.
   *
   * @return string
   *   The rendered content.
   */
  public function getContent();

  /**
   * Get the set of tag names rendered.
   *
   * @return string[]
   *   The set of tags, indexed by tag name.
   */
  public function getRenderedTags();

}
