<?php

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal\Component\Utility\Html;
use Drupal\xbbcode\Parser\TagElementInterface;
use Drupal\xbbcode\Plugin\TagPluginBase;

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
  public function process(TagElementInterface $tag) {
    $source = $tag->getSource();
    if ($tag->isPrepared()) {
      $source = base64_decode($source);
    }
    $content = Html::escape($source);
    return "<code>{$content}</code>";
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(TagElementInterface $tag) {
    return base64_encode($tag->getSource());
  }

}
