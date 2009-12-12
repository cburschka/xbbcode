<?php
// $Id$

/**
 * @file
 * Administrative interface for adding custom tags and assigning module handlers.
 */

/**
 * List custom tags and edit or delete them.
 * @param $form_state
 * @param $name
 *   If passed, load this tag for editing. Otherwise, list all tags and show a 
 *   collapsed tag creation form.
 * @return 
 *   A form ready for building.
 */
function xbbcode_custom_tags($form, &$form_state, $name = NULL) {
  module_load_include('inc', 'xbbcode', 'xbbcode.crud');
  // Whether we are editing an existing tag.
  $editing_tag = !empty($name);

  if ($editing_tag) {
    $tag = xbbcode_custom_tag_load($name);    
    $form['edit'] = array(
      '#type' => 'fieldset',
      '#title' => t('Editing Tag %name', array('%name' => $name)),
      '#collapsible' => FALSE,
    );
  }
  else {
    $tags = xbbcode_custom_tag_names();

    // A bit tricky: Whether the user submitted the tag-creation form to save a new tag.
    // In this case, the otherwise optional fields become required.
    $adding_tag = empty($tags) || (!empty($form_state['input']) && $form_state['input']['op'] == t('Save'));
    
    
    if (count($tags)) {
      foreach ($tags as $tag) {
        $options[$tag] = '['. $tag .'] '. l(t('edit'), "admin/config/xbbcode/tags/$name/edit");
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
    $tag = (object) array(
      'name' => '', 
      'description' => '', 
      'replacewith' => '', 
      'sample' => ''
    );
  }

  $form['edit']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
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
    '#title'=>t('Sample Tag'),
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
    ),
    '#description' => t('A selfclosing tag like [img=http://...] requires no closing tag to follow it.'),
  );
  
  if (!empty($tag->selfclosing)) $form['edit']['options']['#default_value'][] = 'selfclosing';
  if (!empty($tag->dynamic))     $form['edit']['options']['#default_value'][] = 'dynamic';
  if (!empty($tag->multiarg))    $form['edit']['options']['#default_value'][] = 'multiarg';
  
  $form['edit']['replacewith'] = array(
    '#type' => 'textarea',
    '#attributes' => array('style' => 'font-family:monospace'),
    '#title' => t('Rendering Code'),
    '#default_value' => $tag->replacewith,
    '#required' => $editing_tag || $adding_tag,
    '#description' => t(
      'Enter the complete text that [tag]content[/tag] should be replaced with, '.
      'or PHP code that prints/returns the text.',
      array('@url' => url('admin/help/xbbcode'))
    ),
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
  if (!preg_match('/^[a-z0-9]*$/i', $form_state['values']['name'])) form_set_error('name', t('The tag name must be alphanumeric.'));
  
  if ($form['edit']['name']['#default_value'] != $form_state['values']['name']) {
    if (xbbcode_custom_tag_exists($form_state['values']['name'])) {
      form_set_error('name', t('Error while creating or renaming tag: This tag name is already taken. '.
      'Please delete or edit the old tag, or choose a different name.'));
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
      if ($checked) $delete[] = $tag;
    }
  }
  
  xbbcode_custom_tag_delete($delete);
  
  $tags = '[' . implode('], [', $delete) . ']';
  
  drupal_set_message(format_plural(count($delete), 'The tag %tags has been deleted.', 'The tags %tags have been deleted.', array('%tags' => $tags)), 'status');
  xbbcode_rebuild_custom_tags();
  xbbcode_rebuild_handlers();
}

/**
 * Save (create or update) a custom tag.
 */
function xbbcode_custom_tags_save_submit($form, &$form_state) {
  $values = $form_state['values'];
  $values['name'] = strtolower($values['name']);
  
  foreach ($values['options'] as $name => $value) {
    if ($value) $values['options'][$name] = 1;
  }
  $tag = (object) $form_state['values'];
  
  if (xbbcode_custom_tag_save($tag)) {
    if ($form['edit']['name']['#default_value']) {
      drupal_set_message(t('Tag [@name] has been changed.', array('@name' => $values['name'])));
    }
    else {
      drupal_set_message(t('Tag [@name] has been created.', array('@name' => $values['name'])));
    }
  }
  $form_state['redirect'][0] = 'admin/config/content/xbbcode/tags';
  xbbcode_rebuild_custom_tags();
  xbbcode_rebuild_handlers();
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
    '#tree' => TRUE,
  );

  $form['global'] = array(
    '#weight' => -1,
    '#markup' => t('You are changing the global settings.'),
  );

  if (!empty($formats['global'])) {
    $form['global']['#markup'] .= ' '. t('The following formats are affected by the global settings:') .
      '<ul><li>'. implode('</li><li>', $formats['global']) .'</li></ul>';
  }
  if (!empty($formats['specific'])) {
    $form['global']['#markup'] .= ' '. t('The following formats have specific settings and will not be affected:') .
      '<ul><li>'. implode('</li><li>', $formats['specific']) .'</li></ul>';
  }
  
  $xbbcode = xbbcode_settings_handlers_format();
  
  if (!empty($xbbcode)) {
    foreach ($xbbcode as $id => $element) $form[$id] = $element;
  }
  else {
    $form['error'] = array(
      '#markup' => "<p>" . t('No tags or handlers are defined. Please install a module or <a href="@url">create some custom tags</a>.', array('@url' => url('admin/config/xbbcode/tags'))) . "</p>",
    );
    return $form;
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#name' => 'op',
    '#value' => t('Save changes')
  );
  return $form;
}

