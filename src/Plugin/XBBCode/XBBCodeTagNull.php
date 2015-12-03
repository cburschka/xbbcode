<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\XBBCode\XBBCodeTagNull.
 */

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal;
use Drupal\xbbcode\Plugin\XBBCodeTagBase;
use Drupal\xbbcode\XBBCodeTagElement;

/**
 * Provides a fallback placeholder plugin.
 *
 * BBCode tags will be assigned to this plugin when they are still enabled
 *
 * @XBBCodeTag(
 *   id = "xbbcode_tag_null",
 *   label = @Translation("[This tag is unavailable.]"),
 * )
 */
class XBBCodeTagNull extends XBBCodeTagBase {
  /**
   * Tracks if an alert about this tag has been logged.
   *
   * @var bool
   */
  protected $logged = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // Once per tag, log that a tag plugin was missing.
    if (!$this->logged) {
      $this->logged = TRUE;
      Drupal::logger('filter')->alert('Missing BBCode tag plugin: %tag.', ['%tag' => $plugin_id]);
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process(XBBCodeTagElement $tag) {
    return $tag->outerSource();
  }
}
