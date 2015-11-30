<?php

/**
 * @file
 * Contains \Drupal\xbbcode\XBBCodeTagPluginCollection.
 */

namespace Drupal\xbbcode;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of XBBCode tags.
 */
class XBBCodeTagPluginCollection extends DefaultLazyPluginCollection {

  /**
   * All possible tag plugin IDs.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\xbbcode\Plugin\XBBCodeTagInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

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
      unset($this->definitions['xbbcode_tag_null']);
    }

    // Ensure that there is an instance of all available plugins.
    // Note that getDefinitions() are keyed by $plugin_id. $instance_id is the
    // $plugin_id for xbbcodes, since a single xbbcode plugin can only exist once
    // in a format.
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
  protected function initializePlugin($instance_id) {
    $configuration = $this->manager->getDefinition($instance_id);
    // Merge the actual configuration into the default configuration.
    if (isset($this->configurations[$instance_id])) {
      $configuration = NestedArray::mergeDeep($configuration, $this->configurations[$instance_id]);
    }
    $this->configurations[$instance_id] = $configuration;
    parent::initializePlugin($instance_id);
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
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    // Remove configuration if it matches the defaults. In self::getAll(), we
    // load all available xbbcodes, in addition to the enabled xbbcodes stored in
    // configuration. In order to prevent those from bleeding through to the
    // stored configuration, remove all xbbcodes that match the default values.
    // Because xbbcodes are disabled by default, this will never remove the
    // configuration of an enabled xbbcode.
    foreach ($configuration as $instance_id => $instance_config) {
      $default_config = [];
      $default_config['id'] = $instance_id;
      $default_config += $this->get($instance_id)->defaultConfiguration();
      if ($default_config === $instance_config) {
        unset($configuration[$instance_id]);
      }
    }
    return $configuration;
  }

}
