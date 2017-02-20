<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirmation for deleting a custom tag.
 */
class TagDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('xbbcode.admin_tags');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the tag %tag?', ['%tag' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The tag will be removed entirely. Anywhere it is used in text, it will be displayed as entered.');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('The BBCode tag %tag has been deleted.', ['%tag' => $this->entity->label()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
