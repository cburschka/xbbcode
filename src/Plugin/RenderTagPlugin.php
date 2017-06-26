<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\TagProcessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class RenderTagPlugin extends TagPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * RenderTagPlugin constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static($configuration,
                      $plugin_id,
                      $plugin_definition,
                      $container->get('renderer'));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function doProcess(TagElementInterface $tag) {
    $element = $this->buildElement($tag);
    // Use a new render context; metadata bubbles through the filter result.
    // Importantly, this adds language and theme cache contexts, just in
    // case the filter is used in an otherwise theme-independent context.
    $output = $this->renderer->renderPlain($element);
    $result = TagProcessResult::createFromRenderArray($element);
    $result->setProcessedText($output);
    return $result;
  }

  /**
   * Build a render array from the tag.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *
   * @return array
   */
  abstract public function buildElement(TagElementInterface $tag);

}
