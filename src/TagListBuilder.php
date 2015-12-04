<?php

/**
 * @file
 * Contains \Drupal\xbbcode\TagListBuilder.
 */

namespace Drupal\xbbcode;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Build a table view of custom tags.
 */
class TagListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['tag'] = $this->t('Name');
    $header['description'] = $this->t('Description');
    $header['sample'] = $this->t('Sample');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['tag'] = $entity->label();
    $row['description'] = $entity->getDescription();
    $row['sample'] = [
      'data' => $entity->getDefaultSample(),
      'style' => 'font-family:monospace',
    ];
    return $row + parent::buildRow($entity);
  }

}
