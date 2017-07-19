<?php

namespace Drupal\xbbcode\Plugin\Filter;

use Drupal\Component\Utility\Xss;
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
 *     "tags" = ""
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
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['linebreaks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert linebreaks to HTML.'),
      '#default_value' => $this->settings['linebreaks'],
      '#description' => $this->t('Newline <code>\n</code> characters will become <code>&lt;br /&gt;</code> tags.'),
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
    $output = \Drupal::service('renderer')->render($output);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($text, $langcode) {
    return static::doPrepare($this->parser->parse($text));
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $tree = $this->parser->parse($text);
    static::filterXss($tree);

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
   *
   * @return string
   */
  public static function doPrepare(ElementInterface $node) {
    if ($node instanceof NodeElementInterface) {
      $content = [];
      foreach ($node->getChildren() as $child) {
        $content[] = static::doPrepare($child);
      }
      $content = implode('', $content);
      if ($node instanceof TagElementInterface) {
        $name = $node->getName();
        $processor = $node->getProcessor();
        if ($processor instanceof TagPluginInterface) {
          $content = $processor->prepare($content, $node);
        }
        return "[{$name}{$node->getArgument()}]{$content}[/{$name}]";
      }
      return $content;
    }

    return $node->render();
  }

  /**
   * Escape unsafe markup in text elements.
   *
   * @param \Drupal\xbbcode\Parser\Tree\NodeElementInterface $tree
   */
  public static function filterXss(NodeElementInterface $tree) {
    foreach ($tree->getDescendants() as $node) {
      if ($node instanceof TextElement) {
        $node->setText(Xss::filterAdmin($node->getText()));
      }
    }
  }

  /**
   * Add linebreaks inside text elements.
   *
   * @param \Drupal\xbbcode\Parser\Tree\NodeElementInterface $tree
   */
  public static function addLinebreaks(NodeElementInterface $tree) {
    foreach ($tree->getDescendants() as $node) {
      if ($node instanceof TextElement) {
        $node->setText(nl2br($node->getText()));
      }
    }
  }

}
