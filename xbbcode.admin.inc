<?php
// $Id$


/**
 * @file
 * Administrative interface for adding custom tags and assigning module handlers.
 */

/**
 * List custom tags and edit or delete them.
 *
 * @param $form_state
 * @param $name
 *   If passed, load this tag for editing. Otherwise, list all tags and show a
 *   collapsed tag creation form.
 *
 * @return
 *   A form ready for building.
 */
function xbbcode_custom_tags($form, &$form_state, $name = NULL) {
  module_load_include('inc', 'xbbcode', 'xbbcode.crud');
  // Determine whether the user has loaded an existing tag for editing (via edit link).
  $editing_tag = !empty($name); 
  // Determine whether the user submitted the tag-creation form.
  $submitting_form = !empty($form_state['input']) && $form_state['input']['op'] == t('Save');
  // If no tags exist or the form was submitted, then a new tag is being added.
  $adding_tag = !$editing_tag && (empty($tags) || $submitting_form);

  // The upshot is that if a tag is being edited or added, the otherwise optional fields become required.
  
  // If editing a tag, load this tag and populate the form with its values.
  if ($editing_tag) {
    $tag = xbbcode_custom_tag_load($name);
    $form['edit'] = array(
      '#type' => 'fieldset',
      '#title' => t('Editing Tag %name', array('%name' => $name)),
      '#collapsible' => FALSE,
    );
  }
  else {
    $tags = array_keys(xbbcode_custom_tag_load());

    // If any tags already exist, build a list for deletion and editing.
    if (!empty($tags)) {
      foreach ($tags as $tag) {
        $options[$tag] = '[' . $tag . '] ' . l(t('edit'), "admin/config/content/xbbcode/tags/$tag/edit");
      }
      $form['existing'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Existing Tags'),
        '#description' => t('Check these tags and click "Delete" to delete them.'),
        '#options' => $options,
      );
    }

    $form['edit'] = array(
      '#type' => 'fieldset',
      '#title' => t('Create new tag'),
      '#collapsible' => TRUE,
      '#collapsed' => count($tags),
    );

    // Create an empty tag.
    $tag = (object)array(
      'name' => '',
      'description' => '',
      'markup' => '',
      'sample' => '',
    );
  }

  /* Regardless of whether a new tag or an existing tag is being edited,
   * show the edit form now. The fields are required only if a new tag is being
   * saved (during the submission phase), or if an existing tag is being edited.
   */ 

  $form['edit']['name'] = array(
    '#type' => 'textfield',
    '#default_value' => $tag->name,
    '#field_prefix' => '[',
    '#field_suffix' => ']',
    '#required' => $editing_tag || $adding_tag,
    '#maxlength' => 32,
    '#size' => 16,
    '#description' => t('The name of this tag. The name will be used in the text as [name]...[/name]. Must be alphanumeric and will automatically be converted to lowercase.'),
  );

  $form['edit']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $tag->description,
    '#required' => $editing_tag || $adding_tag,
    '#description' => t('This will be shown on help pages'),
  );

  $form['edit']['sample'] = array(
    '#type' => 'textfield',
    '#title' => t('Sample Tag'),
    '#required' => $editing_tag || $adding_tag,
    '#description' => t('Enter an example of how this tag would be used. It will be shown on the help pages.'),
    '#default_value' => $tag->sample,
  );

  $form['edit']['options'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Tag options'),
    '#options' => array(
      'selfclosing' => t('Self-closing'),
      'dynamic' => t('PHP code'),
      'multiarg' => t('Multiple tag attributes'),
      'nocode' => t('Do not allow further BBCode inside the tag to be parsed.'),
    ),
    '#description' => t('A selfclosing tag like [img=http://...] requires no closing tag to follow it.'),
  );

  foreach ($form['edit']['options']['#options'] as $key => $value) {
    if (!empty($tag->$key)) {
      $form['edit']['options']['#default_value'][$key] = $key;
    }
  }

  $form['edit']['markup'] = array(
    '#type' => 'textarea',
    '#attributes' => array('style' => 'font-family:monospace'),
    '#title' => t('Rendering Code'),
    '#default_value' => $tag->markup,
    '#required' => $editing_tag || $adding_tag,
    '#description' => t('The text that [tag]content[/tag] should be replaced with, or PHP code that prints/returns the text.', array('@url' => url('admin/help/xbbcode'))),
  );

  $form['edit']['help'] = array(
    '#type' => 'markup',
    '#title' => t('Coding help'),
    '#markup' => t('<p>The above field should be filled either with HTML or PHP code depending on whether your check the PHP code option. PHP code must be placed in &lt;? ?&gt;, or it will be
    printed literally.</p>
    <p>Regardless of whether you are using static HTML or dynamic PHP, the attributes and content of the tag in the processed tag will be inserted into 
    your code by replacing placeholders. If you would like to assign them to a variable in PHP, you need to assign it as <code>$variable&nbsp;=&nbsp;"{placeholder}";</code></p>
    <dl>
      <dt><code>{content}</code></dt>
      <dd> will be replaced with the text between opening and closing tags, if the tag is not self-closing. E.g.: <code>[url=http://www.drupal.org]<strong>Drupal</strong>[/url]</code></dd>
      <dt><code>{option}</code></dt>
      <dd> will be replaced with the single tag attribute, if the tag does not use multiple attributes. E.g.: <code>[url=<strong>http://www.drupal.org</strong>]Drupal[/url]</code>.</dd>
      <dt>any other <code>{placeholder}</code></dt>
      <dd> will be replaced with the tag attribute of the same name, if the tag uses multiple attributes. E.g: <strong>{by}</strong> is replaced with <code>[quote&nbsp;by=<strong>Author</strong>&nbsp;date=2008]Text[/quote]</code>.</dd>
    </dl>'),
  );

  $form['edit']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#submit' => array('xbbcode_custom_tags_save_submit'),
  );

  if (!empty($name) || count($tags)) {
    $delete = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array('xbbcode_custom_tags_delete_submit'),
    );
    if (!empty($name)) {
      $form['edit']['delete'] = $delete;
    }
    else {
      $form['delete'] = $delete;
    }
  }

  return $form;
}

