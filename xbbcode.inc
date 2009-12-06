<?php

  /* various functions we only need for ourselves. */
  
  function _xbbcode_get_handlers() {
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
  
  function _xbbcode_get_tags($format = 0, $settings = FALSE) {
    return NULL;
    static $defaults = array(
      'replacewith' => '', 
      'description' => '', 
      'sample' => '', 
      'multiarg' => FALSE, 
      'dynamic' => FALSE, 
      'selfclosing' => FALSE,
    );
    static $cache;
    
    if (!$settings && !isset($cache[$format]) && $data = cache_get('xbbcode_tags_'. $format)) {
      $cache[$format] = unserialize($data->data);
      return $cache[$format];  
    }
  
    /* check for format-specific settings */
    $res = db_select('xbbcode_handlers', 'handler')
    ->fields('handler')
    ->condition('format', array(0, $format), 'IN')
    ->execute();

    while ($row = $res->fetch()) {
      $handlers[$row['name']] = $row;
    }

    foreach ($handlers as $tag => $handler) {
      if (!$handler['enabled']) {
        unset($handlers[$tag]);
      }
    }

    $cache[$format] = array();
    debug("Point 1");
    foreach ($handlers as $name => $handler) {
      $tag = (array) $handler;
      $tag += (array) module_invoke($handler['module'], 'xbbcode', 'info', $name);
      $tag += (array) $defaults;
      $cache[$format][$name] = $tag;
    }
    debug("Point 2");
    if ($settings) return $cache[$format];
    debug("Point 3");
    //cache_set('xbbcode_tags_'. $format, serialize($cache[$format]), 'cache', CACHE_PERMANENT);
    return $cache[$format];
  }
  
  function _xbbcode_list_formats() {
    $res = db_query(
      "SELECT {filters}.format, name FROM {filters} NATURAL JOIN {filter_formats} ".
      "WHERE module = 'xbbcode'"
    );
    $formats = array();
    while ($row = db_fetch_array($res)) {
      $formats[$row['format']] = $row['name'];
    }
    return $formats;
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
  
  function xbbcode_get_filter($filter, $format) {
    debug($filter);
    debug($format);
    return FALSE;
    static $filters;
    if (!isset($filters[$format])) {
      $tags = _xbbcode_get_tags($format);
      $filters[$format] = new XBBCodeFilter($tags, $format);
    }
    return $filters[$format];
  }
  
  function _xbbcode_revert_tags($text) {
    return preg_replace('/\[([^\]]+)-[0-9]+-\]/i', '[$1]', $text);
  }
  
