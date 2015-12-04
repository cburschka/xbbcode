<?php

/**
 * @file
 * Contains \Drupal\xbbcode\ElementInterface.
 */

namespace Drupal\xbbcode;

/**
 * A tag occurrence as processed by tag plugins.
 */
interface ElementInterface {
  /**
   * Retrieve a particular attribute of the element.
   *
   * [tag NAME=VALUE]...[/tag]
   *
   * @param string $name
   *   The name of the attribute, or NULL.
   *
   * @return string | array
   *   The value of this attribute, or NULL if it isn't set.
   *   If no name was given, all attributes are returned in an array.
   */
  public function attr($name = NULL);

  /**
   * Retrieve the option-type attribute of the element.
   *
   * [tag=OPTION]...[/tag]
   *
   * @return string
   *   The value of the option.
   */
  public function option();

  /**
   * Retrieve the content of the tag.
   *
   * [tag]CONTENT[/tag]
   *
   * All BBCode inside this content will already be rendered.
   *
   * @return string
   *   The tag content.
   */
  public function content();

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
  public function source();

  /**
   * Retrieve the complete source, including the opening and closing tags.
   *
   * @return string
   *   The tag source.
   */
  public function outerSource();

}
