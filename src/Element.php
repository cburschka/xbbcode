<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Element.
 */

namespace Drupal\xbbcode;

/**
 * A node in the tag tree.
 */
class Element implements ElementInterface {
  const RE_ATTR = '/(?<=\s)(?<key>\w+)=(?:\'(?<val1>(?:[^\\\\\']|[^\\\\](?:\\\\\\\\)*\\\\\')*)\'|\"(?<val2>(?:[^\\\\\"]|[^\\\\](?:\\\\\\\\)*\\\\\")*)\"|(?=[^\'"\s])(?<val3>(?:[^\\\\\s]|(?:\\\\\\\\)*\\\\\s)*)(?=\s|$))(?=\s|$)/';

  private $name;
  private $extra;
  private $attr = [];
  private $option = NULL;
  private $start;
  private $end;
  private $selfclosing;
  private $text;
  private $children = [];
  protected $rendered_tags = [];
  public $index;

  /**
   * Construct an element.
   *
   * @param array $regex_set
   *   The regex match for this tag.
   * @param string $text
   *   The entire source text.
   * @param TagPluginInterface $plugin
   *   The plugin responsible for processing the tag.
   */
  public function __construct(array $regex_set, $text, TagPluginInterface $plugin) {
    $this->name = $match['name'][0];
    $this->extra = base64_decode($match['extra'][0]);
    $this->offset = $match[0][1];
    $this->start = $match['start'][0];
    $this->end = $match['end'][0];
    $this->selfclosing = !empty($match['selfclosing']);
    if ($this->extra && $this->extra[0] == '=') {
      $this->option = preg_replace('/\\\\([\\]\\\\])/', '\1', substr($this->extra, 1));
    }
    else {
      $this->attr = self::parseAttrs($this->extra);
    }
    $this->index = $start;
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
  private static function parseAttrs($string) {
    preg_match_all(self::RE_ATTR, $string, $assignments, PREG_SET_ORDER);
    $attrs = [];
    foreach ($assignments as $assignment) {
      $value = $assignment['val1'] . $assignment['val2'] . $assignment['val3'];
      $attrs[$assignment['key']] = value;
    }
    return $attrs;
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
  public function attr($name = NULL) {
    return $name ? isset($this->attrs[$name]) ? $this->attrs[$name] : NULL : $this->attrs;
  }

  /**
   * {@inheritdoc}
   */
  public function content() {
    if (!isset($this->content)) {
      $this->content = '';
      foreach ($this->children as $child) {
        if ($child instanceof self) {
          $this->content .= $child->render();
          $this->rendered_tags = array_merge($this->rendered_tags, $child->rendered_tags);
        }
        else {
          $this->content .= $child;
        }
      }
    }
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function source() {
    if (!isset($this->source)) {
      $this->source = substr($text, $this->start, $this->end - $this->start);
    }
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function option() {
    return $this->option;
  }

  /**
   * {@inheritdoc}
   */
  public function outerSource() {
    // Reconstruct the opening and closing tags, but render the content.
    return "[$name$extra]" . ($this->end > $this->start ? ($this->content() . "[/$name]") : '');
  }

  /**
   * Render the tag using the assigned plugin.
   *
   * @return string
   *   The rendered output.
   */
  protected function render() {
    $this->rendered_tags[$this->name] = $this->name;
    return $this->plugin->process($this);
  }

  /**
   *
   */
  public function getRenderedTags() {
    return $this->rendered_tags;
  }

}
