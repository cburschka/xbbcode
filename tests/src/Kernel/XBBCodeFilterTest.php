<?php

namespace Drupal\Tests\xbbcode\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\xbbcode\Entity\Tag;
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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'filter', 'xbbcode', 'xbbcode_test_plugin']);

    $tag = Tag::create([
      'id'            => 'bad_tag',
      'label'         => 'Bad Tag',
      'description'   => 'Renders the outer source of itself.',
      'default_name'  => 'bad_tag',
      'sample'        => '[{{ name }}]Content[/{{ name }}]',
      'template_code' => '<{{ tag.name }}>{{ tag.outerSource }}</{{ tag.name }}>',
    ]);
    $tag->save();

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
        'bad_tag'       => [
          'id' => 'xbbcode_tag:bad_tag',
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

    $this->renderer = \Drupal::service('renderer');
  }

  /**
   * Test the parsing of attributes.
   */
  public function testAttributes(): void {
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
                           . '{prepared:' . Html::escape($content) . '}</span>';
    self::assertEquals($expected_markup, $markup);
  }

  /**
   * Test a few basic aspects of the filter.
   */
  public function testFilter(): void {
    $string = [
      $this->randomString(),
      $this->randomString(),
      $this->randomString(),
      $this->randomString(),
      $this->randomString(),
    ];

    $escaped = array_map(static function ($x) {
      return Html::escape($x);
    }, $string);

    $key = [
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    $text = "{$string[0]}[test_plugin {$key[0]}={$key[1]}]{$string[1]}"
          . "[TEST_plugin {$key[1]}={$key[0]}]{$string[2]}[/test_PLUGIN]"
          . "{$string[3]}[/test_plugin]{$string[4]}";
    $expected = "{$escaped[0]}<span data-{$key[0]}=\"{$key[1]}\">{prepared:{$escaped[1]}"
              . "<span data-{$key[1]}=\"{$key[0]}\">{prepared:{$escaped[2]}}</span>"
              . "{$escaped[3]}}</span>{$escaped[4]}";
    self::assertEquals($expected, check_markup($text, 'xbbcode_test'));

    // Check that case is preserved when rendering the bad tag's outer source.
    $text = "{$string[0]}[test_plugin {$key[0]}={$key[1]}]{$string[1]}"
      . "[BAD_tag {$key[1]}={$key[0]}]{$string[2]}[/bad_TAG]"
      . "{$string[3]}[/test_plugin]{$string[4]}";
    $expected = "{$escaped[0]}<span data-{$key[0]}=\"{$key[1]}\">{prepared:{$escaped[1]}"
      . "<bad_tag>[BAD_tag {$key[1]}={$key[0]}]{$escaped[2]}[/bad_TAG]</bad_tag>"
      . "{$escaped[3]}}</span>{$escaped[4]}";
    self::assertEquals($expected, check_markup($text, 'xbbcode_test'));

    $val = preg_replace('/[\\\\\"]/', '\\\\\0', $string[2]);
    $text = "[test_tag]{$string[0]}[test_template]{$string[1]}"
          . "[test_plugin {$key[0]}=\"{$val}\"]{$string[2]}[/test_plugin]"
          . "{$string[3]}[/test_template]{$string[4]}[/test_tag]";

    // The external template file has a trailing \n:
    $expected = "<strong>{$escaped[0]}<em>{$escaped[1]}"
            . "<span data-{$key[0]}=\"{$escaped[2]}\">{prepared:{$escaped[2]}}</span>"
            . "{$escaped[3]}</em>\n{$escaped[4]}</strong>";
    $output = $this->checkMarkup($text, 'xbbcode_test');
    self::assertEquals($expected, $output['#markup']);
    // The order of attachments is effectively arbitrary, but our plugin
    // merges them "top-down", so the outer tag's libraries precede the inner.
    self::assertEquals([
      'library' => [
        'xbbcode_test_plugin/library-template',
        'xbbcode_test_plugin/library-plugin',
      ],
    ], $output['#attached']);
  }

  /**
   * Render a text through the filter system, returning the full render array.
   *
   * @param string $text
   *   The text to be filtered.
   * @param string|null $format_id
   *   (optional) The machine name of the filter format to be used to filter the
   *   text. Defaults to the fallback format. See filter_fallback_format().
   *
   * @return array
   *   The render array, including #markup and #attached.
   */
  private function checkMarkup(string $text, string $format_id = NULL): array {
    $build = [
      '#type'   => 'processed_text',
      '#text'   => $text,
      '#format' => $format_id,
    ];
    try {
      $this->renderer->renderPlain($build);
    }
    catch (\Exception $e) {
      $build['#markup'] = '';
    }
    return $build;
  }

}
