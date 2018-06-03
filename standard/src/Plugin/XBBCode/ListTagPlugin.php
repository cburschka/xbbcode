<?php

namespace Drupal\xbbcode_standard\Plugin\XBBCode;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\RenderTagPlugin;
use Drupal\xbbcode_standard\TreeEncodeTrait;

/**
 * Renders a list.
 *
 * @XBBCodeTag(
 *   id = "list",
 *   label = @Translation("List"),
 *   description = @Translation("List with optional style."),
 *   name = "list",
 * )
 */
class ListTagPlugin extends RenderTagPlugin {

  use TreeEncodeTrait;

  /**
   * {@inheritdoc}
   */
  public function buildElement(TagElementInterface $tag): array {
    $element['#theme'] = 'item_list';
    $style = $tag->getOption() ?: $tag->getAttribute('style');

    [$numbered, $style] = static::validateStyle($style);
    if ($numbered) {
      $element['#list_type'] = 'ol';
    }
    if ($style) {
      $element['#attributes']['style'] = 'list-style-type: ' . $style;
    }

    foreach (self::splitContent($tag->getChildren()) as $i => $item) {
      $element['#items'][$i] = Markup::create($item->getContent());
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSample(): string {
    return $this->t('[{{ name }}=lower-roman]
[*] One
[*] Two
[*] Three
[/{{ name }}]');
  }

  /**
   * @param string $style
   *
   * @return array
   */
  protected static function validateStyle($style): array {
    // The predefined un-ordered styles.
    if (\in_array($style, ['disc', 'circle', 'square', 'none'], TRUE)) {
      return [FALSE, $style];
    }

    // If the style contains no raw HTML characters, decode any character entities.
    if (!preg_match('/\'"<>/', $style)) {
      $style = Html::decodeEntities($style);
    }
    $style = trim($style);

    // Match any quoted string.
    if (preg_match('/
    (?\'quote\'[\'"])
      \\\\
      (?:
        [0-9a-fA-F]{1,6}  # 1-6 hex digits preceded by a backslash.
        |
        [^0-9a-fA-F]      # any other character preceded by a backslash.
      )
      |
      (?!\\k\'quote\')[^\\\\] # any permissible non-backslash character.
    \\k\'quote\'
    /x', $style)) {
      return [FALSE, $style];
    }

    // Match any expression.
    if (preg_match('/
    (?:
      [^"\';]             # anything other than quotes or semicolon.
      |
      \\\\
      (?:
        [0-9a-fA-F]{1,6}  # 1-6 hex digits preceded by a backslash.
        |
        [^0-9a-fA-F]      # any other character preceded by a backslash.
      )
      |
      (?\'quote\'[\'"])
        \\\\
        (?:
          [0-9a-fA-F]{1,6}  # 1-6 hex digits preceded by a backslash.
          |
          [^0-9a-fA-F]      # any other character preceded by a backslash.
        )
        |
        (?!\\k\'quote\')[^\\\\] # any permissible non-backslash character.
      \\k\'quote\'
    )*
    /x', $style)) {
      return [TRUE, $style];
    }

    return [FALSE, ''];
  }

  /**
   * Split the tag's children into list items.
   *
   * @param \Drupal\xbbcode\Parser\Tree\ElementInterface[] $children
   *
   * @return \Drupal\xbbcode\Parser\Tree\NodeElementInterface[]
   */
  protected static function splitContent(array $children): array {
    [$token, $text] = static::encodeTree($children);

    // Trim, and strip linebreaks before newlines.
    $trimmed = preg_replace('/<br\s*\/?>\n/', "\n", $text);
    $breaks = $trimmed !== $text;
    $text = trim($trimmed);

    // Split on [*] at the start of lines.
    $items = preg_split('/^\s*\[\*\]\s*/m', $text);
    array_shift($items);

    foreach ($items as $i => $item) {
      $item = trim($item);
      if ($breaks) {
        $item = nl2br($item);
      }
      $items[$i] = static::decodeTree($item, $children, $token);
    }

    return $items;
  }

}
