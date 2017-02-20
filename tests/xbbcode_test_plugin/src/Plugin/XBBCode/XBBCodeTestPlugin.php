<?php

/**
 * @file
 * Contains \Drupal\xbbcode_test_plugin\Plugin\XBBCode\XBBCodeTestPlugin.
 */

namespace Drupal\xbbcode_test_plugin\Plugin\XBBCode;

use Drupal\Component\Utility\Html;
use Drupal\xbbcode\ElementInterface;
use Drupal\xbbcode\Plugin\TagPlugin;

/**
 * Renders a test tag.
 *
 * @XBBCodeTag(
 *   id = "test_plugin_id",
 *   label = @Translation("Test Plugin Label"),
 *   description = @Translation("Test Plugin Description"),
 *   name = "test_plugin",
 *   sample = @Translation("[{{ name }} foo=bar bar=foo]Lorem Ipsum Dolor Sit Amet[/{{ name }}]")
 * )
 */
class XBBCodeTestPlugin extends TagPlugin {

  /**
   * {@inheritdoc}
   */
  public function process(ElementInterface $tag) {
    $attributes = [];
    foreach ($tag->getAttribute() as $key => $value) {
      $attributes[] = 'data-' . $key . '="' . Html::escape($value) . '"';
    }
    $attributes = implode(' ', $attributes);
    return "<span $attributes>" . $tag->getContent() . '</span>';
  }

}
