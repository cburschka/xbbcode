<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Entity\XBBCodeCustomTag.
 */

namespace Drupal\xbbcode\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Represents a custom XBBCode tag that can be altered by administrators.
 * 
 * @ConfigEntityType(
 *   id = "xbbcode_tag",
 *   label = @Translation("XBBCode custom tag"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\xbbcode\Form\XBBCodeCustomTagAddForm",
 *       "edit" = "Drupal\xbbcode\Form\XBBCodeCustomTagEditForm",
 *       "delete" = "Drupal\xbbcode\Form\XBBCodeCustomTagDeleteForm"
 *     },
 *     "list_builder" = "Drupal\xbbcode\XBBCodeCustomTagListBuilder"
 *   },
 *   config_prefix = "tag",
 *   admin_premission = "administer custom BBCode tags",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/formats/manage/{xbbcode_tag}/edit"
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
class XBBCodeCustomTag extends ConfigEntityBase {
  /**
   * Internal ID of this tag.
   * @var string
   */
  protected $id;
  
  /**
   * Human-readable label of the tag.
   * @var string
   */
  protected $label;

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
   * Name of an internal template for the tag.
   * (Ignored if template_code is set.)
   * @var string
   */
  protected $template_name;
}