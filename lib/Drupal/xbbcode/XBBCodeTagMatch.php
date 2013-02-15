<?php

namespace Drupal\xbbcode;

class XBBCodeTagMatch {
  function __construct($regex_set = NULL) {
    if ($regex_set) {
      $this->closing = $regex_set['closing'][0] == '/';
      $this->name    = strtolower($regex_set['name'][0]);
      $this->attrs   = isset($regex_set['attrs']) ? _xbbcode_parse_attrs($regex_set['attrs'][0]) : array();
      $this->option  = isset($regex_set['option']) ? $regex_set['option'][0] : NULL;
      $this->element = $regex_set[0][0];
      $this->offset  = $regex_set[0][1] + strlen($regex_set[0][0]);
      $this->start   = $regex_set[0][1];
      $this->end     = $regex_set[0][1] + strlen($regex_set[0][0]);
    }
    else {
      $this->offset = 0;
    }
    $this->content = '';
  }

  function attr($name) {
    return isset($this->attrs[$name]) ? $this->attrs[$name] : NULL;
  }

  function break_tag($tag) {
    $this->content .= $tag->element . $tag->content;
    $this->offset = $tag->offset;
  }

  function append($text, $offset) {
    $this->content .= $text;
    $this->offset = $offset;
  }

  function advance($text, $offset) {
    $this->content .= substr($text, $this->offset, $offset - $this->offset);
    $this->offset = $offset;
  }

  function revert($text) {
    $this->content = substr($text, $this->start + strlen($this->element), $this->offset - $this->start - strlen($this->element));
  }
}
