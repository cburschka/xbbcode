<?php

namespace Drupal\xbbcode_standard;

use Drupal\xbbcode\Parser\Tree\TagElement;
use Drupal\xbbcode\Parser\Tree\TextElement;

/**
 * Static helper functions for tag plugins that parse their top-level text.
 *
 * Tags which parse their own content (eg [list] and [table]) are hindered by
 * tags nested inside them. If they parsed the rendered output, they'd need to
 * take responsibility for markup integrity. If they used the source, they'd
 * need to re-run the whole parser on it.
 *
 * Instead, these helpers will reversibly "flatten" a parse tree, concatenating
 * top-level text elements and replacing nested tags with placeholder values.
 */
trait TreeEncodeTrait {

  /**
   * Concatenate the top-level text of the tree.
   *
   * The text elements are concatenated, inserting placeholders for each tag
   * element contained in the children.
   *
   * @param array $children
   *   A sequence of elements representing the children of a single element.
   *
   * @return string[]
   *   An array with two values:
   *   - A unique delimiter token.
   *   - A concatenated string, in which the i-th tag element is replaced with
   *     a placeholder string "{token:i}", and text elements are left in place.
   */
  protected static function encodeTree(array $children): array {
    $output = [];
    foreach ($children as $i => $child) {
      if ($child instanceof TextElement) {
        $output[] = $child->getText();
      }
      else {
        $output[] = $i;
      }
    }
    $text = implode('', $output);

    $token = 100000;
    while (strpos($text, $token) !== FALSE) {
      $token++;
    }

    foreach ($output as $i => $item) {
      if (\is_int($item)) {
        $output[$i] = "{{$token}:{$item}}";
      }
    }

    return [$token, implode('', $output)];
  }

  /**
   * Decode a part of the encoded tree.
   *
   * @param string $cell
   *   The text (or part of the text) of the encoded tree.
   * @param array $children
   *   The children which were previously encoded.
   * @param string $token
   *   The token used as a placeholder.
   *
   * @return \Drupal\xbbcode\Parser\Tree\TagElement
   *   A pseudo-tag element (empty name) containing the part of the tree
   *   represented by $cell.
   */
  protected static function decodeTree($cell, array $children, $token): TagElement {
    $items = preg_split("/{{$token}:(\d+)}/",
                        $cell,
                        NULL,
                        PREG_SPLIT_DELIM_CAPTURE);
    $tree = new TagElement('', '', '');

    foreach ($items as $i => $item) {
      if ($item !== '') {
        $tree->append(($i % 2) ? $children[$item] : new TextElement($item));
      }
    }

    return $tree;
  }

}
