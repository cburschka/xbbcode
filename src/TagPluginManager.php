<?php

namespace Drupal\xbbcode;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\xbbcode\Annotation\XBBCodeTag;
use Drupal\xbbcode\Plugin\TagPluginInterface;
use Traversable;

/**
 * Manages BBCode tags.
 *
 * @see TagPluginBase
 * @see TagPluginInterface
 * @see XBBCodeTag
 * @see plugin_api
 */
class TagPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs an XBBCodeTagPluginManager object.
   *
   * @param Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/XBBCode', $namespaces, $module_handler, TagPluginInterface::class, XBBCodeTag::class);
    $this->alterInfo('xbbcode_info');
    $this->setCacheBackend($cache_backend, 'xbbcode_tags');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'null';
  }

}
