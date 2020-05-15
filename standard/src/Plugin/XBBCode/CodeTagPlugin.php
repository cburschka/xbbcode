<?php

namespace Drupal\xbbcode_standard\Plugin\XBBCode;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\TagPluginBase;
use Drupal\xbbcode\TagProcessResult;
use Drupal\xbbcode\Utf8;

/**
 * Prints raw code.
 *
 * @XBBCodeTag(
 *   id = "code",
 *   label = @Translation("Code"),
 *   description = @Translation("Formats code."),
 *   sample = @Translation("[{{ name }}]This is a [{{ name }}]<code>[/{{ name }}] tag.[/{{ name }}]"),
 *   name = "code",
 * )
 */
class CodeTagPlugin extends TagPluginBase {

  /**
   * {@inheritdoc}
   */
  public function prepare(string $content, TagElementInterface $tag): string {
    // Escape HTML characters, to prevent other filters from creating entities.
    // Use $tag->getSource() instead of $content, to discard the ::prepare()
    // output of nested tags (because they will not be rendered).
    return Utf8::encode($tag->getSource(), '<>&"\'');
  }

  /**
   * {@inheritdoc}
   */
  public function doProcess(TagElementInterface $tag): TagProcessResult {
    $source = Html::escape(Utf8::decode($tag->getSource()));
    return new TagProcessResult(Markup::create("<code>{$source}</code>"));
  }

}
