<?php

namespace Drupal\xbbcode\Parser\Tree;

use Drupal\xbbcode\Parser\Processor\TagProcessorInterface;

/**
 * A tag occurrence as processed by tag plugins.
 */
interface TagElementInterface extends NodeElementInterface {

  /**
   * Get the canonical (lower-case) tag name of this element.
   *
   * @return string
   *   The tag name.
   */
  public function getName(): string;

  /**
   * Get the original opening tag name.
   *
   * Case-insensitively equivalent to ::getName() and ::getClosingName().
   *
   * @return string
   *   The opening tag name.
   */
  public function getOpeningName(): string;

  /**
   * Get the original closing tag name.
   *
   * @return string
   *   The closing tag name.
   */
  public function getClosingName(): string;

  /**
   * Get the original closing tag name.
   *
   * @param string $closing
   *   The closing tag name.
   *
   * @return $this
   */
  public function setClosingName(string $closing): self;

  /**
   * Retrieve the unparsed argument string.
   *
   * @return string
   *   All characters between the tag name and the right square bracket.
   */
  public function getArgument(): string;

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
  public function getAttribute(string $name) : ?string;

  /**
   * Set an attribute of the element.
   *
   * @param string $name
   *   The name of the attribute.
   * @param string|null $value
   *   (Optional) The value of the attribute, or NULL to unset it.
   */
  public function setAttribute(string $name, string $value = NULL): void;

  /**
   * Return all attribute values.
   *
   * @return string[]
   *   The tag attributes, indexed by name.
   */
  public function getAttributes(): array;

  /**
   * Set all attribute values.
   *
   * @param string[] $attributes
   *   The tag attributes, indexed by name.
   */
  public function setAttributes(array $attributes): void;

  /**
   * Retrieve the option-type attribute of the element.
   *
   * [tag=OPTION]...[/tag]
   *
   * @return string
   *   The value of the option.
   */
  public function getOption(): string;

  /**
   * Set the option-style attribute of the element.
   *
   * @param string $value
   *   The value of the option.
   */
  public function setOption(string $value): void;

  /**
   * Retrieve the content source of the tag.
   *
   * [tag]CONTENT[/tag]
   *
   * This is the content of the tag before rendering.
   *
   * @return mixed
   *   The tag content source. Must be a string or a stringable object.
   *
   * @todo Use string|Stringable in PHP 8.
   */
  public function getSource();

  /**
   * Set the content source of the tag.
   *
   * @param string $source
   *   The text between [tag] and [/tag].
   */
  public function setSource(string $source): void;

  /**
   * Retrieve the content including the opening and closing tags.
   *
   * Tags inside the content will still be rendered.
   *
   * @return mixed
   *   The tag source. Must be a string or a stringable object.
   *
   * @todo Use string|Stringable in PHP 8.
   */
  public function getOuterSource();

  /**
   * Retrieve the parent of the current tag.
   *
   * This may be either another tag or the root element.
   *
   * Note that the parent's rendered content will obviously be incomplete
   * during rendering, and should not be accessed.
   *
   * @return \Drupal\xbbcode\Parser\Tree\NodeElementInterface
   *   Parent node.
   */
  public function getParent(): NodeElementInterface;

  /**
   * Set the parent of the current tag.
   *
   * @param \Drupal\xbbcode\Parser\Tree\NodeElementInterface $parent
   *   Parent node.
   */
  public function setParent(NodeElementInterface $parent): void;

  /**
   * Get the assigned processor.
   *
   * @return \Drupal\xbbcode\Parser\Processor\TagProcessorInterface
   *   Tag processor.
   */
  public function getProcessor(): TagProcessorInterface;

  /**
   * Assign a processor to this tag element.
   *
   * @param \Drupal\xbbcode\Parser\Processor\TagProcessorInterface $processor
   *   A tag processor.
   */
  public function setProcessor(TagProcessorInterface $processor): void;

}
