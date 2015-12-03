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
 *       "add" = "Drupal\xbbcode\Form\XBBCodeTagAddForm",
 *       "edit" = "Drupal\xbbcode\Form\XBBCodeTagEditForm",
 *       "delete" = "Drupal\xbbcode\Form\XBBCodeTagDeleteForm"
 *     },
 *     "list_builder" = "Drupal\xbbcode\XBBCodeCustomTagListBuilder",
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
 *     "edit-form" = "/admin/config/content/xbbcode/tags/manage/{xbbcode_tag}",
 *     "delete-form" = "/admin/config/content/xbbcode/tags/manage/{xbbcode_tag}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "sample",
 *     "name",
 *     "selfclosing",
 *     "attached",
 *     "editable",
 *     "template_code",
 *     "template_file"
 *   }
 * )
 */
class XBBCodeCustom extends ConfigEntityBase {

  /**
   * Description of the tag.
   * @var string
   */
  protected $description;

  /**
   * Default tag name.
   * @var string
   */
  protected $name;

  /**
   * Whether or not this expects a closing tag.
   * @var boolean
   */
  protected $selfclosing = FALSE;

  /**
   * Any attachments required to render this tag.
   * @var array
   */
  protected $attached = [];

  /**
   * Sample code.
   * @var string
   */
  protected $sample;

  /**
   * An inline Twig template.
   * @var string
   */
  protected $template_code;

  /**
   * A Twig template file.
   * @var string
   */
  protected $template_file;

  /**
   * Default settings for this tag.
   * @var array
   */
  protected $settings = [];

  /**
   * Whether the tag is editable by admins.
   * This should be left off for tags defined by modules.
   * @var boolean
   */
  protected $editable = FALSE;

  /**
   * The tag description.
   *
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * The default tag name.
   *
   * @return string
   */
  public function getDefaultName() {
    return $this->name;
  }

  /**
   * The default tag name.
   *
   * @return string
   */
  public function getSample() {
    return $this->sample;
  }

  /**
   * The default tag name.
   *
   * @return string
   */
  public function getDefaultSample() {
    return str_replace('{{ name }}', $this->name, $this->sample);
  }

  /**
   * An inline template.
   *
   * @return string
   */
  public function getTemplateCode() {
    return $this->template_code;
  }

  /**
   * An external template file.
   */
  public function getTemplateFile() {
    return $this->template_file;
  }

  /**
   * Whether the tag is self-closing.
   *
   * @return boolean
   */
  public function isSelfclosing() {
    return $this->selfclosing;
  }

  /**
   * Return the attachments for this tag.
   *
   * @return array
   */
  public function getAttachments() {
    return $this->attached;
  }

  /**
   * Whether the tag is editable.
   *
   * @return boolean
   */
  public function isEditable() {
    return $this->editable;
  }
}
