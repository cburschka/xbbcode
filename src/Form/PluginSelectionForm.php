<?php

namespace Drupal\xbbcode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\Core\Url;
use Drupal\xbbcode\TagPluginCollection;
use Drupal\xbbcode\TagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var TagPluginManager
   */
  private $pluginManager;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.xbbcode')
    );
  }

  /**
   * PluginSelectionForm constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   * @param TagPluginManager $pluginManager
   */
  public function __construct(ConfigFactoryInterface $config_factory, TagPluginManager $pluginManager) {
    parent::__construct($config_factory);
    $this->pluginManager = $pluginManager;
  }

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
        '@url' => Url::fromRoute('filter.admin_overview')->toString(),
      ]),
    ];

    $settings = $this->config('xbbcode.settings')->get('tags');
    $tag_collection = new TagPluginCollection($this->pluginManager, $settings);
    $form = self::buildPluginForm($form, $tag_collection);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Generate the handler subform.
   *
   * @param array $form
   * @param TagPluginCollection $plugins
   * @return array
   */
  public static function buildPluginForm(array $form, TagPluginCollection $plugins) {
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
      '#element_validate' => [[self::class, 'validateTags']],
      '#options' => [],
      '#empty' => t('No tags or plugins are defined. Please <a href="@modules">install a tag module</a> or <a href="@custom">create some custom tags</a>.', [
        '@modules' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-extensible-bbcode'])->toString(),
        '@custom' => Url::fromRoute('xbbcode.admin_tags')->toString(),
      ]),
      // The #process function pushes each tableselect checkbox down into an
      // "enabled" sub-element.
      '#process' => [
        [Tableselect::class, 'processTableselect'],
        [self::class, 'processTableselect'],
      ],
      // Don't aggregate the checkboxes.
      '#value_callback' => NULL,
    ];

    foreach ($plugins as $id => $plugin) {
      $form['plugins']['tags']['#options'][$id] = [
        'label' => [
          'data' => $plugin->label(),
        ],
        'description' => [
          'data' => $plugin->getDescription(),
        ],
        'name' => [
          'class' => ['name-selector'],
        ],
        '#attributes' => $plugin->status() ? ['class' => ['selected']] : NULL,
      ];
      $form['plugins']['tags']['#default_value'][$id] = $plugin->status() ? 1 : NULL;

      $name_selector = [
        'name' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#size' => 8,
          '#field_prefix' => '[',
          '#field_suffix' => ']',
          '#default_value' => $plugin->getName(),
          '#attributes' => ['default' => $plugin->getDefaultName()],
        ],
        'default_name' => [
          '#type' => 'markup',
          '#attributes' => ['action' => 'edit'],
          '#markup' => t('<span class="edit">[<a href="#" data-action="edit">@name</a>]</span>', ['@name' => $plugin->getDefaultName()]),
        ],
        'reset' => [
          '#type' => 'markup',
          '#attributes' => ['action' => 'reset'],
          '#markup' => t('<a href="#" data-action="reset">Reset</a>'),
        ],
      ];
      $form['plugins']['extra']['tags'][$id] = $name_selector;
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

  /**
   * Process the tableselect element further, moving checkboxes to a sub-key.
   *
   * @param array $element
   *   The tableselect element.
   *
   * @return array
   *   The element after processing.
   */
  public static function processTableselect(array &$element) {
    foreach (array_keys($element['#options']) as $key) {
      // Remove checkbox values:
      $element[$key]['#default_value'] = $element[$key]['#default_value'] == $element[$key]['#return_value'];
      unset($element[$key]['#return_value']);
      // Move checkboxes to 'status' subkey.
      $element[$key] = ['status' => $element[$key]];
    }
    return $element;
  }

}
