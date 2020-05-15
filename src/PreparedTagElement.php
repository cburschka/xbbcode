<?php

namespace Drupal\xbbcode;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Processor\TagProcessorInterface;
use Drupal\xbbcode\Parser\Tree\ElementInterface;
use Drupal\xbbcode\Parser\Tree\NodeElementInterface;
use Drupal\xbbcode\Parser\Tree\OutputElementInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;

/**
 * Adapter for the tag element that marks markup as safe.
 *
 * This is used to allow Twig in template tags to automatically filter unsafe
 * strings while leaving safe markup intact.
 *
 * The content, source and outer source of the tag are marked as safe.
 * In the outer source, the argument string is run through XSS filtering to
 * ensure it is indeed safe.
 *
 * The option and attribute values are not safe, and therefore left as is.
 */
class PreparedTagElement implements TagElementInterface {
  /**
   * The wrapped tag element.
   *
   * @var \Drupal\xbbcode\Parser\Tree\TagElementInterface
   */
  protected $tag;

  /**
   * The outer source.
   *
   * @var \Drupal\Component\Render\MarkupInterface
   */
  protected $outerSource;

  /**
   * PreparedTagElement constructor.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   *   The tag to be wrapped.
   */
  public function __construct(TagElementInterface $tag) {
    $this->tag = $tag;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->tag->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument(): string {
    return $this->tag->getArgument();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribute(string $name): ?string {
    return $this->tag->getAttribute($name);
  }

  /**
   * {@inheritdoc}
   */
  public function setAttribute(string $name, string $value = NULL): void {
    $this->tag->setAttribute($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes(): array {
    return $this->tag->getAttributes();
  }

  /**
   * {@inheritdoc}
   */
  public function setAttributes(array $attributes): void {
    $this->tag->setAttributes($attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function getOption(): string {
    return $this->tag->getOption();
  }

  /**
   * {@inheritdoc}
   */
  public function setOption(string $value): void {
    $this->tag->setOption($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return Markup::create($this->tag->getContent());
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return Markup::create($this->tag->getSource());
  }

  /**
   * {@inheritdoc}
   */
  public function setSource(string $source): void {
    $this->tag->setSource($source);
  }

  /**
   * {@inheritdoc}
   */
  public function getOuterSource() {
    // Reconstruct the opening and closing tags, but render the content.
    if (!isset($this->outerSource)) {
      $name = $this->tag->getName();
      // The argument string must be made safe before rendering.
      $argument = Xss::filterAdmin($this->tag->getArgument());
      $content = $this->tag->getContent();
      $this->outerSource = Markup::create("[{$name}{$argument}]{$content}[/{$name}]");
    }
    return $this->outerSource;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessor(): TagProcessorInterface {
    return $this->tag->getProcessor();
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessor(TagProcessorInterface $processor): void {
    $this->tag->setProcessor($processor);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): OutputElementInterface {
    return $this->tag->render();
  }

  /**
   * {@inheritdoc}
   */
  public function append(ElementInterface $element): void {
    $this->tag->append($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren(): array {
    return $this->tag->getChildren();
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedChildren($force_render = TRUE): array {
    return $this->tag->getRenderedChildren($force_render);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendants() {
    return $this->tag->getDescendants();
  }

  /**
   * {@inheritdoc}
   */
  public function getParent(): NodeElementInterface {
    return $this->tag->getParent();
  }

  /**
   * {@inheritdoc}
   */
  public function setParent(NodeElementInterface $parent): void {
    $this->tag->setParent($parent);
  }

}
