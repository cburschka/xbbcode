<?php

namespace Drupal\xbbcode;

class XBBCodeTagMatch {
  function __construct(array $regex_set = NULL) {
    if ($regex_set) {
      $this->closing = $regex_set['closing'][0] == '/';
      $this->name    = strtolower($regex_set['name'][0]);
      $this->attrs   = isset($regex_set['attrs']) ? $this->parseAttrs($regex_set['attrs'][0]) : [];
      $this->option  = isset($regex_set['option']) ? $regex_set['option'][0] : NULL;
      $this->element = $regex_set[0][0];
      $this->offset  = $regex_set[0][1] + strlen($regex_set[0][0]);
      $this->start   = $regex_set[0][1];
      $this->end     = $regex_set[0][1] + strlen($regex_set[0][0]);
    }
    else {
      $this->offset = 0;
    }
    $this->content = [];
  }

  function attr($name) {
    return isset($this->attrs[$name]) ? $this->attrs[$name] : NULL;
  }

  function breakTag(XBBCodeTagMatch $tag) {
    $this->content = array_merge($this->content, [$tag->element], $tag->content);
    $this->offset = $tag->offset;
  }

  function append(XBBCodeTagMatch $tag, $offset) {
    $this->content[] = $tag;
    $this->offset = $offset;
  }

  function advance($text, $offset) {
    $this->content[] = substr($text, $this->offset, $offset - $this->offset);
    $this->offset = $offset;
  }

  /**
   * Parse a string of attribute assignments.
   *
   * @param $string
   *   The string containing the arguments, including initial whitespace.
   *
   * @return
   *   An associative array of all attributes.
   */
  private static function parseAttrs($string) {
    preg_match_all('/' . XBBCODE_RE_ATTR . '/', $string, $assignments, PREG_SET_ORDER);
    $attrs = [];
    foreach ($assignments as $assignment) {
      $attrs[$assignment['key']] = $assignment['value'];
    }
    return $attrs;
  }
}
