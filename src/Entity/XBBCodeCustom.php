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
 *     "list_builder" = "Drupal\xbbcode\XBBCodeCustomTagListBuilder"
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
 *     "default_name",
 *     "selfclosing",
 *     "template_code",
 *     "template_name"
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
  protected $default_name;

  /**
   * Whether or not this expects a closing tag.
   * @var boolean
   */
  protected $selfclosing;

  /**
   * Sample code.
   * @var string
   */
  protected $sample;

  /**
   * Twig template of the tag.
   * @var string
   */
  protected $template_code;

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
    return $this->default_name;
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
    return str_replace('{{ name }}', $this->default_name, $this->sample);
  }

  /**
   * The default tag name.
   *
   * @return string
   */
  public function getTemplateCode() {
    return $this->template_code;
  }

  /**
   * Whether the tag is self-closing.
   *
   * @return boolean
   */
  public function isSelfclosing() {
    return $this->selfclosing;
  }
}
