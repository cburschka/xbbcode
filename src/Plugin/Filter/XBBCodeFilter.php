<?php

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\xbbcode\Entity\TagSet;
use Drupal\xbbcode\Parser\XBBCodeParser;
use Drupal\xbbcode\Plugin\TagPluginInterface;
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

  /**
   * The parser.
   *
   * @var \Drupal\xbbcode\Parser\ParserInterface
   */
  protected $parser;

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
    $this->parser = new XBBCodeParser($tags);
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
    return $this->parser->parse($text)->prepare();
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $tree = $this->parser->parse($text);
    $output = $tree->render();

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

}
