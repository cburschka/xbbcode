<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\xbbcode\Parser\Tree\OutputElementInterface;
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
   * Set the plugin configuration after instancing.
   *
   * @param array $configuration
   *   Plugin configuration.
   *
   * @return $this
   */
  public function setConfiguration(array $configuration): self {
    if (isset($configuration['name'])) {
      $this->name = $configuration['name'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  /**
   * Get the plugin configuration.
   *
   * @return array
   *   Plugin configuration.
   */
  public function getConfiguration(): array {
    return [
      'id' => $this->getPluginId(),
      'name' => $this->name,
      'settings' => $this->settings,
    ];
  }

  /**
   * Get default plugin configuration from definition.
   *
   * @return array
   *   Default plugin configuration.
   */
  public function defaultConfiguration(): array {
    return [
      'name' => $this->pluginDefinition['name'],
      'settings' => $this->pluginDefinition['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function status(): bool {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(): string {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSample(): string {
    return $this->pluginDefinition['sample'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSample(): string {
    if (!$this->sample) {
      $this->sample = str_replace('{{ name }}', $this->name, trim($this->getDefaultSample()));
    }
    return $this->sample;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(string $content, TagElementInterface $tag): string {
    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function process(TagElementInterface $tag): OutputElementInterface {
    // Use an adapter that marks rendered output as safe.
    $result = $this->doProcess(new PreparedTagElement($tag));

    // Merge metadata from rendered sub-tags.
    foreach ($tag->getRenderedChildren(FALSE) as $child) {
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
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *   Tag element in the parse tree.
   *
   * @return \Drupal\xbbcode\TagProcessResult
   *   Tag process result.
   */
  abstract public function doProcess(TagElementInterface $tag): TagProcessResult;

}
