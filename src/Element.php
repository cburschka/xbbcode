<?php

namespace Drupal\xbbcode;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Plugin\TagPluginInterface;

/**
 * A node in the tag tree.
 */
class Element implements ElementInterface {

  /**
   * A regular expression that parses the tag's attribute string.
   *
   * @var string
   */
  const RE_ATTR = '/(?<=\s)(?<key>\w+)=(?:\'(?<val1>(?:[^\\\\\']|\\\\[\\\\\'])*)\'|\"(?<val2>(?:[^\\\\\"]|\\\\[\\\\\"])*)\"|(?<val3>(?:[^\\\\\'\"\s\]]|\\\\[\\\\\'\"\s\]])*))(?=\s|$)/';

  private $name;
  private $extra;
  private $attributes = [];
  private $option = NULL;
  private $start;
  private $end;
  private $text;

  /**
   * The plugin interface handling this element.
   *
   * @var \Drupal\xbbcode\Plugin\TagPluginInterface
   */
  private $plugin;

  private $children = [];
  private $renderedTags = [];
  public $index;

  /**
   * Construct an element out of a regex match.
   *
   * @param array $regex_set
   *   The data returned from preg_match() for a single match, including
   *   string offsets.
   * @param string $text
   *   The entire source text.
   * @param \Drupal\xbbcode\Plugin\TagPluginInterface $plugin
   *   The plugin responsible for processing the tag.
   */
  public function __construct(array $regex_set, $text, TagPluginInterface $plugin) {
    $this->name = $regex_set['name'];
    $this->extra = base64_decode($regex_set['extra'][0]);
    $this->index = $regex_set[0][1] + strlen($regex_set[0][0]);
    $this->start = $regex_set['start'][0];
    $this->end = $regex_set['end'][0];
    if ($this->extra && $this->extra[0] == '=') {
      $this->option = preg_replace('/\\\\([\\]\\\\])/', '\1', substr($this->extra, 1));
    }
    else {
      $this->attributes = self::parseAttributes($this->extra);
    }
    $this->text = $text;
    $this->plugin = $plugin;
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
   * Append a completed element to the content.
   *
   * @param string|Element $node
   *   The node to be appended.
   * @param int $index
   *   The end of the node that was appended.
   */
  public function append($node, $index = NULL) {
    $this->children[] = $node;
    if ($index) {
      $this->index = $index;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribute($name) {
    return $this->attributes[$name] ?? NULL;
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
  public function getContent() {
    if (!isset($this->content)) {
      $children = $this->children;
      foreach ($children as $i => $child) {
        if ($child instanceof self) {
          $children[$i] = $child->render();
          $this->renderedTags = array_merge($this->renderedTags, $child->getRenderedTags());
        }
      }
      $this->content = Markup::create(implode('', $children));
    }
    return $this->content;
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
    if (!isset($this->source)) {
      $this->source = substr($this->text, $this->start, $this->end - $this->start);
    }
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getOuterSource() {
    // Reconstruct the opening and closing tags, but render the content.
    if (!isset($this->outerSource)) {
      $extra = Html::escape($this->extra);
      $content = $this->getContent();
      $outerSource = "[{$this->name}{$extra}]{$content}[/{$this->name}]";
      $this->outerSource = Markup::create($outerSource);
    }
    return $this->outerSource;
  }

  /**
   * Render the tag using the assigned plugin.
   *
   * @return string
   *   The rendered output.
   */
  protected function render() {
    $this->renderedTags[$this->name] = $this->name;
    return $this->plugin->process($this);
  }

  /**
   * Get the set of tag names rendered, including this tag itself.
   *
   * @return array
   *   The set of tags.
   */
  public function getRenderedTags() {
    return $this->renderedTags;
  }

}