/**
 * Validation of the custom tags form.
 */
function xbbcode_custom_tags_validate($form, $form_state) {
  if (!preg_match('/^[a-z0-9]*$/i', $form_state['values']['name'])) {
    form_set_error('name', t('The name must be alphanumeric.'));
  }

  if ($form['edit']['name']['#default_value'] != $form_state['values']['name']) {
    if (xbbcode_custom_tag_exists($form_state['values']['name'])) {
      form_set_error('name', t('This name is already taken. Please delete or edit the old tag, or choose a different name.'));
    }
  }
}

/**
 * Delete a custom tag.
 */
function xbbcode_custom_tags_delete_submit($form, $form_state) {
  $delete = array();

  if (!empty($form_state['values']['name'])) {
    $delete[] = $form_state['values']['name'];
  }
  elseif (is_array($form_state['values']['existing'])) {
    foreach ($form_state['values']['existing'] as $tag => $checked) {
      if ($checked) {
        $delete[] = $tag;
      }
    }
  }

  xbbcode_custom_tag_delete($delete);

  $tags = '[' . implode('], [', $delete) . ']';

  drupal_set_message(format_plural(count($delete), 'The tag %tags has been deleted.', 'The tags %tags have been deleted.', array('%tags' => $tags)), 'status');
  drupal_static_reset('xbbcode_custom_tag_load');
  xbbcode_rebuild_handlers();
  xbbcode_rebuild_tags();
}

/**
 * Save (create or update) a custom tag.
 */
