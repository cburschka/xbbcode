<?php

/**
 * @file
 * Contains \Drupal\xbbcode\XBBCodeCustomTagDeriver.
 */

namespace Drupal\xbbcode;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derive a tag plugin for each XBBCodeCustomTag entity.
 */
class XBBCodeCustomTagDeriver implements ContainerDeriverInterface {

  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    
  }

  public function getDerivativeDefinitions($base_plugin_definition) {
    
  }

  public static function create(ContainerInterface $container, $base_plugin_id) {
    
  }
}
