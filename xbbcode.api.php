<?php

/**
 * Declare tags that can be used by XBBCode.
 *
 * @return
 *   An array keyed by tag name, each element of which can contain the following keys:
 *     - markup: A string of HTML code that can contain {content} and {option} placeholders.
 *     - callback: A rendering function to call. The rendering function is passed the $tag
 *       object as an argument, and should return HTML code.
 *       (The callback key will only be used if no markup key is set.)
 *     - options: An array that can contain any of the following keys, set to TRUE.
 *         - nocode: All tags inside the content of this tag will not be parsed
 *         - plain: HTML inside the content of this tag will always be escaped.
 *         - selfclosing: This tag closes itself automatically, analagous to [img=http://url].
 *     - sample: For the help text, provide an example of the tag in use.
 *     - description: A localized description of the tag.
 */
function hook_xbbcode_info() {
  $tags['url'] = array(
    'markup' => '<a href="{option}">{content}</arg>',
    'description' => t('A hyperlink.'),
    'sample' => '[url=http://drupal.org/]Drupal[/url]',
  );
  $tags['img'] = array(
    'markup' => '<img src="{option}" />',
    'options' => array(
      'selfclosing' => TRUE,
    ),
    'description' => t('An image'),
    'sample' => '[img=http://drupal.org/favicon.ico]',
  );
  $tags['code'] = array(
    'markup' => '<code>{option}</code>',
    'options' => array(
      'nocode' => TRUE,
      'plain' => TRUE,
    ),
    'description' => t('Code'),
    'sample' => 'if (x <> 3) then y = (x <= 3)',
  );
  $tags['php'] = array(
    'callback' => '_hook_xbbcode_render_php',
    'options' => array(
      'nocode' => TRUE,
      'plain' => TRUE,
    ),
    'description' => t('Highlighed PHP code'),
    'sample' => '[code]print "Hello world";[/code]',
  );

  return $tags;
}

/**
 * Sample render callback.
 *
 * @param $tag
 *   The tag to be rendered. This object will have the following properties:
 *   - name: Name of the tag
 *   - content: The text between opening and closing tags.
 *   - option: The single argument, if one was entered as in [tag=option].
 *   - args: An array of named arguments, if they were entered as in [tag arg1=a arg2=b]
 * @param $xbbcode_filter
 *   The filter object that is processing the text. The process() and
 *   render_tag() functions on this object may be used to generate and render
 *   further text, but care must be taken to avoid an infinite recursion.
 *   The object will also have the following properties:
 *   - filter: Drupal's filter object, including the settings.
 *   - format: The text format object, including a list of its other filters.
 *
 * @return
 *   HTML markup code. If NULL is returned, the filter will leave the tag unrendered.
 */
function _hook_xbbcode_render_php($tag, $xbbcode_filter) {
  return highlight_string($tag->content, TRUE);
}
