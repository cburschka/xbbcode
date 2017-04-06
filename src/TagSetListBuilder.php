<?php

namespace Drupal\xbbcode;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Build a table view of tag sets.
 */
class TagSetListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['tags'] = $this->t('Tags');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    /** @var \Drupal\xbbcode\Entity\TagSetInterface $entity */
    $row['tags']['data'] = $entity->getPluginCollection()->getSummary();
    return $row + parent::buildRow($entity);
  }

}
