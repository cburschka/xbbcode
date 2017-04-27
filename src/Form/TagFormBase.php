<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\xbbcode\Parser\CallbackTagProcessor;
use Drupal\xbbcode\Parser\TagElementInterface;
use Drupal\xbbcode\Parser\XBBCodeParser;
use Drupal\xbbcode\Plugin\XBBCode\EntityTagPlugin;

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
   * TagViewForm constructor.
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

    $form['template_code'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Template code'),
      '#attributes'    => ['style' => 'font-family:monospace'],
      '#default_value' => $tag->getTemplateCode(),
      '#description'   => $this->t('The template for rendering this tag.'),
      '#required'      => TRUE,
      '#rows'          => max(5, substr_count($tag->getTemplateCode(), "\n")),
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

    try {
      $template = $this->twig->loadTemplate(EntityTagPlugin::TEMPLATE_PREFIX . $tag->getTemplateCode());
      $processor = new CallbackTagProcessor(function (TagElementInterface $element) use ($template) {
        try {
          return $template->render(['tag' => $element]);
        }
        catch (\Error $exception) {
          drupal_set_message($exception->getMessage(), 'error');
          return $element->getOuterSource();
        }
      });
    }
    catch (\Exception $exception) {
      $error = str_replace(EntityTagPlugin::TEMPLATE_PREFIX, '', $exception->getMessage());
      $processor = new CallbackTagProcessor(function (TagElementInterface $element) use ($error) {
        drupal_set_message($error, 'error');
        return $element->getOuterSource();
      });
    }

    $parser = new XBBCodeParser([$tag->getName() => $processor]);

    $form['preview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Preview'),
      'code' => [
        '#markup' => $parser->parse($sample)->render(),
      ],
    ];

    return parent::form($form, $form_state);
  }

}
