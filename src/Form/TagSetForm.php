<?php

namespace Drupal\xbbcode\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xbbcode\Plugin\TagPluginInterface;
use Drupal\xbbcode\TagPluginCollection;
use Drupal\xbbcode\TagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for tag sets.
 */
class TagSetForm extends EntityForm {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The tag plugin manager.
   *
   * @var \Drupal\xbbcode\TagPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a new FilterFormatFormBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\xbbcode\TagPluginManager $pluginManager
   *   The tag plugin manager.
   */
  public function __construct(EntityStorageInterface $storage,
                              TagPluginManager $pluginManager) {
    $this->storage = $storage;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('xbbcode_tag_set'),
      $container->get('plugin.manager.xbbcode')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#weight'        => -30,
    ];
    $form['id'] = [
      '#type'          => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#maxlength'     => 255,
      '#machine_name'  => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#disabled'      => !$this->entity->isNew(),
      '#weight'        => -20,
    ];

    $table = [
      '#type'   => 'xbbcode_plugin_table',
      '#title'  => $this->t('Tags'),
      '#header' => [
        'status'      => $this->t('Status'),
        'name'        => $this->t('Tag name'),
        'label'       => $this->t('Plugin'),
        'description' => $this->t('Settings'),
      ],
      '#tree'   => TRUE,
      '#empty'  => $this->t('No custom tags or plugins are available.'),
      'enabled' => [
        '#title' => $this->t('Enabled tags'),
      ],
      'disabled' => [
        '#title' => $this->t('Disabled tags'),
      ],
    ];

    /** @var \Drupal\xbbcode\Entity\TagSetInterface $tagSet */
    $tagSet = $this->entity;
    $plugins = new TagPluginCollection($this->pluginManager,
                                       $tagSet->getTags());
    $available = $this->pluginManager->getDefinedIds();

    foreach ($plugins as $name => $plugin) {
      /** @var \Drupal\xbbcode\Plugin\TagPluginInterface $plugin */
      $table['enabled'][$name] = $this->buildRow($plugin);

      // Exclude already enabled plugins from the bottom part of the table.
      unset($available[$plugin->getPluginId()]);
    }

    foreach ($available as $plugin_id) {
      /** @var \Drupal\xbbcode\Plugin\TagPluginInterface $plugin */
      try {
        $plugin = $this->pluginManager->createInstance($plugin_id);
        $table['disabled'][$plugin_id] = $this->buildRow($plugin, FALSE);
      }
      catch (PluginException $exception) {
        watchdog_exception('xbbcode', $exception);
      }
    }

    $form['tags'] = $table;

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
  public function exists($id) {
    return (bool) $this->storage->getQuery()->condition('id', $id)->execute();
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
  protected function buildRow(TagPluginInterface $plugin, $enabled = TRUE) {
    $row = [
      '#enabled'      => $enabled,
      '#plugin'       => $plugin,
      '#default_name' => $plugin->getDefaultName(),
    ];

    $row['status'] = [
      '#type'          => 'checkbox',
      '#default_value' => $enabled,
    ];

    $path = $enabled ? 'enabled][' . $plugin->getName() : 'disabled][' . $plugin->getPluginId();
    $row['name'] = [
      '#type'          => 'textfield',
      '#required'      => TRUE,
      '#size'          => 8,
      '#field_prefix'  => '[',
      '#field_suffix'  => ']',
      '#default_value' => $plugin->getName(),
      '#attributes'    => ['default' => $plugin->getDefaultName()],
      '#states' => [
        'enabled' => [':input[name="tags[' . $path . '][status]"]' => ['checked' => TRUE]],
      ],
    ];

    $row['label'] = [
      '#type' => 'inline_template',
      '#template' => '<strong>{{ plugin.label }}</strong><br />
                        {{ plugin.description}}',
      '#context' => ['plugin' => $plugin],
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $exists = [];
    foreach ((array) $form_state->getValue('tags') as $type => $group) {
      foreach ((array) $group as $id => $row) {
        if ($row['status']) {
          $name = $row['name'];
          if (empty($exists[$name])) {
            $exists[$name] = [];
          }
          $exists[$name][] = $form['tags'][$type][$id]['name'];
        }
      }
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
  protected function copyFormValuesToEntity(EntityInterface $entity,
                                            array $form,
                                            FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    $values = $form_state->getValue('tags') + ['enabled' => [], 'disabled' => []];

    /** @var \Drupal\xbbcode\Entity\TagSetInterface $entity */
    $tags = $entity->getTags();

    foreach ((array) $values['enabled'] as $name => $row) {
      // If any currently enabled plugin has been deleted or renamed...
      if (isset($tags[$name]) && (!$row['status'] || $row['name'] !== $name)) {
        // Copy configuration to the new name (if any), and delete the old.
        $tags[$row['name']] = $this->buildPluginConfiguration($row, $tags[$name]);
        unset($tags[$name]);
      }
    }

    foreach ((array) $values['disabled'] as $plugin_id => $row) {
      if ($row['status']) {
        $tags[$row['name']] = $this->buildPluginConfiguration($row);
      }
    }

    $entity->set('tags', $tags);
  }

  /**
   * Build a plugin configuration item from form values.
   *
   * @param array $values
   *   The form values.
   * @param array $existing
   *   The existing plugin configuration (optional).
   *
   * @return array
   *   The new plugin configuration.
   */
  protected function buildPluginConfiguration(array $values, array $existing = []) {
    return ['id' => $values['id']] + $existing;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    if ($result === SAVED_NEW) {
      drupal_set_message($this->t('The BBCode tag set %set has been created.', ['%set' => $this->entity->label()]));
    }
    elseif ($result === SAVED_UPDATED) {
      drupal_set_message($this->t('The BBCode tag set %set has been updated.', ['%set' => $this->entity->label()]));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
