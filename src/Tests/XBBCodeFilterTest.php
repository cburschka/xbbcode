<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Tests\XBBCodeFilterTest.
 */

namespace Drupal\xbbcode\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\KernelTestBase;

/**
 * Test the filter.
 *
 * @group xbbcode
 */
class XBBCodeFilterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'filter',
    'xbbcode',
    'xbbcode_test_plugin',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'filter', 'xbbcode']);

    // Set up a BBCode filter format.
    $xbbcode_format = entity_create('filter_format', [
      'format' => 'xbbcode_test',
      'name' => 'XBBCode Test',
      'filters' => [
        'filter_html_escape' => [
          'status' => 1,
          'weight' => 0,
        ],
        'xbbcode' => [
          'status' => 1,
          'weight' => 1,
          'settings' => [
            'tags' => [
              'test_plugin_id' => [
                'status' => TRUE,
                'name' => 'test_plugin',
              ],
            ],
            'override' => TRUE,
            'linebreaks' => FALSE,
          ],
        ],
      ],
    ]);
    $xbbcode_format->save();
  }

  /**
   * Test the parsing of attributes.
   */
  public function testAttributes() {
    // Generate some attribute values with whitespace, quotes and backslashes.
    $values = [
      $this->randomString() . '\'"\'"  \\\\',
      '\'"\'"  \\\\' . $this->randomString(),
      $this->randomString() . '\'"\'"  \\\\' . $this->randomString(),
    ];

    $keys = [
      $this->randomMachineName(),
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    $attributes = array_combine($keys, $values);

    // Embed a string with single quotes, no quotes and double quotes,
    // each time escaping all the required characters.
    $string = $keys[0] . "='" . preg_replace('/[\\\\\']/', '\\\\\0', $values[0]) . "' "
            . $keys[1] . '=' . preg_replace('/[\\\\\"\'\s]/', '\\\\\0', $values[1]) . ' '
            . $keys[2] . '="' . preg_replace('/[\\\\\"]/', '\\\\\0', $values[2]) . '"';

    $content = $this->randomString();

    $text = "[test_plugin {$string}]{$content}[/test_plugin]";
    $markup = check_markup($text, 'xbbcode_test');
    $expected_markup = '<span data-' . $keys[0] . '="' . SafeMarkup::checkPlain($values[0]) . '" '
                                  . 'data-' . $keys[1] . '="' . SafeMarkup::checkPlain($values[1]) . '" '
                                  . 'data-' . $keys[2] . '="' . SafeMarkup::checkPlain($values[2]) . '">'
                                  . SafeMarkup::checkPlain($content) . '</span>';
    $this->assertEqual($expected_markup, $markup);
  }

  /**
   * Test a few basic aspects of the filter.
   */
  public function testFilter() {
    $string = [
      $this->randomString(),
      $this->randomString(),
      $this->randomString(),
      $this->randomString(),
      $this->randomString(),
    ];

    $escaped = array_map(function($x) {
      return SafeMarkup::checkPlain($x);
    }, $string);

    $key = [
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    $text = "{$string[0]}[test_plugin {$key[0]}={$key[1]}]{$string[1]}"
          . "[test_plugin {$key[1]}={$key[0]}]{$string[2]}[/test_plugin]"
          . "{$string[3]}[/test_plugin]{$string[4]}";
    $markup = check_markup($text, 'xbbcode_test');
    $expected = "{$escaped[0]}<span data-{$key[0]}=\"{$key[1]}\">{$escaped[1]}"
              . "<span data-{$key[1]}=\"{$key[0]}\">{$escaped[2]}</span>"
              . "{$escaped[3]}</span>{$escaped[4]}";
    $this->assertEqual($text, $text);
    $this->assertEqual($expected, $markup);
  }

}
