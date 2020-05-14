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
  public function process(TagElementInterface $tag): OutputElementInterface {
    $output = $this->doProcess($tag);
    if (!($output instanceof OutputElementInterface)) {
      $output = new OutputElement((string) $output);
    }
    return $output;
  }

  /**
   * Override this function to return any printable value.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *   Tag element in the parse tree.
   *
   * @return mixed
   *   Any value that can be cast to string.
   */
  abstract public function doProcess(TagElementInterface $tag);

}
