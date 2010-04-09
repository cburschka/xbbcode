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
  $module_names = xbbcode_module_names();
  $handlers = array();

  foreach ($module_names as $module => $name) {
    $tags = module_invoke($module, 'xbbcode_info');
    if (is_array($tags)) {
      foreach (array_keys($tags) as $tag) {
        $handlers[$tag]['modules'][$module] = $name;
        $handlers[$tag]['info'][$module] = $tags[$tag];
      }
    }
  }
  ksort($handlers);
  return $handlers;
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
  // First, check if the tags are in cache.
  if ($cache = cache_get('xbbcode_tags:' . $format_id)) {
    $tags = $cache->data;
  }
  else {
    // Load the preferred handlers for this text format.
    $handlers = xbbcode_handlers_load($format_id);
    foreach ($handlers as $handler) {
      // Build a list of what modules are used, and what to get from each.
      $providers[$handler->module][$handler->name] = $handler->name;
    }
    
    $default = array(
      'selfclosing' => FALSE, 
      'nocode' => FALSE,
      'plain' => FALSE,
      'multiarg' => FALSE
    );
    foreach ($providers as $module => $provides) {
      $info = module_invoke($module, 'xbbcode_info');
      foreach ($provides as $tag) {
        if (isset($info[$tag])) {
          $tags[$tag] = (object) array(
            'name' => $tag,
            'description' => $info[$tag]['description'],
            'sample'      => $info[$tag]['sample'],
            'options'     => (object) ((array)$info[$tag]['options'] + $default),
            'markup'      => isset($info[$tag]['markup']) ? $info[$tag]['markup'] : NULL,
            'callback'    => !isset($info[$tag]['markup']) ? $info[$tag]['callback'] : NULL,
          );
        }
      }
    }
    cache_set('xbbcode_tags:' . $format_id, $tags, 'cache', CACHE_PERMANENT);
  }

  return $tags;
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
    $tags = module_invoke($module, 'xbbcode_info');
    if (is_array($tags[$name]) && isset($tags[$name]['description'])) {
      $descriptions .= "<p class='tag-$name module-$module xbbcode-description-$class'>" . $tags[$name]['description'] . "</p>\n";
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
function _xbbcode_parse_args($arg_line, $multi = TRUE) {
  if (!$arg_line) {
    return array();
  }
  $arg_line = trim(stripslashes($arg_line));

  if ($arg_line[0] == '=') {
    // The whole string is one argument.
    return array('option' => substr($arg_line, 1));
  }
  else if (!$multi) {
    return array();
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

