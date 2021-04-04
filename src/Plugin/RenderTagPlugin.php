<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\TagProcessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for plugins that produce a Drupal render array.
 */
abstract class RenderTagPlugin extends TagPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Drupal renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * RenderTagPlugin constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal renderer service.
   */
  public function __construct(array $configuration,
                              string $plugin_id,
                              $plugin_definition,
                              RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
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
   */
  public function doProcess(TagElementInterface $tag): TagProcessResult {
    $element = $this->buildElement($tag);
    // Use a new render context; metadata bubbles through the filter result.
    // Importantly, this adds language and theme cache contexts, just in
    // case the filter is used in an otherwise theme-independent context.
    $output = $this->renderer->renderPlain($element);
    $result = TagProcessResult::createFromRenderArray($element);
    $result->setProcessedText((string) $output);
    return $result;
  }

  /**
   * Build a render array from the tag.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *   The tag element in the parse tree.
   *
   * @return array
   *   The render array.
   */
  abstract public function buildElement(TagElementInterface $tag): array;

}
