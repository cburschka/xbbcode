<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\XBBCodeTagInterface.
 */

namespace Drupal\xbbcode\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\xbbcode\Annotation\XBBCodeTag;
use Drupal\xbbcode\XBBCodeTagElement;

/**
 * Defines the interface for XBBCode tag plugins.
 *
 *
 * @see XBBCodeTag
 * @see XBBCodeTagBase
 * @see plugin_api
 */
interface XBBCodeTagInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Returns the administrative label for this tag plugin.
   *
   * @return string
   */
  public function getLabel();

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
  public function process(XBBCodeTagElement $tag);

  /**
   * Return a sample tag for the filter tips.
   * This sample should reference the configured tag name.
   * 
   * @return string
   *   The sample code.
   */
  public function getSample();
}
