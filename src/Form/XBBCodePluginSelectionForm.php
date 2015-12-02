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

class XBBCodePluginSelectionForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xbbcode_plugins';
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
    $form = self::buildPluginForm($form, $tagCollection);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Generate the handler subform.
   */
  public static function buildPluginForm(array $form, XBBCodeTagPluginCollection $plugins) {
    $plugins->sort();

    $form['plugins'] = [
      '#type' => 'fieldset',
      '#tree' => FALSE,
      '#theme' => 'xbbcode_plugin_selection',
      '#attached' => ['library' => ['xbbcode/plugins-table']],
      '#title' => t('Tag settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // We need another #tree element named "tags" to provide a hierarchy for
    // the module selection menus.
    $form['plugins']['extra']['#tree'] = FALSE;
    $form['plugins']['extra']['tags']['#tree'] = TRUE;

    $form['plugins']['tags'] = [
      '#type' => 'tableselect',
      '#header' => [
        'label' => t('Label'),
        'name' => t('Tag name'),
        'description' => t('Description'),
      ],
      '#default_value' => [],
      // @TODO: self::class in PHP 5.6+
      '#element_validate' => [[__CLASS__, 'validateTags']],
      '#options' => [],
      '#empty' => t('No tags or plugins are defined. Please <a href="@modules">install a tag module</a> or <a href="@custom">create some custom tags</a>.', [
        '@modules' => Drupal::url('system.modules_list', [], ['fragment' => 'edit-modules-extensible-bbcode']),
        '@custom' => Drupal::url('xbbcode.admin_tags'),
      ]),
      // The #process function pushes each tableselect checkbox down into an
      // "enabled" sub-element.
      '#process' => [['Drupal\Core\Render\Element\Tableselect', 'processTableselect'], 'xbbcode_plugin_selection_process'],
      // Don't aggregate the checkboxes.
      '#value_callback' => NULL,
    ];

    foreach ($plugins as $id => $plugin) {
      $status = isset($form['#post']) ? $form['#post']['tags'][$id]['status'] : $plugin->status;

      $form['plugins']['tags']['#options'][$id] = [
        'label' => [
          'data' => $plugin->getLabel(),
        ],
        'description' => [
          'data' => $plugin->getDescription(),
        ],
        'name' => [
          'class' => ['name-selector'],
        ],
        '#attributes' => [
          'class' => $status ? ['selected'] : [],
        ],
      ];
      $form['plugins']['tags']['#default_value'][$id] = $plugin->status ? 1 : NULL;

      $name_selector = [
        'name' => [
          '#type' => 'textfield',
          '#required' => $status,
          '#disabled' => !$status,
          '#size' => 8,
          '#field_prefix' => '[',
          '#field_suffix' => ']',
          '#default_value' => $plugin->getName(),
          '#attributes' => ['default' => $plugin->getDefaultName()],
        ],
        'default_name' => [
          '#type' => 'item',
          '#attributes' => ['action' => 'edit'],
          '#markup' => t('[<a href="#" action="edit">@name</a>]', ['@name' => $plugin->getDefaultName()]),
        ],
        'reset' => [
          '#type' => 'item',
          '#attributes' => ['action' => 'reset'],
          '#markup' => t('<a href="#" action="reset">Reset</a>'),
        ],
      ];
      $form['plugins']['extra']['tags'][$id] = $name_selector;
    }
    return $form;
  }

  /**
   * Validate the tags table.
   * This is an element-level validator so it can be used in the filter plugin's
   * settings form as well.
   *
   * @param $element
   *   The form element to validate.
   * @param $form_state
   *   The FormState object.
   */
  public static function validateTags(array $element, FormStateInterface $form_state) {
    // Generate the prefix path of the form element.
    $parents = implode('][', $element['#parents']);

    $names = [];
    $errors = [];

    foreach ($form_state->getValue($element['#parents']) as $id => $plugin) {
      if ($plugin['status']) {
        if (!preg_match('/^[a-z0-9_]+$/', $plugin['name'])) {
          $form_state->setErrorByName("{$parents}][{$id}][name", t('The name [%name] must consist of lower-case letters, numbers and underscores.', ['%name' => $plugin['name']]));
        }
        // Track which plugins are using which names.
        if (!empty($names[$plugin['name']])) {
          $errors[$plugin['name']] = $plugin['name'];
        }
        $names[$plugin['name']][$id] = $id;
      }
    }

    foreach ($errors as $name) {
      foreach ($names[$name] as $id) {
        $form_state->setErrorByName("{$parents}][{$id}][name", t('The name [%name] is used by multiple tags.', ['%name' => $plugin['name']]));
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
