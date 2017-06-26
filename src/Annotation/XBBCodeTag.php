<?php

namespace Drupal\xbbcode\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a BBCode annotation object.
 *
 * Plugin Namespace: Plugin\XBBCode.
 *
 * For a working example, see \Drupal\xbbcode\Plugin\XBBCode\EntityTagPlugin.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class XBBCodeTag extends Plugin {
  /**
   * The human-readable name of the tag.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $label;

  /**
   * Whether or not the plugin is enabled by default.
   *
   * @var bool
   */
  protected $status = FALSE;

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
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $description;

  /**
   * A sample tag for the filter tips.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $sample;

  /**
   * The default settings for the tag.
   *
   * @var array
   */
  protected $settings = [];

}
