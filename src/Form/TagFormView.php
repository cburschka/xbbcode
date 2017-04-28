<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form for viewing a read-only BBCode tag.
 */
class TagFormView extends TagFormBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig')
    );
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
      // Just make them look like it, and read-only.
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

  /**
   * Intercepting the save as a precaution.
   *
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {}

}
