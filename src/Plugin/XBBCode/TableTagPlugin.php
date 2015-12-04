<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\XBBCode\TableTagPlugin.
 */

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\ElementInterface;
use Drupal\xbbcode\Plugin\TagPlugin;

/**
 * Renders a table.
 *
 * @XBBCodeTag(
 *   id = "table",
 *   label = @Translation("Table"),
 *   description = @Translation("Renders a table with optional caption and header."),
 *   name = "table",
 *   sample = @Translation("[{{ name }} caption=Title header=!Item,Color,#Amount]
    Fish,Red,1
    Fish,Blue,2
  [/{{ name }}]")
 * )
 */
class TableTagPlugin extends TagPlugin {
  /**
   * Split only on commas not followed by an odd number of backslashes.
   */
  const SPLIT_COMMA = '/,(?!(\\\\\\\\)*\\\\)/';
  static $alignment = ['' => 'left', '#' => 'right', '!' => 'center'];

  private $renderer;

  private function renderer() {
    if (!$this->renderer) {
      $this->renderer = Drupal::service('renderer');
    }
    return $this->renderer;
  }
  
  private static function splitComma($string) {
    $list = [];
    foreach (preg_split(self::SPLIT_COMMA, strrev($string)) as $token) {
      $list[] = stripslashes(strrev($token));
    }
    return array_reverse($list);
  }
  
  /**
   * {@inheritdoc}
   */
  public function process(ElementInterface $tag) {
    $element = ['#type' => 'table'];
    if ($caption = $tag->attr('caption')) {
      $element['#caption'] = $caption;
    }

    $align = [];
    if ($header = $tag->attr('header')) {
      $element['#header'] = [];
      foreach ($this->splitComma($header) as $cell) {
        if ($cell[0] == '!' || $cell[0] == '#') {
          list($align[], $cell) = [self::$alignment[$cell[0]], substr($cell, 1)];
        } else {
          $align[] = self::$alignment[''];
        }
        $element['#header'][] = $cell;
      }
      if (implode('', $element['#header']) === '') {
        unset($element['#header']);
      }
    }
    foreach (explode("\n", trim($tag->content())) as $i => $row) {
      $element['row-' . $i] = [];
      foreach ($this->splitComma(trim($row)) as $j => $cell) {
        $element['row-' . $i][] = [
          '#markup' => Markup::create($cell),
          '#wrapper_attributes' => $align[$j] ? 
            ['style' => ['text-align:' . $align[$j]]] : NULL,
        ];
      }
    }
    
    // Strip out linebreaks, in case they are converted to HTML.
    return str_replace("\n", '', $this->renderer()->render($element));
  }
}
