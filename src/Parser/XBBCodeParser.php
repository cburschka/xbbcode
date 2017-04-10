<?php

namespace Drupal\xbbcode\Parser;

use Drupal\xbbcode\PluginCollectionInterface;

/**
 * The standard XBBCode parser.
 */
class XBBCodeParser implements ParserInterface {
  const RE_TAG = '/\[(?<closing>\/)(?<name1>[a-z0-9_]+)\]|\[(?<name2>[a-z0-9_]+)(?<arg>(?<attr>(?:\s+(?<key>\w+)=(?:\'(?<val1>(?:[^\\\\\']|\\\\[\\\\\'])*)\'|\"(?<val2>(?:[^\\\\\"]|\\\\[\\\\\"])*)\"|(?=[^\'"\s])(?<val3>(?:[^\\\\\'\"\s\]]|\\\\[\\\\\'\"\s\]])*)))*)|=(?<option>(?:[^\\\\\]]|\\\\[\\\\\]])*))\]/';

  /**
   * The plugins for rendering.
   *
   * @var \Drupal\xbbcode\PluginCollectionInterface
   */
  protected $plugins;

  /**
   * XBBCodeParser constructor.
   *
   * @param \Drupal\xbbcode\PluginCollectionInterface $plugins
   *   The plugins for rendering.
   */
  public function __construct(PluginCollectionInterface $plugins) {
    $this->plugins = $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($text, $prepared = FALSE) {
    $tokens = static::tokenize($text, $this->plugins);
    $tokens = static::validateTokens($tokens);
    if ($prepared) {
      $tokens = static::unpackArguments($tokens);
    }
    return $this->buildTree($text, $tokens);
  }

  /**
   * Find the opening and closing tags in a text.
   *
   * @param string $text
   *   The source text.
   * @param array|\ArrayAccess $allowed
   *   An array keyed by tag name, with non-empty values.
   *
   * @return array[]
   *   The tokens.
   */
  public static function tokenize($text, $allowed) {
    // Find all opening and closing tags in the text.
    $matches = [];
    preg_match_all(self::RE_TAG,
                   $text,
                   $matches,
                   PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $tokens = [];

    foreach ($matches as $i => $match) {
      $name = !empty($match['name1'][0]) ? $match['name1'][0] :
        $match['name2'][0];
      if (empty($allowed[$name])) {
        continue;
      }

      $start = $match[0][1];
      $tokens[] = [
        'name'     => $name,
        'start'    => $start,
        'end'      => $start + strlen($match[0][0]),
        'arg'      => !empty($match['arg'][0]) ? $match['arg'][0] : NULL,
        'closing'  => !empty($match['closing'][0]),
        'prepared' => FALSE,
      ];
    }

    return $tokens;
  }

  /**
   * Validate the nesting, and remove tokens that are not nested.
   *
   * @param array[] $tokens
   *   The tokens.
   *
   * @return array[]
   *   A well-formed list of tokens.
   */
  public static function validateTokens(array $tokens) {
    // Initialize the counter for each tag name.
    $counter = [];
    foreach ($tokens as $token) {
      $counter[$token['name']] = 0;
    }

    $stack = [];

    foreach ($tokens as $i => $token) {
      if ($token['closing']) {
        if ($counter[$token['name']] > 0) {
          // Pop the stack until a matching token is reached.
          do {
            $last = array_pop($stack);
            $counter[$last['name']]--;
          } while ($last['name'] !== $token['name']);

          $tokens[$last['id']] += [
            'length' => $token['start'] - $last['end'],
            'verified' => TRUE,
          ];

          $tokens[$i]['verified'] = TRUE;
        }
      }
      else {
        // Stack this token together with its position.
        $stack[] = $token + ['id' => $i];
        $counter[$token['name']]++;
      }
    }

    // Filter the tokens.
    return array_filter($tokens, function ($token) {
      return !empty($token['verified']);
    });
  }

  /**
   * Decode the base64-encoded argument of each token.
   *
   * @param array[] $tokens
   *   The tokens.
   *
   * @return array[]
   *   The processed tokens.
   */
  public static function unpackArguments(array $tokens) {
    return array_map(function ($token) {
      $token['arg'] = base64_decode(substr($token['arg'], 1));
      $token['prepared'] = TRUE;
      return $token;
    }, $tokens);
  }

  /**
   * Convert a well-formed list of tokens into a tree.
   *
   * @param string $text
   *   The source text.
   * @param array[] $tokens
   *   The tokens.
   *
   * @return \Drupal\xbbcode\Parser\NodeElement
   *   The element representing the tree.
   */
  public function buildTree($text, array $tokens) {
    /** @var \Drupal\xbbcode\Parser\NodeElement[] $stack */
    $stack = [new RootElement()];

    // Tracks the current position in the text.
    $index = 0;

    foreach ($tokens as $token) {
      // Append any text before the token to the parent.
      $leading = substr($text, $index, $token['start'] - $index);
      if ($leading) {
        end($stack)->append(new TextElement($leading));
      }
      // Advance to the end of the token.
      $index = $token['end'];

      if (!$token['closing']) {
        // Push the element on the stack.
        $stack[] = new TagElement(
          $token['name'],
          $token['arg'],
          substr($text, $token['end'], $token['length']),
          $this->plugins[$token['name']],
          $token['prepared']
        );
      }
      else {
        // Pop the closed element.
        $element = array_pop($stack);
        end($stack)->append($element);
      }
    }

    $final = substr($text, $index);
    if ($final) {
      end($stack)->append(new TextElement($final));
    }

    return array_pop($stack);
  }

}
