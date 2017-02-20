<?php

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
 *   sample = @Translation("[{{ name }} caption=Title header=!Item,Color,#Amount]\nFish,Red,1\nFish,Blue,2\n[/{{ name }}]")
 * )
 */
class TableTagPlugin extends TagPlugin {

  /**
   * Match a comma not followed by an odd number of backslashes.
   *
   * @var string
   */
  const SPLIT_COMMA = '/,(?!(\\\\\\\\)*\\\\)/';

  private static $alignment = ['' => 'left', '#' => 'right', '!' => 'center'];

  private $renderer;

  /**
   * Get the rendering service.
   */
  private function renderer() {
    if (!$this->renderer) {
      $this->renderer = Drupal::service('renderer');
    }
    return $this->renderer;
  }

  /**
   * Split string on commas, respecting backslash escape sequences.
   *
   * @param string $string
   *   The string to parse.
   *
   * @return array
   *   The tokens, with one level of backslash sequences stripped.
   */
  private static function splitComma($string) {
    $list = [];
    // Reverse the string in order to use a variable-length look-behind.
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
    if ($caption = $tag->getAttr('caption')) {
      $element['#caption'] = $caption;
    }

    $align = [];
    if ($header = $tag->getAttr('header')) {
      $element['#header'] = [];
      foreach (self::splitComma($header) as $cell) {
        if ($cell[0] == '!' || $cell[0] == '#') {
          list($align[], $cell) = [self::$alignment[$cell[0]], substr($cell, 1)];
        }
        else {
          $align[] = self::$alignment[''];
        }
        $element['#header'][] = $cell;
      }
      if (implode('', $element['#header']) === '') {
        unset($element['#header']);
      }
    }
    foreach (explode("\n", trim($tag->getContent())) as $i => $row) {
      $element['row-' . $i] = [];
      foreach (self::splitComma(trim($row)) as $j => $cell) {
        $element['row-' . $i][] = [
          '#markup' => Markup::create($cell),
          '#wrapper_attributes' => !empty($align[$j]) ?
            ['style' => ['text-align:' . $align[$j]]] : NULL,
        ];
      }
    }

    // Strip out linebreaks, in case they are converted to HTML.
    return str_replace("\n", '', $this->renderer()->render($element));
  }

}
