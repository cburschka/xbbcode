<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\PluginSelectionForm.
 */

namespace Drupal\xbbcode\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\xbbcode\TagPluginCollection;

/**
 * Modify the global tag plugin settings.
 *
 * A part of this form (in buildPluginForm()) is also used
 * to expose the format-specific plugin settings.
 *
 * @see XBBCodeFilter
 */
class PluginSelectionForm extends ConfigFormBase {

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
        '@url' => Drupal::url('filter.admin_overview'),
      ]),
    ];

    $settings = $this->config('xbbcode.settings')->get('tags');
    $tag_collection = new TagPluginCollection(Drupal::service('plugin.manager.xbbcode'), $settings);
    $form = self::buildPluginForm($form, $tag_collection);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Generate the handler subform.
   */
  public static function buildPluginForm(array $form, TagPluginCollection $plugins) {
    $plugins->sort();

    $form['plugins'] = [
      '#type' => 'fieldset',
      '#tree' => FALSE,
      '#title' => t('Tag settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['plugins']['tags'] = [
      '#type' => 'xbbcode_tableselect',
      '#header' => [
        'label' => t('Label'),
        'name' => t('Tag name'),
        'description' => t('Description'),
      ],
      '#default_value' => [],
      '#element_validate' => [[self::class, 'validateTags']],
      '#empty' => t('No tags or plugins are defined. Please <a href="@modules">install a tag module</a> or <a href="@custom">create some custom tags</a>.', [
        '@modules' => Drupal::url('system.modules_list', [], ['fragment' => 'edit-modules-extensible-bbcode']),
        '@custom' => Drupal::url('xbbcode.admin_tags'),
      ]),
    ];

    foreach ($plugins as $id => $plugin) {
      $form['plugins']['tags']['#default_value'][$id] = $plugin->status() ? 1 : NULL;
      $form['plugins']['tags'][$id] = [
        'label' => [
          '#markup' => $plugin->label(),
        ],
        'description' => [
          '#markup' => $plugin->getDescription(),
        ],
        'name' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#size' => 8,
          '#field_prefix' => '[',
          '#field_suffix' => ']',
          '#parents' => ['tags', $id, 'name'],
          '#name' => ['tags'],
          '#value' => $plugin->getName(),
          '#reset_value' => $plugin->getDefaultName(),
        ],
        '#attributes' => $plugin->status() ? ['class' => ['selected']] : NULL,
      ];
      $form['plugins']['tags']['#default_value'][$id] = $plugin->status() ? 1 : NULL;
    }
    return $form;
  }

  /**
   * Validate the tags table.
   *
   * This is an element-level validator in order to be available to the
   * filter plugin's settings form as well.
   *
   * @param array $element
   *   The form element to validate.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public static function validateTags(array $element, FormStateInterface $form_state) {
    // Generate the prefix path of the form element.
    $parents = implode('][', $element['#parents']);

    $names = [];
    $errors = [];

    foreach ($form_state->getValue($element['#parents']) as $id => $plugin) {
      if (!preg_match('/^[a-z0-9_]+$/', $plugin['name'])) {
        $form_state->setErrorByName("{$parents}][{$id}][name", t('The name [%name] must consist of lower-case letters, numbers and underscores.', ['%name' => $plugin['name']]));
      }
      if ($plugin['status']) {
        // Track which plugins are using which names.
        if (!empty($names[$plugin['name']])) {
          $errors[$plugin['name']] = $plugin['name'];
        }
        $names[$plugin['name']][$id] = $id;
      }
    }

    foreach ($errors as $name) {
      foreach ($names[$name] as $id) {
        $form_state->setErrorByName("{$parents}][{$id}][name", t('The name [%name] is used by multiple tags.', ['%name' => $name]));
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
