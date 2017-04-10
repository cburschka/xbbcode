<?php

namespace Drupal\xbbcode;

use Drupal\xbbcode\Parser\TagElementInterface;

/**
 * Encapsulates the processing functionality of a tag plugin.
 */
interface TagProcessorInterface {

  /**
   * Process a tag match.
   *
   * @param \Drupal\xbbcode\Parser\TagElementInterface $tag
   *   The tag to be rendered.
   *
   * @return string
   *   The rendered output.
   */
  public function process(TagElementInterface $tag);

  /**
   * Prepare an element's content for rendering.
   *
   * If NULL is returned, the content will be left alone.
   *
   * @param \Drupal\xbbcode\Parser\TagElementInterface $tag
   *   The tag to be prepared.
   *
   * @return string|null
   *   The prepared output.
   */
  public function prepare(TagElementInterface $tag);

}
