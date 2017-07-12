<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\xbbcode\Parser\Processor\CallbackTagProcessor;
use Drupal\xbbcode\Parser\XBBCodeParser;
use Drupal\xbbcode\Plugin\XBBCode\EntityTagPlugin;
use Drupal\xbbcode\TagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for creating and editing custom tags.
 */
class TagForm extends TagFormBase {

  /**
   * The tag storage.
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
   * Constructs a new TagForm.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The twig service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The tag storage.
   * @param \Drupal\xbbcode\TagPluginManager $manager
   *   The tag plugin manager.
   */
  public function __construct(TwigEnvironment $twig, EntityStorageInterface $storage, TagPluginManager $manager) {
    parent::__construct($twig);
    $this->storage = $storage;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig'),
      $container->get('entity_type.manager')->getStorage('xbbcode_tag'),
      $container->get('plugin.manager.xbbcode')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['name']['#attached']['library'] = ['xbbcode/tag-form'];

    $form['preview']['code'] += [
      '#prefix' => '<div id="ajax-preview">',
      '#suffix' => '</div>',
    ];

    // Update preview if the sample or the template are manually changed.
    // (The sample and the name are kept in sync locally.)
    $form['sample']['#ajax'] = $form['template_code']['#ajax'] = [
      'wrapper' => 'ajax-preview',
      'callback' => [$this, 'ajaxPreview'],
      // Don't refocus into the text field, and only update on change.
      'disable-refocus' => TRUE,
      'event' => 'change',
    ];

    // The preview may need to show error messages on update.
    $form['preview']['#attached']['library'] = ['classy/messages'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxPreview(array $form) {
    return $form['preview']['code'];
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity,
                                            array $form,
                                            FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    /** @var \Drupal\xbbcode\Entity\TagInterface $entity */
    $name = $entity->getName();

    // Ensure the input is safe for regex patterns, as it is not yet validated.
    if (!preg_match('/^\w+$/', $name)) {
      return;
    }

    // Reverse replacement of the tag name.
    $expression = '/(\[\/?)' . $name . '([\s\]=])/';
    $replace = '\1{{ name }}\2';
    $sample = preg_replace($expression, $replace, $form_state->getValue('sample'));
    $entity->set('sample', $sample);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\xbbcode\Entity\TagInterface $tag */
    $tag = $this->entity;

    // Set up a mock parser and do a practice run with this tag.
    $called = FALSE;
    $processor = new CallbackTagProcessor(function () use (&$called) {
      $called = TRUE;
    });
    $parser = new XBBCodeParser([$tag->getName() => $processor]);

    $sample = str_replace('{{ name }}', $tag->getName(), $tag->getSample());
    $tree = $parser->parse($sample);

    try {
      $template = $this->twig->loadTemplate(EntityTagPlugin::TEMPLATE_PREFIX . $tag->getTemplateCode());
      $processor->setProcess(function ($tag) use ($template, &$called) {
        $called = TRUE;
        return $template->render(['tag' => $tag]);
      });
    }
    catch (\Twig_Error $exception) {
      $error = str_replace(EntityTagPlugin::TEMPLATE_PREFIX, '', $exception->getMessage());
      $form_state->setError($form['template_code'], $this->t('The template could not be compiled: @error', ['@error' => $error]));
    }

    try {
      $tree->render();
    }
    catch (\Throwable $exception) {
      $form_state->setError($form['template_code'], $this->t('An error occurred while rendering the template: @error', ['@error' => $exception->getMessage()]));
    }

    if (!$called) {
      $form_state->setError($form['sample'], $this->t('The sample code should contain a valid example of the tag.'));
    }
  }

  /**
   * Determines if the tag already exists.
   *
   * @param string $tag_id
   *   The tag ID.
   *
   * @return bool
   *   TRUE if the tag exists, FALSE otherwise.
   */
  public function exists($tag_id) {
    return (bool) $this->storage->getQuery()->condition('id', $tag_id)->execute();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    if ($result === SAVED_NEW) {
      drupal_set_message($this->t('The BBCode tag %tag has been created.', ['%tag' => $this->entity->label()]));
    }
    elseif ($result === SAVED_UPDATED) {
      drupal_set_message($this->t('The BBCode tag %tag has been updated.', ['%tag' => $this->entity->label()]));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
