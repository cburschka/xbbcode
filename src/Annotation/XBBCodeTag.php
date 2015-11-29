<?php

/**
 * @file
 * Contains \Drupal\filter\Annotation\Filter.
 */

namespace Drupal\xbbcode\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a BBCode annotation object.
 *
 * Plugin Namespace: Plugin\XBBCode
 *
 * For a working example, see \Drupal\xbbcode\Plugin\XBBCode\XBBCodeTagCustom.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class XBBCodeTag extends Plugin {

  /**
   * The tag ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the provider that owns the tag.
   *
   * @var string
   */
  public $provider;

  /**
   * The suggested code-name of the tag.
   *
   * This will be the default name for using the tag in BBCode. It must not
   * contain any whitespace characters.
   *
   * @var string
   */
  public $name;

  /**
   * The human-readable name of the tag.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * Additional administrative information about the filter's behavior.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * A sample tag for the filter tips.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $sample = '';
  
  /**
   * The default settings for the tag.
   *
   * @var array (optional)
   */
  public $settings = array();
}
