<?php

namespace Drupal\xbbcode;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\xbbcode\Parser\Tree\OutputElementInterface;

class TagProcessResult extends BubbleableMetadata implements OutputElementInterface {

  /**
   * @var \Drupal\Component\Render\MarkupInterface
   */
  protected $processedText;

  /**
   * TagProcessResult constructor.
   *
   * @param \Drupal\Component\Render\MarkupInterface $processedText
   */
  public function __construct(MarkupInterface $processedText = NULL) {
    $this->processedText = $processedText;
  }

  /**
   * @return \Drupal\Component\Render\MarkupInterface
   */
  public function getProcessedText() {
    return $this->processedText;
  }

  /**
   * @param MarkupInterface $processedText
   */
  public function setProcessedText(MarkupInterface $processedText) {
    $this->processedText = $processedText;
  }

  /**
   * @param \Drupal\xbbcode\TagProcessResult[] $children
   *
   * @return \Drupal\xbbcode\TagProcessResult
   */
  public function create(array $children) {
    $result = new TagProcessResult(implode('', $children));
    foreach ($children as $child) {
      $result = $result->merge($child);
    }
    return $result;
  }

  /**
   * @return string
   */
  public function __toString() {
    return "{$this->processedText}";
  }

}
