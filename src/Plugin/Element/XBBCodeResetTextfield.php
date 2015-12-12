<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\xbbcode\Plugin\Element;

use Drupal\Core\Render\Element\FormElement;

/**
 * Description of XBBCodeResetTextfield
 *
 * @author christoph
 */
class XBBCodeResetTextfield extends FormElement {
  public function getInfo() {
    $element = parent::getInfo();
    $element['#pre_render'] = [
      [self, 'preRender'],
    ];
    return $element;
  }

  public static function preRender($element) {
    $new = [
      '#attributes' => ['class' => ['reset-textfield']],
      '#attached' => ['library' => ['xbbcode/reset-textfield']],
    ];
    $new['field'] = $element;
    $new['field']['#type'] = 'textfield';
    $new['field']['#attributes']['data-reset'] = $element['#reset_value'];
    $new['default'] = [
      '#attributes' => ['action' => 'edit'],
      '#markup' => t('<span class="edit">[<a href="#" action="edit">@name</a>]</span>', [
        '@name' => $element['#reset_value']
      ]),
    ];
    $new['reset'] = [
      '#attributes' => ['action' => 'reset'],
      '#markup' => t('<a href="#" action="reset">Reset</a>'),
    ];
    return $new;
  }

}
