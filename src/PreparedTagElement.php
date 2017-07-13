<?php

namespace Drupal\xbbcode;

use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Processor\TagProcessorInterface;
use Drupal\xbbcode\Parser\Tree\ElementInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;

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
      $this->argument = html_entity_decode($tag->getArgument());
      $this->attributes = array_map('html_entity_decode', $tag->getAttributes());
      $this->option = html_entity_decode($tag->getOption());
    }
    if (!preg_match('/[<>"\']/', $tag->getSource())) {
      $this->source = Markup::create($tag->getSource());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->tag->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return $this->argument ?: $this->tag->getArgument();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribute($name) {
    $attributes = $this->getAttributes();
    return isset($attributes[$name]) ? $attributes[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    return $this->attributes ?: $this->tag->getAttributes();
  }

  /**
   * {@inheritdoc}
   */
  public function getOption() {
    return $this->option ?: $this->tag->getOption();
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
    return $this->source ?: $this->tag->getSource();
  }

  /**
   * {@inheritdoc}
   */
  public function getOuterSource() {
    return Markup::create($this->tag->getOuterSource());
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessor() {
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
  public function render() {
    return $this->tag->render();
  }

  /**
   * {@inheritdoc}
   */
  public function append(ElementInterface $element) {
    return $this->tag->append($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren() {
    return $this->tag->getChildren();
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedChildren() {
    return $this->tag->getRenderedChildren();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendants() {
    return $this->tag->getDescendants();
  }

}
