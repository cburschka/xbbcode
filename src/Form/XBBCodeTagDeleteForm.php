<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\XBBCodeTagDeleteForm.
 */


namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Confirmation for deleting a custom tag.
 */
class XBBCodeTagDeleteForm extends EntityConfirmFormBase {
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Deleted tag %tag.', ['%format' => $this->entity->label()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }  
}
