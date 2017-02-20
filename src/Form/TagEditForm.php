<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * A form for editing an XBBCode tag.
 */
class TagEditForm extends TagForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->entity->label();
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('The BBCode tag %tag has been updated.', ['%tag' => $this->entity->label()]));
    return $this->entity;
  }

}
