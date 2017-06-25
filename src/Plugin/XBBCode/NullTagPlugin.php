<?php

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\TagPluginBase;
use Drupal\xbbcode\TagProcessResult;

/**
 * Provides a fallback placeholder plugin.
 *
 * BBCode tags will be assigned to this plugin when they are still enabled.
 *
 * @XBBCodeTag(
 *   id = "null",
 *   label = @Translation("[This tag is unavailable.]"),
 *   description = @Translation("The plugin providing this tag could not be loaded."),
 *   sample = @Translation("[{{ name }}]...[/{{ name }}]"),
 *   name = "null"
 * )
 */
class NullTagPlugin extends TagPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    \Drupal::logger('xbbcode')->alert('Missing BBCode tag plugin: %tag.', ['%tag' => $plugin_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function doProcess(TagElementInterface $tag) {
    return new TagProcessResult(Markup::create($tag->getOuterSource()));
  }

}
