<?php

namespace Drupal\xbbcode\Parser;

use Drupal\xbbcode\Parser\Tree\NodeElementInterface;
use Drupal\xbbcode\Parser\Tree\RootElement;
use Drupal\xbbcode\Parser\Tree\TagElement;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Parser\Tree\TextElement;

/**
 * The standard XBBCode parser.
 */
class XBBCodeParser implements ParserInterface {

  /**
   * The plugins for rendering.
   *
   * @var \Drupal\xbbcode\Parser\Processor\TagProcessorInterface[]
   */
  protected $processors;

  /**
   * XBBCodeParser constructor.
   *
   * @param mixed $processors
   *   The plugins for rendering.
   */
  public function __construct($processors = NULL) {
    $this->processors = $processors;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($text) {
    $tokens = static::tokenize($text, $this->processors);
    $tokens = static::validateTokens($tokens);
    $tree = static::buildTree($text, $tokens);
    if ($this->processors) {
      static::decorateTree($tree, $this->processors);
    }
    return $tree;
  }

  /**
   * Find the opening and closing tags in a text.
   *
   * @param string $text
   *   The source text.
   * @param array|\ArrayAccess|null $allowed
   *   An array keyed by tag name, with non-empty values for allowed tags.
   *   Omit this argument to allow all tag names.
   *
   * @return array[]
   *   The tokens.
   */
  public static function tokenize($text, $allowed = NULL) {
    // Find all opening and closing tags in the text.
    $matches = [];
    preg_match_all("%
      \\[
        (?'closing'/?)
        (?'name'[a-z0-9_-]+)
        (?'argument'
          (?:(?=\\k'closing')            # only take an argument in opening tags.
            (?:
              =(?:\\\\.|[^\\\\\\[\\]])*  # unquoted option must escape brackets.
              |
              =(?'quote1'['\"]|&quot;|&\\#039;)
               (?:\\\\.|(?!\\k'quote1')[^\\\\])*
               \\k'quote1'
              |
              (?:\\s+[\\w-]+=
                (?:
                  (?'quote2'['\"]|&quot;|&\\#039;)
                  (?:\\\\.|(?!\\k'quote2')[^\\\\])*
                  \\k'quote2'
                  |
                  (?!\\g'quote2')        # unquoted values cannot begin with quotes.
                  (?:\\\\.|[^\\[\\]\\s\\\\])*
                )
              )*
            )
          )?
        )
      \\]
      %x",
      $text,
      $matches,
      PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $tokens = [];

    foreach ($matches as $i => $match) {
      $name = $match['name'][0];
      if ($allowed && empty($allowed[$name])) {
        continue;
      }

      $start = $match[0][1];
      $tokens[] = [
        'name'     => $name,
        'start'    => $start,
        'end'      => $start + strlen($match[0][0]),
        'argument' => $match['argument'][0],
        'closing'  => !empty($match['closing'][0]),
      ];
    }

    return $tokens;
  }

  /**
   * Parse a string of attribute assignments.
   *
   * @param string $argument
   *   The string containing the attributes, including initial whitespace.
   *
   * @return array
   *   An associative array of all attributes.
   */
  public static function parseAttributes($argument) {
    $assignments = [];
    preg_match_all("/
    (?<=\\s)                                # preceded by whitespace.
    (?'key'[\\w-]+)=
    (?:
        (?'quote'['\"]|&quot;|&\\#039;)     # quotes may be encoded.
        (?'value'
          (?:\\\\.|(?!\\\\|\\k'quote')[^\\\\])*   # value can contain the delimiter.
        )
        \\k'quote'
        |
        (?'unquoted'
          (?!\\g'quote')           # unquoted values cannot start with a quote.
          (?:\\\\.|[^\\s\\\\])*
        )
    )
    (?=\\s|$)/x", $argument, $assignments, PREG_SET_ORDER);
    $attributes = [];
    foreach ($assignments as $assignment) {
      // Strip backslashes from the escape sequences in each case.
      $value = $assignment['value'] ?: $assignment['unquoted'];
      $attributes[$assignment['key']] = stripslashes($value);
    }
    return $attributes;
  }

  /**
   * Parse an option string.
   *
   * @param string $argument
   *   The argument string, including the initial =.
   *
   * @return string
   *   The parsed option value.
   */
  public static function parseOption($argument) {
    if (preg_match("/
      ^=
      (?'quote'[\'\"]|&quot;|&\\#039;)
      (?'value'.*)
      \\k'quote'
      $/x", $argument, $match)) {
      $value = $match['value'];
    }
    else {
      $value = substr($argument, 1);
    }

    return stripslashes($value);
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
            'length'   => $token['start'] - $last['end'],
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
   * Convert a well-formed list of tokens into a tree.
   *
   * @param string $text
   *   The source text.
   * @param array[] $tokens
   *   The tokens.
   *
   * @return \Drupal\xbbcode\Parser\Tree\NodeElement
   *   The element representing the tree.
   */
  public static function buildTree($text, array $tokens) {
    /** @var \Drupal\xbbcode\Parser\Tree\NodeElement[] $stack */
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
          $token['argument'],
          substr($text, $token['end'], $token['length'])
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

  /**
   * Assign processors to the tag elements of a tree.
   *
   * @param \Drupal\xbbcode\Parser\Tree\NodeElementInterface $node
   *   The tree to decorate.
   * @param \Drupal\xbbcode\Parser\Processor\TagProcessorInterface[]|\ArrayAccess $processors
   *   The processors, keyed by name.
   */
  public static function decorateTree(NodeElementInterface $node,
                                      $processors) {
    foreach ($node->getChildren() as $child) {
      if ($child instanceof TagElementInterface) {
        $child->setParent($node);
        if ($processor = $processors[$child->getName()]) {
          $child->setProcessor($processor);
        }
        static::decorateTree($child, $processors);
      }
    }
  }

}
