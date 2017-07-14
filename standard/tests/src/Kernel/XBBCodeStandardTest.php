<?php

namespace Drupal\Tests\xbbcode_standard\Kernel;

use Drupal\Component\Utility\Html;
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
    // Disable the other filters to avoid side-effects.
    $format = FilterFormat::load('xbbcode');
    $format
      ->setFilterConfig('filter_url', ['status' => FALSE])
      ->setFilterConfig('filter_html_escape', ['status' => FALSE])
      ->setFilterConfig('filter_htmlcorrector', ['status' => FALSE]);
    $format->save();

    // Ten iterations, just in case of weird edge cases.
    for ($i = 0; $i < 10; $i++) {
      foreach ($this->getTags() as $case) {
        static::assertEquals($case[1], check_markup($case[0], 'xbbcode_test'));
      }
    }

    // The spoiler tag generates a random dynamic value.
    $input = $this->randomString(32) . '>\'"; email@example.com http://example.com/';
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

  /**
   * @return array[]
   */
  private function getTags() {
    $input = $this->randomString(128);

    // We may generate b,i,u,s,* tags. All others are sufficiently unlikely.
    // Replace the name with "x" to avoid this.
    $input = preg_replace('/\\[\/?[*bius](?!\w)/', '\\[x', $input);

    $content = Html::escape($input);

    // The option must escape square brackets.
    $option = preg_replace('/[\[\]\\\\]/', '\\\\$0', $input);
    // If the option starts and ends with the same quote, add a backslash.
    if ($option[0] === $option[-1] && preg_match('/[\'\"]/', $option[0])) {
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

}
