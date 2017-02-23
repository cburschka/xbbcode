<?php

namespace Drupal\xbbcode\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\xbbcode\Plugin\TagPlugin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a tag plugin for each XBBCodeCustom entity.
 */
class TagPluginDeriver extends DeriverBase implements ContainerDeriverInterface {
  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a Deriver.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   */
  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('xbbcode_tag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $xbbcode_tags = $this->storage->loadMultiple();
    foreach ($xbbcode_tags as $id => $tag) {
      /** @var \Drupal\xbbcode\Entity\TagInterface $tag */
      $this->derivatives[$id] = [
        'id' => 'xbbcode_tag' . TagPlugin::DERIVATIVE_SEPARATOR . $id,
        'label' => $tag->label(),
        'description' => $tag->getDescription(),
        'sample' => $tag->getSample(),
        'name' => $tag->getName(),
        'attached' => $tag->getAttachments(),
      ] + $base_plugin_definition;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
