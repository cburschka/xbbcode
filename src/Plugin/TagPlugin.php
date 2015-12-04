<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\TagPlugin.
 */

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\xbbcode\Annotation\XBBCodeTag;

/**
 * Provides a base class for XBBCode tag plugins.
 *
 * @see XBBCodeTag
 * @see TagPluginInterface
 * @see plugin_api
 */
abstract class TagPlugin extends PluginBase implements TagPluginInterface {
  /**
   * A Boolean indicating whether this tag is enabled.
   *
   * @var bool
   */
  protected $status = FALSE;

  /**
   * The configurable tag name.
   *
   * @var string
   */
  protected $name;


  /**
   * The sample code of this tag.
   *
   * @var string
   */
  protected $sample;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->name = $this->pluginDefinition['name'];
    $this->settings = $this->pluginDefinition['settings'];
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['status'])) {
      $this->status = (bool) $configuration['status'];
    }
    if (isset($configuration['name'])) {
      $this->name = $configuration['name'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'status' => $this->status,
      'name' => $this->name,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'status' => FALSE,
      'name' => $this->pluginDefinition['name'],
      'settings' => $this->pluginDefinition['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSample() {
    return $this->pluginDefinition['sample'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSample() {
    if (!$this->sample) {
      $this->sample = str_replace('{{ name }}', $this->name, trim($this->getDefaultSample()));
    }
    return $this->sample;
  }

  /**
   * {@inheritdoc}
   */
  public function isSelfclosing() {
    return $this->pluginDefinition['selfclosing'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return $this->pluginDefinition['attached'];
  }
}
