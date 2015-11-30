<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\XBBCode\XBBCodeTemplateTag.
 */

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal\xbbcode\Plugin\XBBCodeTagBase;

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
  public function process($tag) {
    $this->getTemplate()->render($tag);
  }
}
