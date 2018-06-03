<?php

namespace Drupal\xbbcode_standard\Plugin\XBBCode;

use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\RenderTagPlugin;
use Drupal\xbbcode_standard\TreeEncodeTrait;

/**
 * Renders a table.
 *
 * @XBBCodeTag(
 *   id = "table",
 *   label = @Translation("Table"),
 *   description = @Translation("Table with optional caption and header."),
 *   name = "table",
 * )
 */
class TableTagPlugin extends RenderTagPlugin {

  use TreeEncodeTrait;

  /**
   * The alignment indicators.
   */
  public const ALIGNMENT = [
    '~' => 'left',
    '!' => 'center',
    '#' => 'right',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildElement(TagElementInterface $tag): array {
    $element['#type'] = 'table';

    if ($caption = $tag->getAttribute('caption')) {
      $element['#caption'] = $caption;
    }

    $alignments = [];
    if ($header = $tag->getAttribute('header') ?: $tag->getOption()) {
      /** @var string[] $headers */
      $headers = self::tabulateText($header)[0] ?: [$header];
      foreach ($headers as $i => $cell) {
        // Check if the label starts with an alignment symbol.
        if ($cell && array_key_exists($cell[0], self::ALIGNMENT)) {
          $alignments[$i] = self::ALIGNMENT[$cell[0]];
          $headers[$i] = substr($cell, 1);
        }
        else {
          // Trim leading whitespace.
          $headers[$i] = ltrim($cell);
          $alignments[$i] = NULL;
        }
      }
      if (implode('', $headers)) {
        $element['#header'] = $headers;
      }
    }

    foreach (static::tabulateTree($tag->getChildren()) as $i => $row) {
      foreach ((array) $row as $j => $cell) {
        $content = $cell->getContent();

        // If not explicitly aligned, auto-align numeric strings.
        if (!isset($alignments[$j])) {
          $alignments[$j] = '';
        }
        $align = $alignments[$j] ?: (is_numeric($content) ? 'right' : NULL);
        $element["row-$i"][$j] = [
          '#markup' => Markup::create($content),
          '#wrapper_attributes' => $align ?
            ['style' => ['text-align:' . $align]] : NULL,
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSample(): string {
    // Generate the sample here, as annotations don't do well with linebreaks.
    return $this->t(
      '[{{ name }} caption=Title header=!Item,Color,#Amount]
Fish,Red,1
Fish,Blue,2
[/{{ name }}]
[{{ name }}=~Left,Auto,!Center,#Right]
One,Two,Three,"Four, Five"
1,2,3,4
[/{{ name }}]
');
  }

  /**
   * @param array $children
   *
   * @return \Drupal\xbbcode\Parser\Tree\TagElementInterface[][]
   */
  private static function tabulateTree(array $children): array {
    $table = [];
    [$token, $text] = static::encodeTree($children);

    foreach (self::tabulateText($text) as $i => $row) {
      foreach ((array) $row as $j => $cell) {
        $table[$i][$j] = self::decodeTree($cell, $children, $token);
      }
    }

    return $table;
  }

  /**
   * Tabulate a text into lines and columns.
   *
   * @param string $text
   *   The text to tabulate.
   *
   * @return string[][]
   *   The tabulated array, or false if it is atomic.
   */
  private static function tabulateText($text): array {
    // Trim, and strip linebreaks before newlines.
    $trimmed = preg_replace('/<br\s*\/?>\n/', "\n", $text);
    $breaks = $trimmed !== $text;
    $text = trim($trimmed);

    // Tokenize on linebreaks and commas. Collapse multiple linebreaks.
    preg_match_all("/
      (?:
        (?'quote'['\"]|&quot;|&\#039;)
        (?'quoted'
          (?:\\\\.|(?!\\\\|\\k'quote')[^\\\\])*
        )
        \\k'quote'
        |
        (?'unquoted'
          (?:\\\\.|[^\\\\,\\v])*
        )
      )
      (?'delimiter',|\\v+|$)
      /sx", $text, $match, PREG_SET_ORDER);
    array_pop($match);

    $rows = [];
    $row = [];
    foreach ((array) $match as $token) {
      $value = stripslashes($token['quoted'] ?: $token['unquoted']);
      // Reinsert HTML linebreaks, if we removed them.
      if ($breaks) {
        $value = nl2br($value);
      }

      $row[] = $value;

      // Unless it is a column delimiter, end the row.
      if ($token['delimiter'] !== ',') {
        $rows[] = $row;
        $row = [];
      }
    }

    return $rows;
  }

}
