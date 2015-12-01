<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\XBBCodeTagBase.
 */

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\xbbcode\Annotation\XBBCodeTag;

/**
 * Provides a base class for XBBCode tag plugins.
 *
 * @see XBBCodeTag
 * @see XBBCodeTagInterface
 * @see plugin_api
 */
abstract class XBBCodeTagBase extends PluginBase implements XBBCodeTagInterface {

  /**
   * The plugin ID of this tag.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * The name of the provider that owns this tag.
   *
   * @var string
   */
  public $provider;

  /**
   * A Boolean indicating whether this tag is enabled.
   *
   * @var bool
   */
  public $status = FALSE;

  /**
   * The configurable tag name.
   *
   * @var string
   */
  public $name;


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

    $this->provider = $this->pluginDefinition['provider'];
    $this->name = $this->pluginDefinition['name'];
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
      $this->sample = NULL;
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
      'provider' => $this->pluginDefinition['provider'],
      'status' => $this->status,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'provider' => $this->pluginDefinition['provider'],
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
  public function getLabel() {
    return $this->pluginDefinition['label'];
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
  public function getSample() {
    if (!$this->sample) {
      $this->sample = str_replace('{{ name }}', $this->name, $this->pluginDefinition['sample']);
    }
    return $this->sample;
  }

  /**
   * {@inheritdoc}
   */
  public function isSelfclosing() {
    return $this->pluginDefinition['selfclosing'];
  }
}
