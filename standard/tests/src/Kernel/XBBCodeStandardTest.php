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

    foreach ($this->getTags() as $case) {
      static::assertEquals($case[1], check_markup($case[0], 'xbbcode'));
    }

    // The spoiler tag generates a random dynamic value.
    $input = $this->randomString(32) . '>\'"; email@example.com http://example.com/';
    $input = html_entity_decode($input);
    $input = str_replace('<', '', $input);
    $escaped = htmlspecialchars($input, ENT_NOQUOTES);
    $bbcode = "[spoiler]{$input}[/spoiler]";
    $element = $this->checkMarkup($bbcode, 'xbbcode');
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
    // Add some quotes, semicolon, email and URL.
    $input = $this->randomString(32) . '>\'"; email@example.com http://example.com/';
    // The core markup filter is buggy with things that look like HTML tags,
    // and may strip it rather than escaping.
    $input = str_replace('<', '', html_entity_decode($input));

    // Content doesn't escape any quotes;
    $content = htmlspecialchars($input, ENT_NOQUOTES);

    // The option must escape closing square brackets.
    $option = str_replace(']', '\\]', $input);

    // Attribute has escaped quotes.
    // Also, all semicolons must be part of character entities.
    $style = Html::escape(str_replace(';', '', $input));
    $attribute = Html::escape($input);

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
      "<a href=\"$attribute\" title=\"$attribute\">$content</a>",
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
      "<code>[b]{$attribute}[/b]</code>",
    ];

    // Exhaustively test cases here.
    $width = random_int(0, 1000);
    $height = random_int(0, 1000);

    $tags[] = [
      "[img={$width}x{$height}]{$input}[/img]",
      "<img src=\"{$attribute}\" alt=\"{$attribute}\" style=\"width:{$width}px;height:{$height}px;\" />",
    ];
    $tags[] = [
      "[img width={$width} height={$height}]{$input}[/img]",
      "<img src=\"{$attribute}\" alt=\"{$attribute}\" style=\"width:{$width}px;height:{$height}px;\" />",
    ];
    $tags[] = [
      "[img={$width}x]{$input}[/img]",
      "<img src=\"{$attribute}\" alt=\"{$attribute}\" style=\"width:{$width}px;\" />",
    ];
    $tags[] = [
      "[img width={$width}]{$input}[/img]",
      "<img src=\"{$attribute}\" alt=\"{$attribute}\" style=\"width:{$width}px;\" />",
    ];
    $tags[] = [
      "[img=x{$height}]{$input}[/img]",
      "<img src=\"{$attribute}\" alt=\"{$attribute}\" style=\"height:{$height}px;\" />",
    ];
    $tags[] = [
      "[img height={$height}]{$input}[/img]",
      "<img src=\"{$attribute}\" alt=\"{$attribute}\" style=\"height:{$height}px;\" />",
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
