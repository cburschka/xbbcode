<?php

namespace Drupal\xbbcode\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\xbbcode\Plugin\TagPluginInterface;
use Drupal\xbbcode\TagPluginCollection;
use Drupal\xbbcode\TagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function count;

/**
 * Base form for tag sets.
 */
class TagSetForm extends EntityForm {

  use LabeledFormTrait;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tagStorage;

  /**
   * The format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $formatStorage;

  /**
   * The tag plugin manager.
   *
   * @var \Drupal\xbbcode\TagPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a new FilterFormatFormBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $tagStorage
   *   The entity storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $formatStorage
   *   The format storage.
   * @param \Drupal\xbbcode\TagPluginManager $pluginManager
   *   The tag plugin manager.
   */
  public function __construct(EntityStorageInterface $tagStorage,
                              EntityStorageInterface $formatStorage,
                              TagPluginManager $pluginManager) {
    $this->tagStorage = $tagStorage;
    $this->formatStorage = $formatStorage;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $typeManager = $container->get('entity_type.manager');
    return new static(
      $typeManager->getStorage('xbbcode_tag_set'),
      $typeManager->getStorage('filter_format'),
      $container->get('plugin.manager.xbbcode')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form = $this->addLabelFields($form);

    // Begin by creating a table with checkboxes for each plugin.
    $form['_tags'] = [
      '#type'       => 'tableselect',
      '#title'      => $this->t('Tags'),
      '#header'     => [
        'name'  => $this->t('Tag name'),
        'label' => $this->t('Plugin'),
      ],
      '#options'    => [],
      '#empty'      => $this->t('No custom tags or plugins are available.'),
    ];

    /** @var \Drupal\xbbcode\Entity\TagSetInterface $tagSet */
    $tagSet = $this->entity;
    $plugins = new TagPluginCollection($this->pluginManager,
                                       $tagSet->getTags());
    $available = $this->pluginManager->getDefinedIds();

    $settings = [];

    // Add the fields for the activated plugins, keyed by current tag name.
    // (This is because the same plugin might be active with multiple names.)
    foreach ($plugins as $name => $plugin) {
      /** @var \Drupal\xbbcode\Plugin\TagPluginInterface $plugin */
      $settings["enabled:$name"] = $this->buildRow($plugin, TRUE);
      $form['_tags']['#default_value']["enabled:$name"] = TRUE;

      // Exclude already enabled plugins from the bottom part of the table.
      unset($available[$plugin->getPluginId()]);
    }

    // Add the fields for the available plugins, keyed by plugin ID.
    // (This is because multiple plugins might use the same default tag name.)
    foreach ($available as $plugin_id) {
      /** @var \Drupal\xbbcode\Plugin\TagPluginInterface $plugin */
      try {
        $plugin = $this->pluginManager->createInstance($plugin_id);
        $settings["available:$plugin_id"] = $this->buildRow($plugin, FALSE);
      }
      catch (PluginException $exception) {
        // If the plugin is broken, log it and don't show it.
        watchdog_exception('xbbcode', $exception);
      }
    }

    // Add placeholders in the tableselect.
    foreach ($settings as $key => $row) {
      foreach ((array) $row as $name => $cell) {
        $form['_tags']['#options'][$key][$name]['data'] = $name;
      }
    }

    // Put the settings forms into a virtual _settings subkey.
    $form['_settings'] = $settings;
    $form['_settings']['#tree'] = TRUE;

    // Move the settings from there into the tableselect rows when rendering.
    $form['#pre_render'][] = static function (array $form): array {
      $table = &$form['_tags'];
      $settings = $form['_settings'];
      foreach (Element::children($settings) as $key) {
        foreach ((array) $settings[$key] as $name => $cell) {
          $table['#options'][$key][$name]['data'] = $cell;
        }
      }
      unset($form['_settings']);
      return $form;
    };

    $formats = $this->formatStorage
      ->getQuery()
      ->condition('filters.xbbcode.status', TRUE)
      ->execute();
    if ($formats) {
      $form['formats'] = [
        '#type'        => 'checkboxes',
        '#title'       => $this->t('Text formats'),
        '#description' => $this->t('Text formats that use this tag set.'),
        '#options'     => [],
      ];
      foreach ($this->formatStorage->loadMultiple($formats) as $id => $format) {
        $form['formats']['#options'][$id] = $format->label();
      }
      if (!$this->entity->isNew()) {
        $form['formats']['#default_value'] = $this->formatStorage
          ->getQuery()
          ->condition('filters.xbbcode.settings.tags', $this->entity->id())
          ->execute();
      }
    }

    return parent::form($form, $form_state);
  }

  /**
   * Determines if the tag already exists.
   *
   * @param string $id
   *   The tag set ID.
   *
   * @return bool
   *   TRUE if the tag set exists, FALSE otherwise.
   */
  public function exists(string $id): bool {
    return (bool) $this->tagStorage->getQuery()->condition('id', $id)->execute();
  }

  /**
   * Build a table row for a single plugin.
   *
   * @param \Drupal\xbbcode\Plugin\TagPluginInterface $plugin
   *   The plugin instance.
   * @param bool $enabled
   *   Whether or not the plugin is currently enabled.
   *
   * @return array
   *   A form array to put into the parent table.
   */
  protected function buildRow(TagPluginInterface $plugin, bool $enabled): array {
    $row = [
      '#enabled'      => $enabled,
      '#default_name' => $plugin->getDefaultName(),
    ];

    $path = $enabled ? 'enabled:' . $plugin->getName() : 'available:' . $plugin->getPluginId();
    $row['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Tag name'),
      '#title_display' => 'invisible',
      '#required'      => TRUE,
      '#size'          => 8,
      '#field_prefix'  => '[',
      '#field_suffix'  => ']',
      '#default_value' => $plugin->getName(),
      '#pattern'       => '[a-z0-9_-]+',
      '#attributes'    => ['default' => $plugin->getDefaultName()],
      '#states'        => [
        'enabled' => [
          ':input[name="_tags[' . $path . ']"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $row['label'] = [
      '#type'     => 'inline_template',
      '#template' => '<strong>{{ plugin.label }}</strong><br />{{ plugin.description}}',
      '#context'  => ['plugin' => $plugin],
    ];

    $row['id'] = [
      '#type'  => 'value',
      '#value' => $plugin->getPluginId(),
    ];

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $exists = [];

    $enabled = array_filter($form_state->getValue('_tags'));
    $settings = &$form_state->getValue('_settings');

    foreach (array_keys($enabled) as $key) {
      $name = $settings[$key]['name'];
      $exists[$name][$key] = $form['_settings'][$key]['name'];
    }

    foreach ($exists as $name => $rows) {
      if (count($rows) > 1) {
        foreach ((array) $rows as $row) {
          $form_state->setError($row, $this->t('The name [@tag] is used by multiple tags.', ['@tag' => $name]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state): void {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    $enabled = array_keys(array_filter($form_state->getValue('_tags')));
    $settings = &$form_state->getValue('_settings');

    $tags = [];

    foreach ($enabled as $key) {
      $row = $settings[$key];
      $tags[$row['name']] = $this->buildPluginConfiguration($row);
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity->set('tags', $tags);
  }

  /**
   * Build a plugin configuration item from form values.
   *
   * @param array $values
   *   The form values.
   *
   * @return array
   *   The new plugin configuration.
   */
  protected function buildPluginConfiguration(array $values): array {
    return ['id' => $values['id']];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $old = $form['formats']['#default_value'];
    $new = array_filter($form_state->getValue('formats'));

    $update = [
      '' => array_diff_assoc($old, $new),
      $this->entity->id() => array_diff_assoc($new, $old),
    ];

    foreach ($update as $tag_set => $formats) {
      /** @var \Drupal\filter\FilterFormatInterface $format */
      foreach ($this->formatStorage->loadMultiple($formats) as $format) {
        $filter = $format->filters('xbbcode');
        $config = $filter->getConfiguration();
        $config['settings']['tags'] = $tag_set;
        $filter->setConfiguration($config);
        $format->save();
      }
    }

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The BBCode tag set %set has been created.', ['%set' => $this->entity->label()]));
    }
    elseif ($result === SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The BBCode tag set %set has been updated.', ['%set' => $this->entity->label()]));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    $entity = parent::getEntity();
    assert($entity instanceof EntityInterface);
    return $entity;
  }

}
