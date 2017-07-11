<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\PreparedTagElement;
use Drupal\xbbcode\TagProcessResult;

/**
 * Provides a base class for XBBCode tag plugins.
 *
 * @see XBBCodeTag
 * @see TagPluginInterface
 * @see plugin_api
 */
abstract class TagPluginBase extends PluginBase implements TagPluginInterface {
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
   * The settings for this tag plugin.
   *
   * @var array
   */
  protected $settings;

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
      'name' => $this->name,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'name' => $this->pluginDefinition['name'],
      'settings' => $this->pluginDefinition['settings'],
    ];
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
  public function prepare($content, TagElementInterface $tag) {
    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function process(TagElementInterface $tag) {
    // Use an adapter that marks rendered output as safe.
    $result = $this->doProcess(new PreparedTagElement($tag));

    // Merge metadata from sub-tags.
    foreach ($tag->getRenderedChildren() as $child) {
      if ($child instanceof TagProcessResult) {
        $result = $result->merge($child);
      }
    }
    return $result;
  }

  /**
   * Create the actual output.
   *
   * Tag plugins should override this function rather than ::process(),
   * in order to let the metadata from sub-tags bubble up.
   *
   * Override ::process() only if sub-tags are either not printed in the output,
   * or the plugin handles the metadata on its own.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *
   * @return \Drupal\xbbcode\TagProcessResult
   */
  abstract public function doProcess(TagElementInterface $tag);

}
