<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\XBBCodeCustomTagFormBase.
 */


namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactory as QueryFactory2;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for custom tags.
 */
abstract class XBBCodeTagFormBase extends EntityForm {
  /**
   * The entity query factory.
   *
   * @var QueryFactory2
   */
  protected $queryFactory;

  /**
   * Constructs a new FilterFormatFormBase.
   *
   * @param QueryFactory2 $query_factory
   *   The entity query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  public function form(array $form, FormStateInterface $form_state) {
    $tag = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $tag->label(),      
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $tag->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH,
      '#disabled' => !$tag->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $tag->getDescription(),
      '#description' => t('Describe this tag. This will be shown in the filter tips and on administration pages.'),
      '#required' => TRUE,
    ];

    $form['default_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default name'),
      '#default_value' => $tag->getDefaultName(),
      '#description' => $this->t('The default code name of this tag. It must contain only lowercase letters, numbers and underscores.'),
      '#field_prefix' => '[',
      '#field_suffix' => ']',
      '#maxlength' => 32,
      '#size' => 16,
      '#required' => TRUE,
    ];


    $form['sample'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sample code'),
      '#default_value' => $tag->getSample(),
      '#description' => $this->t('Give an example of how this tag sould be used. Use <code>{{ name }}</code> in place of the tag name to allow configuration.'),
      '#required' => TRUE,
    ];

    $form['selfclosing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Self-closing'),
      '#default_value' => $tag->isSelfclosing(),
      '#description' => $this->t('The tag is self-closing and requires no closing tag, like <code>[hr]</code>).'),
    ];

    $form['template_code'] = [
      '#type' => 'textarea',
      '#attributes' => ['style' => 'font-family:monospace'],
      '#title' => $this->t('Template code'),
      '#default_value' => $tag->getTemplateCode(),
      '#description' => $this->t('The template for rendering this tag.'),
      '#required' => TRUE,
    ];

    $form['help'] = [
      '#type' => 'markup',
      '#title' => $this->t('Coding help'),
      '#markup' => $this->t('<p>The above field should be filled with <a href="http://twig.sensiolabs.org/documentation">Twig</a> template code.</p>
      <p>The following variables are available for use:</p>
      <dl>
        <dt><code>tag.content</code></dt>
        <dd>The text between opening and closing tags, if the tag is not self-closing. Example: <code>[url=http://www.drupal.org]<strong>Drupal</strong>[/url]</code></dd>
        <dt><code>tag.option</code></dt>
        <dd>The single tag attribute, if one is entered. Example: <code>[url=<strong>http://www.drupal.org</strong>]Drupal[/url]</code>.</dd>
        <dt><code>tag.attr.*</dt>
        <dd>A named tag attribute. Example: <strong>{{ tag.attr.by }}}</strong> for <code>[quote&nbsp;by=<strong>Author</strong>&nbsp;date=2008]Text[/quote]</code>.</dd>
      </dl>'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $name = $form_state->getValue('name');
    if (!preg_match('/^[a-z0-9_]+$/', $name)) {
      $form_state->setErrorByName('name', $this->t('The default name must consist of lower-case letters, numbers and underscores.'));
    }

    if ($form['edit']['name']['#default_value'] != $name) {
      if (xbbcode_custom_tag_exists($name)) {
        $form_state->setErrorByName('name', $this->t('This name is already taken. Please delete or edit the old tag, or choose a different name.'));
      }
    }
  }

  
  /**
   * Determines if the tag already exists.
   *
   * @param string $tag_id
   *   The tag ID
   *
   * @return bool
   *   TRUE if the format exists, FALSE otherwise.
   */
  public function exists($tag_id) {
    return (bool) $this->queryFactory
      ->get('xbbcode_tag')
      ->condition('id', $tag_id)
      ->execute();
  }
}
