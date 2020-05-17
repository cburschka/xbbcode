<?php

namespace Drupal\xbbcode;

use Drupal\Component\Plugin\Exception\PluginException;
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
   * The IDs of all defined plugins.
   *
   * @var array
   */
  protected $ids;

  /**
   * The default collection.
   *
   * @var \Drupal\xbbcode\TagPluginCollection
   */
  protected $defaultCollection;

  /**
   * Array of default configurations.
   *
   * @var array
   */
  protected $defaultConfiguration;

  /**
   * List of default name collisions.
   *
   * @var string[][]
   */
  protected $defaultNameCollisions;

  /**
   * Constructs an XBBCodeTagPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/XBBCode', $namespaces, $module_handler, TagPluginInterface::class, XBBCodeTag::class);
    $this->alterInfo('xbbcode_info');
    $this->setCacheBackend($cache_backend, 'xbbcode_tag_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []): string {
    return 'null';
  }

  /**
   * Return an array of all defined plugin IDs.
   *
   * @return string[]
   *   The plugin IDs.
   */
  public function getDefinedIds(): array {
    if (!$this->ids) {
      $ids = array_keys($this->getDefinitions());
      $this->ids = array_combine($ids, $ids);
      unset($this->ids['null']);
    }
    return $this->ids;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions(): void {
    parent::clearCachedDefinitions();
    $this->ids = NULL;
    $this->defaultConfiguration = NULL;
    $this->defaultNameCollisions = NULL;

    // Refresh the default plugin collection, if it is active.
    if ($this->defaultCollection) {
      $this->defaultCollection->setConfiguration($this->getDefaultConfiguration());
    }
  }

  /**
   * Create a plugin collection based on all available plugins.
   *
   * If multiple plugins use the same default name, the last one will be used.
   *
   * @return \Drupal\xbbcode\TagPluginCollection
   *   The plugin collection.
   */
  public function getDefaultCollection(): TagPluginCollection {
    if (!$this->defaultCollection) {
      $configurations = $this->getDefaultConfiguration();
      $this->defaultCollection = new TagPluginCollection($this, $configurations);
    }
    return $this->defaultCollection;
  }

  /**
   * Get a default configuration array based on all available plugins.
   *
   * Tag plugins have no settings, so we just need to collect plugin IDs.
   *
   * @return array[][]
   *   Associative array of:
   *     default tag name => ['id' => plugin ID]
   */
  protected function getDefaultConfiguration(): array {
    if ($this->defaultConfiguration === NULL || $this->defaultNameCollisions === NULL) {
      $this->defaultConfiguration = [];
      $this->defaultNameCollisions = [];

      foreach ($this->getDefinedIds() as $plugin_id) {
        /** @var \Drupal\xbbcode\Plugin\TagPluginInterface $plugin */
        try {
          $plugin = $this->createInstance($plugin_id);
          $name = $plugin->getName();
          $this->defaultConfiguration[$name]['id'] = $plugin_id;

          if (!isset($this->defaultNameCollisions[$name])) {
            $this->defaultNameCollisions[$name] = [];
          }
          $this->defaultNameCollisions[$name][] = $plugin_id;
        }
        catch (PluginException $exception) {
          watchdog_exception('xbbcode', $exception);
        }
      }

      foreach ($this->defaultNameCollisions as $name => $ids) {
        if (count($ids) < 2) {
          unset($this->defaultNameCollisions[$name]);
        }
      }
    }

    return $this->defaultConfiguration;
  }

  /**
   * Check for collisions in the default names of plugins.
   *
   * @return string[][]
   *   Associative array of default tag name => array of plugin IDs.
   */
  public function getDefaultNameCollisions(): array {
    if ($this->defaultNameCollisions === NULL) {
      $this->getDefaultConfiguration();
    }

    return $this->defaultNameCollisions;
  }

}
