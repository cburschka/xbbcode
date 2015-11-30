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

  /**
   * Delete selected custom tags.
   */
  public function _submitFormDelete(array &$form, FormStateInterface $form_state) {
    $delete = [];

    if ($form_state->getValue('name')) {
      $delete[] = $form_state->getValue('name');
    }
    elseif (is_array($form_state->getValue('existing'))) {
      foreach ($form_state->getValue('existing') as $tag => $checked) {
        if ($checked) {
          $delete[] = $tag;
        }
      }
    }

    xbbcode_custom_tag_delete($delete);

    $tags = '[' . implode('], [', $delete) . ']';

    drupal_set_message(Drupal::translation()->formatPlural(count($delete), 'The tag %tags has been deleted.', 'The tags %tags have been deleted.', ['%tags' => $tags]), 'status');
    drupal_static_reset('xbbcode_custom_tag_load');
  }

  /**
   * Save (create or update) a custom tag.
   */
  public function _submitFormSave(array &$form, FormStateInterface $form_state) {
    $tag = (object) $form_state->getValues();
    $tag->name = strtolower($tag->name);
    foreach ($tag->options as $name => $value) {
      $tag->options[$name] = $value ? 1 : 0;
    }
    $tag->options['php'] = $tag->php;

    if (xbbcode_custom_tag_save($tag)) {
      if ($form['edit']['name']['#default_value']) {
        drupal_set_message($this->t('Tag [@name] has been changed.', ['@name' => $tag->name]));
      }
      else {
        drupal_set_message($this->t('Tag [@name] has been created.', ['@name' => $tag->name]));
      }
    }
    $form_state->setRedirect('xbbcode.admin_tags');
    drupal_static_reset('xbbcode_custom_tag_load');
  }
}
