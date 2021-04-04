<?php

namespace Drupal\xbbcode\Plugin;

use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\TagProcessResult;
use Twig\Environment as TwigEnvironment;
use Twig\TemplateWrapper as TwigTemplateWrapper;

/**
 * This is a tag that delegates processing to a Twig template.
 */
class TemplateTagPlugin extends TagPluginBase {

  /**
   * The twig environment.
   *
   * @var \Twig\Environment
   */
  protected $twig;

  /**
   * The serializable identifier of the template.
   *
   * (Either a template name or inline code.)
   *
   * @var string
   */
  protected $template;

  /**
   * Ephemeral reference to the template.
   *
   * This is private because it cannot be serialized, and must be reloaded
   * through the twig environment after hydration.
   *
   * @var \Twig\TemplateWrapper
   */
  private $templateWrapper;

  /**
   * TemplateTagPlugin constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Twig\Environment $twig
   *   Twig environment service.
   * @param string|null $template
   *   The template.
   */
  public function __construct(array $configuration,
                              string $plugin_id,
                              $plugin_definition,
                              TwigEnvironment $twig,
                              $template = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->twig = $twig;
    $this->template = $template;
  }

  /**
   * Get the tag template.
   *
   * @return \Twig_TemplateWrapper
   *   The compiled template that should render this tag.
   *
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  protected function getTemplate(): TwigTemplateWrapper {
    if (!$this->templateWrapper) {
      $this->templateWrapper = $this->twig->load($this->template);
    }
    return $this->templateWrapper;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public function doProcess(TagElementInterface $tag): TagProcessResult {
    return new TagProcessResult($this->getTemplate()->render([
      'settings' => $this->settings,
      'tag' => $tag,
    ]));
  }

}
