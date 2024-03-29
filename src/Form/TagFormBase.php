<?php

namespace Drupal\xbbcode\Form;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\xbbcode\Entity\TagInterface;
use Drupal\xbbcode\Parser\Processor\CallbackTagProcessor;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Parser\XBBCodeParser;
use Drupal\xbbcode\Plugin\Filter\XBBCodeFilter;
use Drupal\xbbcode\Plugin\XBBCode\EntityTagPlugin;
use Drupal\xbbcode\PreparedTagElement;
use Twig\Error\Error as TwigError;

/**
 * Base form for custom tags.
 */
class TagFormBase extends EntityForm {

  use LabeledFormTrait;

  /**
   * The twig service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * TagFormBase constructor.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The twig service.
   */
  public function __construct(TwigEnvironment $twig) {
    $this->twig = $twig;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = $this->addLabelFields($form);

    /** @var \Drupal\xbbcode\Entity\TagInterface $tag */
    $tag = $this->entity;
    $sample = str_replace('{{ name }}', $tag->getName(), $tag->getSample());

    $form['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#default_value' => $tag->getDescription(),
      '#description'   => $this->t('Describe this tag. This will be shown in the filter tips and on administration pages.'),
      '#required'      => TRUE,
      '#rows'          => max(5, substr_count($tag->getDescription(), "\n")),
    ];

    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Default name'),
      '#default_value' => $tag->getName(),
      '#description'   => $this->t('The default code name of this tag. It must contain only lowercase letters, numbers, hyphens and underscores.'),
      '#field_prefix'  => '[',
      '#field_suffix'  => ']',
      '#maxlength'     => 32,
      '#size'          => 16,
      '#required'      => TRUE,
      '#pattern'       => '[a-z0-9_-]+',
    ];

    $form['sample'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Sample code'),
      '#attributes'    => ['style' => 'font-family:monospace'],
      '#default_value' => $sample,
      '#description'   => $this->t('Give an example of how this tag should be used.'),
      '#required'      => TRUE,
      '#rows'          => max(5, substr_count($tag->getSample(), "\n")),
    ];

    $form['editable'] = [
      '#type'  => 'value',
      '#value' => TRUE,
    ];

    $template_code = $tag->getTemplateCode();

    // Load the template code from a file if necessary.
    // Not used for custom tags, but allows replacing files with inline code.
    if (!$template_code && $file = $tag->getTemplateFile()) {
      // The source must be loaded directly, because the template class won't
      // have it unless it is loaded from the file cache.
      try {
        $path = $this->twig->load($file)->getSourceContext()->getPath();
        $template_code = rtrim(file_get_contents($path));
      }
      catch (TwigError $exception) {
        watchdog_exception('xbbcode', $exception);
        $this->messenger()->addError($exception->getMessage());
      }
    }

