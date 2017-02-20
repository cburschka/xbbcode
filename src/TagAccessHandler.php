<?php

namespace Drupal\xbbcode;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\xbbcode\Entity\TagEntityInterface;

/**
 * Control access to XBBCodeTag entities.
 */
class TagAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /**
     * @var TagEntityInterface $entity
     */
    if (($operation === 'update' || $operation === 'delete') && !$entity->isEditable()) {
      return AccessResult::forbidden();
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
