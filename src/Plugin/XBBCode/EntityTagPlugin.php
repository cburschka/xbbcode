<?php

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\TemplateTagPlugin;
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
  const TEMPLATE_PREFIX = '{# inline_template_start #}';

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
   * The twig environment.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

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
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The twig template loader.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityStorageInterface $storage,
                              TwigEnvironment $twig) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = $storage;
    $this->twig = $twig;
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
      $container->get('entity_type.manager')->getStorage('xbbcode_tag'),
      $container->get('twig')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Syntax
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
        $template = self::TEMPLATE_PREFIX . $code;
      }
      $this->template = $this->twig->loadTemplate($template);
    }
    return $this->template;
  }

  /**
   * {@inheritdoc}
   */
  public function doProcess(TagElementInterface $tag) {
    // Output is dependent on the tag entity.
    $result = parent::doProcess($tag);
    $result->addAttachments($this->getEntity()->getAttachments());
    $result->addCacheableDependency($this->getEntity());
    return $result;
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
