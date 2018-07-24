<?php

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\xbbcode\Entity\TagInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\TemplateTagPlugin;
use Drupal\xbbcode\TagProcessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A tag plugin based on a custom tag entity.
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
   * The prefix that precedes an inline template.
   *
   * @var string
   */
  public const TEMPLATE_PREFIX = '{# inline_template_start #}';

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The custom tag entity this plugin is derived from.
   *
   * (Not serialized for performance reasons.)
   *
   * @var \Drupal\xbbcode\Entity\TagInterface
   */
  private $entity;

  /**
   * Constructs a new custom tag plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The twig template loader.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The tag storage.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              TwigEnvironment $twig,
                              EntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $twig);
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twig'),
      $container->get('entity_type.manager')->getStorage('xbbcode_tag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(): \Twig_TemplateWrapper {
    // Lazily prepare the template, if it does not exist yet.
    if ($this->template === NULL) {
      $entity = $this->getEntity();
      $code = $entity->getTemplateCode();
      $file = $entity->getTemplateFile();
      $this->template = ($file && !$code) ? $file : self::TEMPLATE_PREFIX . $code;
    }
    // Delegate template-loading to the parent.
    return parent::getTemplate();
  }

  /**
   * {@inheritdoc}
   */
  public function doProcess(TagElementInterface $tag): TagProcessResult {
    $result = parent::doProcess($tag);
    $result->addCacheableDependency($this->getEntity());
    $result->addAttachments($this->getEntity()->getAttachments());
    return $result;
  }

  /**
   * Loads the custom tag entity of the plugin.
   *
   * @return \Drupal\xbbcode\Entity\TagInterface
   *   The custom tag entity.
   */
  protected function getEntity(): TagInterface {
    if (!$this->entity) {
      $id = $this->getDerivativeId();
      $this->entity = $this->storage->load($id);
    }
    return $this->entity;
  }

}
