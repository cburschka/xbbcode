<?php

namespace Drupal\xbbcode;

/**
 * Implementation of UTF-8 character utilities.
 *
 * @package Drupal\xbbcode
 */
class Utf8 {

  /**
   * Finds the character code for a UTF-8 character: like ord() but for UTF-8.
   *
   * @param string $character
   *   A single UTF-8 character.
   *
   * @return int
   *   The character code, or -1 if an illegal character is found.
   *
   * @see \Drupal\Core\Transliteration\PhpTransliteration::ordUTF8()
   */
  public static function ord($character) {
    $first_byte = ord($character[0]);

    if (($first_byte & 0x80) === 0) {
      // Single-byte form: 0xxxxxxxx.
      return $first_byte;
    }
    if (($first_byte & 0xe0) === 0xc0) {
      // Two-byte form: 110xxxxx 10xxxxxx.
      return (($first_byte & 0x1f) << 6) +
             (ord($character[1]) & 0x3f);
    }
    if (($first_byte & 0xf0) === 0xe0) {
      // Three-byte form: 1110xxxx 10xxxxxx 10xxxxxx.
      return (($first_byte & 0x0f) << 12) +
             ((ord($character[1]) & 0x3f) << 6) +
             (ord($character[2]) & 0x3f);
    }
    if (($first_byte & 0xf8) === 0xf0) {
      // Four-byte form: 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx.
      return (($first_byte & 0x07) << 18) +
             ((ord($character[1]) & 0x3f) << 12) +
             ((ord($character[2]) & 0x3f) << 6) + (ord($character[3]) & 0x3f);
    }

    // Other forms are not legal.
    return -1;
  }

  /**
   * Encodes a numeric code point to a UTF-8 string.
   *
   * @param int $code
   *   A single unicode code point (an integer between 0 ... 0x10ffff).
   *
   * @return string
   *   The UTF-8 string, or an empty string for invalid code points.
   */
  public static function chr($code) {
    // Code point must be non-negative.
    if ($code < 0) {
      return '';
    }
    // Single byte (0xxxxxxx).
    if ($code < 0x80) {
      return chr($code);
    }
    // Two bytes (110xxxxx 10xxxxxx).
    if ($code < 0x800) {
      return chr(0xc0 | $code >> 6) .
             chr(0x80 | 0x3f & $code);
    }
    // Three bytes (1110xxxx 10xxxxxx 10xxxxxx).
    if ($code < 0x10000) {
      return chr(0xe0 | $code >> 12) .
             chr(0x80 | 0x3f & ($code >> 6)) .
             chr(0x80 | 0x3f & $code);
    }
    // Four bytes (11110xxx 10xxxxxx 10xxxxxx 10xxxxxx).
    if ($code < 0x110000) {
      return chr(0xf0 | $code >> 18) .
             chr(0x80 | 0x3f & ($code >> 12)) .
             chr(0x80 | 0x3f & ($code >> 6)) .
             chr(0x80 | 0x3f & $code);
    }

    // Code point must be less than or equal to 0x10ffff.
    return '';
  }

  /**
   * Escape specified characters in a UTF8 string to \uXXXX and \UXXXXXXXX.
   *
   * (This resembles the escape sequences of json_encode, but uses a single
   * eight-digit hex code for non-BMP instead of a UTF16 surrogate pair.)
   *
   * Existing sequences will get an extra backslash. Backslashes before
   * existing and new sequences are doubled for distinction. Other backslashes
   * are left unchanged.
   *
   * @param string $string
   *   The string to encode.
   * @param string $characters
   *   A valid character group (without []) to match.
   *   Without a group, all non-ASCII characters are escaped.
   *
   * @return string
   *   The encoded string.
   */
  public static function encode($string, $characters = NULL) {
    $characters = $characters ?: '^\x00-\x7f';

    // Escape existing \uXXXX and \UXXXXXXXX sequences.
    // This is done by doubling the number of backslashes preceding them.
    $string = preg_replace('/(\\\\+)(u[\da-fA-F]{4}|U[\da-fA-F]{8})/',
                           '$1$0',
                           $string);

    // Encode all blacklisted characters (or all non-ASCII characters).
    // Double any backslashes preceding them.
    return preg_replace_callback('/(\\\\*)([' . $characters . '])/u',
      function ($match) {
        $code = self::ord($match[2]);
        $sequence = sprintf($code < 0x10000 ? '\u%04x' : '\U%08x', $code);
        return $match[1] . $match[1] . $sequence;
      },
                                 $string);
  }

  /**
   * Decode \uXXXX and \UXXXXXXXX sequences in a UTF8 string.
   *
   * @param string $string
   *   The string to decode.
   *
   * @return string
   *   The decoded string.
   */
  public static function decode($string) {
    // Decode sequences with an odd number of backslashes.
    $string = preg_replace_callback('/(?<!\\\\)((?:\\\\\\\\)*)\\\\(u[\da-fA-F]{4}|U[\da-fA-F]{8})/',
      function ($match) {
        $prefix = str_repeat('\\', strlen($match[1]) / 2);
        return $prefix . self::chr(hexdec($match[2]));
      },
                                    $string);

    // Remove backslashes from escaped escape sequences.
    return preg_replace_callback('/(\\\\+)(u[\da-fA-F]{4}|U[\da-fA-F]{8})/',
      function ($match) {
        return substr($match[1], strlen($match[1]) / 2) . $match[2];
      },
                                 $string);
  }

}
