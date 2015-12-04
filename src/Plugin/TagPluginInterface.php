<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\TagPluginInterface.
 */

namespace Drupal\xbbcode\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\xbbcode\Annotation\XBBCodeTag;
use Drupal\xbbcode\ElementInterface;

/**
 * Defines the interface for XBBCode tag plugins.
 *
 *
 * @see TagPlugin
 * @see XBBCodeTag
 * @see plugin_api
 */
interface TagPluginInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Returns the status of this tag plugin.
   *
   * @return boolean
   */
  public function status();

  /**
   * Returns the administrative label for this tag plugin.
   *
   * @return string
   */
  public function label();

  /**
   * Returns the administrative description for this tag plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Returns the default tag name.
   *
   * @return string
   */
  public function getDefaultName();

  /**
   * Returns TRUE if the tag is self-closing.
   *
   * @return boolean
   */
  public function isSelfclosing();

  /**
   * Process a tag match.
   *
   * @param object $tag
   *   The tag to be rendered.
   *
   * @return string
   *   The rendered output.
   */
  public function process(ElementInterface $tag);

  /**
   * Return the unprocessed sample code. This should have
   * {{ name }} placeholders for the tag name.
   *
   * @return string
   *   The sample code.
   */
  public function getDefaultSample();

  /**
   * Return a sample tag for the filter tips.
   * This sample should reference the configured tag name.
   *
   * @return string
   *   The sample code.
   */
  public function getSample();

  /**
   * Return attachments for a tag.
   *
   * @return array
   *   A valid #attach array.
   */
  public function getAttachments();
}
