<?php

function _xbbcode_highlighter_pear($library) {
  $path=drupal_get_path('module','xbbcode_highlighter');
  
  switch ($library) {
    case 'highlighter':
      $filename = 'Highlighter.php';
      $match = '/Text\/Highlighter.php$/';
      break;
    case 'parser':
      $filename = 'Parser.php';
      $match = '/XML\/Parser.php$/';
  }
  
  $files=file_scan_directory($path, $filename);
  foreach ($files as $i => $file) {
    if (!preg_match($match, $i)) {
      unset($files[$i]);
    }
  }
  
  if (count($files) == 1) {
    $choice=dirname(dirname(current(array_keys($files))));
    variable_set('xbbcode_highlighter_pear_'. $library, $choice);
    if (variable_get('xbbcode_highlighter_pear_highlighter',false) && variable_get('xbbcode_highlighter_pear_parser',false)) {
      drupal_set_message(t("XBBCode highlighter has automatically detected the !library library it requires.", array('!library' => $library)));
    }		  
    return true;
  }
  else if (!$files) {
    return false;
  }
  else {
    return $files;
  }    
}
