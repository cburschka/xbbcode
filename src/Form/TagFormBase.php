<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\TwigEnvironment;

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
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -30,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#maxlength' => 255,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#disabled' => !$this->entity->isNew(),
      '#weight' => -20,
    ];

    /** @var \Drupal\xbbcode\Entity\TagInterface $tag */
    $tag = $this->entity;

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $tag->getDescription(),
      '#description' => $this->t('Describe this tag. This will be shown in the filter tips and on administration pages.'),
      '#required' => TRUE,
      '#rows' => max(5, substr_count($tag->getDescription(), "\n")),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default name'),
      '#default_value' => $tag->getName(),
      '#description' => $this->t('The default code name of this tag. It must contain only lowercase letters, numbers and underscores.'),
      '#field_prefix' => '[',
      '#field_suffix' => ']',
      '#maxlength' => 32,
      '#size' => 16,
      '#required' => TRUE,
      '#pattern' => '\w+',
    ];

    $form['sample'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sample code'),
      '#attributes' => ['style' => 'font-family:monospace'],
      '#default_value' => str_replace('{{ name }}', $tag->getName(), $tag->getSample()),
      '#description' => $this->t('Give an example of how this tag should be used.'),
      '#required' => TRUE,
      '#rows' => max(5, substr_count($tag->getSample(), "\n")),
    ];

    $form['editable'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

    $form['template_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template code'),
      '#attributes' => ['style' => 'font-family:monospace'],
      '#default_value' => $tag->getTemplateCode(),
      '#description' => $this->t('The template for rendering this tag.'),
      '#required' => TRUE,
      '#rows' => max(15, substr_count($tag->getTemplateCode(), "\n")),
    ];

    $form['help'] = [
      '#type' => 'inline_template',
      '#title' => $this->t('Coding help'),
      '#template' => '<p>{{ header }}</p>
        <dl>
          {% for var, description in vars %}
          <dt><code>{{ "{{ " ~ var ~ " }}" }}</code></dt>
          <dd>{{ description }}</dd>
          {% endfor %}
        </dl>',
      '#context' => [
        'header' => $this->t('The above field should be filled with <a href="http://twig.sensiolabs.org/documentation">Twig</a> template code. The following variables are available for use:'),
        'vars' => [
          'tag.content' => $this->t('The text between opening and closing tags. Example: <code>[url=http://www.drupal.org]<strong>Drupal</strong>[/url]</code>'),
          'tag.option' => $this->t('The single tag attribute, if one is entered. Example: <code>[url=<strong>http://www.drupal.org</strong>]Drupal[/url]</code>.'),
          'tag.attr.*' => $this->t('A named tag attribute. Example: <code>{{ tag.attr.by }}</code> for <code>[quote by=<strong>Author</strong> date=2008]Text[/quote]</code>.'),
          'tag.source' => $this->t('The source content of the tag. Example: <code>[code]<strong>&lt;strong&gt;[i]...[/i]&lt;/strong&gt;</strong>[/code]</code>.'),
          'tag.outerSource' => $this->t('The content of the tag, wrapped in the original opening and closing elements. Example: <code><strong>[url=http://www.drupal.org]Drupal[/url]</strong></code>.<br/>
            This can be printed to render the tag as if it had not been processed.'),
        ],
      ],
    ];

    $form['warning'] = [
      '#type' => 'item',
      '#markup' => $this->t("<strong>Warning: Do not print these variables using <code>raw</code>.</strong> The attribute and option variables bypass the text format's other filters, and contain unsafe user input."),
    ];

    return parent::form($form, $form_state);
  }

}