/**
 * Modify handler settings (subform).
 */
function xbbcode_settings_handlers_format($format = 0) {
  drupal_add_css(drupal_get_path('module', 'xbbcode') . '/xbbcode.css');
  drupal_add_js(drupal_get_path('module', 'xbbcode') . '/xbbcode.js');
  module_load_include('inc', 'xbbcode');
  $handlers = _xbbcode_build_handlers();
  $modules = xbbcode_module_names();  
  $defaults = xbbcode_handlers_load($format, TRUE);

  $options = array();
  if (empty($handlers)) {
    return FALSE;
  }
  foreach ($handlers as $handler) {
    $options[$handler['name']][$handler['module']] = $modules[$handler['module']];  
  }
  ksort($options);

  $form['format'] = array(
    '#type' => 'value',
    '#value' => $format,
  );
  
  $form['tags'] = array(
    '#type' => 'fieldset',
    '#theme' => 'xbbcode_settings_handlers_format',
    '#tree' => TRUE,
    '#title' => t('Tag settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  
  $form['tags']['_enabled'] = array(
    '#type' => 'tableselect',
    '#header' => array(
      'tag' => t('Tag'),
      'module' => t('Module'),
      'weight' => t('Weight'),
    ),
    '#value' => array(),
    '#options' => array(),
    '#attributes' => array('id' => 'xbbcode-handlers'),
  );
  
  $sort_order = array_combine(array_keys($options), array_keys(array_keys($options)));

  foreach ($options as $name => $handler) {
    $form['tags']['_enabled']['#options'][$name] = array(
      'tag' => array(
        'data' => _xbbcode_build_descriptions($name, array_keys($handler), $defaults[$name]->module),
        'class' => array('xbbcode-tag-description'),
      ),
      'module' => 'handler-select',
      'weight' => 'weight-select',
      '#weight' => $defaults[$name]->weight * 1000 + $sort_order[$name],
      '#attributes' => array('class' => $defaults[$name]->enabled ? array('selected') : array()),
    );
    $form['tags']['_enabled']['#value'][$name] = $defaults[$name]->enabled ? 1 : NULL;
    
    $form['tags'][$name]['module'] = array(
      '#type' => 'select',
      '#options' => $handler,
      '#default_value' => $defaults[$name]->module,
      '#attributes' => array('class' => array('xbbcode-tag-handler')),
    );

    $form['tags'][$name]['weight'] = array(
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
function theme_xbbcode_settings_handlers_format(array $variables) {
  $fieldset = $variables['fieldset'];

  $fieldset['_enabled']['#attributes']['id'] = 'xbbcode-handlers';

  foreach (element_children($fieldset) as $tag) {
    if (isset($fieldset[$tag]['#type'])) {
      continue;
    }
    
    if (count($fieldset[$tag]['module']['#options']) == 1) {
      $fieldset[$tag]['module'] = array(
        '#type' => 'markup',
        '#markup' => current($fieldset[$tag]['module']['#options']),
      );
    }
    
    $fieldset['_enabled']['#options'][$tag]['module'] = drupal_render($fieldset[$tag]['module']);
    $fieldset['_enabled']['#options'][$tag]['weight'] = drupal_render($fieldset[$tag]['weight']);
    $fieldset['_enabled']['#options'][$tag]['#attributes']['class']['draggable'] = 'draggable';
  }

  drupal_add_tabledrag('xbbcode-handlers', 'order', 'sibling', 'xbbcode-tag-weight');
  return drupal_render($fieldset['_enabled']);
}

/**
 * Save the handler settings.
 */
function xbbcode_settings_handlers_submit($form, $form_state) {
  $format_id = $form_state['values']['format'];
  $tags = $form_state['values']['tags'];
  $enabled = $tags['_enabled'];
  unset($tags['_enabled']);

  // When switching a specific format to global settings, delete the specific settings.
  // Ignore the rest of the form.
  if (xbbcode_format_is_specific($format_id) && $form_state['values']['override'] == 'global') {
    xbbcode_handlers_delete($format_id);
    drupal_set_message(t('The format-specific settings were reset. You can now change the global settings below.'), 'status');
  }
  else {
    foreach ($tags as $name => $values) {
      $values['name'] = $name;
      $values['enabled'] = $enabled[$name] ? 1 : 0;
      xbbcode_handler_save((object)$values, $format_id);
    }
  }
  drupal_set_message(t('Tag settings were updated.'), 'status');
}
