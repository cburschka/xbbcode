<?php

namespace Drupal\xbbcode\Parser\Processor;

use Drupal\xbbcode\Parser\Tree\OutputElement;
use Drupal\xbbcode\Parser\Tree\OutputElementInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;

/**
 * Base tag processor for wrapping the output.
 *
 * @package Drupal\xbbcode\Parser
 */
abstract class TagProcessorBase implements TagProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(TagElementInterface $tag) {
    $output = $this->doProcess($tag);
    if (!($output instanceof OutputElementInterface)) {
      $output = new OutputElement("$output");
    }
    return $output;
  }

  /**
   * Override this function to return any printable value.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *
   * @return mixed
   */
  abstract public function doProcess(TagElementInterface $tag);

}
