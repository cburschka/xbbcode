<?php

namespace Drupal\xbbcode\Plugin\XBBCode;

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
  public function process(TagElementInterface $tag) {
    // Overriding ::process() because we don't print rendered content.
    $source = $tag->getSource();
    if ($tag->isPrepared()) {
      // Restore escaped HTML characters.
      $source = Utf8::decode($source);
    }
    $content = Html::escape($source);
    return new TagProcessResult(Markup::create("<code>{$content}</code>"));
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(TagElementInterface $tag) {
    // Escape HTML characters, to prevent other filters from creating entities.
    return Utf8::encode($tag->getSource(), '<>&"\'');
  }

  /**
   * {@inheritdoc}
   */
  public function doProcess(TagElementInterface $tag) {}

}
