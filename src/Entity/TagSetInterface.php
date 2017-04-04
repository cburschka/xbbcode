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

}
