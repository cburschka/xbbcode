<?php

namespace Drupal\xbbcode_standard\Plugin\XBBCode;

use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\RenderTagPlugin;

/**
 * Renders a table.
 *
 * @XBBCodeTag(
 *   id = "table",
 *   label = @Translation("Table"),
 *   description = @Translation("Renders a table with optional caption and header."),
 *   name = "table",
 * )
 */
class TableTagPlugin extends RenderTagPlugin {

  /**
   * Match a comma not followed by an odd number of backslashes.
   *
   * @var string
   */
  const SPLIT_COMMA = '/,(?!(\\\\\\\\)*\\\\)/';

  private static $alignment = ['' => 'left', '#' => 'right', '!' => 'center'];

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
  public function buildElement(TagElementInterface $tag) {
    $element['#type'] = 'table';

    if ($caption = $tag->getAttribute('caption')) {
      $element['#caption'] = $caption;
    }

    $align = [];
    if ($header = $tag->getAttribute('header')) {
      $element['#header'] = [];
      foreach (self::splitComma($header) as $cell) {
        if ($cell[0] === '!' || $cell[0] === '#') {
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

    // Strip linebreaks from the output, to avoid having them rendered as HTML.
    $element['#post_render'][] = function ($output) {
      return Markup::create(str_replace("\n", '', $output));
    };

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSample() {
    // Generate the sample here, as annotations don't do well with linebreaks.
    return $this->t("[{{ name }} caption=Title header=!Item,Color,#Amount]\nFish,Red,1\nFish,Blue,2\n[/{{ name }}]");
  }

}