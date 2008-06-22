<?php

// This file is only loaded if the libraries are in place, so we can load them now.

require_once 'Text/Highlighter.php';
require_once 'Text/Highlighter/Renderer.php';
require_once 'Text/Highlighter/Renderer/Html.php';


function xbbcode_highlighter_highlight($settings, $text) {
  static $path;
  
  // Load the language class if possible, or abort.
  if (!isset($path)) $path = file_directory_path() . '/highlighter/';
  if (!include_once $settings['language'] . '.php') {
    drupal_set_message(t('Highlighter class for language %lang could not be loaded. Code was not highlighted.', array('%lang' => $settings['language'])), 'error');
    return $text;
  }
  
  // Initialize the highlighting engine and make sure it is working.
  $hl =& Text_Highlighter::factory($settings['language']);
  if (!is_callable(array($hl, 'setRenderer'))) {
    drupal_set_message(t('The generated class for the %lang language appears to be corrupted.', array('%lang' => $settings['language'])), 'error');
    return $text;
  }

  // Initialize the rendering engine and set the configuration options. 
  $options = array(
    'numbers' => $settings['numbers'] ? HL_NUMBERS_LI : NULL,
    'tabsize' => $settings['tabsize'] ? $settings['tabsize'] : 2,
  );
  $renderer =& new Text_Highlighter_Renderer_HTML($options);
  $hl->setRenderer($renderer);
  
  // Prepare the text and highlight it.
  $text = trim($text, "\n\r "); // whitespace around the code must be gone.
  $html = $hl->highlight($text);
  
  // The list items don't mix with auto-converted linebreaks.
  if ($settings['numbers']) $html = str_replace("\n", "", $html);
   
  return $html;
}
