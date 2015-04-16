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
  $table = &$fieldset['tags'];
  $selectors = &$fieldset['extra']['tags'];

  $table['#attributes']['id'] = 'xbbcode-handlers';

  foreach (array_keys($table['#options']) as $tag) {
    $table['#options'][$tag]['module']['data'] = drupal_render($selectors[$tag]['module']);
  }
  ksort($table['#options']);

  $html = drupal_render($table);
  foreach (element_children($fieldset) as $element) {
    $html .= drupal_render($fieldset[$element]);
  }
  return $html;
}
