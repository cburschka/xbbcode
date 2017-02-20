<?php

namespace Drupal\xbbcode\Form;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * A form for viewing a read-only BBCode tag.
 */
class TagViewForm extends TagEditForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Load the template code from a file if necessary.
    if (!$form['template_code']['#default_value'] && $file = $this->entity->getTemplateFile()) {
      // The source must be loaded directly, because the template class won't
      // have it unless it is loaded from the file cache.
      $source = Drupal::service('twig')->getLoader()->getSource($file);
      $form['template_code']['#default_value'] = $source;
      $form['template_code']['#rows'] = max(5, count(explode("\n", $source)));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Disable all form elements.
    foreach (Element::children($form) as $key) {
      $form[$key]['#required'] = FALSE;
      // Actually disabling text fields makes their content non-selectable.
      // Just make them look like it.
      $type = $form[$key]['#type'];
      if ($type === 'textfield' || $type === 'textarea') {
        $form[$key]['#attributes']['readonly'] = 'readonly';
        $form[$key]['#wrapper_attributes']['class']['form-disabled'] = 'form-disabled';
      }
      else {
        $form[$key]['#disabled'] = TRUE;
      }
    }
    return $form;
  }

  /**
   * Intercepting the submit as a precaution.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
