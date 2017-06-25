<?php

namespace Drupal\xbbcode\Parser\Processor;

use Drupal\xbbcode\Parser\Tree\TagElementInterface;

/**
 * Encapsulates the processing functionality of a tag plugin.
 */
interface TagProcessorInterface {

  /**
   * Process a tag match.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *   The tag to be rendered.
   *
   * @return \Drupal\xbbcode\Parser\Tree\OutputElementInterface
   *   The rendered output.
   */
  public function process(TagElementInterface $tag);

}
