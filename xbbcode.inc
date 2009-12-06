<?php
// $Id$

/**
 * @file
 * General library of internal functions only called by this module.
 */


function _xbbcode_build_handlers() {
  // This function does not use caching because it is used only in the settings page.
  $all = array();
  
  foreach (module_implements('xbbcode') as $module) {
    $tags = module_invoke($module, 'xbbcode', 'list'); 
    if (is_array($tags)) {
      foreach ($tags as $i => $tag) {
        if (!preg_match('/^[a-z0-9]+$/i', $tag)) unset($tags[$i]); // ignore invalid names
        else $tags[$i] = array('name' => $tag, 'module' => $module);
      }
      $all = array_merge($all, $tags);
    }
  }

  return $all;
}

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
    $tag = (object) (array(
      'name' => $handler->name,
      'module' => $handler->module,
      'weight' => $handler->weight,
    ) + module_invoke($handler->module, 'xbbcode', 'info', $handler->name) + $defaults);
    $tags[$handler->name] = $tag;
  }
  return $tags;
  //cache_set('xbbcode_tags_' . $format, serialize($cache[$format]), 'cache', CACHE_PERMANENT);
}

function _xbbcode_one_time_code($text) { 
  // find an internal delimiter that's guaranteed not to collide with our given text.
  do $code = md5(rand(1000, 9999));
  while (preg_match("/$code/", $text));
  return $code;
}
  
function _xbbcode_parse_args($args) {
  $args = str_replace(array("\\\"", '\\\''), array("\"",'\''), $args);
  if (!$args) return;                           // return if they don't exist.
 
  if ($args[0] == '=') return substr($args, 1); // the whole string is one argument
  else $args = substr($args, 1);                // otherwise, remove leading space
  
  $otc = _xbbcode_one_time_code($args);         // generate our non-colliding one-time-code.
  
  // first, if there are quoted strings anywhere, strip quotes and escape spaces inside.
  $args = preg_replace('/"([^"]*)"|\'([^\']*)\'/e', 'str_replace(\' \',"[space-'. $otc. ']","$1$2")', $args);
  
  // now we have a simple space-separated text.
  $args = split(" +", $args);
  
  foreach ($args as $assignment) {
    if (!preg_match('/^([a-z]+)=(.*)$/', $assignment, $match)) continue;
    $parsed[$match[1]] = str_replace("[space-$otc]", ' ', $match[2]);
  }
  return $parsed;
}

/**
 * Create or load a parser object.
 * @param unknown_type $format_id
 * @return unknown_type
 */
function xbbcode_get_filter($format_id) {
  $filters = &drupal_static(__FUNCTION__, array());
  if (!isset($filters[$format_id])) {
    $tags = _xbbcode_build_tags($format_id);
    $filters[$format_id] = new XBBCodeFilter($tags, $format_id);
  }
  return $filters[$format_id];
}

function _xbbcode_revert_tags($text) {
  return preg_replace('/\[([^\]]+)-[0-9]+-\]/i', '[$1]', $text);
}

