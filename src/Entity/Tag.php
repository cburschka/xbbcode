<?php

namespace Drupal\xbbcode\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Represents a custom XBBCode tag that can be altered by administrators.
 *
 * @ConfigEntityType(
 *   id = "xbbcode_tag",
 *   label = @Translation("custom tag"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\xbbcode\Form\TagForm",
 *       "edit" = "Drupal\xbbcode\Form\TagForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "view" = "Drupal\xbbcode\Form\TagFormView",
 *       "copy" = "Drupal\xbbcode\Form\TagFormCopy"
 *     },
 *     "list_builder" = "Drupal\xbbcode\TagListBuilder",
 *     "access" = "Drupal\xbbcode\TagAccessHandler"
 *   },
 *   config_prefix = "tag",
 *   admin_permission = "administer custom BBCode tags",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/xbbcode/tags/manage/{xbbcode_tag}/edit",
 *     "delete-form" = "/admin/config/content/xbbcode/tags/manage/{xbbcode_tag}/delete",
 *     "view-form" = "/admin/config/content/xbbcode/tags/manage/{xbbcode_tag}/view",
 *     "copy-form" = "/admin/config/content/xbbcode/tags/manage/{xbbcode_tag}/copy",
 *     "collection" = "/admin/config/content/xbbcode/tags"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "sample",
 *     "name",
 *     "attached",
 *     "editable",
 *     "template_code",
 *     "template_file"
 *   }
 * )
 */
class Tag extends ConfigEntityBase implements TagInterface {

  /**
   * Description of the tag.
   *
   * @var string
   */
  protected $description;

  /**
   * Default tag name.
   *
   * @var string
   */
  protected $name;

  /**
   * Any attachments required to render this tag.
   *
   * @var array
   */
  protected $attached = [];

  /**
   * Sample code.
   *
   * @var string
   */
  protected $sample;

  /**
   * An inline Twig template.
   *
   * @var string
   */
  protected $template_code;

  /**
   * A Twig template file.
   *
   * @var string
   */
  protected $template_file;

  /**
   * Settings for this tag.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Whether the tag is editable by admins.
   *
   * This should be left off for tags defined by modules.
   *
   * @var bool
   */
  protected $editable = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
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
  public function getSample() {
    return $this->sample;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateCode() {
    return $this->template_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateFile() {
    return $this->template_file;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return $this->attached;
  }

  /**
   * {@inheritdoc}
   */
  public function isEditable() {
    return $this->editable;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);

    // Rebuild the tag plugins.
    \Drupal::service('plugin.manager.xbbcode')->clearCachedDefinitions();

    // Filters can't tag their formats' cache, so invalidate it explicitly.
    if ($tags = $this->filterFormatCacheTags()) {
      filter_formats_reset();
    }
    if (!$update) {
      // New tags affect all filters without a tag set.
      $tags['xbbcode_tag_new'] = 'xbbcode_tag_new';
    }
    if ($tags) {
      Cache::invalidateTags($tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type,
                                                   array $entities) {
    /** @var \Drupal\xbbcode\Entity\Tag[] $entities */
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
   * {@inheritdoc}
   */
  protected function getFormats() {
    $formats = [];
    try {
      $storage = \Drupal::entityTypeManager()->getStorage('filter_format');
      $ids = $storage->getQuery()
                     ->condition('filters.xbbcode.status', TRUE)
                     ->execute();

      /** @var \Drupal\filter\FilterFormatInterface $format */
      foreach ($storage->loadMultiple($ids) as $id => $format) {
        $config = $format->filters('xbbcode')->getConfiguration();
        $tag_set_id = $config['settings']['tags'];

        // If it references an existing tag set without this tag, skip.
        if ($tag_set_id) {
          /** @var \Drupal\xbbcode\Entity\TagSetInterface $tag_set */
          $tag_set = TagSet::load($tag_set_id);
          if ($tag_set && !$tag_set->hasTag($this->id())) {
            continue;
          }
        }

        // Otherwise, include it.
        $formats[$id] = $format;
      }

    }
    catch (InvalidPluginDefinitionException $exception) {
    }

    return $formats;
  }

  /**
   * @return string[]
   */
  protected function filterFormatCacheTags() {
    if ($formats = $this->getFormats()) {
      $tags = ['config:filter_format_list'];
      foreach ($formats as $id => $format) {
        $tags[] = "config:filter_format:{$id}";
      }
      return array_combine($tags, $tags);
    }

    return [];
  }

}
