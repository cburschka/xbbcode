<?php

namespace Drupal\xbbcode;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\xbbcode\Parser\Tree\OutputElementInterface;

/**
 * Represents the output of a tag processor in a tree.
 */
class TagProcessResult extends BubbleableMetadata implements OutputElementInterface {

  /**
   * Processed content.
   *
   * @var string
   */
  protected $processedText;

  /**
   * TagProcessResult constructor.
   *
   * @param string $processedText
   *   Processed content.
   */
  public function __construct(string $processedText = NULL) {
    $this->processedText = $processedText;
  }

  /**
   * Get processed content.
   *
   * @return string
   *   Processed content.
   */
  public function getProcessedText(): string {
    return $this->processedText;
  }

  /**
   * Set processed content.
   *
   * @param string $processedText
   *   Processed content.
   */
  public function setProcessedText(string $processedText): void {
    $this->processedText = $processedText;
  }

  /**
   * Concatenate a sequence of results into one.
   *
   * @param \Drupal\xbbcode\TagProcessResult[] $children
   *   Sequence of tag process results.
   *
   * @return \Drupal\xbbcode\TagProcessResult
   *   The concatenated result with merged metadata.
   */
  public static function create(array $children): TagProcessResult {
    $result = new TagProcessResult(implode('', $children));
    foreach ($children as $child) {
      $result = $result->merge($child);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return (string) $this->processedText;
  }

}
