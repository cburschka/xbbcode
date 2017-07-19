<?php

namespace Drupal\Tests\xbbcode_standard\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class XBBCodeStandardTest
 *
 * @group xbbcode
 */
class XBBCodeStandardTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'filter',
    'xbbcode',
    'xbbcode_standard',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['xbbcode', 'xbbcode_standard']);

    // Set up a BBCode filter format.
    $format = [
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
            'linebreaks' => FALSE,
          ],
        ],
      ],
    ];
    FilterFormat::create($format)->save();
  }

  /**
   * Test all of the tags installed by this module.
   */
  public function testTags() {
    // Ten iterations, just in case of weird edge cases.
    for ($i = 0; $i < 10; $i++) {
      foreach ($this->getTags() as $case) {
        $expected = self::stripSpaces($case[1]);
        $actual = self::stripSpaces(check_markup($case[0], 'xbbcode_test'));
        static::assertEquals($expected, $actual);
      }
      // The spoiler tag generates a random dynamic value.
      $input = $this->randomString(2048) . '>\'"; email@example.com http://example.com/';
      $input = str_replace('<', '', $input);
      $escaped = Html::escape($input);
      $bbcode = "[spoiler]{$input}[/spoiler]";
      $element = $this->checkMarkup($bbcode, 'xbbcode_test');
      preg_match('/id="xbbcode-spoiler-(\d+)"/', $element['#markup'], $match);
      $key = $match[1];
      $this->assertNotNull($key);
      $expected =   "<input id=\"xbbcode-spoiler-{$key}\" type=\"checkbox\" class=\"xbbcode-spoiler\" />"
                    . "<label class=\"xbbcode-spoiler\" for=\"xbbcode-spoiler-{$key}\">{$escaped}</label>";
      static::assertEquals($expected, $element['#markup']);
    }

  }

  /**
   * @return array[]
   */
  private function getTags() {
    $input = $this->randomString(128);
    // Add a long run of backslashes to check for backtracking.
    $input .= str_repeat('\\', 128);
    // Add a pathological mix of raw and encoded characters.
    $input .= '<>&&amp;&quot;&amp;amp;amp;quot;&gt;';

    // Mask any existing tag names that happen to be generated.
    $names = [
      'align', 'b', 'color', 'font', 'i', 'url', 'list', 'quote',
      'size', 's', 'sub', 'sup', 'u', 'code', 'img', 'table',
    ];
    $replacement = Unicode::strtolower($this->randomMachineName());
    $input = preg_replace('/(\\[\\/?)(' . implode('|', $names) . ')(?!\w+)/', '$0' . $replacement, $input);
    // Also mask any list item delimiters.
    $input = str_replace('[*]', '[**]', $input);

    $content = Html::escape($input);

    // The option must escape square brackets.
    $option = preg_replace('/[\[\]\\\\]/', '\\\\$0', $input);
    // If the option starts with a quote, add a backslash.
    if (preg_match('/^[\'\"]/', $option)) {
      $option = '\\' . $option;
    }

    // Attribute has escaped quotes.
    // Also, all semicolons must be part of character entities.
    $style = Html::escape(str_replace(';', '', $input));

    $tags[] = [
      "[align={$option}]{$input}[/align]",
      "<p style=\"text-align:$style\">$content</p>",
    ];
    $tags[] = [
      "[b]{$input}[/b]",
      "<strong>$content</strong>",
    ];
    $tags[] = [
      "[color={$option}]{$input}[/color]",
      "<span style=\"color:$style\">$content</span>",
    ];
    $tags[] = [
      "[font={$option}]{$input}[/font]",
      "<span style=\"font-family:$style\">$content</span>",
    ];
    $tags[] = [
      "[i]{$input}[/i]",
      "<em>$content</em>",
    ];
    $tags[] = [
      "[url={$option}]{$input}[/url]",
      "<a href=\"$content\" title=\"$content\">$content</a>",
    ];
    $tags[] = [
      "[list={$option}][*]{$input}\n[*]{$input}\n[/list]",
      "<ul style=\"list-style-type:$style\"><li>$content</li><li>$content</li></ul>",
    ];
    $tags[] = [
      "[quote]{$input}[/quote]",
      "<blockquote>$content</blockquote>",
    ];
    $tags[] = [
      "[size={$option}]{$input}[/size]",
      "<span style=\"font-size:$style\">$content</span>",
    ];
    $tags[] = [
      "[s]{$input}[/s]",
      "<s>$content</s>",
    ];
    $tags[] = [
      "[sub]{$input}[/sub]",
      "<sub>$content</sub>",
    ];
    $tags[] = [
      "[sup]{$input}[/sup]",
      "<sup>$content</sup>",
    ];
    $tags[] = [
      "[u]{$input}[/u]",
      "<span style=\"text-decoration:underline\">$content</span>",
    ];

    $tags[] = [
      "[code][b]{$input}[/b][/code]",
      "<code>[b]{$content}[/b]</code>",
    ];

    // Exhaustively test cases here.
    $width = random_int(0, 1000);
    $height = random_int(0, 1000);

    $tags[] = [
      "[img={$width}x{$height}]{$input}[/img]",
      "<img src=\"{$content}\" alt=\"{$content}\" style=\"width:{$width}px;height:{$height}px;\" />",
    ];
    $tags[] = [
      "[img width={$width} height={$height}]{$input}[/img]",
      "<img src=\"{$content}\" alt=\"{$content}\" style=\"width:{$width}px;height:{$height}px;\" />",
    ];
    $tags[] = [
      "[img={$width}x]{$input}[/img]",
      "<img src=\"{$content}\" alt=\"{$content}\" style=\"width:{$width}px;\" />",
    ];
    $tags[] = [
      "[img width={$width}]{$input}[/img]",
      "<img src=\"{$content}\" alt=\"{$content}\" style=\"width:{$width}px;\" />",
    ];
    $tags[] = [
      "[img=x{$height}]{$input}[/img]",
      "<img src=\"{$content}\" alt=\"{$content}\" style=\"height:{$height}px;\" />",
    ];
    $tags[] = [
      "[img height={$height}]{$input}[/img]",
      "<img src=\"{$content}\" alt=\"{$content}\" style=\"height:{$height}px;\" />",
    ];

    // Tables have an extra backslash level, which is applied first.
    $cell = preg_replace('/[\'\",\\\\]/', '\\\\$0', $input);
    $header = preg_replace('/[\s\[\]\\\\]/', '\\\\$0', $cell);
    $attribute = preg_replace('/[\'\"\s\[\]\\\\]/', '\\\\$0', $input);
    $headers = "~{$header}0,\\ {$header}1,!{$header}2,#{$header}3";
    if (preg_match('/^[\'\"]/', $headers[0])) {
      $headers = '\\' . $headers;
      $attribute = '\\' . $attribute;
    }
    $row = implode(',', array_fill(0, 4, $cell));
    $output_row = <<<DOC
<tr>
  <td style="text-align:left">{$content}</td>
  <td>{$content}</td>
  <td style="text-align:center">{$content}</td>
  <td style="text-align:right">{$content}</td>
</tr>
DOC;
    $table_body = str_repeat("$row\n", 5);
    $output = str_repeat($output_row, 5);
    $table = <<<DOC
<table class="responsive-enabled" data-striping="1">
  <caption>{$content}-caption</caption>
  <thead>
    <tr>
      <th>{$content}0</th>
      <th>{$content}1</th>
      <th>{$content}2</th>
      <th>{$content}3</th>
    </tr>
  </thead>
  <tbody>
    {$output}
  </tbody>
</table>
DOC;

    $tags[] = [
      "[table header={$headers} caption={$attribute}-caption]\n{$table_body}[/table]",
      $table,
    ];

    return $tags;
  }

  /**
   * A variant of check_markup that returns the full element.
   *
   * This is needed to check the #attached key.
   *
   * @param string $text
   *   The input text.
   * @param string $format_id
   *   The format ID.
   *
   * @return array
   */
  private function checkMarkup($text, $format_id) {
    $build = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => $format_id,
      '#filter_types_to_skip' => [],
      '#langcode' => '',
    ];
    \Drupal::service('renderer')->renderPlain($build);
    return $build;
  }

  /**
   * Strip interstitial white space between tags.
   *
   * This produces a normal form for templates that use odd indentation.
   *
   * @param string $html
   *
   * @return string
   */
  private static function stripSpaces($html) {
    return preg_replace('/(?<=^|>)\s+(?=<|$)/', '', $html);
  }

}
