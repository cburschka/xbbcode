<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\XBBCodeTemplateTag.
 */

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Plugin\XBBCodeTagBase;
use Drupal\xbbcode\XBBCodeTagElement;

/**
 * This is a tag that delegates processing to a Twig template.
 */
abstract class XBBCodeTemplateTag extends XBBCodeTagBase {
  /**
   * @return Twig_Template
   *   The compiled template that should render this tag.
   */
  abstract public function getTemplate();

  /**
   * {@inheritdoc}
   */
  public function process(XBBCodeTagElement $tag) {
    return $this->getTemplate()->render([
      'tag' => [
        'content' => Markup::create($tag->content()),
        'source' => $tag->source(),
        'outerSource' => $tag->outerSource(),
        'attr' => $tag->attr(),
        'option' => $tag->option(),
      ]
    ]);
  }
}
