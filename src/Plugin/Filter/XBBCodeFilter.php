<?php

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\xbbcode\Element;
use Drupal\xbbcode\Entity\TagSet;
use Drupal\xbbcode\Plugin\TagPluginInterface;
use Drupal\xbbcode\RootElement;
use Drupal\xbbcode\TagPluginCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class XBBCodeFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The tag plugins.
   *
   * @var \Drupal\xbbcode\TagPluginCollection
   */
  protected $tags;

  const RE_TAG = '/\[(?<closing>\/)(?<name1>[a-z0-9_]+)\]|\[(?<name2>[a-z0-9_]+)(?<extra>(?<attr>(?:\s+(?<key>\w+)=(?:\'(?<val1>(?:[^\\\\\']|\\\\[\\\\\'])*)\'|\"(?<val2>(?:[^\\\\\"]|\\\\[\\\\\"])*)\"|(?=[^\'"\s])(?<val3>(?:[^\\\\\'\"\s\]]|\\\\[\\\\\'\"\s\]])*)))*)|=(?<option>(?:[^\\\\\]]|\\\\[\\\\\]])*))\]/';
  const RE_INTERNAL = '/\[(?<closing>\/)xbbcode:(?<name1>[a-z0-9_]+)\]|\[xbbcode:(?<name2>[a-z0-9_]+):(?<extra>[A-Za-z0-9+\/]*=*):(?<start>\d+):(?<end>\d+)\]/';

  /**
   * XBBCodeFilter constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\xbbcode\TagPluginCollection $tags
   *   Tag plugins.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              TagPluginCollection $tags) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tags = $tags;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    if (!empty($configuration['settings']['tags'])) {
      /** @var \Drupal\xbbcode\Entity\TagSetInterface $tagSet */
      $storage = $container->get('entity_type.manager')->getStorage('xbbcode_tag_set');
      $tagSet = $storage->load($configuration['settings']['tags']);
      $tags = $tagSet->getPluginCollection();
    }
    else {
      $manager = $container->get('plugin.manager.xbbcode');
      $tags = TagPluginCollection::createDefaultCollection($manager);
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $tags
    );
  }

  /**
   * Create a new filter using only a plugin collection.
   *
   * @param \Drupal\xbbcode\TagPluginCollection $tags
   *   The tag plugins.
   *
   * @return \Drupal\xbbcode\Plugin\Filter\XBBCodeFilter
   *   A bare filter instance.
   */
  public static function createFromCollection(TagPluginCollection $tags) {
    return new static(['settings' => ['linebreaks' => TRUE]], NULL, ['provider' => NULL], $tags);
  }

  /**
   * Create a new filter that only processes a single tag.
   *
   * @param \Drupal\xbbcode\Plugin\TagPluginInterface $tag
   *   A single tag plugin.
   *
   * @return \Drupal\xbbcode\Plugin\Filter\XBBCodeFilter
   *   A bare filter instance.
   */
  public static function createFromTag(TagPluginInterface $tag) {
    return static::createFromCollection(TagPluginCollection::createFromTags([$tag->getName() => $tag]));
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

    $options = [];
    foreach (TagSet::loadMultiple() as $id => $tag) {
      /** @var \Drupal\xbbcode\Entity\TagSetInterface $tag */
      $options[$id] = $tag->label();
    }
    $form['tags'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Tag set'),
      '#empty_value'   => '',
      '#default_value' => $this->settings['tags'],
      '#options'       => $options,
      '#description'   => $this->t('Without a <a href=":url">tag set</a>, this filter will use all available tags with default settings.', [
        ':url' => Url::fromRoute('entity.xbbcode_tag_set.collection')->toString(),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $output = $this->tags->getTable();
      $output['#caption'] = $this->t('You may use the following BBCode tags:');
    }
    else {
      $output = $this->tags->getSummary();
      $output['#prefix'] = $this->t('You may use the following BBCode tags:') . ' ';
    }

    // TODO: Remove once FilterInterface::tips() is modernized.
    $output = \Drupal::service('renderer')->render($output);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($text, $langcode) {
    // Find all opening and closing tags in the text.
    $matches = [];
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
      if ($this->tags->has($match['name'])) {
        if ($match['closing'][0]) {
          if ($open_by_name[$match['name']] > 0) {
            do {
              $last = array_pop($tag_stack);
              $open_by_name[$last['name']]--;
            } while ($last['name'] !== $match['name']);
            $last['end'] = $match[0][1];
            $convert[$last['id']] = $last;
            $convert[$i] = $match;
          }
        }
        else {
          $match['id'] = $i;
          $tag_stack[] = $match;
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
      /** @var \Drupal\xbbcode\Plugin\TagPluginInterface $tag */
      $tag = $this->tags[$name];
      $attached = BubbleableMetadata::mergeAttachments($attached, $tag->getAttachments());
    }

    $result = new FilterProcessResult($output);
    $result->setAttachments($attached);
    return $result;
  }

  /**
   * Prepare and process a string directly.
   *
   * @param string $text
   *   A string to process.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The result of applying the filter.
   */
  public function processFull($text) {
    return $this->process($this->prepare($text, NULL), NULL);
  }

  /**
   * Build the tag tree from a text.
   *
   * @param string $text
   *   The prepared text to parse.
   * @param string $source
   *   The original source text.
   *
   * @return \Drupal\xbbcode\RootElement
   *   A virtual element containing the input text.
   */
  private function buildTree($text, $source) {
    // Find all opening and closing tags in the text.
    $matches = [];
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
        $tag = new Element($match, $source, $this->tags[$match['name']]);
        end($stack)->append(substr($text, end($stack)->index, $match[0][1] - end($stack)->index));
        $stack[] = $tag;
      }
    }

    $root = array_pop($stack);
    $root->append(substr($text, $root->index));
    return $root;
  }

}
