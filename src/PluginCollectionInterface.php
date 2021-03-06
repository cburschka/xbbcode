<?php

namespace Drupal\xbbcode;

/**
 * Common methods to access a plugin collection.
 *
 * This abstraction has the simple purpose of allowing functions to take both
 * an associative array of plugins and a full plugin collection instance.
 *
 * @todo This interface defines methods implemented in Drupal core, which lack
 *       parameter types. Fix after strict typing is added to Drupal core in
 *       https://drupal.org/project/drupal/issues/3050720
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
   *
   * @noinspection PhpMissingParamTypeInspection
   */
  public function has($instance_id): bool;

  /**
   * Gets a plugin instance, initializing it if necessary.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being retrieved.
   *
   * @noinspection PhpMissingParamTypeInspection
   */
  public function &get($instance_id);

  /**
   * Stores an initialized plugin.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being stored.
   * @param mixed $value
   *   An instantiated plugin.
   *
   * @noinspection PhpMissingParamTypeInspection
   */
  public function set($instance_id, $value);

  /**
   * Removes an initialized plugin.
   *
   * The plugin can still be used; it will be reinitialized.
   *
   * @param string $instance_id
   *   The ID of the plugin instance to remove.
   *
   * @noinspection PhpMissingParamTypeInspection
   */
  public function remove($instance_id);

}
