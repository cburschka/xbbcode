<?php

namespace Drupal\xbbcode\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

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
 *       "view" = "Drupal\xbbcode\Form\TagFormView"
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

}
