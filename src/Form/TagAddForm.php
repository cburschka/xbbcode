<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * A form for adding an XBBCode tag.
 */
class TagAddForm extends TagForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('The BBCode tag %tag has been created.', ['%tag' => $this->entity->label()]));
    return $this->entity;
  }

}
