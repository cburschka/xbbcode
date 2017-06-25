<?php

namespace Drupal\xbbcode\Form;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\xbbcode\Parser\Processor\CallbackTagProcessor;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Parser\XBBCodeParser;
use Drupal\xbbcode\Plugin\Filter\XBBCodeFilter;
use Drupal\xbbcode\Plugin\XBBCode\EntityTagPlugin;
use Drupal\xbbcode\PreparedTagElement;

/**
 * Base form for custom tags.
 */
class TagFormBase extends EntityForm {

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
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#weight'        => -30,
    ];
    $form['id'] = [
      '#type'          => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#maxlength'     => 255,
      '#machine_name'  => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#disabled'      => !$this->entity->isNew(),
      '#weight'        => -20,
    ];

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
        $template_code = rtrim($this->twig->getLoader()->getSource($file));
      }
      catch (\Twig_Error_Loader $exception) {
        watchdog_exception('xbbcode', $exception);
        drupal_set_message($exception->getMessage(), 'error');
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
    ];

    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Coding help'),
      '#open' => FALSE,
    ];

    $form['help']['variables'] = [
      '#theme'        => 'xbbcode_help',
      '#title'        => $this->t('The above field should be filled with <a href="http://twig.sensiolabs.org/documentation">Twig</a> template code. The following variables are available for use:'),
      '#label_prefix' => 'tag.',
      '#rows'         => [
        'content'     => $this->t('The text between opening and closing tags, after rendering nested elements. Example: <code>[url=http://www.drupal.org]<strong>Drupal</strong>[/url]</code>'),
        'option'      => $this->t('The single tag attribute, if one is entered. Example: <code>[url=<strong>http://www.drupal.org</strong>]Drupal[/url]</code>.'),
        'attribute'   => [
          'suffix'      => ['s.*', "('*')"],
          'description' => $this->t('A named tag attribute. Example: <code>{{ tag.attributes.by }}</code> for <code>[quote by=<strong>Author</strong> date=2008]Text[/quote]</code>.'),
        ],
        'source'      => $this->t('The source content of the tag. Example: <code>[code]<strong>&lt;strong&gt;[i]...[/i]&lt;/strong&gt;</strong>[/code]</code>.'),
        'outerSource' => $this->t('The content of the tag, wrapped in the original opening and closing elements. Example: <code><strong>[url=http://www.drupal.org]Drupal[/url]</strong></code>.<br/>
          This can be printed to render the tag as if it had not been processed.'),
      ],
    ];

    $form['preview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Preview'),
    ];

    try {
      $template = $this->twig->loadTemplate(EntityTagPlugin::TEMPLATE_PREFIX . "\n" . $template_code);
      $processor = new CallbackTagProcessor(function (TagElementInterface $element) use ($template) {
        return $template->render(['tag' => new PreparedTagElement($element)]);
      });
      $parser = new XBBCodeParser([$tag->getName() => $processor]);
      $tree = $parser->parse($sample);
      XBBCodeFilter::filterXss($tree);
      $output = $tree->render();
      $form['preview']['code']['#markup'] = Markup::create($output);
    }
    catch (\Twig_Error $exception) {
      drupal_set_message($exception->getRawMessage(), 'error');
      $form['preview']['code']['template'] = $this->templateError($exception);
    }

    return parent::form($form, $form_state);
  }

  /**
   * Render the code of a broken template with line numbers.
   *
   * @param \Twig_Error $exception
   *   The twig error for an inline template.
   *
   * @return mixed
   *   The HTML string.
   */
  public function templateError(\Twig_Error $exception) {
    $template = $exception->getTemplateFile();
    $lines = explode("\n", $template);
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

}