function xbbcode_custom_tags_save_submit($form, &$form_state) {
  $tag = (object) $form_state['values'];
  $tag->name = strtolower($tag->name);
  foreach ($tag->options as $name => $value) {
    $tag->options[$name] = $value ? 1 : 0;
  }

  if (xbbcode_custom_tag_save($tag)) {
    if ($form['edit']['name']['#default_value']) {
      drupal_set_message(t('Tag [@name] has been changed.', array('@name' => $tag->name)));
    }
    else {
      drupal_set_message(t('Tag [@name] has been created.', array('@name' => $tag->name)));
    }
  }
  $form_state['redirect'] = array('admin/config/content/xbbcode/tags', array());
  drupal_static_reset('xbbcode_custom_tag_load');
  xbbcode_rebuild_handlers();
  xbbcode_rebuild_tags();
}

/**
 * Modify the global handler settings.
 */
function xbbcode_settings_handlers(&$form_state) {
  module_load_include('inc', 'xbbcode', 'xbbcode.crud');
  // Find out which formats use global settings.
  $formats = xbbcode_formats();

  $form = array(
    'global' => array(),
    'tags' => array(),
  );

  $form['global'] = array(
    '#weight' => -1,
    '#markup' => t('You are changing the global settings.'),
  );

  if (!empty($formats['global'])) {
    $form['global']['#markup'] .= ' ' . t('The following formats are affected by the global settings:');
    $form['global']['#markup'] .= '<ul><li>' . implode('</li><li>', $formats['global']) . '</li></ul>';
  }
  if (!empty($formats['specific'])) {
    $form['global']['#markup'] .= ' ' . t('The following formats have specific settings and will not be affected:');
    $form['global']['#markup'] .= '<ul><li>' . implode('</li><li>', $formats['specific']) . '</li></ul>';
  }

  $form['tags'] = xbbcode_settings_handlers_format();

  $form['save'] = array(
    '#type' => 'submit',
    '#name' => 'op',
    '#value' => t('Save changes'),
    '#submit' => array('xbbcode_settings_handlers_save_submit'),
  );
  $form['reorder'] = array(
    '#type' => 'submit',
    '#name' => 'op',
    '#value' => t('Reset to alphabetical'),
    '#submit' => array('xbbcode_settings_handlers_reorder_submit'),
  );
  return $form;
}

/**
 * Modify handler settings (subform).
 */
