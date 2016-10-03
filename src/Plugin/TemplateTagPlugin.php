<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\TemplateTagPlugin.
 */

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Render\Markup;
use Drupal\xbbcode\ElementInterface;

/**
 * This is a tag that delegates processing to a Twig template.
 */
abstract class TemplateTagPlugin extends TagPlugin {
  /**
   * Get the tag template.
   *
   * @return Twig_Template
   *   The compiled template that should render this tag.
   */
  abstract public function getTemplate();

  /**
   * {@inheritdoc}
   */
  public function process(ElementInterface $tag) {
    return $this->getTemplate()->render([
      'settings' => $this->settings,
      'tag' => [
        'content' => Markup::create($tag->getContent()),
        'source' => $tag->getSource(),
        'outerSource' => $tag->getOuterSource(),
        'attr' => $tag->getAttr(),
        'option' => $tag->getOption(),
      ],
    ]);
  }

}
