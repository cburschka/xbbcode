<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Entity\EntityInterface;

/**
 * Form for creating a copy of a BBCode tag.
 */
class TagFormCopy extends TagForm {

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $entity = $entity->createDuplicate();

    // Relabel the entity with a sequential number.
    $label = $entity->label();
    if (preg_match('/^(.*?)\s*(\d+)$/', $label, $match)) {
      list(, $label, $number) = $match;
    }
    else {
      $number = 1;
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity->set('label', $label . ' ' . ($number + 1));

    parent::setEntity($entity);
  }

}
