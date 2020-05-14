<?php

namespace Drupal\xbbcode;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Tree\OutputElementInterface;

/**
 * Represents the output of a tag processor in a tree.
 */
class TagProcessResult extends BubbleableMetadata implements OutputElementInterface {

  /**
   * Processed content.
   *
   * @var \Drupal\Component\Render\MarkupInterface
   */
  protected $processedText;

  /**
   * TagProcessResult constructor.
   *
   * @param \Drupal\Component\Render\MarkupInterface $processedText
   *   Processed content.
   */
  public function __construct(MarkupInterface $processedText = NULL) {
    $this->processedText = $processedText;
  }

  /**
   * Get processed content.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   Processed content.
   */
  public function getProcessedText(): MarkupInterface {
    return $this->processedText;
  }

  /**
   * Set processed content.
   *
   * @param \Drupal\Component\Render\MarkupInterface $processedText
   *   Processed content.
   */
  public function setProcessedText(MarkupInterface $processedText): void {
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
    $result = new TagProcessResult(Markup::create(implode('', $children)));
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
