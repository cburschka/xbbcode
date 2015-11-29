<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\XBBCodeTagForm.
 */

namespace Drupal\xbbcode\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xbbcode\XBBCodeTagPluginCollection;

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
      '#markup' => $this->t('You are changing the global settings. These settings can be overridden in each <a href="@url">text format</a> that uses Extensible BBCode.', [
        '@url' => Drupal::url('filter.admin_overview')
      ]),
    ];

    $settings = $this->config('xbbcode.settings')->get('tags');
    $tagCollection = new XBBCodeTagPluginCollection(\Drupal::service('plugin.manager.xbbcode'), $settings);
    $form = self::buildFormHandlers($form, $tagCollection);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Generate the handler subform.
   */
  public static function buildFormHandlers(array $form, XBBCodeTagPluginCollection $plugins) {
    $plugins->sort();

    $form['handlers'] = [
      '#type' => 'fieldset',
      '#tree' => FALSE,
      '#theme' => 'xbbcode_settings_handlers_format',
      '#attached' => ['library' => ['xbbcode/handlers-table']],
      '#title' => t('Tag settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // We need another #tree element named "tags" to provide a hierarchy for
    // the module selection menus.
    $form['handlers']['extra']['#tree'] = FALSE;
    $form['handlers']['extra']['tags']['#tree'] = TRUE;

    $form['handlers']['tags'] = [
      '#type' => 'tableselect',
      '#header' => [
        'label' => t('Label'),
        'name' => t('Tag name'),
        'description' => t('Description'),
      //  'module' => t('Module'),
      ],
      '#default_value' => [],
      '#options' => [],
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

    foreach ($plugins as $id => $plugin) {
      $form['handlers']['tags']['#options'][$id] = [
        'label' => [
          'data' => $plugin->getLabel(),
//          'class' => ['xbbcode-tag-td'],
        ],
        'name' => [
          'data' => 'tag-name',
        ],
        'description' => [
          'data' => $plugin->getDescription(),
//          'class' => ['xbbcode-tag-description'],
        ],
/*        'module' => [
          'data' => 'handler-select',
          'class' => ['xbbcode-tag-td'],
        ],*/
      ];
      $form['handlers']['tags']['#default_value'][$id] = $plugin->status ? 1 : NULL;

      $status = isset($form['#post']) ? $form['#post']['tags'][$id]['status'] : $plugin->status;
      
      $tag_name = [
        '#type' => 'textfield',
        '#required' => $status,
        '#disabled' => !$status,
        '#field_prefix' => '[',
        '#default_value' => $plugin->name,
        '#field_suffix' => '] <a default="' . $plugin->getDefaultTagName() . '" href="#">' . t('Reset') . '</a>',
      ];
      $form['handlers']['extra']['tags'][$id]['name'] = $tag_name;
      
/*      if (count($handler['modules']) > 1) {
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
      $form['handlers']['extra']['tags'][$name]['module'] = $module_selector;*/
    }
    return $form;
  }
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $names = [];
    foreach ($form_state->getValue('tags') as $id => $plugin) {
      drupal_set_message($plugin['status']);
      if ($plugin['status']) {
        if ($names[$plugin['name']]) {
          $form_state->setErrorByName("tags[{$id}][name]", t('The name [%name] is used by multiple tags.', ['%name' => $plugin['name']]));
        }
        $names[$plugin['name']] = $id;
      }
    }
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
