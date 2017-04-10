<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\TwigEnvironment;
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\xbbcode\Entity\TagInterface $tag */
    $tag = $this->entity;

    $name = $tag->getName();
    if (!preg_match('/^[a-z0-9_]+$/', $name)) {
      $form_state->setError($form['name'], $this->t('The name must consist of lower-case letters, numbers and underscores.'));
    }

    $sample = str_replace('{{ name }}', $name, $tag->getSample());

    $tokens = XBBCodeParser::tokenize($sample, [$name => $name]);
    $tokens = XBBCodeParser::validateTokens($tokens);
    if (count($tokens) < 2) {
      $form_state->setError($form['sample'], $this->t('The sample code should contain a valid example of the tag.'));
    }

    try {
      $this->twig->loadTemplate(EntityTagPlugin::TEMPLATE_PREFIX . $tag->getTemplateCode());
    }
    catch (\Twig_Error $exception) {
      $error = str_replace(EntityTagPlugin::TEMPLATE_PREFIX, '', $exception->getMessage());
      $form_state->setError($form['template_code'], $this->t('The twig code could not be compiled: @error', ['@error' => $error]));
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
    $this->manager->clearCachedDefinitions();
    if ($result === SAVED_NEW) {
      drupal_set_message($this->t('The BBCode tag %tag has been created.', ['%tag' => $this->entity->label()]));
    }
    elseif ($result === SAVED_UPDATED) {
      drupal_set_message($this->t('The BBCode tag %tag has been updated.', ['%tag' => $this->entity->label()]));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
