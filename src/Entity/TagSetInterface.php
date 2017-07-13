<?php

namespace Drupal\xbbcode\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for custom tag entities.
 *
 * @package Drupal\xbbcode\Entity
 */
interface TagSetInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Get the configured tag plugins.
   *
   * @return array
   *   All tags in this set, indexed by name.
   */
  public function getTags();

  /**
   * Check if a particular tag plugin is active.
   *
   * @param string $plugin_id
   *
   * @return bool
   */
  public function hasTag($plugin_id);

  /**
   * Check if any tag plugin has a particular name.
   *
   * @param string $name
   *
   * @return bool
   */
  public function hasTagName($name);

  /**
   * Get the plugin collection.
   *
   * @return \Drupal\xbbcode\TagPluginCollection
   *   The plugin collection.
   */
  public function getPluginCollection();

}
