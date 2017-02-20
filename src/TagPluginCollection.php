<?php

namespace Drupal\xbbcode;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of tag plugins.
 */
class TagPluginCollection extends DefaultLazyPluginCollection {
  /**
   * The manager used to instantiate the plugins.
   *
   * @var TagPluginManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(TagPluginManager $manager, array $configurations = []) {
    parent::__construct($manager, $configurations);
  }

  /**
   * All possible tag plugin IDs.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Retrieves definitions and creates an instance for each XBBCode tag plugin.
   *
   * This is used for the XBBCode handler administration page, which lists all
   * available plugins.
   */
  public function getAll() {
    // Retrieve all available xbbcode plugin definitions.
    if (!$this->definitions) {
      $this->definitions = $this->manager->getDefinitions();

      // Do not allow the null tag to be used directly, only as a fallback.
      unset($this->definitions[$this->manager->getFallbackPluginId('')]);
    }

    foreach ($this->definitions as $plugin_id => $definition) {
      if (!isset($this->pluginInstances[$plugin_id])) {
        $this->initializePlugin($plugin_id);
      }
    }

    return $this->pluginInstances;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($plugin_id) {
    $configuration = $this->manager->getDefinition($plugin_id);
    // Merge the actual configuration into the default configuration.
    if (isset($this->configurations[$plugin_id])) {
      $configuration = NestedArray::mergeDeep($configuration, $this->configurations[$plugin_id]);
    }
    $this->configurations[$plugin_id] = $configuration;
    parent::initializePlugin($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function sort() {
    $this->getAll();
    return parent::sort();
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($a, $b) {
    return strnatcasecmp($this->get($a)->getName(), $this->get($b)->getName());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    // Remove configuration if it matches the defaults.
    foreach ($configuration as $plugin_id => $instance_config) {
      $default_config = [];
      $default_config['id'] = $plugin_id;
      $default_config += $this->get($plugin_id)->defaultConfiguration();
      if ($default_config === $instance_config) {
        unset($configuration[$plugin_id]);
      }
    }
    return $configuration;
  }

}
