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
   * The tag's parent element.
   *
   * @var \Drupal\xbbcode\Parser\Tree\NodeElementInterface
   */
  private $parent;

  /**
   * Opening tag name.
   *
   * @var string
   */
  private $openingName;

  /**
   * Closing tag name.
   *
   * @var string
   */
  private $closingName;

  /**
   * TagElement constructor.
   *
   * @param string $opening
   *   The opening tag name.
   * @param string $argument
   *   The argument (everything past the tag name)
   * @param string $source
   *   The source of the content.
   */
  public function __construct(string $opening, string $argument, string $source) {
    $this->name = mb_strtolower($opening);
    $this->openingName = $opening;
    $this->argument = $argument;
    $this->source = $source;

    if ($argument && $argument[0] === '=') {
      $this->option = XBBCodeParser::parseOption($argument);
    }
    else {
      $this->attributes = XBBCodeParser::parseAttributes($argument);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpeningName(): string {
    return $this->openingName;
  }

  /**
   * {@inheritdoc}
   */
  public function getClosingName(): string {
    return $this->closingName;
  }

  /**
   * {@inheritdoc}
   */
  public function setClosingName(string $closing): TagElementInterface {
    $this->closingName = $closing;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument(): string {
    return $this->argument;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribute(string $name): ?string {
    return $this->attributes[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttribute(string $name, string $value = NULL): void {
    $this->attributes[$name] = $value;
    if ($value === NULL) {
      unset($this->attributes[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes(): array {
    return $this->attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttributes(array $attributes): void {
    $this->attributes = $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption(): string {
    return $this->option ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setOption(string $value): void {
    $this->option = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource(): string {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource(string $source): void {
    $this->source = $source;
  }

  /**
   * {@inheritdoc}
   */
  public function getOuterSource(): string {
    // Reconstruct the opening and closing tags, but render the content.
    if (!isset($this->outerSource)) {
      $content = $this->getContent();
      $this->outerSource = "[{$this->openingName}{$this->argument}]{$content}[/{$this->closingName}]";
    }
    return $this->outerSource;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent(): NodeElementInterface {
    return $this->parent;
  }

  /**
   * {@inheritdoc}
   */
  public function setParent(NodeElementInterface $parent): void {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   *   If the tag does not have an assigned processor.
   */
  public function render(): OutputElementInterface {
    if (!$this->getProcessor()) {
      throw new \InvalidArgumentException("Missing processor for tag [{$this->name}]");
    }
    return $this->getProcessor()->process($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessor(): TagProcessorInterface {
    return $this->processor;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessor(TagProcessorInterface $processor): void {
    $this->processor = $processor;
  }

}
