<?php

namespace Drupal\xbbcode_test_plugin\Plugin\XBBCode;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\TagPluginBase;
use Drupal\xbbcode\TagProcessResult;

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
class XBBCodeTestPlugin extends TagPluginBase {

  /**
   * {@inheritdoc}
   */
  public function doProcess(TagElementInterface $tag): TagProcessResult {
    $attributes = [];
    foreach ($tag->getAttributes() as $key => $value) {
      $escaped = ($value instanceof MarkupInterface) ? $value : Html::escape($value);
      $attributes[] = "data-{$key}=\"{$escaped}\"";
    }
    $attributes = implode(' ', $attributes);
    return new TagProcessResult(
      Markup::create("<span $attributes>{$tag->getContent()}</span>")
    );
  }

}
