<?php

namespace Drupal\xbbcode\Parser;

/**
 * A tag occurrence as processed by tag plugins.
 */
interface TagElementInterface extends NodeElementInterface {

  /**
   * Retrieve a particular attribute of the element.
   *
   * [tag NAME=VALUE]...[/tag]
   *
   * @param string $name
   *   The name of the attribute, or NULL.
   *
   * @return string|null
   *   The value of this attribute, or NULL if it isn't set.
   */
  public function getAttribute($name);

  /**
   * Return all attribute values.
   *
   * @return string[]
   *   The tag attributes, indexed by name.
   */
  public function getAttributes();

  /**
   * Retrieve the option-type attribute of the element.
   *
   * [tag=OPTION]...[/tag]
   *
   * @return string
   *   The value of the option.
   */
  public function getOption();

  /**
   * Retrieve the rendered content of this tag.
   *
   * @return string
   *   The tag content.
   */
  public function getContent();

  /**
   * Retrieve the content source of the tag.
   *
   * [tag]CONTENT[/tag]
   *
   * This is the content of the tag before rendering.
   *
   * @return string
   *   The tag content source.
   */
  public function getSource();

  /**
   * Retrieve the content including the opening and closing tags.
   *
   * Tags inside the content will still be rendered.
   *
   * @return string
   *   The tag source.
   */
  public function getOuterSource();

  /**
   * Check if the element was prepared before rendering.
   *
   * This is true if the content is being processed by the filter plugin.
   *
   * @return bool
   *   TRUE if the tag was run through prepare(), FALSE otherwise.
   */
  public function isPrepared();

}
