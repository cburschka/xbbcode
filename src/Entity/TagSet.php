<?php

namespace Drupal\xbbcode\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\xbbcode\TagPluginCollection;

/**
 * Represents a set of configured tags.
 *
 * @ConfigEntityType(
 *   id = "xbbcode_tag_set",
 *   label = @Translation("tag set"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\xbbcode\Form\TagSetForm",
 *       "edit" = "Drupal\xbbcode\Form\TagSetForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\xbbcode\TagSetListBuilder"
 *   },
 *   config_prefix = "tag_set",
 *   admin_permission = "administer BBCode tag sets",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/xbbcode/sets/manage/{xbbcode_tag_set}/edit",
 *     "delete-form" = "/admin/config/content/xbbcode/sets/manage/{xbbcode_tag_set}/delete",
 *     "collection" = "/admin/config/content/xbbcode/sets"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "tags"
 *   }
 * )
 */
class TagSet extends ConfigEntityBase implements TagSetInterface {

  /**
   * The tag plugin collection configuration.
   *
   * @var array
   */
  protected $tags = [];

  /**
   * The tag plugin collection.
   *
   * @var TagPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getTags(): array {
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollection(): TagPluginCollection {
    if (!$this->pluginCollection) {
      $pluginManager = \Drupal::service('plugin.manager.xbbcode');
      $this->pluginCollection = new TagPluginCollection($pluginManager, $this->getTags());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['tags' => $this->getPluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($plugin_id): bool {
    foreach ($this->tags as $tag) {
      if ($tag['id'] === $plugin_id) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTagName($name): bool {
    return array_key_exists($name, $this->tags);
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);

    if ($update && $tags = $this->filterFormatCacheTags()) {
      Cache::invalidateTags($tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type,
                                                   array $entities) {
    /** @var \Drupal\xbbcode\Entity\TagSet[] $entities */
    parent::invalidateTagsOnDelete($entity_type, $entities);

    $tags = [];
    foreach ($entities as $entity) {
      $tags += $entity->filterFormatCacheTags();
    }
    if ($tags) {
      filter_formats_reset();
      Cache::invalidateTags($tags);
    }
  }

  /**
   * Get the IDs of all formats using this format.
   *
   * @return string[]
   */
  protected function filterFormatCacheTags(): array {
    $tags = [];

    try {
      $formats = \Drupal::entityTypeManager()
                    ->getStorage('filter_format')
                    ->getQuery()
                    ->condition('filters.xbbcode.status', TRUE)
                    ->condition('filters.xbbcode.settings.tags', $this->id())
                    ->execute();
      if ($formats) {
        $tags[] = ['config:filter_format_list'];
        foreach ($formats as $id) {
          $tags[] = "config:filter_format:{$id}";
        }
        $tags = array_combine($tags, $tags);
      }
    }
    catch (InvalidPluginDefinitionException|PluginNotFoundException $exception) {
      watchdog_exception('filter', $exception);
    }

    return $tags;
  }

}
