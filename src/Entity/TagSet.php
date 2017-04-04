<?php

namespace Drupal\xbbcode\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\xbbcode\TagPluginCollection;

/**
 * Represents a set of configured tags.
 *
 * @ConfigEntityType(
 *   id = "xbbcode_tag_set",
 *   label = @Translation("Tag set"),
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
   * @var \Drupal\xbbcode\TagPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * Gets the plugin collections used by this entity.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections() {
    if (!$this->pluginCollection) {
      $pluginManager = \Drupal::service('plugin.manager.xbbcode');
      $this->pluginCollection = new TagPluginCollection($pluginManager, $this->getTags());
    }
    return ['tags' => $this->pluginCollection];
  }

}
