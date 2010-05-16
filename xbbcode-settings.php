<?php

  
  function xbbcode_custom_tags($name='') {
    $form=array();
    
    if (!$name) {
      $res = db_query("SELECT name, dynamic, replacewith FROM {xbbcode_custom_tags}");
      while ($row = db_fetch_array($res)) $tags[] = $row;
      
      $form['existing'] = array(
        '#type' => 'fieldset',
        '#title' => t('Existing Tags'),
        '#description' => t('Check these tags and click "Delete" to delete them.'),
        '#collapsible' => TRUE,
        '#collapsed' => !count($tags) || $name,
      );
      
      if ($tags) foreach ($tags as $tag) {
        $form['existing']['delete_'. $tag['name']] = array(
          '#type' => 'checkbox',
          '#title' => '['. $tag['name'] .'] '. l(t('edit'), 'admin/settings/xbbcode/tags/'. $tag['name']),
          '#description' => $tag['description'],
        );
      }
      
      $form['edit'] = array(
        '#type' => 'fieldset',
        '#title' => t('Editing Tag !name', array('!name' => $name)),
        '#collapsible' => TRUE,
        '#collapsed' => count($tags) && !$name,
      );
      unset($tag);
    } 
  else {
      $tag = db_fetch_object(db_query("SELECT * FROM {xbbcode_custom_tags} WHERE name='%s'", $name));
      $form['edit']['oldname' ] =array(
        '#type' => 'hidden',
        '#value' => $name,
      );
    }
    
    if (!$tag) {
      $form['edit']['#title'] = t('Add new XBBCode tag');
    }
    
    $form['edit']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('[name]'),
      '#default_value' => $tag->name,
      '#required' => $name,
      '#maxlength' => 32,
      '#size' => 16,
      '#description' => t('The name of this tag. The name will be used in the text as [name]...[/name]. Must be alphanumeric and will automatically be converted to lowercase.'),
    );
    
    $form['edit']['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $tag->description,
      '#required' => $name,
      '#description' => t('This will be shown on help pages'),
    );
    
    $form['edit']['sample'] = array(
      '#type' => 'textfield',
      '#title'=>t('Sample Tag'),
      '#required' => $name,
      '#description' => t('Enter an example of how this tag would be used. It will be shown on the help pages.'),
      '#default_value' => $tag->sample,
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
    
    if ($tag->selfclosing) $form['edit']['options']['#default_value'][] = 'selfclosing';
    if ($tag->dynamic) $form['edit']['options']['#default_value'][] = 'dynamic';
    if ($tag->multiarg) $form['edit']['options']['#default_value'][] = 'multiarg';
    
    $form['edit']['replacewith'] = array(
      '#type' => 'textarea',
      '#title' => t('Replacement code'),
      '#default_value' => $tag->replacewith,
      '#required' => $name,
      '#description' => t('Enter the complete text that [tag]content[/tag] should be replaced with,
      or PHP code that returns the text. Use the '. l("help page", "admin/help/xbbcode") .' if necessary.'),
    );
    
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
    );
    
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
    );
    
    return $form;
  }
  
  function xbbcode_custom_tags_validate($id, $form) {
    if (!preg_match('/^[a-z0-9]*$/i', $form['name'])) form_set_error('name', t('The tag name must be alphanumeric.'));
    
    if ($form['oldname'] != $form['name']) {
      if (db_result(db_query("SELECT * FROM {xbbcode_custom_tags} WHERE name='%s'", $form['name'])))
      form_set_error('name', t('Error while creating or renaming tag: This tag name is already ".
      "taken. Please delete or edit the old tag, or choose a different name.'));
    }
  }
  
  function xbbcode_custom_tags_submit($id, $form) {
    if ($form['op']==t('Delete')) {
      if ($form['name'] && db_query("DELETE FROM {xbbcode_custom_tags} WHERE name='%s'",$form['name'])) {
        $del[$name] = TRUE;
      }
      foreach ($form as $name => $value) {
        if (!$value || !preg_match('/^delete_(.*)$/', $name, $match)) continue;
        if (db_query("DELETE FROM {xbbcode_custom_tags} WHERE name='%s'",$match[1])) {
          $del[$match[1]] = TRUE;
        }
      }
    
      foreach ($del as $name => $value) {
        drupal_set_message(t('Tag [!name] has been deleted.', array('!name' => $name)), 'status');
      }
    }
  
    if ($form['name']) {
      foreach ($form['options'] as $name => $value) {
        if ($value) $form['options'][$name] = 1;
      }
      if ($form['oldname']) {
        $sql = 
          "UPDATE {xbbcode_custom_tags} SET name = '%s', replacewith = '%s', ".
          "description = '%s', sample = '%s', dynamic = %d, selfclosing = %d, multiarg = %d ".
          "WHERE name = '%s'";
        $message = t('Tag [%name] has been updated.', array('%name' => $form['name']));
      } 
      else {
        $sql = 
          "INSERT INTO {xbbcode_custom_tags} ". 
          "(name, replacewith, description, sample, dynamic, selfclosing, multiarg) ".
          "VALUES ('%s', '%s', '%s', '%s', %d, %d, %d)";
        $message = t('Tag [%name] has been added.',array('%name' => $form['name']));
      }
      $success = db_query(
        $sql, $form['name'], $form['replacewith'], $form['description'], 
        $form['sample'], $form['options']['dynamic'], $form['options']['selfclosing'], 
        $form['options']['multiarg'], $form['oldname']
      );
  
      if ($success) drupal_set_message($message, 'status');
    }
    return 'admin/settings/xbbcode/tags';
  }
  
  function xbbcode_settings_handlers($format = -1, $format_name = 'Global') {
    $tags = _xbbcode_get_handlers();

    /* check for format-specific settings */
    if ($format != -1) {
      $use_format = db_result(db_query("SELECT COUNT(*) FROM {xbbcode_handlers} WHERE format=%d", $format));
    }
    $use_format = $use_format ? $format : -1;
  
    $res = db_query(
      "SELECT name, module, enabled, weight FROM {xbbcode_handlers} WHERE format = %d ORDER BY weight, name", 
      $use_format
    );
  
    while ($row = db_fetch_object($res)) {
      $defaults[$row->name] = $row;
    }
  
    $handlers = array();
    foreach ($tags as $tag) {
      $handlers[$tag['name']][$tag['module']] = $tag['module'];  
    }
    ksort($handlers); // sort them alphabetically.
    
    $form = array(
      'global' => array(),
      'tags' => array(),
      '#tree' => TRUE,
    );
  
    $form['format'] = array(
      '#type' => 'value',
      '#value' => $format
    );
  
    $form['format_name'] = array(
      '#type' => 'value',
      '#value' => $format_name
    );
	
    if ($use_format != $format) {
      $form['global'] = array(
        '#type' => 'item',
        '#weight' => -10,
        '#value' => t(
          "You are changing the settings for !format for the first time. Until you save them, ".
          "changes to the global settings will affect !format as well, but not afterward. ".
          "You can later reset these format-specific settings to the global configuration."
        )
      );
    } 
    else if ($format == -1 && _xbbcode_list_formats()) {
      $form['global'] = array(
        '#type' => 'item',
        '#weight' => -1,
        '#value' => t(
          "You are changing the global settings. These values will be used for any future format ".
          "that uses the XBBCode filter, as well as all existing formats whose settings haven't been ".
		  "modified.")
      );
    }

    foreach ($handlers as $name=>$handler) {
      $form['tags'][$name] = array(
        '#type' => 'fieldset',
        '#title' => "[$name]",
      );
      $form['tags'][$name]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => t("Enabled"),
        '#default_value' => $defaults[$name]->enabled,
      );

      if (count($handler) > 1) {
        $form['tags'][$name]['module'] = array(
          '#type' => 'select',
          '#title' => t("Handled by Module"),
          '#options' => $handler,
          '#default_value' => $defaults[$name]->module,
        );
      }
      else {
        /* unfortunately, we now need two form elements, one for sending and one for showing. */
        $form['tags'][$name]['handler'] = array(
          '#type' => 'item',
          '#title' => t("Handled by Module"),
          '#value' => current($handler),
        );
        $form['tags'][$name]['module'] = array(
          '#type' => 'value',
          '#title' => t("Handled by Module"),
          '#value' => current($handler),
        );
      }

      $form['tags'][$name]['weight'] = array(
        '#type' => 'weight',
        '#title' => t("Weight"),
        '#delta' => 5,
        '#default_value' => $defaults[$name]->weight,
      );
    }

    $form['submit']=array('#type'=>'submit','#name'=>'op','#value'=>t('Save changes'));
	
    if ($use_format != -1) {
      $form['restore'] = array(
        '#type' => 'submit', 
        '#name' => 'op',
        '#value' => t('Restore global values')
      );
    }
    return $form;  
  }
  
  function theme_xbbcode_settings_handlers(&$form) {
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
    uasort($form['tags'],'_element_sort'); // sort by weight.
    
    foreach (element_children($form['tags']) as $i) {
      $tag = &$form['tags'][$i];
      foreach ($tag as $j=>$field) {
      	if (is_array($field)) unset($tag[$j]['#title']); // remove the titles
      }

      // Fetch values
      $enabled = $tag['enabled']['#default_value'];
      $handler = $tag['handler']['#default_value'];
	  
      // Generate block row
      $row = array(
        drupal_render($tag['enabled']),
        "[$i]",
        drupal_render($tag['handler']).drupal_render($tag['module']),
        drupal_render($tag['weight']),
      );
      $rows[] = $row;
    }

    unset($form['tags']); // to avoid the virtual fieldsets being rendered.

    // Finish table
    $output = theme('table', $header, $rows, array('id' => 'xbbcode-handlers'));
    $output .= drupal_render($form);
    return $output;
  }
  
  function xbbcode_settings_handlers_submit($form_id, $form) {
    $tags = $form['tags'];
    $format = $form['format'];
    $format_name = $form['format_name'];
    if ($form['restore'] == t("Restore global values")) {
      db_query("DELETE FROM {xbbcode_handlers} WHERE format = %d AND format != -1", $format);
      drupal_set_message(
        t(
          "Tag settings of format %format were reset to the global values.",
          array('%format' => $format_name)
        ),
        'status'
      );
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
    drupal_set_message(t('Tag settings of format %format were updated.', array('%name' => $format_name)), 'status');
  }
