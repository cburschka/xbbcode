<?php

  
  function xbbcode_custom_tags($form_state, $name = NULL) {
    $editing_tag = !empty($name);
    $adding_tag = !empty($form_state['post']['op']) && $form_state['post']['op'] == t('Save');
    
    $tag = array('name' => '', 'description' => '', 'replacewith' => '', 'sample' => '');    
    if ($name) {
      $tag = xbbcode_get_custom_tag($name);
      
      $form['edit'] = array(
        '#type' => 'fieldset',
        '#title' => t('Editing Tag %name', array('%name' => $name)),
        '#collapsible' => FALSE,
      );
    }
    else {
      $tags = xbbcode_get_custom_tag();
      
      if (count($tags)) {
        foreach ($tags as $tag) {
          $options[$tag] = '['. $tag .'] '. l(t('edit'), 'admin/settings/xbbcode/tags/'. $tag . '/edit');
        }
	$tag = array('name' => '', 'description' => '', 'replacewith' => '', 'sample' => '');

        $form['existing'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Existing Tags'),
          '#description' => t('Check these tags and click "Delete" to delete them.'),
          '#options' => $options,
        );
      
      }

      $form['edit'] = array(
        '#type' => 'fieldset',
        '#title' => t('Add new XBBCode tag'),
        '#collapsible' => TRUE,
        '#collapsed' => count($tags),
      );
    }

    $form['edit']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $name,
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
      '#default_value' => $tag['description'],
      '#required' => $editing_tag || $adding_tag,
      '#description' => t('This will be shown on help pages'),
    );
    
    $form['edit']['sample'] = array(
      '#type' => 'textfield',
      '#title'=>t('Sample Tag'),
      '#required' => $editing_tag || $adding_tag,
      '#description' => t('Enter an example of how this tag would be used. It will be shown on the help pages.'),
      '#default_value' => $tag['sample'],
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
    
    if (!empty($tag['selfclosing'])) $form['edit']['options']['#default_value'][] = 'selfclosing';
    if (!empty($tag['dynamic']))     $form['edit']['options']['#default_value'][] = 'dynamic';
    if (!empty($tag['multiarg']))    $form['edit']['options']['#default_value'][] = 'multiarg';
    
    $form['edit']['replacewith'] = array(
      '#type' => 'textarea',
      '#title' => t('Replacement code'),
      '#default_value' => $tag['replacewith'],
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
      '#value' => t('<p>The above field should be filled either with HTML or PHP code depending on whether your check the PHP code option.</p>
      <p>Regardless of whether you are using static HTML or dynamic PHP, the attributes and content of the tag in the processed tag will be inserted into 
      your code by replacing placeholders. If you would like to assign them to a variable in PHP, you need to assign it as <code>$variable&nbsp;=&nbsp;"{placeholder}";</code></p>
      <dl>
        <dt><code>{content}</code></dt>
	<dd> will be replaced with the text between opening and closing tags, if the tag is not self-closing. E.g.: <code>[url=http://www.drupal.org]<strong>Drupal</strong>[/url]</code></dd>
        <dt><code>{option}</code></dt>
	<dd> will be replaced with the single tag attribute, if the tag does not use multiple attributes. E.g.: <code>[url=<strong>http://www.drupal.org</strong>]Drupal[/url]</code>.</dd>
	<dt>any other <code>{placeholder}</code></dt>
	<dd> will be replaced with the tag attribute of the same name, if the tag uses multiple attributes. E.g: <strong>{by}</strong> is replaced with <code>[quote&nbsp;by=<strong>Author</strong>&nbsp;date=2008]Text[/quote]</code>.</dd>
      </dl>
      <p>Note that named attributes that are not used will currently <em>not replace their placeholders</em>.</p>'),
    );
    
    $form['edit']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => array('xbbcode_custom_tags_save_submit'),
    );
    
    if (!empty($name) || count($tags)) {
      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
        '#submit' => array('xbbcode_custom_tags_delete_submit'),
      );
    }

    return $form;
  }
  
  function xbbcode_custom_tags_validate($form, $form_state) {


    if (!preg_match('/^[a-z0-9]*$/i', $form_state['values']['name'])) form_set_error('name', t('The tag name must be alphanumeric.'));
    
    if ($form['edit']['name']['#default_value'] != $form_state['values']['name']) {
      $existing = db_result(db_query("SELECT * FROM {xbbcode_custom_tags} WHERE name='%s'", $form_state['values']['name']));
      if ($existing) {
        form_set_error('name', t('Error while creating or renaming tag: This tag name is already taken. '.
        'Please delete or edit the old tag, or choose a different name.'));
      }
    }
  }
  
  function xbbcode_custom_tags_delete_submit($form, $form_state) {
    $del = array();
    
    if (!empty($form_state['values']['name'])) {
      $del[$form_state['values']['name']] = db_query("DELETE FROM {xbbcode_custom_tags} WHERE name = '%s'", $form_state['values']['name']);
    }
    elseif (is_array($form_state['values']['existing'])) {
      foreach ($form_state['values']['existing'] as $tag => $delete) {
        if ($delete) $del[$tag] = db_query("DELETE FROM {xbbcode_custom_tags} WHERE name = '%s'", $tag);
      }
    }
    
    foreach ($del as $name => $success) {
      if ($success) {
        drupal_set_message(t('Tag [@name] has been deleted.', array('@name' => $name)), 'status');
      } else {
        drupal_set_message(t('Tag [@name] could not be deleted.', array('@name' => $name)), 'status');
      }
    }
  }
  
  function xbbcode_custom_tags_save_submit($form, $form_state) {
    $values = $form_state['values'];
    $values['name'] = strtolower($values['name']);
    
    foreach ($values['options'] as $name => $value) {
      if ($value) $values['options'][$name] = 1;
    }
    if ($form['edit']['name']['#default_value']) {
      $sql = 
        "UPDATE {xbbcode_custom_tags} SET name = '%s', replacewith = '%s', ".
        "description = '%s', sample = '%s', dynamic = %d, selfclosing = %d, multiarg = %d ".
        "WHERE name = '%s'";
      $message = t('Tag [@name] has been updated.', array('@name' => $values['name']));
      $error = t('Tag [@name] could not be updated.', array('@name' => $values['name']));
    } 
    else {
      $sql = 
        "INSERT INTO {xbbcode_custom_tags} ". 
        "(name, replacewith, description, sample, dynamic, selfclosing, multiarg) ".
        "VALUES ('%s', '%s', '%s', '%s', %d, %d, %d)";
      $message = t('Tag [@name] has been added.', array('@name' => $values['name']));
      $error = t('Tag [@name] could not be added.', array('@name' => $values['name']));
    }
    $success = db_query(
      $sql, $values['name'], $values['replacewith'], $values['description'], 
      $values['sample'], $values['options']['dynamic'], $values['options']['selfclosing'], 
      $values['options']['multiarg'], $form['edit']['name']['#default_value']
    );
  
    if ($success) drupal_set_message($message, 'status');
    else drupal_set_message($error, 'error');
    $form_state['redirect'] = 'admin/settings/xbbcode/tags';
  }
  
  function xbbcode_settings_handlers() {
    /* check for format-specific settings */
    $res = db_query("SELECT DISTINCT format, name FROM {filter_formats} NATURAL JOIN {filters} f WHERE f.module = 'xbbcode'");
    while ($row = db_fetch_array($res)) {
      $formats[$row['format']] = array('name' => $row['name'], 'specific' => FALSE);
    } 
    
    $res = db_query("SELECT DISTINCT format FROM {xbbcode_handlers}");
    while ($row = db_fetch_array($res)) {
      $formats[$row['format']]['specific'] = TRUE;
    }
    
    $global = array();
    $specific = array();
    foreach ($formats as $format => $set) {
      if (!isset($set['name'])) continue;
      if ($set['specific']) $specific[] = $set['name'];
      else $global[] = $set['name'];
    }
    
    $form = array(
      'global' => array(),
      'tags' => array(),
      '#tree' => TRUE,
    );

    $form['global'] = array(
      '#weight' => -1,
      '#value' => t('You are changing the global settings.'),
    );

    if (!empty($global)) {
      $form['global']['#value'] .= ' '. t('The following formats are affected by the global settings:') .
        '<ul><li>'. implode('</li><li>', $global) .'</li></ul>';
    }
    if (!empty($specified)) {
      $form['global']['#value'] .= ' '. t('The following formats have specific settings and will not be affected:') .
        '<ul><li>'. implode('</li><li>', $specified) .'</li></ul>';
    }

    $xbbcode = xbbcode_settings_handlers_form();
    foreach ($xbbcode as $id => $element) $form[$id] = $element;

    $form['submit'] = array(
      '#type' => 'submit',
      '#name' => 'op',
      '#value' => t('Save changes')
    );

    return $form;
  }

  function xbbcode_settings_handlers_form($format = 0) {
    $handlers = _xbbcode_get_handlers();  
    $defaults = _xbbcode_get_tags($format, TRUE);
  
    $options = array();
    foreach ($handlers as $handler) {
      $options[$handler['name']][$handler['module']] = $handler['module'];  
    }
    
    ksort($options);
    $form = array();
	
    $form['format'] = array(
      '#type' => 'value',
      '#value' => $format,
    );
    
    $form['tags'] = array(
      '#type' => 'fieldset',
      '#theme' => 'xbbcode_settings_handlers_form',
      '#tree' => TRUE,
      '#title' => t('Tag settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    foreach ($options as $name => $handler) {
      $form['tags'][$name] = array(
        '#type' => 'fieldset',
        '#title' => "[$name]",
        '#weight' => !empty($defaults[$name]['weight']) ? $defaults[$name]['weight'] : 0,
      );
      $form['tags'][$name]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => t("Enabled"),
        '#default_value' => isset($defaults[$name]['enabled']) ? $defaults[$name]['enabled'] : TRUE,
      );

      $form['tags'][$name]['handler'] = array(
        '#type' => 'select',
        '#title' => t("Handled by Module"),
        '#options' => $handler,
        '#default_value' => !empty($defaults[$name]['module']) ? $defaults[$name]['module'] : '',
      );

      $form['tags'][$name]['weight'] = array(
        '#type' => 'weight',
        '#title' => t("Weight"),
        '#delta' => 5,
        '#default_value' => !empty($defaults[$name]['weight']) ? $defaults[$name]['weight'] : 0,
      );
    }

    return $form;  
  }
  
  function theme_xbbcode_settings_handlers_form($fieldset) {
    $header = array(
      array(
        'data' => t('Enabled')
      ),
      array(
        'data' => t('Name')
      ),
      array(
        'data' => t('Handler')
      ),
      array(
        'data' => t('Weight')
      ),
    );

    // Build rows
    $rows = array();
    ksort($fieldset);
    
    foreach (element_children($fieldset) as $i) {
      if ($fieldset[$i]['#type'] != 'fieldset') continue;
      foreach ($fieldset[$i] as $j => $field) {
        if (is_array($field)) unset($fieldset[$i][$j]['#title']); // remove the titles
      }

      if (count($fieldset[$i]['handler']['#options']) == 1) {
        $fieldset[$i]['handler'] = array(
          '#type' => 'item',
          '#value' => current($fieldset[$i]['handler']['#options']),
        );
      }

      // Generate block row
      $row = array(
        drupal_render($fieldset[$i]['enabled']),
        "[$i]",
        drupal_render($fieldset[$i]['handler']),
        drupal_render($fieldset[$i]['weight']),
      );
      $rows[] = $row;
      $junk = drupal_render($fieldset[$i]);
    }

    // Finish table
    $output = theme('table', $header, $rows, array('id' => 'xbbcode-handlers'));
    $output .= drupal_render($form);
    return $output;
  }
  
  function xbbcode_settings_handlers_submit($form, $form_state) {
    $tags = $form_state['values']['tags'];
    $format = $form_state['values']['format'];
    if ($form_state['values']['format'] && $form_state['values']['override'] == 'global') {
      db_query("DELETE FROM {xbbcode_handlers} WHERE format = %d AND format != -1", $format);
	  drupal_set_message(t('The format-specific settings were reset.'), 'status');
	  return;
    }
    
    db_query('DELETE FROM {xbbcode_handlers} WHERE format = %d', $format);

    $args = array();
    $vals = array();
    
    foreach ($tags as $name => $settings) {
      $args[] = "('%s', %d, '%s', %d, %d)";
      if (!$settings['handler']) $settings['handler'] = current($form['tags'][$name]['handler']['#options']);
      array_push($vals, $name, $format, $settings['handler'], $settings['enabled'], $settings['weight']);
    }
    
    db_query('INSERT INTO {xbbcode_handlers} (name, format, module, enabled, weight) VALUES ' . implode(', ', $args), $vals);
    
    cache_clear_all('xbbcode_tags_'. $format, 'cache');
    drupal_set_message(t('Tag settings were updated.'), 'status');
  }