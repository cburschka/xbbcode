<?php

namespace Drupal\xbbcode;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Augmented version of Xss that defuses markup instead of removing it.
 */
class XssEscape extends Xss {

  /**
   * {@inheritdoc}
   */
  protected static function split($string, $html_tags, $class): string {
    // Sanity check.
    if (!is_subclass_of($class, Xss::class)) {
      $class = static::class;
    }

    $output = parent::split($string, $html_tags, $class);

    if ($output !== '') {
      return $output;
    }

    if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9\-]+)\s*([^>]*)>?|(<!--.*?-->)$%', $string, $matches)) {
      // Seriously malformed.
      return Html::escape($string);
    }

    $elem = $matches[2];

    // When in whitelist mode, an element is disallowed when not listed.
    if ($class::needsRemoval($html_tags, $elem)) {
      return Html::escape($string);
    }

    // This should be unreachable.
    return '';
  }

}
