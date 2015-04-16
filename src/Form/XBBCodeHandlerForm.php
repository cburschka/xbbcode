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
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Generate the handler subform.
   */
  public static function buildFormHandlers(array $form, array $defaults) {
    module_load_include('inc', 'xbbcode');
    $handlers = _xbbcode_build_handlers();

    $form['handlers'] = [
      '#type' => 'fieldset',
      '#theme' => 'xbbcode_settings_handlers_format',
      '#attached' => ['library' => ['xbbcode/handlers-table']],
      '#title' => t('Tag settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // We need another #tree element named "tags" to provide a hierarchy for
    // the module selection menus.
    $form['handlers']['extra']['tags']['#tree'] = TRUE;

    $form['handlers']['tags'] = [
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
      // The #process function pushes each tableselect checkbox down into an
      // "enabled" sub-element.
      '#process' => [['Drupal\Core\Render\Element\Tableselect', 'processTableselect'], 'xbbcode_settings_handlers_process'],
      // Don't aggregate the checkboxes.
      '#value_callback' => NULL,
    ];

    foreach ($handlers as $name => $handler) {
      if (!array_key_exists($name, $defaults)) {
        $defaults[$name] = ['enabled' => FALSE, 'module' => NULL];
      }

      $form['handlers']['tags']['#options'][$name] = [
        'tag' => [
          'data' => "[$name]",
          'class' => ['xbbcode-tag-td'],
        ],
        'description' => [
          'data' => [
            '#markup' => _xbbcode_build_descriptions($name, $handler['info'], $defaults[$name]['module']),
          ],
          'class' => ['xbbcode-tag-description'],
        ],
        'module' => [
          'data' => 'handler-select',
          'class' => ['xbbcode-tag-td'],
        ],
        '#attributes' => ['class' => $defaults[$name]['enabled'] ? ['selected'] : []],
      ];
      $form['handlers']['tags']['#default_value'][$name] = $defaults[$name]['enabled'] ? 1 : NULL;

      if (count($handler['modules']) > 1) {
        $module_selector = [
          '#type' => 'select',
          '#options' => $handler['modules'],
          '#default_value' => $defaults[$name]['enabled'],
          '#attributes' => ['class' => ['xbbcode-tag-handler']],
        ];
      } else {
        $module_selector = [
          'shown' => [
            '#type' => 'markup',
            '#markup' => current($handler['modules']),
          ],
          '#type' => 'value',
          '#value' => key($handler['modules']),
        ];
      }
      $form['handlers']['extra']['tags'][$name]['module'] = $module_selector;
    }
    return $form;
  }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('xbbcode.settings')
      ->set('tags', $form_state->getValue('tags'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
