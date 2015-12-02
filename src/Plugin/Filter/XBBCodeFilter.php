<?php

/**
 * @file
 * Contains Drupal\xbbcode\Plugin\Filter\XBBCodeFilter.
 */

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\xbbcode\Form\XBBCodePluginSelectionForm;
use Drupal\xbbcode\XBBCodeRootElement;
use Drupal\xbbcode\XBBCodeTagMatch;
use Drupal\xbbcode\XBBCodeTagPluginCollection;

/**
 * Provides a filter that converts BBCode to HTML.
 *
 * @Filter(
 *   id = "xbbcode",
 *   title = @Translation("Convert BBCode into HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "override" = FALSE,
 *     "linebreaks" = TRUE,
 *     "tags" = {}
 *   }
 * )
 */
class XBBCodeFilter extends FilterBase {
  /**
   * Configured tags for this filter.
   *
   * An associative array of tags assigned to the filter, keyed by the
   * instance ID of each tag and using the properties:
   * - id: The plugin ID of the tag plugin instance.
   * - provider: The name of the provider that owns the tag.
   * - status: (optional) A Boolean indicating whether the tag is
   *   enabled in the filter. Defaults to FALSE.
   * - settings: (optional) An array of configured settings for the tag.
   *
   * Use XBBCodeFilter::tags() to access the actual tags.
   *
   * @var array
   */
  private $tags = [];
  private $tagCollection;

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

    $this->tags = $this->settings['override'] ? $this->settings['tags'] : Drupal::config('xbbcode.settings')->get('tags');

    // During installation, the global settings may not have been installed yet.
    $this->tags = $this->tags !== NULL ? $this->tags : [];

    $this->tagCollection = new XBBCodeTagPluginCollection(\Drupal::service('plugin.manager.xbbcode'), $this->tags, TRUE);
  }


  public function tags($instance_id = NULL) {
    $this->tagCollection->sort();

    if (isset($instance_id)) {
      return $this->tagCollection->get($instance_id);
    }
    return $this->tagCollection;
  }
  /**
   * Settings callback for the filter settings of xbbcode.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['linebreaks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert linebreaks to HTML.'),
      '#default_value' => $this->settings['linebreaks'],
      '#description' => $this->t('Newline <code>\n</code> characters will become <code>&lt;br /&gt;</code> tags.'),
    ];

    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the <a href="@url">global settings</a> with specific settings for this format.', ['@url' => Drupal::url('xbbcode.settings')]),
      '#default_value' => $this->settings['override'],
      '#description' => $this->t('Overriding the global settings allows you to disable or enable specific tags for this format, while other formats will not be affected by the change.'),
      '#attributes' => [
        'onchange' => 'Drupal.toggleFieldset(jQuery("#edit-filters-xbbcode-settings-tags"))',
      ],
    ];

    $form = XBBCodePluginSelectionForm::buildPluginForm($form, $this->tags());
    $form['plugins']['#type'] = 'details';
    $form['plugins']['#open'] = $this->settings['override'];

    $parents = $form['#parents'];
    $parents[] = 'tags';
    $form['plugins']['tags']['#parents'] = $parents;
    $form['plugins']['extra']['tags']['#parents'] = $parents;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $table = [
        '#type' => 'table',
        '#caption' => $this->t('Allowed BBCode tags:'),
        '#header' => [$this->t('Tag Description'), $this->t('You Type'), $this->t('You Get')],
        '#empty' => $this->t('BBCode is active, but no tags are available.'),
      ];
      foreach ($this->tags() as $id => $tag) {
        $table[$id] = [
          [
            '#markup' => "<strong>[{$tag->name}]</strong><br />" . $tag->getDescription(),
            '#attributes' => ['class' => ['description']],
          ],
          [
            '#markup' => '<code>' . nl2br(SafeMarkup::checkPlain($tag->getSample())) . '</code>',
            '#attributes' => ['class' => ['type']],
          ],
          [
            '#markup' => $this->process($tag->getSample(), NULL)->getProcessedText(),
            '#attributes' => ['class' => ['get']],
          ],
        ];
      }
      return Drupal::service('renderer')->render($table);
    }
    else {
      foreach ($this->tags() as $id => $tag) {
        $tag = [
          '#type' => 'inline_template',
          '#template' => '<abbr title="{{ tag.description }}">[{{ tag.name }}]</abbr>',
          '#context' => ['tag' => $tag],
        ];
        $tags[$id] = Drupal::service('renderer')->render($tag);
      }
      if (empty($tags)) {
        return $this->t('BBCode is active, but no tags are available.');
      }
      $tags = Markup::create(implode(', ', $tags));
      return $this->t('You may use these tags: @tags', ['@tags' => $tags]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    foreach ($this->tags() as $id => $plugin) {
      $this->tagsByName[$plugin->name] = $plugin;
    }

    $tree = $this->buildTree($text);
    $output = $this->renderTree($tree->content);

    // The core AutoP filter breaks inline tags that span multiple paragraphs.
    // Since there is no advantage in using <p></p> tags, this filter uses
    // ordinary <br /> tags which are usable inside inline tags.
    if ($this->settings['linebreaks']) {
      $output = nl2br($output);
    }

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
      if (isset($this->tagsByName[$tag->name])) {
        $tag->selfclosing = $this->tagsByName[$tag->name]->isSelfclosing();
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
    foreach ($tree as $root) {
      if (is_object($root)) {
        $root->content = $this->renderTree($root->content);
        $rendered = $this->renderTag($root);
        $root = $rendered !== NULL ? $rendered : $root->outerSource();
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
    return $this->tagsByName[$tag->name]->process($tag);
  }
}
