<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\TagProcessResult;

/**
 * This is a tag that delegates processing to a Twig template.
 */
abstract class TemplateTagPlugin extends TagPluginBase {

  /**
   * Get the tag template.
   *
   * @return \Twig_Template
   *   The compiled template that should render this tag.
   */
  abstract protected function getTemplate();

  /**
   * {@inheritdoc}
   */
  public function doProcess(TagElementInterface $tag) {
    return new TagProcessResult(Markup::create($this->getTemplate()->render([
      'settings' => $this->settings,
      'tag' => $tag,
    ])));
  }

}
