<?php

namespace Drupal\xbbcode;

use Drupal\xbbcode\Parser\TagElementInterface;

/**
 * A simple wrapper that allows using callable functions as tag plugins.
 *
 * @package Drupal\xbbcode
 */
class CallbackTagProcessor implements TagProcessorInterface {

  /**
   * A processing callback.
   *
   * @var callable
   */
  protected $callback;

  /**
   * TagProcessor constructor.
   *
   * @param callable $callback
   *   A processing callback.
   */
  public function __construct(callable $callback) {
    $this->callback = $callback;
  }

  /**
   * Get the callback.
   *
   * @return callable
   *   A processing callback.
   */
  public function getCallback() {
    return $this->callback;
  }

  /**
   * Set the callback.
   *
   * @param callable $callback
   *   A processing callback.
   */
  public function setCallback(callable $callback) {
    $this->callback = $callback;
  }

  /**
   * {@inheritdoc}
   */
  public function process(TagElementInterface $tag) {
    // TODO: PHP 7+ supports ($this->callback)($tag).
    $callback = $this->callback;
    return $callback($tag);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(TagElementInterface $tag) {}

}
