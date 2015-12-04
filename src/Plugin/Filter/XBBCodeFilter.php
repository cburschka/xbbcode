<?php

/**
 * @file
 * Contains Drupal\xbbcode\Plugin\Filter\XBBCodeFilter.
 */

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\xbbcode\Element;
use Drupal\xbbcode\Form\PluginSelectionForm;
use Drupal\xbbcode\RootElement;
use Drupal\xbbcode\TagPluginCollection;

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

    $this->tagCollection = new TagPluginCollection(Drupal::service('plugin.manager.xbbcode'), $this->tags);
  }

  /**
   * Return the TagPluginCollection, or find a particular tag by its ID.
   *
   * This collection contains all available plugins, enabled or not.
   *
   * @param string $instance_id
   * @return TagPluginCollection
   */
  public function tags($instance_id = NULL) {
    $this->tagCollection->sort();

    if (isset($instance_id)) {
      return $this->tagCollection->get($instance_id);
    }
    return $this->tagCollection;
  }

  /**
   * Return the enabled tags indexed by name, or find a particular tag from its name.
   *
   * @param string $name
   * @return array
   */
  public function tagsByName($name = NULL) {
    if (!isset($this->tagsByName)) {
      foreach ($this->tags() as $id => $plugin) {
        if ($plugin->status) {
          $this->tagsByName[$plugin->name] = $plugin;
        }
      }
      ksort($this->tagsByName);
    }
    if (isset($name)) {
      if (isset($this->tagsByName[$name])) {
        return $this->tagsByName[$name];
      }
      else {
        return NULL;
      }
    }
    return $this->tagsByName;
  }

  /**
   * {@inheritdoc}
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

    $form = PluginSelectionForm::buildPluginForm($form, $this->tags());
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
      foreach ($this->tagsByName() as $id => $tag) {
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
            '#markup' => Markup::create($this->process($tag->getSample(), NULL)->getProcessedText()),
            '#attributes' => ['class' => ['get']],
          ],
        ];
      }
      return Drupal::service('renderer')->render($table);
    }
    else {
      $tags = [
        '#theme' => 'item_list',
        '#prefix' => $this->t('You may use these tags:'),
        '#wrapper_attributes' => ['class' => ['xbbcode-tips-list']],
        '#attached' => ['library' => ['xbbcode/filter-tips']],
        '#items' => [],
      ];
      foreach ($this->tagsByName() as $id => $tag) {
        $tags['#items'][$tag->name] = [
          '#type' => 'inline_template',
          '#template' => '<abbr title="{{ tag.description }}">[{{ tag.name }}]</abbr>',
          '#context' => ['tag' => $tag],
        ];
      }
      ksort($tags['#items']);
      if (!$tags['#items']) {
        return $this->t('BBCode is active, but no tags are available.');
      }
      return Drupal::service('renderer')->render($tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    list($tree, $tags) = $this->buildTree($text);
    $output = $this->renderTree($tree->content);

    // The core AutoP filter breaks inline tags that span multiple paragraphs.
    // Since there is no advantage in using <p></p> tags, this filter uses
    // ordinary <br /> tags which are usable inside inline tags.
    if ($this->settings['linebreaks']) {
      $output = nl2br($output);
    }

    $attached = [];
    foreach ($tags as $name) {
      $tag = $this->tagsByName($name)->getAttachments();
      $attached = BubbleableMetadata::mergeAttachments($attached, $tag);
    }

    $result = new FilterProcessResult($output);
    $result->setAttachments($attached);
    return $result;
  }

  /**
   * Build the tag tree from a text.
   *
   * @param type $text
   * @return array
   *   Two values: The tree and a (unique) array of all tag names encountered.
   */
  private function buildTree($text) {
    // Find all opening and closing tags in the text.
    preg_match_all(XBBCODE_RE_TAG, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    // Initialize the name tracker, and the list of valid tags.
    $open_by_name = [];
    $tags = [];
    $foundTags = [];
    foreach ($matches as $match) {
      $tag = new Element($match);
      if ($this->tagsByName($tag->name)) {
        $tag->selfclosing = $this->tagsByName($tag->name)->isSelfclosing();
        $tags[] = $tag;
        $open_by_name[$tag->name] = 0;
      }
    }

    // Initialize the stack with a root element.
    $stack = [new RootElement()];
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
        $foundTags[$tag->name] = $tag->name;
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
        $foundTags[$tag->name] = $tag->name;
      }
    }

    // Add the remainder of the text, and then break any tags still open.
    end($stack)->advance($text, strlen($text));
    while (count($stack) > 1) {
      $dangling = array_pop($stack);
      end($stack)->breakTag($dangling);
    }
    return [end($stack), $foundTags];
  }

  /**
   * Render a tag tree to HTML.
   *
   * @param array $tree
   * @return string
   */
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
  private function renderTag(Element $tag) {
    return $this->tagsByName($tag->name)->process($tag);
  }
}
