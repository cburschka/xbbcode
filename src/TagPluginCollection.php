<?php

namespace Drupal\xbbcode;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\xbbcode\Parser\XBBCodeParser;

/**
 * A collection of tag plugins.
 *
 * @property \Drupal\xbbcode\TagPluginManager manager
 */
class TagPluginCollection extends DefaultLazyPluginCollection implements PluginCollectionInterface {

  use PluginCollectionArrayAdapter;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(TagPluginManager $manager, array $configurations = []) {
    static::prepareConfiguration($configurations);
    parent::__construct($manager, $configurations);
    $this->sort();
  }

  /**
   * Create a plugin collection directly from an array of tag plugins.
   *
   * @param \Drupal\xbbcode\Plugin\TagPluginInterface[] $tags
   *   The tag plugins.
   *
   * @return static
   *   A plugin collection.
   */
  public static function createFromTags(array $tags) {
    $configurations = [];
    foreach ($tags as $name => $tag) {
      $configurations[$name]['id'] = $tag->getPluginId();
    }
    $collection = new static(\Drupal::service('plugin.manager.xbbcode'), $configurations);
    $collection->pluginInstances = $tags;
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration($configuration) {
    static::prepareConfiguration($configuration);
    parent::setConfiguration($configuration);
  }

  /**
   * Prepare the configuration array.
   *
   * @param array $configurations
   *   The configuration array.
   */
  protected static function prepareConfiguration(array &$configurations) {
    // Copy instance ID into configuration as the tag name.
    foreach ($configurations as $instance_id => &$configuration) {
      $configuration['name'] = $instance_id;
    }
  }

  /**
   * Create a plugin collection based on all available plugins.
   *
   * If multiple plugins use the same default name, the last one will be used.
   *
   * @param \Drupal\xbbcode\TagPluginManager $manager
   *   The plugin collection.
   *
   * @return \Drupal\xbbcode\TagPluginCollection
   *   The plugin collection.
   */
  public static function createDefaultCollection(TagPluginManager $manager) {
    $configurations = [];
    foreach ($manager->getDefinedIds() as $plugin_id) {
      /** @var \Drupal\xbbcode\Plugin\TagPluginInterface $plugin */
      try {
        $plugin = $manager->createInstance($plugin_id);
        $configurations[$plugin->getName()]['id'] = $plugin_id;
      }
      catch (PluginException $exception) {
        watchdog_exception('xbbcode', $exception);
      }
    }

    return new static($manager, $configurations);
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($a, $b) {
    // Sort by instance ID (which is the tag name) instead of plugin ID.
    return strnatcasecmp($a, $b);
  }

  /**
   * Generate a list of configured tags for display.
   *
   * @return array
   *   A render element.
   */
  public function getSummary() {
    $tags = [
      '#theme' => 'item_list',
      '#wrapper_attributes' => ['class' => ['xbbcode-tips-list']],
      '#attached' => ['library' => ['xbbcode/filter-tips']],
      '#items' => [],
      '#empty' => $this->t('None'),
    ];
    foreach ($this as $name => $tag) {
      $tags['#items'][$name] = [
        '#type' => 'inline_template',
        '#template' => '<abbr title="{{ tag.description }}">[{{ tag.name }}]</abbr>',
        '#context' => ['tag' => $tag],
      ];
    }
    return $tags;
  }

  /**
   * Generate a table of available tags, with samples.
   *
   * @return array
   *   A render element.
   */
  public function getTable() {
    $table = [
      '#type' => 'table',
      '#caption' => $this->t('Allowed BBCode tags:'),
      '#header' => [
        $this->t('Tag Description'),
        $this->t('You Type'),
        $this->t('You Get'),
      ],
      '#empty' => $this->t('BBCode is active, but no tags are available.'),
    ];

    foreach ($this as $name => $tag) {
      /** @var \Drupal\xbbcode\Plugin\TagPluginInterface $tag */
      $parser = new XBBCodeParser(TagPluginCollection::createFromTags([$name => $tag]));
      $sample = $parser->parse($tag->getSample())->render();
      $table[$name] = [
        [
          '#type' => 'inline_template',
          '#template' => '<strong>[{{ tag.name }}]</strong><br /> {{ tag.description }}',
          '#context' => ['tag' => $tag],
          '#attributes' => ['class' => ['description']],
        ],
        [
          '#type' => 'inline_template',
          '#template' => '<code>{{ tag.sample|nl2br }}</code>',
          '#context' => ['tag' => $tag],
          '#attributes' => ['class' => ['type']],
        ],
        [
          '#markup' => Markup::create($sample),
          '#attached' => $tag->getAttachments(),
          '#attributes' => ['class' => ['get']],
        ],
      ];
    }
    return $table;
  }

}