    $form['template_code'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Template code'),
      '#attributes'    => ['style' => 'font-family:monospace'],
      '#default_value' => $template_code,
      '#description'   => $this->t('The template for rendering this tag.'),
      '#required'      => TRUE,
      '#rows'          => max(5, 1 + substr_count($template_code, "\n")),
      '#attached'      => $tag->getAttachments(),
    ];

    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Coding help'),
      '#open' => FALSE,
    ];

    $form['help']['variables'] = [
      '#theme'        => 'xbbcode_help',
      '#title'        => $this->t('The above field should be filled with <a href="https://twig.symfony.com/doc/2.x/">Twig</a> template code. The following variables are available for use:'),
      '#label_prefix' => 'tag.',
      '#rows'         => [
        'content'     => $this->t('The text between opening and closing tags, after rendering nested elements. Example: <code>[url=https://www.drupal.org]<strong>Drupal</strong>[/url]</code>'),
        'option'      => $this->t('The single tag attribute, if one is entered. Example: <code>[url=<strong>https://www.drupal.org</strong>]Drupal[/url]</code>.'),
        'attribute'   => [
          'suffix'      => ['s.*', "('*')"],
          'description' => $this->t('A named tag attribute. Example: <code>{{ tag.attributes.by }}</code> for <code>[quote by=<strong>Author</strong> date=2008]Text[/quote]</code>.'),
        ],
        'source'      => $this->t('The source content of the tag. Example: <code>[code]<strong>&lt;strong&gt;[i]...[/i]&lt;/strong&gt;</strong>[/code]</code>.'),
        'outerSource' => $this->t('The content of the tag, wrapped in the original opening and closing elements. Example: <code><strong>[url=https://www.drupal.org]Drupal[/url]</strong></code>.<br/>
          This can be printed to render the tag as if it had not been processed.'),
      ],
    ];

    $form['preview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Preview'),
    ];

    try {
      $template = $this->twig->load(EntityTagPlugin::TEMPLATE_PREFIX . "\n" . $template_code);
      $processor = new CallbackTagProcessor(static function (TagElementInterface $element) use ($template) {
        return $template->render(['tag' => new PreparedTagElement($element)]);
      });
      $parser = new XBBCodeParser([$tag->getName() => $processor]);
      $tree = $parser->parse($sample);
      XBBCodeFilter::filterXss($tree);
      $output = $tree->render();
      $form['preview']['code']['#markup'] = Markup::create($output);
    }
    catch (TwigError $exception) {
      $this->messenger()->addError($exception->getRawMessage());
      $form['preview']['code']['template'] = $this->templateError($exception);
    }

    $form['attached'] = [
      '#type'  => 'details',
      '#title' => $this->t('Attachments (advanced)'),
      '#description' => $this->t('Changes are not reflected in the preview until the form is saved.'),
      '#open'  => FALSE,
      '#tree'  => TRUE,
    ];
    $libraries = $tag->getAttachments()['library'] ?? [];
    $form['attached']['library'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Libraries'),
      '#default_value' => implode("\n", $libraries),
      '#rows'          => max(1, 1 + count($libraries)),
      '#description'   => $this->t(
        'Libraries are static assets (scripts and stylesheets) <a href=":url">defined by modules or themes</a>, to be included wherever this tag is rendered. Specify one library per line, in the form <code>module_name/library_name</code>.',
        [
          ':url' => 'https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module#library',
        ]
      ),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state): void {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    assert($entity instanceof TagInterface);
    $attached = [];
    if ($libraries = trim($form_state->getValue(['attached', 'library']))) {
      $attached['library'] = explode("\n", $libraries);
    }
    $entity->set('attached', $attached);
  }

  /**
   * Render the code of a broken template with line numbers.
   *
   * @param \Twig\Error\Error $exception
   *   The twig error for an inline template.
   *
   * @return array
   *   Render array showing the code with the error's line highlighted.
   */
  public function templateError(TwigError $exception): array {
    $source = $exception->getSourceContext();
    $code = $source ? $source->getCode() : '';

    $lines = explode("\n", $code);
    // Remove the inline template header.
    array_shift($lines);
    $number = $exception->getTemplateLine() - 2;

    $output = [
      '#prefix' => '<pre class="template">',
      '#suffix' => '</pre>',
    ];

    foreach ($lines as $i => $line) {
      $output[$i] = [
        '#prefix' => '<span>',
        '#suffix' => "</span>\n",
        '#markup' => new HtmlEscapedText($line),
      ];
    }
    $output[$number]['#prefix'] = '<span class="line-error">';

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    $entity = parent::getEntity();
    assert($entity instanceof EntityInterface);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);

    if (!$this->entity->isNew()) {
      // Add access check on the save button.
      if (isset($actions['submit'])) {
        $actions['submit']['#access'] = $this->entity->access('update');
      }

      try {
        $actions['copy'] = [
          '#type'       => 'link',
          '#attributes' => ['class' => ['button']],
          '#title'      => $this->t('Copy'),
          '#access'     => $this->entity->access('create'),
          '#url'        => $this->entity->toUrl('copy-form'),
        ];
      }
      catch (EntityMalformedException $e) {
      }

    }
    return $actions;
  }

}
