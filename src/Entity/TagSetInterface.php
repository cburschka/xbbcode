<?php

namespace Drupal\xbbcode\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\xbbcode\TagPluginCollection;

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
  public function getTags(): array;

  /**
   * Check if a particular tag plugin is active.
   *
   * @param string $plugin_id
   *   Plugin ID.
   *
   * @return bool
   *   TRUE if the plugin is active in this tag set.
   */
  public function hasTag(string $plugin_id): bool;

  /**
   * Check if any tag plugin has a particular name.
   *
   * @param string $name
   *   Tag name.
   *
   * @return bool
   *   TRUE if the tag set has assigned this name to a plugin.
   */
  public function hasTagName(string $name): bool;

  /**
   * Get the plugin collection.
   *
   * @return \Drupal\xbbcode\TagPluginCollection
   *   The plugin collection.
   */
  public function getPluginCollection(): TagPluginCollection;

}
