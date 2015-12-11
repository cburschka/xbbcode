<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Entity\XBBCodeCustom.
 */

namespace Drupal\xbbcode\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Represents a custom XBBCode tag that can be altered by administrators.
 *
 * @ConfigEntityType(
 *   id = "xbbcode_tag",
 *   label = @Translation("BBCode custom tag"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\xbbcode\Form\TagAddForm",
 *       "edit" = "Drupal\xbbcode\Form\TagEditForm",
 *       "delete" = "Drupal\xbbcode\Form\TagDeleteForm",
 *       "view" = "Drupal\xbbcode\Form\TagViewForm"
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
 *     "view-form" = "/admin/config/content/xbbcode/tags/manage/{xbbcode_tag}/view"
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
class TagEntity extends ConfigEntityBase {

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
   * Default settings for this tag.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Whether the tag is editable by admins.
   *
   * This should be left off for tags defined by modules.
   *
   * @var boolean
   */
  protected $editable = FALSE;

  /**
   * The tag description.
   *
   * @return string
   *   Tag description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * The default tag name.
   *
   * @return string
   *   Default tag name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * The sample code.
   *
   * @return string
   *   Tag sample code (using {{ name }} placeholders).
   */
  public function getSample() {
    return $this->sample;
  }

  /**
   * An inline template.
   *
   * @return string
   *   The Twig template code.
   */
  public function getTemplateCode() {
    return $this->template_code;
  }

  /**
   * An external template file.
   *
   * This file must be registered with the theme registry via hook_theme().
   *
   * @return string
   *   A template file.
   */
  public function getTemplateFile() {
    return $this->template_file;
  }

  /**
   * Return the attachments for this tag.
   *
   * @return array
   *   A valid array to put into #attached.
   */
  public function getAttachments() {
    return $this->attached;
  }

  /**
   * Whether the tag is editable.
   *
   * @return bool
   *   Tag is editable.
   */
  public function isEditable() {
    return $this->editable;
  }

}
