<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper function for adding label fields to an entity form.
 *
 * @package Drupal\xbbcode\Form
 */
trait LabeledFormTrait {

  use StringTranslationTrait;

  /**
   * Gets the form entity.
   *
   * The form entity which has been used for populating form element defaults.
   *
   * Redeclared here because PHP traits cannot implement interfaces.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The current form entity.
   *
   * @see \Drupal\Core\Entity\EntityFormInterface::getEntity()
   */
  abstract public function getEntity(): EntityInterface;

  /**
   * Add label fields to the form array.
   *
   * @param array $form
   *   Form array.
   *
   * @return array
   *   Form array.
   */
  public function addLabelFields(array $form): array {
    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $this->getEntity()->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#weight'        => -30,
    ];
    $form['id'] = [
      '#type'          => 'machine_name',
      '#default_value' => $this->getEntity()->id(),
      '#maxlength'     => 255,
      '#machine_name'  => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#disabled'      => !$this->getEntity()->isNew(),
      '#weight'        => -20,
    ];
    return $form;
  }

}
