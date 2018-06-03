<?php

namespace Drupal\xbbcode_standard;

use Drupal\xbbcode\Parser\Tree\TagElement;
use Drupal\xbbcode\Parser\Tree\TextElement;

trait TreeEncodeTrait {

  /**
   * Concatenate the top-level text of the tree, inserting placeholders
   * for each contained tag element.
   *
   * @param array $children
   *
   * @return string[]
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
   * @param array $children
   * @param string $token
   *
   * @return \Drupal\xbbcode\Parser\Tree\TagElement
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
