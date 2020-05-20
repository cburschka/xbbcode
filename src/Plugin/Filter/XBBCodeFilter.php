<?php

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\xbbcode\Parser\Tree\ElementInterface;
use Drupal\xbbcode\Parser\Tree\NodeElementInterface;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Parser\Tree\TextElement;
use Drupal\xbbcode\Parser\XBBCodeParser;
use Drupal\xbbcode\Plugin\TagPluginInterface;
use Drupal\xbbcode\TagPluginManager;
use Drupal\xbbcode\TagProcessResult;
use Drupal\xbbcode\XssEscape;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter that converts BBCode to HTML.
 *
 * @Filter(
 *   id = "xbbcode",
 *   module = "xbbcode",
 *   title = @Translation("Extensible BBCode"),
 *   description = @Translation("Render <code>[bbcode]</code> tags to HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "linebreaks" = TRUE,
 *     "tags" = "",
 *     "xss" = TRUE,
 *   }
 * )
 */
class XBBCodeFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The tag set storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The tag plugin manager.
   *
   * @var \Drupal\xbbcode\TagPluginManager
   */
  protected $manager;

  /**
   * The tag plugins.
   *
   * @var \Drupal\xbbcode\TagPluginCollection
   */
  protected $tags;

  /**
   * The tag set (optional).
   *
   * @var \Drupal\xbbcode\Entity\TagSetInterface
   */
  protected $tagSet;

  /**
   * The parser.
   *
   * @var \Drupal\xbbcode\Parser\ParserInterface
   */
  protected $parser;

  /**
   * The cache tags that invalidate this filter.
   *
   * @var string[]
   */
  protected $cacheTags = [];

  /**
   * XBBCodeFilter constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The tag set storage.
   * @param \Drupal\xbbcode\TagPluginManager $manager
   *   The tag plugin manager.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityStorageInterface $storage,
                              TagPluginManager $manager) {
    $this->storage = $storage;
    $this->manager = $manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('xbbcode_tag_set'),
      $container->get('plugin.manager.xbbcode')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    if ($this->settings['tags'] &&
        $this->tagSet = $this->storage->load($this->settings['tags'])
    ) {
      $this->tags = $this->tagSet->getPluginCollection();
      $this->cacheTags = $this->tagSet->getCacheTags();
    }
    else {
      $this->tags = $this->manager->getDefaultCollection();
      // Without a tag set, invalidate it when any custom tag is created.
      $this->cacheTags = ['xbbcode_tag_new'];
    }
    $this->parser = new XBBCodeParser($this->tags);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['linebreaks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert linebreaks to HTML.'),
      '#default_value' => $this->settings['linebreaks'],
      '#description' => $this->t('Newline <code>\n</code> characters will become <code>&lt;br /&gt;</code> tags.'),
    ];

    $form['xss'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict unsafe HTML by escaping.'),
      '#default_value' => $this->settings['xss'],
      '#description' => $this->t('Do not disable this feature unless it interferes with other filters. Disabling it can make your site vulnerable to script injection, unless HTML is already restricted by other filters.'),
    ];

    $options = [];
    foreach ($this->storage->loadMultiple() as $id => $tag) {
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

    if ($collisions = $this->manager->getDefaultNameCollisions()) {
      $form['collisions'] = [
        '#theme'      => 'item_list',
        '#items'      => array_map(
          static function ($x) {
            return "[$x]";
          }, array_keys($collisions)
        ),
        '#prefix'     => $this->t(
          'The following default names are each used by multiple plugins. A tag set is needed to assign unique names; otherwise each name will be assigned to one of its plugins arbitrarily.'),
      ];
    }

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

    $output['#cache']['tags'] = $this->cacheTags;

    // TODO: Remove once FilterInterface::tips() is modernized.
    $output = Drupal::service('renderer')->render($output);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($text, $langcode): string {
    $tree = $this->parser->parse($text);
    return static::doPrepare($tree);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $tree = $this->parser->parse($text);

    if ($this->settings['xss']) {
      static::filterXss($tree);
    }

    // Reverse any HTML filtering in attribute and option strings.
    static::decodeHtml($tree);

    // The core AutoP filter breaks inline tags that span multiple paragraphs.
    // Since there is no advantage in using <p></p> tags, this filter uses
    // ordinary <br /> tags which are usable inside inline tags.
    if ($this->settings['linebreaks']) {
      static::addLinebreaks($tree);
    }

    $output = $tree->render();
    $result = new FilterProcessResult($output);
    $result->addCacheTags($this->cacheTags);
    foreach ($tree->getRenderedChildren() as $child) {
      if ($child instanceof TagProcessResult) {
        $result = $result->merge($child);
      }
    }

    return $result;
  }

  /**
   * Recursively apply source transformations to each tag element.
   *
   * @param \Drupal\xbbcode\Parser\Tree\ElementInterface $node
   *   The parse tree.
   *
   * @return string
   *   The fully prepared source.
   */
  public static function doPrepare(ElementInterface $node): string {
    if ($node instanceof NodeElementInterface) {
      $content = [];
      foreach ($node->getChildren() as $child) {
        $content[] = static::doPrepare($child);
      }
      $content = implode('', $content);
      if ($node instanceof TagElementInterface) {
        $processor = $node->getProcessor();
        if ($processor instanceof TagPluginInterface) {
          $content = $processor->prepare($content, $node);
        }
        return "[{$node->getOpeningName()}{$node->getArgument()}]{$content}[/{$node->getClosingName()}]";
      }
      return $content;
    }

    return $node->render();
  }

  /**
   * Reverse HTML encoding that other filters may have applied.
   *
   * The "option" and "attribute" values are provided to plugins as raw input
   * (and will be filtered by them before printing).
   *
   * @param \Drupal\xbbcode\Parser\Tree\NodeElementInterface $tree
   *   The parse tree.
   */
  public static function decodeHtml(NodeElementInterface $tree): void {
    $filter = static function (string $text): string {
      // If the string is free of raw HTML, decode its entities.
      if (!preg_match('/[<>"\']/', $text)) {
        $text = Html::decodeEntities($text);
      }
      return $text;
    };

    foreach ($tree->getDescendants() as $node) {
      if ($node instanceof TagElementInterface) {
        $node->setOption($filter($node->getOption()));
        $node->setAttributes(array_map($filter, $node->getAttributes()));
        $node->setSource($filter($node->getSource()));
      }
    }
  }

  /**
   * Escape unsafe markup in text elements and the source of tag elements.
   *
   * This is a safety feature that allows the BBCode processor to be used
   * on its own (without HTML restrictors) while still maintaining
   * markup safety.
   *
   * @param \Drupal\xbbcode\Parser\Tree\NodeElementInterface $tree
   *   The parse tree.
   */
  public static function filterXss(NodeElementInterface $tree): void {
    foreach ($tree->getDescendants() as $node) {
      if ($node instanceof TextElement) {
        $node->setText(XssEscape::filterAdmin($node->getText()));
      }
      if ($node instanceof TagElementInterface) {
        $node->setSource(XssEscape::filterAdmin($node->getSource()));
      }
    }
  }

  /**
   * Add linebreaks inside text elements.
   *
   * @param \Drupal\xbbcode\Parser\Tree\NodeElementInterface $tree
   *   The parse tree.
   */
  public static function addLinebreaks(NodeElementInterface $tree): void {
    foreach ($tree->getDescendants() as $node) {
      if ($node instanceof TextElement) {
        $node->setText(nl2br($node->getText()));
      }
    }
  }

}
