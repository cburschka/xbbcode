<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\XBBCodeTagForm.
 */

namespace Drupal\xbbcode\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class XBBCodeHandlerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xbbcode_handlers';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['xbbcode.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['global'] = [
      '#weight' => -1,
      '#markup' => t('You are changing the global settings. These settings can be overridden in each <a href="@url">text format</a> that uses Extensible BBCode.', [
        '@url' => Drupal::url('filter.admin_overview')
      ]),
    ];

    $defaults = $this->config('xbbcode.settings')->get('tags');
    $form = self::buildFormHandlers($form, $defaults);

    $form['save'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * Generate the handler subform.
   */
  public function buildFormHandlers(array $form, array $defaults) {
    module_load_include('inc', 'xbbcode');
    $handlers = _xbbcode_build_handlers();

    $form['tags'] = [
      '#type' => 'fieldset',
      '#theme' => 'xbbcode_settings_handlers_format',
      '#attached' => ['library' => ['xbbcode/handlers-table']],
      '#tree' => TRUE,
      '#title' => t('Tag settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#value_callback' => [get_class($this), 'valueHandlers'],
    ];

    $form['tags']['_enabled'] = [
      '#type' => 'tableselect',
      '#header' => [
        'tag' => t('Name'),
        'description' => t('Description'),
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
          'data' => "[$name]",
          'class' => ['xbbcode-tag-td'],
        ],
        'description' => [
          'data' => [
            '#markup' => _xbbcode_build_descriptions($name, $handler['info'], $defaults[$name]->module),
          ],
          'class' => ['xbbcode-tag-description'],
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
   * Prepare handlers for storage.
   */
  public static function valueHandlers($element, $input = FALSE, FormStateInterface $form_state) {
    return $input;
  }
}
