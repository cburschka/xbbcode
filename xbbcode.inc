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
function _xbbcode_build_filter($filter, $format) {
  $filters = &drupal_static(__FUNCTION__, array());
  if (!isset($filters[$format->format])) {
    $tags = _xbbcode_build_tags($format->format);
    $filters[$format->format] = new XBBCodeFilter($tags, $filter, $format);
  }
  return $filters[$format->format];
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
  if ($cache = cache_get("xbbcode_tags:$format_id")) {
    $tags = $cache->data;
  }
  else {
    // Load the database interface.
    module_load_include('inc', 'xbbcode', 'xbbcode.crud');
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
      'multiarg' => FALSE,
    );
    foreach ($providers as $module => $provides) {
      $info = module_invoke($module, 'xbbcode_info');
      foreach ($provides as $tag) {
        if (isset($info[$tag])) {
          if (!isset($info[$tag]['options'])) {
            $info[$tag]['options'] = array();
          }
          $tags[$tag] = (object)array(
            'name'        => $tag,
            'description' => $info[$tag]['description'],
            'sample'      => $info[$tag]['sample'],
            'options'     => (object) ($info[$tag]['options'] + $default),
            'markup'      => isset($info[$tag]['markup']) ? $info[$tag]['markup'] : NULL,
            'callback'    => !isset($info[$tag]['markup']) ? $info[$tag]['callback'] : NULL,
          );
        }
      }
    }
    cache_set("xbbcode_tags:$format_id", $tags, 'cache', CACHE_PERMANENT);
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
function _xbbcode_build_descriptions($name, $handlers, $selected = NULL) {
  if (count($handlers) == 1) {
    $selected = key($handlers);
  }

  $descriptions = "<strong>[$name]</strong>\n";
  foreach ($handlers as $module => $info) {
    $class = $selected && ($module == $selected) ? 'visible' : 'invisible';
    if (isset($info['description'])) {
      $descriptions .= "<p class='tag-$name module-$module xbbcode-description-$class'>{$info['description']}</p>\n";
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
  $arg_line = trim(stripslashes($arg_line));
  if (!$arg_line) {
    return array();
  }

  if ($arg_line[0] == '=') {
    // The whole string is one argument.
    return array('option' => substr($arg_line, 1));
  }

  // First, if there are quotes anywhere, strip quotes and escape spaces inside.
  $otc = md5(REQUEST_TIME . mt_rand());
  $arg_line = preg_replace('/"(.*?)"|\'(.*?)\'/e', 'str_replace(" ", "[space-' . $otc . ']", "$1$2")', $arg_line);

  // Now we have a simple space-separated text.
  $arguments = preg_split('/ +/', $arg_line);
  foreach ($arguments as $assignment) {
    if (preg_match('/^([a-z]+)=(.*)$/', $assignment, $match)) {
      $parsed[$match[1]] = str_replace("[space-$otc]", ' ', $match[2]);
    }
  }
  return $parsed;
}

