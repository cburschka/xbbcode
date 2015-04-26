<?php

/**
 * @file
 * Contains Drupal\xbbcode\Plugin\Filter\XBBCodeFilter.
 */

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
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
 *     "override" = FALSE,
 *     "tags" = {}
 *   }
 * )
 */
class XBBCodeFilter extends FilterBase {
  private $tags;

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
    $this->tags = _xbbcode_build_tags($this->tag_settings ? $this->tag_settings : []);
  }

  /**
   * Settings callback for the filter settings of xbbcode.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the <a href="@url">global settings</a> with specific settings for this format.', ['@url' => Drupal::url('xbbcode.admin_handlers')]),
      '#default_value' => $this->settings['override'],
      '#description' => $this->t('Overriding the global settings allows you to disallow or allow certain special tags for this format, while other formats will not be affected by the change.'),
      '#attributes' => [
        'onchange' => 'Drupal.toggleFieldset(jQuery("#edit-filters-xbbcode-settings-tags"))',
      ],
    ];

    $form = XBBCodeHandlerForm::buildFormHandlers($form, $this->tag_settings);
    $form['handlers']['#type'] = 'details';
    $form['handlers']['#open'] = $this->settings['override'];

    $parents = $form['#parents'];
    $parents[] = 'tags';
    $form['handlers']['tags']['#parents'] = $parents;
    $form['handlers']['extra']['tags']['#parents'] = $parents;

    return $form;
  }

  public function tips($long = FALSE) {
    if (!$this->tags) {
      return $this->t('BBCode is enabled, but no tags are defined.');
    }

    if ($long) {
      $table = [
        '#type' => 'table',
        '#caption' => $this->t('Allowed BBCode tags:'),
        '#header' => [$this->t('Tag Description'), $this->t('You Type'), $this->t('You Get')],
      ];
      foreach ($this->tags as $name => $tag) {
        $table[$name] = [
          [
            '#markup' => "<strong>[$name]</strong><br />" . $tag->description,
            '#attributes' => ['class' => ['description']],
          ],
          [
            '#markup' => '<code>' . str_replace("\n", '<br />', SafeMarkup::checkPlain($tag->sample)) . '</code>',
            '#attributes' => ['class' => ['type']],
          ],
          [
            '#markup' => $this->process($tag->sample, NULL)->getProcessedText(),
            '#attributes' => ['class' => ['get']],
          ],
        ];
      }
      return Drupal::service('renderer')->render($table);
    }
    else {
      foreach ($this->tags as $name => $tag) {
        $tags[$name] = '<abbr title="' . $tag->description . '">[' . $name . ']</abbr>';
      }
      return ['#markup' => $this->t('You may use these tags: !tags', ['!tags' => implode(', ', $tags)])];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $tree = $this->buildTree($text);
    $output = $this->renderTree($tree->content);
    return new FilterProcessResult($output);
  }

  private function buildTree($text) {
    // Find all opening and closing tags in the text.
    preg_match_all(XBBCODE_RE_TAG, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    // Initialize the name tracker, and the list of valid tags.
    $open_by_name = [];
    $tags = [];
    foreach ($matches as $match) {
      $tag = new XBBCodeTagMatch($match);
      if (isset($this->tags[$tag->name])) {
        $tag->selfclosing = $this->tags[$tag->name]->options->selfclosing;
        $tags[] = $tag;
        $open_by_name[$tag->name] = 0;
      }
    }

    // Initialize the stack with a root element.
    $stack = [new XBBCodeRootElement()];
    foreach ($tags as $tag) {
      // Add text before the new tag to the parent
      end($stack)->advance($text, $tag->start);

      // Case 1: The tag is opening and not self-closing.
      if (!$tag->closing && !$tag->selfclosing) {
        // Stack the open tag, and increment the tracker.
        array_push($stack, $tag);
        $open_by_name[$tag->name]++;
      }

      // Case 2: The tag is self-closing.
      elseif ($tag->selfclosing) {
        end($stack)->append($tag, $tag->end);
      }

      // Case 3: The tag closes an existing tag.
      elseif ($open_by_name[$tag->name]) {
        $open_by_name[$tag->name]--;

        // Find the last matching opening tag, breaking any unclosed tag since then.
        while (end($stack)->name != $tag->name) {
          $dangling = array_pop($stack);
          end($stack)->breakTag($dangling);
          $open_by_name[$dangling->name]--;
        }
        $current = array_pop($stack);
        $current->advance($text, $tag->start);
        $current->source = substr($text, $current->end, $current->offset - $current->end);
        $current->closer = $tag;
        end($stack)->append($current, $tag->end);
      }
    }

    // Add the remainder of the text, and then break any tags still open.
    end($stack)->advance($text, strlen($text));
    while (count($stack) > 1) {
      $dangling = array_pop($stack);
      end($stack)->breakTag($dangling);
    }
    return end($stack);
  }

  private function renderTree($tree) {
    $output = '';
    foreach ($tree as $i => $root) {
      if (is_object($root)) {
        $root->content = $this->renderTree($root->content);
        $rendered = $this->renderTag($root);
        $root = $rendered !== NULL ? $rendered : $root->getOuterText();
      }
      $output .= $root;
    }
    return $output;
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
  private function renderTag(XBBCodeTagMatch $tag) {
    if ($callback = $this->tags[$tag->name]->callback) {
      return $callback($tag);
    } else {
      $replace['{content}'] = $tag->content;
      $replace['{source}'] = $tag->source;
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
