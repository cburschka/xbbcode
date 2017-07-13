<?php

namespace Drupal\xbbcode;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Render\BubbleableMetadata;
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
    parent::__construct($manager, $configurations);
    $this->setConfiguration($configurations);
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
    // Copy instance ID into configuration as the tag name.
    foreach ($configuration as $instance_id => $plugin) {
      $configuration[$instance_id]['name'] = $instance_id;
    }
    parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    // Strip tag name from configuration.
    $configuration = parent::getConfiguration();
    $original = [];
    foreach ($configuration as $instance_id => $plugin) {
      $name = $plugin['name'];
      unset($plugin['name']);
      $original[$name] = $plugin;
    }
    return $original;
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
      $tree = $parser->parse($tag->getSample());
      $sample = $tree->render();
      $attachments = [];

      foreach ($tree->getRenderedChildren() as $child) {
        if ($child instanceof TagProcessResult) {
          $attachments = BubbleableMetadata::mergeAttachments($attachments, $child->getAttachments());
        }
      }
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
          '#attached' => $attachments,
          '#attributes' => ['class' => ['get']],
        ],
      ];
    }

    return $table;
  }

}
