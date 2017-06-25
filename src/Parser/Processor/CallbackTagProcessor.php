<?php

namespace Drupal\xbbcode\Parser\Processor;

use Drupal\xbbcode\Parser\Tree\TagElementInterface;

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
  protected $processFunction;

  /**
   * An optional prepare callback.
   *
   * @var callable
   */
  private $prepareFunction;

  /**
   * TagProcessor constructor.
   *
   * @param callable $process
   *   A processing callback.
   * @param callable $prepare
   *   An optional prepare callback.
   */
  public function __construct(callable $process, callable $prepare = NULL) {
    $this->processFunction = $process;
    $this->prepareFunction = $prepare;
  }

  /**
   * Get the callback.
   *
   * @return callable
   *   A processing callback.
   */
  public function getProcess() {
    return $this->processFunction;
  }

  /**
   * Set the callback.
   *
   * @param callable $process
   *   A processing callback.
   */
  public function setProcess(callable $process) {
    $this->processFunction = $process;
  }

  /**
   * {@inheritdoc}
   */
  public function process(TagElementInterface $tag) {
    // TODO: PHP 7+ supports ($this->process)($tag).
    $process = $this->processFunction;
    return $process($tag);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(TagElementInterface $tag) {
    if ($prepare = $this->prepareFunction) {
      return $prepare($tag);
    }
  }

}
