<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xbbcode\TagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for creating and editing custom tags.
 */
class TagForm extends TagFormBase {
  /**
   * @var \Drupal\xbbcode\TagPluginManager
   */
  protected $manager;

  /**
   * Constructs a new FilterFormatFormBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param \Drupal\xbbcode\TagPluginManager $manager
   */
  public function __construct(EntityStorageInterface $storage, TagPluginManager $manager) {
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
      $container->get('entity_type.manager')->getStorage('xbbcode_tag'),
      $container->get('plugin.manager.xbbcode')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $name = &$form_state->getValue('name');
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
   *   TRUE if the tag exists, FALSE otherwise.
   */
  public function exists($tag_id) {
    return (bool) $this->storage->getQuery()
      ->condition('id', $tag_id)
      ->execute();
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
