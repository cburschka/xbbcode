<?php

/**
 * @file
 * Contains Drupal\xbbcode\Plugin\Filter\XBBCodeFilter.
 */

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
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
  private $tags;
  private $tagCollection;

  const RE_TAG = '/\[(?<closing>\/)(?<name1>[a-z0-9_]+)\]|\[(?<name2>[a-z0-9_]+)(?<extra>(?<attr>(?:\s+(?<key>\w+)=(?:\'(?<val1>(?:[^\\\\\']|\\\\[\\\\\'])*)\'|\"(?<val2>(?:[^\\\\\"]|\\\\[\\\\\"])*)\"|(?=[^\'"\s])(?<val3>(?:[^\\\\\'\"\s\]]|\\\\[\\\\\'\"\s\]])*)))*)|=(?<option>(?:[^\\\\\]]|\\\\[\\\\\]])*))\]/';
  const RE_INTERNAL = '/\[(?<closing>\/)xbbcode:(?<name1>[a-z0-9_]+)\]|\[xbbcode:(?<name2>[a-z0-9_]+):(?<extra>[A-Za-z0-9+\/]*=*):(?<start>\d+):(?<end>\d+)\]/';

  /**
   * Return the TagPluginCollection, or find a particular tag by its ID.
   *
   * This collection contains all available plugins, enabled or not.
   *
   * @param string $plugin_id
   *   The plugin ID (optional).
   *
   * @return TagPluginCollection | TagPluginInterface
   *   Either the entire collection or one tag plugin.
   */
  public function tags($plugin_id = NULL) {
    if (!isset($this->tags)) {
      $tags = $this->settings['override'] ? $this->settings['tags'] : Drupal::config('xbbcode.settings')->get('tags');
      // During installation, the global settings may not have been installed yet.
      $this->tags = !is_null($tags) ? $tags : [];

      $this->tagCollection = new TagPluginCollection(Drupal::service('plugin.manager.xbbcode'), $this->tags);
      $this->tagCollection->sort();
    }

    if (isset($plugin_id)) {
      return $this->tagCollection->get($plugin_id);
    }
    return $this->tagCollection;
  }

  /**
   * Return the enabled pluging indexed by name, or find one plugin by name.
   *
   * @param string $name
   *   The name of the tag plugin.
   *
   * @return array | TagPluginInterface
   *   Either the entire array or one tag plugin.
   */
  public function tagsByName($name = NULL) {
    if (!isset($this->tagsByName)) {
      $this->tagsByName = [];
      foreach ($this->tags() as $id => $plugin) {
        if ($plugin->status()) {
          $this->tagsByName[$plugin->getName()] = $plugin;
        }
      }
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
      '#title' => $this->t('Override the <a href="@url">global settings</a> with specific settings for this format.', [
        '@url' => Url::fromRoute('xbbcode.settings')->toString(),
      ]),
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
        '#header' => [
          $this->t('Tag Description'),
          $this->t('You Type'),
          $this->t('You Get'),
        ],
        '#empty' => $this->t('BBCode is active, but no tags are available.'),
      ];
      foreach ($this->tagsByName() as $name => $tag) {
        $table[$name] = [
          [
            '#type' => 'inline_template',
            '#template' => '<strong>[{{ tag.name }}]</strong><br /> {{ tag.description }}',
            '#context' => ['tag' => $tag],
            '#attributes' => ['class' => ['description']],
          ],
          [
            '#type' => 'inline_template',
            '#template' => '<code>{{ tag.sample|nl2br }}</code>',
            '#context' => ['tag' => $tag],
            '#attributes' => ['class' => ['type']],
          ],
          [
            '#markup' => Markup::create($this->process($this->prepare($tag->getSample(), NULL), NULL)->getProcessedText()),
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
      foreach ($this->tagsByName() as $name => $tag) {
        $tags['#items'][$name] = [
          '#type' => 'inline_template',
          '#template' => '<abbr title="{{ tag.description }}">[{{ tag.name }}]</abbr>',
          '#context' => ['tag' => $tag],
        ];
      }
      if (!$tags['#items']) {
        return $this->t('BBCode is active, but no tags are available.');
      }
      return Drupal::service('renderer')->render($tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($text, $langcode) {
    // Find all opening and closing tags in the text.
    preg_match_all(self::RE_TAG, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $open_by_name = [];
    $tag_stack = [];
    $convert = [];

    foreach ($matches as $i => $match) {
      $matches[$i]['name'] = !empty($match['name1'][0]) ? $match['name1'][0] : $match['name2'][0];
      $matches[$i]['start'] = $match[0][1] + strlen($match[0][0]);
      $open_by_name[$matches[$i]['name']] = 0;
    }

    foreach ($matches as $i => $match) {
      if ($tag = $this->tagsByName($match['name'])) {
        if ($match['closing'][0]) {
          if ($open_by_name[$match['name']] > 0) {
            do {
              $last = array_pop($tag_stack);
              $open_by_name[$last['name']]--;
            } while ($last['name'] != $match['name']);
            $last['end'] = $match[0][1];
            $convert[$last['id']] = $last;
            $convert[$i] = $match;
          }
        }
        else {
          $match['id'] = $i;
          array_push($tag_stack, $match);
          $open_by_name[$match['name']]++;
        }
      }
    }

    // Sort matched opening and closing tags by position.
    ksort($convert);

    // Generate the prepared text.
    $offset = 0;
    $output = '';
    foreach ($convert as $tag) {
      // Append everything up to the tag.
      $output .= substr($text, $offset, $tag[0][1] - $offset);
      if ($tag['closing'][0]) {
        $output .= "[/xbbcode:{$tag['name']}]";
      }
      else {
        $output .= '[xbbcode:' . $tag['name'] . ':' . base64_encode($tag['extra'][0]) . ':' . $tag['start'] . ':' . $tag['end'] . ']';
      }
      $offset = $tag['start'];
    }
    $output .= substr($text, $offset);

    $output = preg_replace('/\[(-*\/?xbbcode)\]/', '[-\1]', $output);
    $output .= '[xbbcode]' . base64_encode($text) . '[/xbbcode]';

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    preg_match('/\[xbbcode\]([a-zA-Z0-9+\/]*=*)\[\/xbbcode\]/', $text, $match, PREG_OFFSET_CAPTURE);

    // Cut the encoded source out of the text.
    $source = base64_decode($match[1][0]);
    $text = substr($text, 0, $match[0][1]) . substr($text, $match[0][1] + strlen($match[0][0]));
    $text = preg_replace('/\[-(-*\/?xbbcode)\]/', '[\1]', $text);

    $tree = $this->buildTree($text, $source);
    $output = $tree->getContent();

    // The core AutoP filter breaks inline tags that span multiple paragraphs.
    // Since there is no advantage in using <p></p> tags, this filter uses
    // ordinary <br /> tags which are usable inside inline tags.
    if ($this->settings['linebreaks']) {
      $output = nl2br($output);
    }

    $attached = [];
    foreach ($tree->getRenderedTags() as $name) {
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
   * @param string $text
   *   The source text to parse.
   *
   * @return array
   *   Two values: The tree and a (unique) array of all tag names encountered.
   */
  private function buildTree($text, $source) {
    // Find all opening and closing tags in the text.
    preg_match_all(self::RE_INTERNAL, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $stack = [new RootElement()];
    foreach ($matches as $match) {
      $match['name'] = !empty($match['name1'][0]) ? $match['name1'][0] : $match['name2'][0];
      if ($match['closing'][0]) {
        $last = array_pop($stack);
        $last->append(substr($text, $last->index, $match[0][1] - $last->index), $match[0][1]);
        end($stack)->append($last, $match[0][1] + strlen($match[0][0]));
      }
      else {
        $tag = new Element($match, $source, $this->tagsByName($match['name']));
        end($stack)->append(substr($text, end($stack)->index, $match[0][1] - end($stack)->index));
        array_push($stack, $tag);
      }
    }

    $root = array_pop($stack);
    $root->append(substr($text, $root->index));
    return $root;
  }

}
