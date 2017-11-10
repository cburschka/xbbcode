<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\xbbcode\Parser\Processor\TagProcessorInterface;
use Drupal\xbbcode\Parser\Tree\OutputElementInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;

/**
 * Defines the interface for XBBCode tag plugins.
 *
 * @see TagPluginBase
 * @see XBBCodeTag
 * @see plugin_api
 */
interface TagPluginInterface extends TagProcessorInterface, PluginInspectionInterface {

  /**
   * Returns the status of this tag plugin.
   *
   * @return bool
   *   Plugin status.
   */
  public function status(): bool;

  /**
   * Returns the administrative label for this tag plugin.
   *
   * @return string
   *   Plugin label.
   */
  public function label(): string;

  /**
   * Returns the administrative description for this tag plugin.
   *
   * @return string
   *   Plugin description.
   */
  public function getDescription(): string;

  /**
   * Returns the configured name.
   *
   * @return string
   *   The tag name.
   */
  public function getName(): string;

  /**
   * Returns the default tag name.
   *
   * @return string
   *   Plugin default name.
   */
  public function getDefaultName(): string;

  /**
   * Return the unprocessed sample code.
   *
   * This should have {{ name }} placeholders for the tag name.
   *
   * @return string
   *   The sample code.
   */
  public function getDefaultSample(): string;

  /**
   * Return a sample tag for the filter tips.
   *
   * This sample should reference the configured tag name.
   *
   * @return string
   *   The sample code.
   */
  public function getSample(): string;

  /**
   * Generate output from a tag element.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *   The tag element to process.
   *
   * @return \Drupal\xbbcode\Parser\Tree\OutputElementInterface
   *   Actually a TagProcessResult, but PHP does not support covariant types.
   *
   * @see \Drupal\xbbcode\TagProcessResult
   */
  public function process(TagElementInterface $tag): OutputElementInterface;

  /**
   * Transform an elements' content, to armor against other filters.
   *
   * - Use the inner content if all children will be rendered.
   * - Use $tag->getSource() if no children will be rendered.
   * - Traverse the tag's descendants for more complex cases.
   *
   * @param string $content
   *   The content, after applying inner transformations.
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *   The original tag element.
   *
   * @return string
   *   The prepared output.
   */
  public function prepare($content, TagElementInterface $tag): string;

}
