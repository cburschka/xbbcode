<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\xbbcode\Parser\Processor\TagProcessorInterface;
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
  public function status();

  /**
   * Returns the administrative label for this tag plugin.
   *
   * @return string
   *   Plugin label.
   */
  public function label();

  /**
   * Returns the administrative description for this tag plugin.
   *
   * @return string
   *   Plugin description.
   */
  public function getDescription();

  /**
   * Returns the configured name.
   *
   * @return string
   *   The tag name.
   */
  public function getName();

  /**
   * Returns the default tag name.
   *
   * @return string
   *   Plugin default name.
   */
  public function getDefaultName();

  /**
   * Return the unprocessed sample code.
   *
   * This should have {{ name }} placeholders for the tag name.
   *
   * @return string
   *   The sample code.
   */
  public function getDefaultSample();

  /**
   * Return a sample tag for the filter tips.
   *
   * This sample should reference the configured tag name.
   *
   * @return string
   *   The sample code.
   */
  public function getSample();

  /**
   * Generate output from a tag element.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *   The tag element to process.
   *
   * @return \Drupal\xbbcode\TagProcessResult
   */
  public function process(TagElementInterface $tag);

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
  public function prepare($content, TagElementInterface $tag);

}
