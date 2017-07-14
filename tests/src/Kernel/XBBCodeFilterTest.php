<?php

namespace Drupal\Tests\xbbcode\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\xbbcode\Entity\TagSet;

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
    $this->installConfig(['system', 'filter', 'xbbcode', 'xbbcode_test_plugin']);
    $this->container->get('plugin.manager.xbbcode')->clearCachedDefinitions();

    $tag_set = TagSet::create([
      'id'    => 'test_set',
      'label' => 'Test Set',
      'tags'  => [
        'test_plugin'   => [
          'id' => 'test_plugin_id',
        ],
        'test_tag'      => [
          'id' => 'xbbcode_tag:test_tag_id',
        ],
        'test_template' => [
          'id' => 'xbbcode_tag:test_tag_external',
        ],
      ],
    ]);
    $tag_set->save();

    // Set up a BBCode filter format.
    $xbbcode_format = FilterFormat::create([
      'format'  => 'xbbcode_test',
      'name'    => 'XBBCode Test',
      'filters' => [
       'filter_html_escape' => [
         'status' => 1,
         'weight' => 0,
       ],
       'xbbcode'            => [
         'status'   => 1,
         'weight'   => 1,
         'settings' => [
           'tags'       => 'test_set',
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
      $this->randomString() . '\'"\'"  ]\\\\' . $this->randomString(),
    ];

    $keys = [
      $this->randomMachineName(),
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    // Embed a string with single quotes, no quotes and double quotes,
    // each time escaping all the required characters.
    $string = $keys[0] . "='" . preg_replace('/[\\\\\']/', '\\\\\0', $values[0]) . "' "
            . $keys[1] . '=' . preg_replace('/[\\\\\"\'\s\[\]]/', '\\\\\0', $values[1]) . ' '
            . $keys[2] . '="' . preg_replace('/[\\\\\"]/', '\\\\\0', $values[2]) . '"';

    $content = $this->randomString() . '[v=';

    $text = "[test_plugin {$string}]{$content}[/test_plugin]";
    $markup = check_markup($text, 'xbbcode_test');
    $expected_markup = '<span data-' . $keys[0] . '="' . Html::escape($values[0]) . '" '
                           . 'data-' . $keys[1] . '="' . Html::escape($values[1]) . '" '
                           . 'data-' . $keys[2] . '="' . Html::escape($values[2]) . '">'
                           . Html::escape($content) . '</span>';
    self::assertEquals($expected_markup, $markup);
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

    $escaped = array_map(function ($x) {
      return Html::escape($x);
    }, $string);

    $key = [
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    $text = "{$string[0]}[test_plugin {$key[0]}={$key[1]}]{$string[1]}"
          . "[test_plugin {$key[1]}={$key[0]}]{$string[2]}[/test_plugin]"
          . "{$string[3]}[/test_plugin]{$string[4]}";
    $expected = "{$escaped[0]}<span data-{$key[0]}=\"{$key[1]}\">{$escaped[1]}"
              . "<span data-{$key[1]}=\"{$key[0]}\">{$escaped[2]}</span>"
              . "{$escaped[3]}</span>{$escaped[4]}";
    self::assertEquals($expected, check_markup($text, 'xbbcode_test'));

    $val = preg_replace('/[\\\\\"]/', '\\\\\0', $string[2]);
    $text = "[test_tag]{$string[0]}[test_template]{$string[1]}"
          . "[test_plugin {$key[0]}=\"{$val}\"]{$string[2]}[/test_plugin]"
          . "{$string[3]}[/test_template]{$string[4]}[/test_tag]";

    // The external template file has a trailing \n:
    $expected = "<strong>{$escaped[0]}<em>{$escaped[1]}"
            . "<span data-{$key[0]}=\"{$escaped[2]}\">{$escaped[2]}</span>"
            . "{$escaped[3]}</em>\n{$escaped[4]}</strong>";
    self::assertEquals($expected, check_markup($text, 'xbbcode_test'));
  }

}
