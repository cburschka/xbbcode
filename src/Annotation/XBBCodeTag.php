<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Annotation\XBBCodeTag.
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
   * The human-readable name of the tag.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  protected $label;

  /**
   * The suggested code-name of the tag.
   *
   * This will be the default name for using the tag in BBCode. It must not
   * contain any whitespace characters.
   *
   * @var string
   */
  protected $name;

  /**
   * Additional administrative information about the filter's behavior.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  protected $description;

  /**
   * A sample tag for the filter tips.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  protected $sample;

  /**
   * Self-closing.
   */
  protected $selfclosing = FALSE;

  /**
   * The default settings for the tag.
   *
   * @var array (optional)
   */
  protected $settings = [];

  /**
   * The tag attachments. This must be a valid #attached array.
   * @var array
   */
  protected $attached = [];
}
