<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\XBBCode\XBBCodeCustomTag.
 */

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\xbbcode\Plugin\XBBCodeTagBase;
use Drupal\xbbcode\XBBCodeCustom;
use Drupal\xbbcode\XBBCodeTagElement;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic custom block type.
 *
 * @XBBCodeTag(
 *  id = "xbbcode_tag",
 *  label = "Custom tag",
 *  admin_label = @Translation("Custom tag"),
 *  category = @Translation("Custom"),
 *  deriver = "Drupal\xbbcode\Plugin\Derivative\XBBCodeCustom"
 * )
 */
class XBBCodeCustomTag extends XBBCodeTagBase implements ContainerFactoryPluginInterface {
  /**
   * The custom tag entity this plugin is derived from.
   * @var XBBCodeCustom
   */
  protected $entity;

  /**
   * Constructs a new XBBCodeCustomTag.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $entity_manager->getStorage('xbbcode_tag');
  }

  /**
   * {@inheritdoc}
   */
  public function process(XBBCodeTagElement $tag) {
    $environment = Drupal::service('twig');
    $markup = $environment->renderInline($this->getEntity()->getTemplateCode(), ['tag' => $tag]);
    return $markup;
  }

  /**
   * Loads the custom tag entity of the plugin.
   *
   * @return XBBCodeCustom
   *   The custom tag entity.
   */
  protected function getEntity() {
    if (!isset($this->entity)) {
      $id = $this->getDerivativeId();
      $this->entity = $this->storage->load($id);
    }
    return $this->entity;
  }


  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }
}
