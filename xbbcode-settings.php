<?php

  
  function xbbcode_custom_tags($name = NULL) {
    $form = array();
    
    if (!$name) {
      $tags = xbbcode_get_custom_tag();
      
      if (count($tags)) {
        $form['existing'] = array(
          '#type' => 'fieldset',
          '#title' => t('Existing Tags'),
          '#description' => t('Check these tags and click "Delete" to delete them.'),
          '#collapsible' => FALSE,
          '#tree' => TRUE,
        );
      
        foreach ($tags as $tag) {
          $form['existing']['delete_'. $tag['name']] = array(
            '#type' => 'checkbox',
            '#title' => '['. $tag .'] '. l(t('edit'), 'admin/settings/xbbcode/tags/'. $tag . '/edit'),
            '#description' => $tag['description'],
          );
        }  
      }

      $form['edit'] = array(
        '#type' => 'fieldset',
        '#title' => t('Add new XBBCode tag'),
        '#collapsible' => TRUE,
        '#collapsed' => count($tags),
      );
    } 
    else {
      $tag = xbbcode_get_custom_tag($name);

      $form['edit'] = array(
        '#type' => 'fieldset',
        '#title' => t('Editing Tag %name', array('%name' => $name)),
        '#collapsible' => FALSE,
      );
    }

    $form['edit']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('[name]'),
      '#default_value' => $name,
      '#required' => !empty($name),
      '#maxlength' => 32,
      '#size' => 16,
      '#description' => t('The name of this tag. The name will be used in the text as [name]...[/name]. Must be alphanumeric and will automatically be converted to lowercase.'),
    );
    
    $form['edit']['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $tag['description'],
      '#required' => !empty($name),
      '#description' => t('This will be shown on help pages'),
    );
    
    $form['edit']['sample'] = array(
      '#type' => 'textfield',
      '#title'=>t('Sample Tag'),
      '#required' => !empty($tag),
      '#description' => t('Enter an example of how this tag would be used. It will be shown on the help pages.'),
      '#default_value' => $tag['sample'],
    );
    
    $form['edit']['options'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Tag options'),
      '#options' => array(
        'selfclosing' => t('Self-closing'),
        'dynamic' => t('Use dynamic replacement'),
        'multiarg' => t('Uses multiple named arguments'),
      ),
      '#description' => t('A selfclosing tag like [img=http://...] requires no closing tag to follow it.
      For dynamic tags, the replacement text is evaluated as PHP code.'),
    );
    
    if ($tag['selfclosing']) $form['edit']['options']['#default_value'][] = 'selfclosing';
    if ($tag['dynamic'])     $form['edit']['options']['#default_value'][] = 'dynamic';
    if ($tag['multiarg'])    $form['edit']['options']['#default_value'][] = 'multiarg';
    
    $form['edit']['replacewith'] = array(
      '#type' => 'textarea',
      '#title' => t('Replacement code'),
      '#default_value' => $tag['replacewith'],
      '#required' => empty($name),
      '#description' => t(
        'Enter the complete text that [tag]content[/tag] should be replaced with, '.
        'or PHP code that returns the text. Use the <a href="@url">help page</a> if necessary.',
        array('@url' => url('admin/help/xbbcode'))
      ),
    );
    
    $form['edit']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => 'xbbcode_custom_tags_save_submit',
    );
    
    if (!empty($name) || count($tags)) {
      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
        '#submit' => 'xbbcode_custom_tags_delete_submit',
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
    if (empty($form_state['values']['name'])) {
      $del[$name] = db_query("DELETE FROM {xbbcode_custom_tags} WHERE name='%s'", $form_state['values']['name']);
    }
    foreach ($form_state['values']['existing'] as $name => $value) {
      if ($value) {
        $del[$name] = db_query("DELETE FROM {xbbcode_custom_tags} WHERE name='%s'", $name);
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
    return 'admin/settings/xbbcode/tags';
  }
  
  function xbbcode_settings_handlers() {
    /* check for format-specific settings */
    $res = db_query(
      "SELECT DISTINCT {xbbcode_handlers}.format AS format, {filter_formats}.name AS name ".
      "FROM {filter_formats} JOIN {filters} ".
	  "ON {filter_formats}.format = {filters}.format AND {filters}.module = 'xbbcode' ".
	  "LEFT JOIN {xbbcode_handlers} ".
      "ON {filter_formats}.format = {xbbcode_handlers}.format"
    );
    while ($row = db_fetch_array($res)) {
      if (!empty($row['format'])) $specified[] = $row['name'];
      else $global[] = $row['name'];
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

  function xbbcode_settings_handlers_form($format = -1) {
    $handlers = _xbbcode_get_handlers();  
    $defaults = _xbbcode_get_tags($format, TRUE);
  
    $options = array();
    foreach ($handlers as $handler) {
      $options[$handler['name']][$handler['module']] = $handler['module'];  
    }

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
      );
      $form['tags'][$name]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => t("Enabled"),
        '#default_value' => $defaults[$name]['enabled'],
      );

      $form['tags'][$name]['handler'] = array(
        '#type' => 'select',
        '#title' => t("Handled by Module"),
        '#options' => $handler,
        '#default_value' => $defaults[$name]['module'],
      );

      $form['tags'][$name]['weight'] = array(
        '#type' => 'weight',
        '#title' => t("Weight"),
        '#delta' => 5,
        '#default_value' => $defaults[$name]['weight'],
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
    uasort($fieldset, 'element_sort'); // sort by weight.
    
    foreach (element_children($fieldset) as $i) {
      foreach ($fieldset[$i] as $j => $field) {
        if (is_array($field)) unset($fieldset[$i][$j]['#title']); // remove the titles
      }

      if (count($fieldset[$i]['handler']['#options']) == 1) {
        $fieldset[$i]['handler'] = array(
          '#type' => 'item',
          '#value' => $fieldset[$i]['handler']['#default_value'],
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
    }

    unset($fieldset); // to avoid the virtual fieldsets being rendered.

    // Finish table
    $output = theme('table', $header, $rows, array('id' => 'xbbcode-handlers'));
    $output .= drupal_render($form);
    return $output;
  }
  
  function xbbcode_settings_handlers_submit($form, $form_state) {
    $tags = $form_state['values']['tags'];
    $format = $form['format'];
    if ($form_state['values']['override'] == 'global' && $tags['format'] > -1) {
      db_query("DELETE FROM {xbbcode_handlers} WHERE format = %d AND format != -1", $format);
	  drupal_set_message(t('The format-specific settings were reset.'), 'status');
	  return;
    }

    foreach ($tags as $name => $settings) {
      if (db_result(db_query("SELECT COUNT(*) FROM {xbbcode_handlers} WHERE name = '%s' AND format = %d", $name, $format))) {
        $sql = "UPDATE {xbbcode_handlers} SET module = '%s', enabled = %d, weight = %d WHERE name = '%s' AND format = %d";
      }
      else {
        $sql = 
          "INSERT INTO {xbbcode_handlers} (module, enabled, weight, name, format) ".
          "VALUES ('%s', %d, %d, '%s', %d)";
      }
      db_query($sql, $settings['module'], $settings['enabled'], $settings['weight'], $name,$format);
    }
	cache_clear_all('xbbcode_tags_'. $format, 'cache');
    drupal_set_message(t('Tag settings were updated.', array('%name' => $format_name)), 'status');
  }
