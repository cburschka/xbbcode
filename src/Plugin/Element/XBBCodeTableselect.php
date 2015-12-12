<?php

namespace Drupal\xbbcode\Plugin\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Tableselect;

/**
 * Provides a form element for a table with extra fields in the columns.
 * 
 * @FormElement("xbbcode_tableselect")
 */
class XBBCodeTableselect extends Tableselect {
  public function getInfo() {
    $info = parent::getInfo();
    $info['#process'] = [[self, 'processTableselect']];
    return $info;
  }

  public static function preRenderTableselect($element) {

  }
  
  /**
   * Renders the plugin selection subform as a table.
   */
  function theme_xbbcode_plugin_selection($variables) {
    $fieldset = $variables['fieldset'];
    $table = &$fieldset['tags'];
    $extra = &$fieldset['extra']['tags'];

    $table['#attributes']['id'] = 'xbbcode-plugins';

    foreach (array_keys($table['#options']) as $tag) {
      $table['#options'][$tag]['name']['data'] = drupal_render($extra[$tag]);
    }
    ksort($table['#options']);

    $html = drupal_render($table);
    foreach (Element::children($fieldset) as $element) {
      $html .= drupal_render($fieldset[$element]);
    }
    return $html;
  }

  /**
   * Process the tableselect element further, moving checkboxes to a sub-key.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   tableselect element.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processTableselect(array &$element, FormStateInterface $form_state, array &$complete_form) {
    drupal_set_message("HERE");
    // Pretend that our sub keys are #options keys:
    $element['#options'] = [];
    foreach (Element::children($elements) as $key) {
      $element['#options'][$key] = TRUE;
    }

    // Generate checkboxes:
    parent::processTableselect($element, $form_state, $complete_form);

    // Move the checkbox down:
    foreach (array_keys($element['#options']) as $key) {
      // Remove checkbox values:
      $element[$key]['#default_value'] = $element[$key]['#default_value'] == $element[$key]['#return_value'];
      unset($element[$key]['#return_value']);

      // Move checkboxes to the subkey.
      $element[$key] = ['status' => $element[$key]];
    }
    return $element;
  }

}
