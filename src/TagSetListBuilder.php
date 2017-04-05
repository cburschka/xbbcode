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
    $tags = array_keys($entity->getTags());
    foreach ($tags as $i => $tag) {
      $tags[$i] = "[$tag]";
    }
    $row['tags'] = implode(', ', $tags);
    if (!$tags) {
      $row['tags'] = $this->t('<em>None</em>');
    }
    return $row + parent::buildRow($entity);
  }

}
