<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\XBBCodeTagAddForm.
 */

namespace Drupal\xbbcode\Form;

use Drupal;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for adding an XBBCode tag.
 */
class XBBCodeTagAddForm extends XBBCodeTagFormBase {
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('Created BBCode tag %tag.', ['%tag' => $this->entity->label()]));
    return $this->entity;
  }
}
