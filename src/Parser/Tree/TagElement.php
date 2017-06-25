<?php

namespace Drupal\xbbcode\Parser\Tree;

use Drupal\xbbcode\Parser\Processor\TagProcessorInterface;
use Drupal\xbbcode\Parser\XBBCodeParser;

/**
 * A BBCode tag element.
 */
class TagElement extends NodeElement implements TagElementInterface {

  /**
   * The processor handling this element.
   *
   * @var \Drupal\xbbcode\Parser\Processor\TagProcessorInterface
   */
  private $processor;

  /**
   * The tag argument.
   *
   * @var string
   */
  private $argument;

  /**
   * The tag content source.
   *
   * @var string
   */
  private $source;

  /**
   * The tag name.
   *
   * @var string
   */
  private $name;

  /**
   * The tag attributes.
   *
   * @var string[]
   */
  private $attributes = [];

  /**
   * The tag option.
   *
   * @var string
   */
  private $option;

  /**
   * If the element was run through prepare().
   *
   * @var bool
   */
  private $prepared;

  /**
   * TagElement constructor.
   *
   * @param string $name
   *   The tag name.
   * @param string $argument
   *   The argument (everything past the tag name)
   * @param string $source
   *   The source of the content.
   * @param \Drupal\xbbcode\Parser\TagProcessorInterface $processor
   *   The plugin that will render this tag.
   * @param bool $prepared
   *   Whether the element was prepared.
   */
  public function __construct($name, $argument, $source, TagProcessorInterface $processor = NULL, $prepared = FALSE) {
    $this->name = $name;
    $this->argument = $argument;
    $this->source = $source;
    $this->processor = $processor;
    $this->prepared = $prepared;

    if ($argument && $argument[0] === '=') {
      $option = substr($argument, 1);
      // Strip backslashes before ] and \ characters.
      $this->option = str_replace(['\\]', '\\\\'], [']', '\\'], $option);
    }
    else {
      $this->attributes = XBBCodeParser::parseAttributes($argument);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribute($name) {
    if (isset($this->attributes[$name])) {
      return $this->attributes[$name];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption() {
    return $this->option;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getOuterSource() {
    // Reconstruct the opening and closing tags, but render the content.
    if (!isset($this->outerSource)) {
      $content = $this->getContent();
      $this->outerSource = "[{$this->name}{$this->argument}]{$content}[/{$this->name}]";
    }
    return $this->outerSource;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   *   If the tag does not have an assigned processor.
   */
  public function render() {
    if (!$this->processor) {
      throw new \InvalidArgumentException("Missing processor for tag [{$this->name}]");
    }
    return $this->processor->process($this);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    $content = ($this->processor ? $this->processor->prepare($this) : NULL) ?: parent::prepare();
    return "[{$this->name}{$this->argument}]{$content}[/{$this->name}]";
  }

  /**
   * {@inheritdoc}
   */
  public function isPrepared() {
    return $this->prepared;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrepared($prepared = TRUE) {
    $this->prepared = $prepared;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessor(TagProcessorInterface $processor) {
    $this->processor = $processor;
  }

}
