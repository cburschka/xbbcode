<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xbbcode\TagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tag delete form.
 */
class TagDeleteForm extends EntityDeleteForm {

  /**
   * Tag plugin manager.
   *
   * @var \Drupal\xbbcode\TagPluginManager
   */
  protected $manager;

  /**
   * Construct the TagDeleteForm.
   *
   * @param \Drupal\xbbcode\TagPluginManager $manager
   *   Tag plugin manager.
   */
  public function __construct(TagPluginManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.xbbcode'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->manager->clearCachedDefinitions();
  }

}
