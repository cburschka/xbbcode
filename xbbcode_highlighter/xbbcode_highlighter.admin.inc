<?php

function xbbcode_highlighter_settings() {
  global $xbbcode_highlighter_okay;
  
  if (!$xbbcode_highlighter_okay) {
    // Switch to the installer if it's not yet installed.
    return xbbcode_highlighter_setup();
  }
  xbbcode_highlighter_discovery();  

  $res = db_query("SELECT id, name, description, sample FROM {xbbcode_highlighter}");
  $rows = array();
  while ($row = db_fetch_array($result)) {
    $rows[$row['id']] = $row;
  }
  
  $form['#tree'] = TRUE;
  $form['help'] = array(
    '#type' => 'item',
    '#value' => t('To provide well-formatted tooltips, individual descriptions and code samples must be entered manually.'),
  );
  $form['codes'] = array(
    '#type' => 'fieldset',
    '#title' => t('Languages'),
    '#collapsible' => TRUE,
  );

  if (!$rows) {
    $form['codes'] = array(
      '#type' => 'item',
      '#value' => t('No languages have been detected. You must import some first.'),
    );
  }
  
  foreach ($rows as $code => $row) {
    $form['codes'][$code] = array(
      '#type' => 'fieldset',
      '#title' => $row['name'] ? $row['name'] : strtoupper($row['id']),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE
    );
    $form['codes'][$code]['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $row['name'],
      '#description' => t('Enter a proper name for this language. By default, the machine-readable code of the language will be displayed in upper-case.'),
    );
    $form['codes'][$code]['name'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#description' => t('An optional description will be shown in the long filter tips.'),
      '#default_value' => $row['description'],
    );
    $form['codes'][$code]['sample'] = array(
      '#type' => 'textarea',
      '#title' => t('Code sample'),
      '#description' => t("Provide a sample of code (such as a Hello World program) written in this language."),
      '#default_value' => $row['sample'],
    );  
  }

  return system_settings_form($form);
}

function xbbcode_highlighter_settings_submit($form, &$form_state) {
  global $xbbcode_highlighter_okay;
  if (!$xbbcode_highlighter_okay) {
    return xbbcode_highlighter_setup_submit();
  }

  foreach ($form_state['values']['codes'] as $code => $setting) {
    db_query("UPDATE {xbbcode_highlighter} SET name = '%s', description = '%s', sample = '%s' WHERE id = '%s'",
    $setting['name'], $settings['description'], $settings['sample'], $code);
  }
  drupal_set_message(t('The configuration was saved.'));
}


function xbbcode_highlighter_import() {
  if (!$xbbcode_highlighter_okay) {
    $form['no'] = array(
      '#type' => 'item',
      '#value' => t('You cannot import any language files until the required libraries are installed.'),
    );
    return $form;
  }
  
  $form['#attributes'] = array('enctype' => 'multipart/form-data');
  $form['help'] = array(
    '#type' => 'item',
    '#value' => t('You can import a new syntax highlighting scheme here. You may upload either an XML file or a PHP class.'),
  );
  $form['language'] = array(
    '#type' => 'file',
    '#title' => t('File to import'),
    '#description' => t('Upload an XML or PHP file.'),
    '#required' => TRUE,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import'),
  );
  
  return $form;
}

function xbbcode_highlighter_import_validate($form, &$form_state) {
  if (!preg_match('/^([^\.]+)\.(php\.txt|xml)$/', $_FILES['files']['name']['language'])) {
    form_set_error('language', t('Your file must have an xml or php extension.'));
  }
}

