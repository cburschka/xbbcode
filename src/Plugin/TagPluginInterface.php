<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\xbbcode\Parser\Processor\TagProcessorInterface;

/**
 * Defines the interface for XBBCode tag plugins.
 *
 * @see TagPluginBase
 * @see XBBCodeTag
 * @see plugin_api
 */
interface TagPluginInterface extends TagProcessorInterface, ConfigurablePluginInterface, PluginInspectionInterface, CacheableDependencyInterface, AttachmentsInterface {

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

}
