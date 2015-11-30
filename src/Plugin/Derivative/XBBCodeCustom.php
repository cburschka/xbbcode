<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\Derivative\XBBCodeCustom.
 */

namespace Drupal\xbbcode\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a tag plugin for each XBBCodeCustom entity.
 */
class XBBCodeCustom extends DeriverBase implements ContainerDeriverInterface {
  /**
   * Entity storage
   *
   * @var EntityStorageInterface
   */
  protected $storage;

  
  /**
   * Constructs a Deriver.
   *
   * @param EntityStorageInterface $storage
   *   The entity storage.
   */
  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('xbbcode_tag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    var_dump("STARTING", $base_plugin_definition);
    $xbbcode_tags = $this->storage->loadMultiple();
    foreach ($xbbcode_tags as $tag) {
      var_dump("DERIVING");
      $this->derivatives[$tag->uuid()] = $base_plugin_definition;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