function xbbcode_settings_handlers_format($format = XBBCODE_GLOBAL) {
  drupal_add_css(drupal_get_path('module', 'xbbcode') . '/xbbcode.css');
  drupal_add_js(drupal_get_path('module', 'xbbcode') . '/xbbcode.js');
  module_load_include('inc', 'xbbcode');
  $handlers = _xbbcode_build_handlers();
  $defaults = xbbcode_handlers_load($format, TRUE);

  $form = array(
    '#type' => 'fieldset',
    '#theme' => 'xbbcode_settings_handlers_format',
    '#tree' => TRUE,
    '#title' => t('Tag settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['_enabled'] = array(
    '#type' => 'tableselect',
    '#header' => array(
      'tag' => t('Tag'),
      'module' => t('Module'),
      'weight' => t('Weight'),
    ),
    '#value' => array(),
    '#options' => array(),
    '#attributes' => array('id' => 'xbbcode-handlers'),
    '#empty' => t('No tags or handlers are defined. Please install a module or <a href="@url">create some custom tags</a>.', array('@url' => url('admin/config/content/xbbcode/tags'))),
  );

  if (!empty($handlers)) {
    $sort_order = array_combine(array_keys($handlers), array_keys(array_keys($handlers)));
  }
  foreach ($handlers as $name => $handler) {
    $form['_enabled']['#options'][$name] = array(
      'tag' => array(
        'data' => _xbbcode_build_descriptions($name, $handler['info'], $defaults[$name]->module),
        'class' => array('xbbcode-tag-description', 'xbbcode-tag-td'),
      ),
      'module' => array(
        'data' => 'handler-select',
        'class' => array('xbbcode-tag-td'),
      ),
      'weight' => array(
        'data' => 'weight-select',
        'class' => array('xbbcode-tag-td'),
      ),
      '#weight' => $defaults[$name]->weight * 1000 + $sort_order[$name],
      '#attributes' => array('class' => $defaults[$name]->enabled ? array('selected') : array()),
    );
    $form['_enabled']['#value'][$name] = $defaults[$name]->enabled ? 1 : NULL;

    $form[$name]['module'] = array(
      '#type' => 'select',
      '#options' => $handler['modules'],
      '#default_value' => $defaults[$name]->module,
      '#attributes' => array('class' => array('xbbcode-tag-handler')),
    );

    $form[$name]['weight'] = array(
      '#type' => 'weight',
      '#delta' => 5,
      '#default_value' => $defaults[$name]->weight,
      '#attributes' => array('class' => array('xbbcode-tag-weight')),
    );
  }
  return $form;
}

/**
 * Renders the handlers subform as a table.
 */
function theme_xbbcode_settings_handlers_format($variables) {
  $fieldset = $variables['fieldset'];

  $fieldset['_enabled']['#attributes']['id'] = 'xbbcode-handlers';

  foreach (element_children($fieldset) as $tag) {
    if (isset($fieldset[$tag]['#type'])) {
      continue;
    }

    if (count($fieldset[$tag]['module']['#options']) == 1) {
      $fieldset[$tag]['module'] = array(
        'shown' => array(
          '#type' => 'markup',
          '#markup' => current($fieldset[$tag]['module']['#options']),
        ),
        '#type' => 'value',
        '#value' => key($fieldset[$tag]['module']['#options']),
      );
    }

    $fieldset['_enabled']['#options'][$tag]['module']['data'] = drupal_render($fieldset[$tag]['module']);
    $fieldset['_enabled']['#options'][$tag]['weight']['data'] = drupal_render($fieldset[$tag]['weight']);
    $fieldset['_enabled']['#options'][$tag]['#attributes']['class']['draggable'] = 'draggable';
  }

  drupal_add_tabledrag('xbbcode-handlers', 'order', 'sibling', 'xbbcode-tag-weight');
  return drupal_render($fieldset['_enabled']);
}

/**
 * Save the handler settings.
 */
function xbbcode_settings_handlers_save_submit($form, $form_state) {
  // Determine if the settings are edited globally or in a text format.
  if (isset($form['#format'])) {
    $format_id =  $form['#format']->format;
    $settings  = $form_state['values']['filters']['xbbcode']['settings'];
  }
  else {
    $format_id = XBBCODE_GLOBAL;
    $settings  = $form_state['values'];
  }

  if ($format_id == XBBCODE_GLOBAL || $settings['override']) {
    // Change the global settings or a format with specific settings.
    $enabled = $settings['tags']['_enabled'];
    unset($settings['tags']['_enabled']);
    foreach ($settings['tags'] as $name => $values) {
      $values['name'] = $name;
      $values['enabled'] = $enabled[$name] ? 1 : 0;
      xbbcode_handler_save((object)$values, $format_id);
    }
    drupal_set_message(t('The tag settings were updated.'));
    xbbcode_rebuild_tags($format_id);
  }
  else {
    // When a format is changed from specific to global, delete the specific settings.
    $reset = xbbcode_handlers_delete_format($format_id);
    if ($reset) {
      drupal_set_message(t('The format-specific tag settings were reset.'));
      xbbcode_rebuild_tags($format_id);
    }
  }
}

/**
 * Reset handlers to alphabetical order. 
 */
function xbbcode_settings_handlers_reorder_submit($form, $form_state) {
  xbbcode_handlers_reorder($form_state['values']['format']);
  drupal_set_message(t('The tag weights have been reset to alphabetical order.'));
  xbbcode_rebuild_tags();
}
