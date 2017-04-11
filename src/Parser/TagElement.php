<?php

namespace Drupal\xbbcode\Parser;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;

/**
 * A BBCode tag element.
 */
class TagElement extends NodeElement implements TagElementInterface {

  /**
   * A regular expression that parses the tag's attribute string.
   *
   * @var string
   */
  const RE_ATTR = '/(?<=\s)(?<key>\w+)=(?:\'(?<val1>(?:[^\\\\\']|\\\\[\\\\\'])*)\'|\"(?<val2>(?:[^\\\\\"]|\\\\[\\\\\"])*)\"|(?<val3>(?:[^\\\\\'\"\s\]]|\\\\[\\\\\'\"\s\]])*))(?=\s|$)/';

  /**
   * The processor handling this element.
   *
   * @var \Drupal\xbbcode\Parser\TagProcessorInterface
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
  public function __construct($name, $argument, $source, TagProcessorInterface $processor, $prepared) {
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
      $this->attributes = self::parseAttributes($argument);
    }
  }

  /**
   * Parse a string of attribute assignments.
   *
   * @param string $string
   *   The string containing the arguments, including initial whitespace.
   *
   * @return array
   *   An associative array of all attributes.
   */
  private static function parseAttributes($string) {
    $assignments = [];
    preg_match_all(self::RE_ATTR, $string, $assignments, PREG_SET_ORDER);
    $attributes = [];
    foreach ($assignments as $assignment) {
      // Strip backslashes from the escape sequences in each case.
      if (!empty($assignment['val1'])) {
        // Single-quoted values escape single quotes and backslashes.
        $value = preg_replace('/\\\\([\\\\\'])/', '\1', $assignment['val1']);
      }
      elseif (!empty($assignment['val2'])) {
        // Double-quoted values escape double quotes and backslashes.
        $value = preg_replace('/\\\\([\\\\\"])/', '\1', $assignment['val2']);
      }
      else {
        // Unquoted values must escape quotes, spaces, backslashes and brackets.
        $value = preg_replace('/\\\\([\\\\\'\"\s\]])/', '\1', $assignment['val3']);
      }
      $attributes[$assignment['key']] = $value;
    }
    return $attributes;
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
      $extra = Html::escape($this->argument);
      $content = $this->getContent();
      $outerSource = "[{$this->name}{$extra}]{$content}[/{$this->name}]";
      $this->outerSource = Markup::create($outerSource);
    }
    return $this->outerSource;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $this->renderedTags[$this->name] = $this->name;
    return $this->processor->process($this);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    $extra = base64_encode($this->argument);
    $content = $this->processor->prepare($this);
    if ($content === NULL) {
      $content = parent::prepare();
    }
    return "[{$this->name}={$extra}]{$content}[/{$this->name}]";
  }

  /**
   * {@inheritdoc}
   */
  public function isPrepared() {
    return $this->prepared;
  }

}
