<?php

/**
 * @file
 * Administrative interface for modifying tags and settings.
 */

/**
 * Renders the handlers subform as a table.
 */
function theme_xbbcode_settings_handlers_format($variables) {
  $fieldset = $variables['fieldset'];
  $fieldset['_enabled']['#attributes']['id'] = 'xbbcode-handlers';

  foreach (element_children($fieldset) as $tag) {
    if (isset($fieldset[$tag]['#type'])) {
      continue;
    }

    if (count($fieldset[$tag]['module']['#options']) == 1) {
      $fieldset[$tag]['module'] = array(
        'shown' => array(
          '#type' => 'markup',
          '#markup' => current($fieldset[$tag]['module']['#options']),
        ),
        '#type' => 'value',
        '#value' => key($fieldset[$tag]['module']['#options']),
      );
    }

    $fieldset['_enabled']['#options'][$tag]['module']['data'] = drupal_render($fieldset[$tag]['module']);
  }
  ksort($fieldset['_enabled']['#options']);

  $html = drupal_render($fieldset['_enabled']);
  foreach (element_children($fieldset) as $element) {
    $html .= drupal_render($fieldset[$element]);
  }
  return $html;
}
