<?php

namespace Drupal\xbbcode_standard\Plugin\XBBCode;

use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Processor\CallbackTagProcessor;
use Drupal\xbbcode\Parser\Tree\TagElement;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Parser\Tree\TextElement;
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

  const ALIGNMENT = [
    '#' => 'right',
    '!' => 'center',
    ''  => '',
  ];

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
      /** @var string[] $headers */
      list ($headers) = self::tabulateText($header) ?: [[$header]];
      foreach ($headers as $i => $cell) {
        if ($cell[0] === '!' || $cell[0] === '#') {
          $align[$i] = self::ALIGNMENT[$cell[0]];
          $headers[$i] = substr($cell, 1);
        }
        else {
          $align[$i] = self::ALIGNMENT[''];
        }
        $headers[$i] = stripslashes($headers);
      }

      // If the header contains no labels, don't add a header row.
      if (implode('', $headers)) {
        $element['#header'] = $headers;
      }
    }

    foreach (static::tabulateTree($tag->getChildren()) as $i => $row) {
      foreach ($row as $j => $cell) {
        $element["row-$i"][$j] = [
          '#markup' => Markup::create($cell->getContent()),
          '#wrapper_attributes' => !empty($align[$j]) ?
            ['style' => ['text-align:' . $align[$j]]] : NULL,
        ];
      }
    }

    return $element;
  }

  /**
   * Split an array of elements into rows and cells.
   *
   * @param \Drupal\xbbcode\Parser\Tree\ElementInterface[] $children
   *
   * @return \Drupal\xbbcode\Parser\Tree\TagElementInterface[][]
   */
  private static function tabulateTree(array $children) {
    $output = [];
    /** @var \Drupal\xbbcode\Parser\Tree\NodeElementInterface[] $row */
    $row = [$cell = self::createCell()];

    foreach ($children as $child) {
      if (
        $child instanceof TextElement &&
        $data = self::tabulateText($child->getText())
      ) {
        // Start with the first line here.
        do {
          $line = array_shift($data);
          do {
            $cell->append(new TextElement(stripslashes(array_shift($line))));
          } while ($line && $row[] = $cell = self::createCell());
        } while (
          // If there are more lines, start a new row and repeat.
          $data &&
          ($output[] = $row) &&
          ($row = [$cell = self::createCell()])
        );
      }
      else {
        $cell->append($child);
      }
    }

    // If the final row isn't empty, add it.
    if ($row && $row[0]->getChildren()) {
      $output[] = $row;
    }
    return $output;
  }

  /**
   * Create a virtual element for grouping the children.
   *
   * @return \Drupal\xbbcode\Parser\Tree\TagElementInterface
   */
  private static function createCell() {
    $tag = new TagElement('td', '', '');
    $tag->setProcessor(new CallbackTagProcessor(function (TagElementInterface $tag) {
      return $tag->getContent();
    }));
    return $tag;
  }

  /**
   * Tabulate a text into lines and columns.
   *
   * @param string $text
   *   The text to tabulate.
   *
   * @return string[][]|bool
   *   The tabulated array, or false if it is atomic.
   */
  private static function tabulateText($text) {
    $tabulated = [];
    $buffer = [];
    // Tokenize the string on linebreaks and commas, trimming HTML linebreaks.
    $text = trim(preg_replace('/<br\s*\/?>/', '', $text)) . "\n";
    if (1 >= preg_match_all('/
          ((?:\\\\.|[^\\\\\n,])*)
          ([,\n])
          /x', $text, $match, PREG_SET_ORDER)) {
      return FALSE;
    }

    foreach ((array) $match as $token) {
      $buffer[] = $token[1];
      if ($token[2] !== ',') {
        $tabulated[] = $buffer;
        $buffer = [];
      }
    }

    if ($buffer) {
      $tabulated[] = $buffer;
    }
    return $tabulated;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSample() {
    // Generate the sample here, as annotations don't do well with linebreaks.
    return $this->t("[{{ name }} caption=Title header=!Item,Color,#Amount]\nFish,Red,1\nFish,Blue,2\n[/{{ name }}]");
  }

}
