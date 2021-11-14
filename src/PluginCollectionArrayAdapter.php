<?php

namespace Drupal\xbbcode;

/**
 * Adapt plugin collection methods for array access.
 *
 * @package Drupal\xbbcode
 */
trait PluginCollectionArrayAdapter {

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset): bool {
    /** @var \Drupal\xbbcode\PluginCollectionInterface $this */
    return $this->has($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    /** @var \Drupal\xbbcode\PluginCollectionInterface $this */
    return $this->get($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value): void {
    /** @var \Drupal\xbbcode\PluginCollectionInterface $this */
    $this->set($offset, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset): void {
    /** @var \Drupal\xbbcode\PluginCollectionInterface $this */
    $this->remove($offset);
  }

}