function xbbcode_highlighter_import_submit($form, &$form_state) {
  require_once('Text/Highlighter/Generator.php');
  $path = file_directory_path() . '/highlighter';
  
  // To upload PHP files, we need to temporarily switch off security.
  variable_set('allow_insecure_uploads', 1);
  $file = file_save_upload('language', array(), $path, FILE_EXISTS_REPLACE);
  
  // And switch it back on.
  variable_set('allow_insecure_uploads', 0);

  preg_match('/^([^\.]+)\.(php\.txt|xml)$/', $file->filename, $match);
  $base = $match[1];
  $ext = $match[2];
  
  // Strip the safety extension
  if ($ext == 'php.txt') {
    file_move($file->filepath, "$path/$base.php", FILE_EXISTS_REPLACE);
    $ext = 'php';
    $pattern = '/class Text_Highlighter_([^ ]+) extends Text_Highlighter/';
  }
  else {
    $pattern = '/<highlight lang="([^"]+)"/';
  }
  
  // Make sure the file is named the same as its contained language (and contains one at all).
  $fp = fopen("$path/$base.$ext", 'r');
  while (!feof($fp) && !$lang_code) {
    if (preg_match($pattern, fgets($fp), $match)) {
      $lang_code = strtolower($match[1]);
    }
  }
  fclose($fp);
  if (!$lang_code) {
    drupal_set_message(t('Your file failed a sanity check. Make sure that it contains either an XML document of the type highlighter, or a PHP class extending Text_Highlighter.'), 'error');
    return;
  }
  if ($lang_code != $base) {
    file_move($t = "$path/$base.$ext", "$path/$lang_code.$ext", FILE_EXISTS_REPLACE);
  }
  
  // If it's an XML file, generate a class.
  if ($ext == 'xml') {
    require_once 'Text/Highlighter/Generator.php'; 
    $generator =& new Text_Highlighter_Generator("$path/$lang_code.xml");
    $generator->generate();
    $generator->saveCode("$path/$lang_code.php");
    if (file_exists("$path/$lang_code.php")) {
      drupal_set_message(t('Your XML file was successfully parsed.'));
    }
    else {
      drupal_set_message(t('There was an error when generating the class file.'), 'error');
      return;
    }
  }  
  // If it's a PHP file, make a trial run to ensure it works.
  else {
    include_once("$path/$lang_code.php");
    require_once 'Text/Highlighter.php';
    $hl =& Text_Highlighter::factory($lang_code);
    if (is_callable(array($hl, 'setRenderer'))) {
      drupal_set_message(t('Your PHP class file was successfully registered.'));
    }
    else {
      drupal_set_message(t('Your PHP class file appears to be invalid.'), 'error');
      return;
    }
  }

  db_query("INSERT INTO {xbbcode_highlighter} (id) VALUES('%s')", $lang_code);
  drupal_set_message(t('The syntax language %lang was imported.', array('%lang' => $lang_code)));
  xbbcode_add_module('xbbcode_highlighter');
}

function xbbcode_highlighter_setup() {
  include_once(drupal_get_path('module','xbbcode_highlighter').'/xbbcode_highlighter-setup.inc');

  $highlighter = xbbcode_highlighter_pear('highlighter');
  $parser = xbbcode_highlighter_pear('parser');
  
  switch ($highlighter) {
    case FALSE:
      $form['text'] = array(
        '#type'  => 'textfield',
        '#title' => t('Text Highlighter'),
        '#description' => t('The PEAR Text Highlighter library is required for this module. Please <a href="@url">download the package</a> and extract it in a subdirectory of this module. If you reload this page after the package is installed, the module will automatically detect it and save you the filling out of this form. The Highlighter.php file must reside in a directory named <em>Text</em>.',
        array('@url' => url('http://pear.php.net/package/Text_Highlighter'))),      
      );
      break;
    case 1:
      $form['text'] = array(
        '#type' => 'item',
        '#title' => t('Text Highlighter'),
        '#value' => variable_get('xbbcode_highlighter_pear_highlighter', ''),
        '#description' => t('The Text_Highlighter library was detected at this location and installed.'),
      );
      break;
    case 2:
      break; // No field at all.
    default:
      foreach ($highlighter as $filename  =>  $file) {
        $options[dirname(dirname($filename))] = dirname($filename);
      }
      $form['text'] = array(
        '#type' => 'select',
        '#title' => t('Text Highlighter'),
        '#options' => $options,
        '#description' => t('The module believes it has found the Text_Highlighter library in multiple locations. Choose the correct one.'),
      );
      break;
  }
  $options = array();
  switch ($parser) {
    case FALSE:
      $form['xml'] = array(
        '#type'  => 'textfield',
        '#title' => t('XML Parser'),
        '#description' => t('The PEAR XML_Parser library is required for this module. Please <a href="@url">download the package</a> and extract it in a subdirectory of this module. If you reload this page after the package is installed, the module will automatically detect it and save you the filling out of this form. Note: The file <em>Parser.php</em> must be in a directory named <em>XML</em> because the Text_Highlighter library expects it there.',
        array('@url' => url('http://pear.php.net/package/XML_Parser'))),      
      );
      break;
    case 1:
      $form['xml'] = array(
        '#type' => 'item',
        '#title' => t('XML Parser'),
        '#value' => variable_get('xbbcode_highlighter_pear_parser', ''),
        '#description' => t('The XML_Parser library was detected at this location and installed.'),
      );
      break;
    case 2:
      break; // No field at all.
    default:
      foreach ($parser as $filename  =>  $file) {
        $options[dirname(dirname($filename))] = dirname($filename);
      }
      $form['xml'] = array(
        '#type' => 'select',
        '#title' => t('XML Parser'),
        '#options' => $options,
        '#description' => t('The module believes it has found the XML_Parser library in multiple locations. Choose the correct one.'),
      );
      break;
  }

  if (variable_get('xbbcode_highlighter_pear_highlighter', FALSE) && variable_get('xbbcode_highlighter_pear_parser', FALSE)) {
    return $form;
  }
  else return system_settings_form($form);
}

