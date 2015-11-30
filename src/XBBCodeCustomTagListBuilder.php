<?php



namespace Drupal\xbbcode;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**

 */
class XBBCodeCustomTagListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['tag'] = t('Name');
    $header['description'] = t('Description');
    $header['sample'] = t('Sample');
    $header['output'] = t('Output');
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
    $row['output'] = '@TODO';
    return $row + parent::buildRow($entity);
  }
}
