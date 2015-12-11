<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\TagForm.
 */

namespace Drupal\xbbcode\Form;

use Drupal;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory as QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for custom tags.
 */
abstract class TagForm extends EntityForm {
  /**
   * The entity query factory.
   *
   * @var QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new FilterFormatFormBase.
   *
   * @param QueryFactory $query_factory
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

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $tag = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $tag->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -30,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $tag->id(),
      '#maxlength' => 255,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#disabled' => !$tag->isNew(),
      '#weight' => -20,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $tag->getDescription(),
      '#description' => $this->t('Describe this tag. This will be shown in the filter tips and on administration pages.'),
      '#required' => TRUE,
      '#rows' => max(5, count(explode("\n", $tag->getDescription()))),
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
    ];

    $form['sample'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sample code'),
      '#attributes' => ['style' => 'font-family:monospace'],
      '#default_value' => $tag->getSample(),
      '#description' => $this->t('Give an example of how this tag should be used. Use "<code>{{ name }}</code>" in place of the tag name.'),
      '#required' => TRUE,
      '#rows' => max(5, count(explode("\n", $tag->getSample()))),
    ];

    $form['selfclosing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Self-closing'),
      '#default_value' => $tag->isSelfclosing(),
      '#description' => $this->t('The tag is self-closing and requires no closing tag, like <code>[hr]</code>).'),
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
      '#rows' => max(15, count(explode("\n", $tag->getTemplateCode()))),
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
        <dt><code>tag.attr.*</code></dt>
        <dd>A named tag attribute. Example: <strong>{{ tag.attr.by }}}</strong> for <code>[quote&nbsp;by=<strong>Author</strong>&nbsp;date=2008]Text[/quote]</code>.</dd>
        <dt><code>tag.source</code></dt>
        <dd>The original text content of the tag. Example: <code>[code]<strong>[i]...[/i]</strong>[/code]</code>.</dd>
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
      $form_state->setErrorByName('name', $this->t('The name [%name] must consist of lower-case letters, numbers and underscores.', ['%name' => $name]));
    }
  }

  /**
   * Determines if the tag already exists.
   *
   * @param string $tag_id
   *   The tag ID.
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    Drupal::service('plugin.manager.xbbcode')->clearCachedDefinitions();
    $form_state->setRedirectUrl(new Url('xbbcode.admin_tags'));
  }

}
