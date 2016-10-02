<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Element.
 */

namespace Drupal\xbbcode;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Plugin\TagPluginInterface;

/**
 * A node in the tag tree.
 */
class Element implements ElementInterface {
  const RE_ATTR = '/(?<=\s)(?<key>\w+)=(?:\'(?<val1>(?:[^\\\\\']|\\\\[\\\\\'])*)\'|\"(?<val2>(?:[^\\\\\"]|\\\\[\\\\\"])*)\"|(?<val3>(?:[^\\\\\'\"\s\]]|\\\\[\\\\\'\"\s\]])*))(?=\s|$)/';

  private $name;
  private $extra;
  private $attrs = [];
  private $option = NULL;
  private $start;
  private $end;
  private $text;
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
   * @param TagPluginInterface $plugin
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
      $this->attrs = self::parseAttrs($this->extra);
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
  private static function parseAttrs($string) {
    preg_match_all(self::RE_ATTR, $string, $assignments, PREG_SET_ORDER);
    $attrs = [];
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
      $attrs[$assignment['key']] = $value;
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
          $this->renderedTags = array_merge($this->renderedTags, $child->getRenderedTags());
        }
        else {
          $this->content .= $child;
        }
      }
      $this->content = Markup::create($this->content);
    }
    return $this->content;
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
  public function source() {
    if (!isset($this->source)) {
      $this->source = substr($this->text, $this->start, $this->end - $this->start);
    }
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function outerSource() {
    // Reconstruct the opening and closing tags, but render the content.
    return Markup::create('[' . $this->name
      . Html::escape($this->extra)
      . ']' . $this->content() . "[/{$this->name}]");
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
