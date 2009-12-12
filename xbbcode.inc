<?php
// $Id$


/**
 * @file
 * General library of internal functions only called by this module.
 */

/**
 * Create or load a parser object.
 *
 * @param $format_id
 *
 * @return The XBBCodeFilter object.
 */
function _xbbcode_build_filter($format_id) {
  $filters = &drupal_static(__FUNCTION__, array());
  if (!isset($filters[$format_id])) {
    $tags = _xbbcode_build_tags($format_id);
    $filters[$format_id] = new XBBCodeFilter($tags, $format_id);
  }
  return $filters[$format_id];
}

/**
 * Discover the handlers by module hook invokation.
 *
 * @return all modules for all tags.
 */
function _xbbcode_build_handlers() {
  // This function does not use caching because it is used only in the settings page.
  $all = array();

  foreach (module_implements('xbbcode') as $module) {
    $tags = module_invoke($module, 'xbbcode', 'list');
    if (is_array($tags)) {
      foreach ($tags as $i => $tag) {
        $tags[$i] = array('name' => $tag, 'module' => $module);
      }
      $all = array_merge($all, $tags);
    }
  }
  return $all;
}

/**
 * Invoke all handlers to get the tags for a certain format.
 *
 * @param $format_id
 * @param $settings
 *
 * @return A list of tag objects.
 */
function _xbbcode_build_tags($format_id, $settings = FALSE) {
  $handlers = xbbcode_handlers_load($format_id);
  $tags = array();

  $defaults = array(
    'replacewith' => '',
    'description' => '',
    'sample' => '',
    'multiarg' => FALSE,
    'dynamic' => FALSE,
    'selfclosing' => FALSE,
  );

  foreach ($handlers as $handler) {
    $tag = (object)array(
      'name' => $handler->name,
      'module' => $handler->module,
      'weight' => $handler->weight,
      'info' => module_invoke($handler->module, 'xbbcode', 'info', $handler->name),
    );
    foreach ($defaults as $key => $val) {
      if (!isset($tag->info->$key)) {
        $tag->info->$key = $val;
      }
    }
    $tags[$handler->name] = $tag;
  }
  return $tags;
  //cache_set('xbbcode_tags_' . $format, serialize($cache[$format]), 'cache', CACHE_PERMANENT);
}

/**
 * Format multiple descriptions of a tag in several paragraphs.
 * Optionally make some of them invisible.
 *
 * @param $tag
 *   Name of the tag
 * @param $modules
 *   Modules that implement this tag.
 * @param $selected
 *   Module that should be visible.
 *
 * @return
 *   Text.
 */
function _xbbcode_build_descriptions($name, $modules, $selected = NULL) {
  if (count($modules) == 1) {
    $selected = current($modules);
  }

  $descriptions = "<strong>[$name]</strong>\n";
  foreach ($modules as $module) {
    $class = $selected && ($module == $selected) ? 'visible' : 'invisible';
    $tag = module_invoke($module, 'xbbcode', 'info', $name);
    if (is_object($tag) && isset($tag->description)) {
      $descriptions .= "<p class='tag-$name module-$module xbbcode-description-$class'>$tag->description</p>\n";
    }
  }
  return $descriptions;
}

/**
 * Parse a multi-argument string.
 *
 * @param $args string
 *
 * @return an associative array.
 */
function _xbbcode_parse_args($arg_line) {
  if (!$arg_line) {
    return array();
  }
  $arg_line = trim(stripslashes($arg_line));

  if ($arg_line[0] == '=') {
    // The whole string is one argument.
    return array('option' => substr($arg_line, 1));
  }

  $otc = _xbbcode_one_time_code($arg_line);

  // First, if there are quotes anywhere, strip quotes and escape spaces inside.
  $arg_line = preg_replace('/"(.*?)"|\'(.*?)\'/e', 'str_replace(" ", "[space-' . $otc . ']", "$1$2")', $arg_line);

  // Fow we have a simple space-separated text.
  $arguments = split(" +", $arg_line);

  foreach ($arguments as $assignment) {
    if (preg_match('/^([a-z]+)=(.*)$/', $assignment, $match)) {
      $parsed[$match[1]] = str_replace("[space-$otc]", ' ', $match[2]);
    }
  }
  return $parsed;
}

/**
 * Strip all generated identifiers from this text, reverting it to what it was.
 *
 * @param $text containing things like [tag-0-29df84][/tag-0-29df84]
 *
 * @return Text containing only [tag][/tag]
 */
function _xbbcode_revert_tags($text, $code) {
  return preg_replace("/\[(.+?)-[0-9]+-$code\]/i", '[$1]', $text);
}

/**
 * Generate a code that can be used to insert internal identifiers into a text
 * without collisions.
 *
 * @param $text
 *
 * @return
 */
function _xbbcode_unique_code($text) {
  // Find an internal delimiter that does not occur in the text.
  do {
    $code = md5(rand());
  }
  // If this next line ever evaluates to true, congratulations.
  while (preg_match("/$code/", $text) && watchdog('xbbcode', 'The universe is broken: %code occurred in %text.', array('%code' => $code, '%text' => $text)));
  return $code;
}

