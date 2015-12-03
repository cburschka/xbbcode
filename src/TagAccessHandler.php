<?php

/**
 * @file
 * Contains \Drupal\xbbcode\TagAccessHandler.
 */

namespace Drupal\xbbcode;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control access to XBBCodeTag entities.
 */
class TagAccessHandler extends EntityAccessControlHandler {
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if (in_array($operation, ['update', 'delete']) && !$entity->isEditable()) {
      return AccessResult::forbidden();
    }
    return parent::checkAccess($entity, $operation, $account);
  }
}
