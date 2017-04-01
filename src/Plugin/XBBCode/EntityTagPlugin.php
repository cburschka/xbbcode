<?php

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\xbbcode\Plugin\TemplateTagPlugin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic custom block type.
 *
 * @XBBCodeTag(
 *  id = "xbbcode_tag",
 *  label = "Custom tag",
 *  admin_label = @Translation("Custom tag"),
 *  category = @Translation("Custom"),
 *  deriver = "Drupal\xbbcode\Plugin\Derivative\TagPluginDeriver"
 * )
 */
class EntityTagPlugin extends TemplateTagPlugin implements ContainerFactoryPluginInterface {
  /**
   * The custom tag entity this plugin is derived from.
   *
   * @var \Drupal\xbbcode\Entity\TagInterface
   */
  protected $entity;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new custom tag plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The tag storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('xbbcode_tag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate() {
    if (!isset($this->template)) {
      $entity = $this->getEntity();
      $code = $entity->getTemplateCode();
      $file = $entity->getTemplateFile();
      if ($file && !$code) {
        $template = $file;
      }
      else {
        $template = '{# inline_template_start #}' . $code;
      }
      $this->template = \Drupal::service('twig')->loadTemplate($template);
    }
    return $this->template;
  }

  /**
   * Loads the custom tag entity of the plugin.
   *
   * @return \Drupal\xbbcode\Entity\TagInterface
   *   The custom tag entity.
   */
  protected function getEntity() {
    if (!$this->entity) {
      $id = $this->getDerivativeId();
      $this->entity = $this->storage->load($id);
    }
    return $this->entity;
  }

}