function xbbcode_highlighter_setup_validate($form, &$form_state) {
  $values = $form_state['values'];
  if (!variable_get('xbbcode_highlighter_pear_highlighter', FALSE) && (!$values['text'] || !file_exists($values['text'].'/Text/Highlighter.php'))) {
    form_set_error('text', t('The Text_Highlighter library could not be found at this location.'));
  }
  if (!variable_get('xbbcode_highlighter_pear_parser', FALSE) && (!$values['xml'] || !file_exists($values['xml'].'/XML/Parser.php'))) {
    form_set_error('xml', t('The XML_Parser library could not be found at this location.'));
  }
}

function xbbcode_highlighter_setup_submit($form, &$form_state) {
  $values = $form_state['values'];
  if ($form['text']) variable_set('xbbcode_highlighter_pear_highlighter', $form['text']);
  if ($form['xml']) variable_set('xbbcode_highlighter_pear_parser', $form['xml']);
}

function xbbcode_highlighter_pear($library) {
  $path = drupal_get_path('module', 'xbbcode_highlighter');
  
  switch ($library) {
    case 'highlighter':
      $filename = 'Highlighter.php';
      $match = '/Text\/Highlighter.php$/';
      $package = 'Text_Highlighter';
      break;
    case 'parser':
      $filename = 'Parser.php';
      $match = '/XML\/Parser.php$/';
      $package = 'XML_Parser';
  }
  
  if (@include_once($match)) {
    drupal_set_message(t('The %lib package is already installed and requires no further setup.', array('%lib' => $package)));
    return 2;
  }
  
  $files = file_scan_directory($path, $filename);
  foreach ($files as $i => $file) {
    if (!preg_match($match, $i)) {
      unset($files[$i]);
    }
  }
  
  if (count($files) == 1) {
    $choice = dirname(dirname(current(array_keys($files))));
    variable_set('xbbcode_highlighter_pear_'. $library, $choice);
    if (variable_get('xbbcode_highlighter_pear_highlighter', FALSE) && variable_get('xbbcode_highlighter_pear_parser', FALSE)) {
      drupal_set_message(t('XBBCode Highlighter has automatically detected the %lib package it requires.', array('%lib' => $package)));
    }     
    return 1;
  }
  else if (!$files) {
    return FALSE;
  }
  else {
    return $files;
  }    
}

function xbbcode_highlighter_discovery() {
  static $languages;
  
  if (!isset($languages)) {
    $res = db_query('SELECT id FROM {xbbcode_highlighter}');
    while ($row = db_fetch_array($res)) {
      $languages[$row['id']] = $row['id'];
    }
  }
  require_once('Text/Highlighter/Generator.php');    
  
  $path = file_directory_path() . '/highlighter/';
  
  // Find all PHP and XML files in this folder.
  $classes = file_scan_directory($path, '^.+\.php$', array(), FALSE, '', 'name');
  $xml = file_scan_directory($xmlpath,'^.+\.xml$', array(), FALSE, '', 'name');
  
  $generated = array();
  foreach ($xml as $name => $file) {
    // If the class is missing, generate one:
    if (!$classes[$name]) {
      $generator =& new Text_Highlighter_Generator("$path/$name.xml");
      $generator->generate();
      $generator->saveCode("$path/$name.php");
      $generated[$name] = $name;
    }
  }
  
  // Rescan if necessary.
  if (!empty($generated)) {
    $classes = file_scan_directory($path, '^.+\.php$', array(), FALSE, '', 'name');
  }

  foreach ($classes as $name => $file) {
    // If the language is not registered, register it:
    if (!$languages[$name]) {
      db_query("INSERT INTO {xbbcode_highlighter} (id) VALUES('%s')", $name);
      if (!isset($generated[$name])) $registered[$name] = $name;
    }
  }

  if (count($generated)) {
    drupal_set_message(t('Drupal detected %count XML language files and generated PHP classes from them: %list.',
      array('%count' => count($generated), '%list' => implode(', ', $generated))));
  }
  
  if (count($registered)) {
    drupal_set_message(t('Drupal detected %count PHP language classes and registered them: %list',
    array('%count' => count($registered), '%list' => implode(', ', $registered))));
  }
  if ($registered || $generated) xbbcode_add_module('xbbcode_highlighter');
}
