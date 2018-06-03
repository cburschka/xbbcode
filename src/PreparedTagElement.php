<?php

namespace Drupal\xbbcode;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Processor\TagProcessorInterface;
use Drupal\xbbcode\Parser\Tree\ElementInterface;
use Drupal\xbbcode\Parser\Tree\NodeElementInterface;
use Drupal\xbbcode\Parser\Tree\OutputElementInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Parser\Tree\TextElement;

/**
 * Adapter for the tag element that marks markup as safe.
 *
 * The source and arguments are examined for unencoded HTML.
 * If there is none, they are marked as safe to avoid double-escaping entities.
 *
 * The rendered content is always marked as safe.
 */
class PreparedTagElement implements TagElementInterface {
  /**
   * @var \Drupal\xbbcode\Parser\Tree\TagElementInterface
   */
  protected $tag;

  /**
   * @var \Drupal\Component\Render\MarkupInterface
   */
  protected $argument;

  /**
   * @var \Drupal\Component\Render\MarkupInterface[]
   */
  protected $attributes = [];

  /**
   * @var \Drupal\Component\Render\MarkupInterface
   */
  protected $option;

  /**
   * @var \Drupal\Component\Render\MarkupInterface
   */
  protected $source;

  /**
   * PreparedTagElement constructor.
   *
   * @param \Drupal\xbbcode\Parser\Tree\TagElementInterface $tag
   */
  public function __construct(TagElementInterface $tag) {
    $this->tag = $tag;

    // If the argument string is free of raw HTML, decode its entities.
    if (!preg_match('/[<>"\']/', $tag->getArgument())) {
      $this->argument = Html::decodeEntities($tag->getArgument());
      $this->attributes = array_map([Html::class, 'decodeEntities'], $tag->getAttributes());
      $this->option = Html::decodeEntities($tag->getOption());
    }
    if (!preg_match('/[<>"\']/', $tag->getSource())) {
      $this->source = Html::decodeEntities($tag->getSource());
    }

    // Wrap text elements in markup interface; the input is already filtered.
    foreach ($this->getChildren() as $child) {
      if ($child instanceof TextElement) {
        $child->setText(Markup::create($child->getText()));
      }
    }
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
    return $this->argument ?: $this->tag->getArgument();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribute($name): ?string {
    $attributes = $this->getAttributes();
    return $attributes[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes(): array {
    return $this->attributes ?: $this->tag->getAttributes();
  }

  /**
   * {@inheritdoc}
   */
  public function getOption(): string {
    return $this->option ?: $this->tag->getOption();
  }

  /**
   * {@inheritdoc}
   */
  public function getContent(): MarkupInterface {
    return Markup::create($this->tag->getContent());
  }

  /**
   * {@inheritdoc}
   */
  public function getSource(): string {
    return $this->source ?: $this->tag->getSource();
  }

  /**
   * {@inheritdoc}
   */
  public function getOuterSource(): string {
    return Markup::create($this->tag->getOuterSource());
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
  public function setProcessor(TagProcessorInterface $processor) {
    return $this->tag->setProcessor($processor);
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
  public function getRenderedChildren(): array {
    return $this->tag->getRenderedChildren();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendants() {
    return $this->tag->getDescendants();
  }

  /**
   * Retrieve the parent of the current tag.
   *
   * This may be either another tag or the root element.
   *
   * Note that the parent's rendered content will obviously be incomplete
   * during rendering, and should not be accessed.
   *
   * @return \Drupal\xbbcode\Parser\Tree\NodeElementInterface
   */
  public function getParent(): NodeElementInterface {
    return $this->tag->getParent();
  }

  /**
   * Set the parent of the current tag.
   *
   * @param \Drupal\xbbcode\Parser\Tree\NodeElementInterface $parent
   */
  public function setParent(NodeElementInterface $parent): void {
    $this->tag->setParent($parent);
  }

}
