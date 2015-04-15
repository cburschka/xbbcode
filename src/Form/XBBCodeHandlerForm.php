<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\XBBCodeTagForm.
 */

namespace Drupal\xbbcode\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class XBBCodeHandlerForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xbbcode_handlers';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $format = XBBCODE_GLOBAL) {
    // Load the database interface.
    module_load_include('inc', 'xbbcode', 'xbbcode.crud');
    // Find out which formats use global settings.
    $formats = xbbcode_formats();

    $form = [];

    if ($format == XBBCODE_GLOBAL) {
      $form = self::_buildFormGlobal($form);
    }

    module_load_include('inc', 'xbbcode');
    $handlers = _xbbcode_build_handlers();
    $defaults = xbbcode_handlers_load($format, TRUE);

    $form['tags'] = [
      '#type' => 'fieldset',
      '#theme' => 'xbbcode_settings_handlers_format',
      '#attached' => ['library' => ['xbbcode/handlers-table']],
      '#tree' => TRUE,
      '#title' => t('Tag settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['tags']['_enabled'] = [
      '#type' => 'tableselect',
      '#header' => [
        'tag' => t('BBCode tag'),
        'module' => t('Module'),
      ],
      '#default_value' => [],
      '#options' => [],
      '#attributes' => ['id' => 'xbbcode-handlers'],
      '#empty' => t('No tags or handlers are defined. Please <a href="@modules">install a tag module</a> or <a href="@custom">create some custom tags</a>.', [
        '@modules' => Drupal::url('system.modules_list', [], ['fragment' => 'edit-modules-extensible-bbcode']),
        '@custom' => Drupal::url('xbbcode.admin_tags'),
      ]),
    ];

    foreach ($handlers as $name => $handler) {
      $form['tags']['_enabled']['#options'][$name] = [
        'tag' => [
          'data' => _xbbcode_build_descriptions($name, $handler['info'], $defaults[$name]->module),
          'class' => ['xbbcode-tag-description', 'xbbcode-tag-td'],
        ],
        'module' => [
          'data' => 'handler-select',
          'class' => ['xbbcode-tag-td'],
        ],
        '#attributes' => ['class' => $defaults[$name]->enabled ? ['selected'] : []],
      ];
      $form['tags']['_enabled']['#default_value'][$name] = $defaults[$name]->enabled ? 1 : NULL;

      $form['tags'][$name]['module'] = [
        '#type' => 'select',
        '#options' => $handler['modules'],
        '#default_value' => $defaults[$name]->module,
        '#attributes' => ['class' => ['xbbcode-tag-handler']],
      ];
    }
    return $form;

  }

  /**
   * Modify the global handler settings.
   */
  private function _buildFormGlobal(array $form) {
    $form['global'] = [
      '#weight' => -1,
      '#markup' => t('You are changing the global settings.'),
    ];

    foreach ($formats as &$list) {
      foreach ($list as $format_id => $format_name) {
        $list[$format_id] = l($format_name, 'admin/config/content/formats/' . $format_id);
      }
    }

    if (!empty($formats['specific'])) {
      if (!empty($formats['global'])) {
        $form['global']['#markup'] .= ' ' . t('The following formats are affected by the global settings:');
        $form['global']['#markup'] .= '<ul><li>' . implode('</li><li>', $formats['global']) . '</li></ul>';
      }
      else {
        $form['global']['#markup'] .= ' ' . t('All formats using XBBCode currently override the global settings, so they have no effect.');
      }
      $form['global']['#markup'] .= ' ' . t('The following formats override the global settings, and will not be affected:');
      $form['global']['#markup'] .= '<ul><li>' . implode('</li><li>', $formats['specific']) . '</li></ul>';
    }
    else {
      $form['global']['#markup'] .= ' ' . t('All formats currently follow the global settings.');
    }

    $form['save'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    // Determine if the settings are edited globally or in a text format.
    if (isset($form['#format'])) {
      // If a format has just been created, the #format info is still empty.
      if (!empty($form['#format']->format)) {
        $format_id = $form['#format']->format;
      }
      else {
        $format_id = $form_state['values']['format'];
      }
      $settings = $form_state['values']['filters']['xbbcode']['settings'];
    }
    else {
      $format_id = XBBCODE_GLOBAL;
      $settings = $form_state['values'];
    }

    if ($format_id == XBBCODE_GLOBAL || $settings['override']) {
      // Change the global settings or a format with specific settings.
      $enabled = $settings['tags']['_enabled'];
      unset($settings['tags']['_enabled']);
      foreach ($settings['tags'] as $name => $values) {
        if (is_array($values)) {
          $values['name'] = $name;
          $values['enabled'] = $enabled[$name] ? 1 : 0;
          xbbcode_handler_save((object)$values, $format_id);
        }
      }
      drupal_set_message(t('The tag settings were updated.'));
      xbbcode_rebuild_tags($format_id);
    }
    else {
      // If the format doesn't override, remove any specific settings.
      if (xbbcode_handlers_delete_format($format_id)) {
        drupal_set_message(t('The format-specific tag settings were reset.'));
        xbbcode_rebuild_tags($format_id);
      }
    }
  }
}
