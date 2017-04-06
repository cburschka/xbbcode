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
  public function offsetExists($offset) {
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
  public function offsetSet($offset, $value) {
    /** @var \Drupal\xbbcode\PluginCollectionInterface $this */
    return $this->set($offset, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    /** @var \Drupal\xbbcode\PluginCollectionInterface $this */
    return $this->remove($offset);
  }

}
