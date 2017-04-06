<?php

namespace Drupal\xbbcode;

/**
 * Common methods to access a plugin collection.
 */
interface PluginCollectionInterface extends \ArrayAccess, \Countable, \IteratorAggregate {

  /**
   * Determines if a plugin instance exists.
   *
   * @param string $instance_id
   *   The ID of the plugin instance to check.
   *
   * @return bool
   *   TRUE if the plugin instance exists, FALSE otherwise.
   */
  public function has($instance_id);

  /**
   * Gets a plugin instance, initializing it if necessary.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being retrieved.
   */
  public function &get($instance_id);

  /**
   * Stores an initialized plugin.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being stored.
   * @param mixed $value
   *   An instantiated plugin.
   */
  public function set($instance_id, $value);

  /**
   * Removes an initialized plugin.
   *
   * The plugin can still be used; it will be reinitialized.
   *
   * @param string $instance_id
   *   The ID of the plugin instance to remove.
   */
  public function remove($instance_id);

}
