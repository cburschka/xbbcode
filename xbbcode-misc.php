<?php

  /* various functions we only need for ourselves. */
  
  function _xbbcode_get_module_tags() {
    $all = array();
    $modules = module_implements('xbbcode');
    
    foreach ($modules as $module) {
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
  
  function _xbbcode_get_tags($format = -1) {
    static $cache;
    if (!$cache[$format] && $data = cache_get('xbbcode_tags_'. $format)) {
      $cache[$format] = unserialize($data->data);
      return $cache[$format];  
    }
  
    /* check for format-specific settings */
    if ($format != -1) {
      $use_format = db_result(db_query("SELECT COUNT(*) FROM {xbbcode_handlers} WHERE format=%d AND enabled", $format));
    }
    $use_format = $use_format ? $format : -1;
    
    $res = db_query("SELECT name, module, weight FROM {xbbcode_handlers} WHERE format IN (-1, %d) AND enabled ORDER BY format,name ", $use_format);
    $handlers = array();
    while ($row = db_fetch_array($res)) {
      $handlers[$row['name']] = $row;
    }
    
    $cache[$format] = array();
    foreach ($handlers as $name => $handler) {
      $tag = module_invoke($handler['module'], 'xbbcode', 'info', $name);
      $tag['module'] = $handler['module'];
      $tag['weight'] = $handler['weight'];
      $cache[$format][$name] = $tag;
    }
  
    cache_set('xbbcode_tags_'. $format, 'cache', serialize($cache[$format]), time() + 86400);
    return $tags;
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
      if (!preg_match('/^([a-z]+)=(.*)$/', $line, $match)) continue;
      $parsed[$match[1]] = str_replace("[space-$otc]", ' ', $match[2]);
    }
    return $parsed;
  }
  
  function xbbcode_get_filter($format = -1) {
    static $filters;
    if (!$filters[$format]) {
      $tags = _xbbcode_get_tags($format);
      $filters[$format] = new XBBCodeFilter($tags, $format);
    }
    return $filters[$format];
  }
  
  function _xbbcode_revert_tags($text) {
    return preg_replace('/\[([^\]]+)-[0-9]+-\]/i', '[$1]', $text);
  }
  
  function xbbcode_get_custom_tag($tag = NULL) {
    static $tags;
    if (!$tags) {
      db_query("SELECT name, sample, description, selfclosing, dynamic, multiarg, replacewith FROM {xbbcode_custom_tags}");
      while ($row = db_fetch_array($res)) $tags[$row['name']] = $row;
    }
    if ($tag) return $tags[$tag];
    else return array_keys($tags);
  }
  
