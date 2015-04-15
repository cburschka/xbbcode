<?php

/**
 * @file
 * Contains Drupal\xbbcode\Plugin\Filter\XBBCodeFilter.
 */

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\xbbcode\Form\XBBCodeHandlerForm;
use Drupal\xbbcode\XBBCodeTagMatch;
use Drupal\xbbcode\XBBCodeRootElement;

/**
 * Provides a filter that converts BBCode to HTML.
 *
 * @Filter(
 *   id = "xbbcode",
 *   title = @Translation("Convert BBCode into HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "autoclose" = FALSE,
 *     "override" = FALSE,
 *     "tags" = {}
 *   }
 * )
 */
class XBBCodeFilter extends FilterBase {
  var $tags;

  /**
   * Construct a filter object from a bundle of tags, and the format ID.
   *
   * @param $tags
   *   Tag array.
   * @param $format
   *   Text format ID.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    module_load_include('inc', 'xbbcode');
    $this->tag_settings = $this->settings['override'] ? $this->settings['tags'] : Drupal::config('xbbcode.settings')->get('tags');
    $this->tags = _xbbcode_build_tags($this->tag_settings);
  }

  /**
   * Settings callback for the filter settings of xbbcode.
   */
  function settingsForm(array $form, FormStateInterface $form_state) {
    $form['autoclose'] = [
      '#type' => 'checkbox',
      '#title' => t("Automatically close tags left open at the end of the text."),
      '#description' => t("You will need to enable this option if you use automatic teasers on your site. BBCode will never generate broken HTML, but otherwise the BBCode tags broken by the teaser will simply not be processed."),
      '#default_value' => $this->settings['autoclose'],
    ];

    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => t('Override the <a href="@url">global settings</a> with specific settings for this format.', ['@url' => Drupal::url('xbbcode.admin_handlers')]),
      '#default_value' => $this->settings['override'],
      '#description' => t('Overriding the global settings allows you to disallow or allow certain special tags for this format, while other formats will not be affected by the change.'),
      '#attributes' => [
        'onchange' => 'Drupal.toggleFieldset(jQuery("#edit-filters-xbbcode-settings-tags"))',
      ],
    ];

    $form = XBBCodeHandlerForm::buildFormHandlers($form, $this->tag_settings);
    $form['handlers']['#type'] = 'details';
    $form['handlers']['#open'] = $this->settings['override'];

    return $form;
  }

  /**
   * Execute the filter on a particular text.
   *
   * Note: This function makes use of substr() and strlen() instead of Drupal
   * wrappers. This is the correct approach as all offsets are calculated by
   * the PREG_OFFSET_CAPTURE setting of preg_match_all(), which returns
   * byte offsets rather than character offsets.
   *
   * @param $text
   *   The text to be filtered.
   *
   * @return
   *   HTML code.
   */
  function process($text, $langcode) {
    // Find all opening and closing tags in the text.
    preg_match_all(XBBCODE_RE_TAG, $text, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    if (!$tags) {
      return $text;
    }

    // Initialize the stack with a root tag, and the name tracker.
    $stack = [new XBBCodeRootElement()];
    $open_by_name = [];
    foreach ($tags as $i => $tag) {
      $tag = $tags[$i] = new XBBCodeTagMatch($tag);
      $open_by_name[$tag->name] = 0;
    }

    foreach ($tags as $tag) {
      // Case 1: The tag is opening, and known to the filter.
      if (!$tag->closing && isset($this->tags[$tag->name])) {
        // Add text before the new tag to the parent, then stack the new tag.
        end($stack)->advance($text, $tag->start);

        // Stack the newly opened tag, or render it if it's selfclosing.
        if ($this->tags[$tag->name]->options->selfclosing) {
          $rendered = $this->render_tag($tag);
          if ($rendered === NULL) {
            $rendered = $tag->element;
          }
          end($stack)->append($rendered, $tag->end);
        } else {
          array_push($stack, $tag);
          $open_by_name[$tag->name]++;
        }
      }
      // Case 2: The tag is closing, and an opening tag exists.
      elseif ($tag->closing && !empty($open_by_name[$tag->name])) {
        // Find the last matching opening tag, breaking any unclosed tag since then.
        while (end($stack)->name != $tag->name) {
          $dangling = array_pop($stack);
          end($stack)->break_tag($dangling);
          $open_by_name[$dangling->name]--;
        }
        end($stack)->advance($text, $tag->start);
        $open_by_name[$tag->name]--;

        // If the tag forbids rendering its content, revert to the unrendered text.
        if ($this->tags[$tag->name]->options->nocode) {
          end($stack)->revert($text);
        }
        if ($this->tags[$tag->name]->options->plain) {
          // We will double-encode entities only if non-encoded chars exist.
          if (end($stack)->content != htmlspecialchars(end($stack)->content, ENT_QUOTES, 'UTF-8', FALSE)) {
            end($stack)->content = check_plain(end($stack)->content);
          }
        }

        // Append the rendered HTML to the content of its parent tag.
        $current = array_pop($stack);
        $rendered = $this->render_tag($current);
        if ($rendered === NULL) {
          $rendered = $current->element . $current->content . $tag->element;
        }
        end($stack)->append($rendered, $tag->end);
      }
    }
    end($stack)->content .= substr($text, end($stack)->offset);

    if ($this->autoclose_tags) {
      while (count($stack) > 1) {
        // Render the unclosed tag and pop it off the stack
        $output = $this->render_tag(array_pop($stack));
        end($stack)->content .= $output;
      }
    } else {
      while (count($stack) > 1) {
        $current = array_pop($stack);
        $content = $current->element . $current->content;
        end($stack)->content .= $content;
      }
    }

    return end($stack)->content;
  }

  /**
   * Render a single tag.
   *
   * @param $tag
   *   The complete match object, including its name, content and attributes.
   *
   * @return
   *   HTML code to insert in place of the tag and its content.
   */
  function render_tag(XBBCodeTagMatch $tag) {
    if ($callback = $this->tags[$tag->name]->callback) {
      return $callback($tag, $this);
    } else {
      $replace['{content}'] = $tag->content;
      $replace['{option}'] = $tag->option;
      foreach ($tag->attrs as $name => $value) {
        $replace['{' . $name . '}'] = $value;
      }

      $markup = str_replace(
        array_keys($replace), array_values($replace), $this->tags[$tag->name]->markup
      );

      // Make sure that unset placeholders are replaced with empty strings.
      $markup = preg_replace('/{\w+}/', '', $markup);

      return $markup;
    }
  }

}
