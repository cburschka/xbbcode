<?php

namespace Drupal\xbbcode;

class XBBCodeRootElement extends XBBCodeTagMatch {
  function __construct() {
    $this->offset = 0;
    $this->start = 0;
    $this->content = '';
  }
}
