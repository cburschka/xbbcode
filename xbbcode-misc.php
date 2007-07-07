<?php

/* various functions we only need for ourselves. */

function _xbbcode_get_module_tags() {
  $all = array();
  $modules = module_implements('xbbcode');
  foreach ($modules as $module) {
    $tags = module_invoke($module, 'xbbcode', 'list');
    if ($tags) {
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
  /* check for format-specific settings */
  if ($format != -1) {
    $use_format = db_result(db_query("SELECT COUNT(*) FROM {xbbcode_handlers} WHERE format=%d AND enabled", $format));
  }
  $use_format = $use_format ? $format : -1;
  $res = db_query("SELECT name, module, weight FROM {xbbcode_handlers} WHERE format IN (-1, %d) AND enabled ORDER BY format", $use_format);
  while ($row = db_fetch_array($res)) {
    $handlers[$row['name']] = $row;
  }
  foreach ($handlers as $name => $handler)
  {
    $tag = module_invoke($handler['module'], 'xbbcode', 'info', $name);
    $tag['module'] = $handler['module'];
    $tag['weight'] = $handler['weight'];
    $tags[$name] = $tag;
  }
  return $tags;
}

function _xbbcode_list_formats()
{
  $res = db_query("select a.format,name from {filters} a natural join {filter_formats} b where module='xbbcode'");
  $formats = array();
  while ($row = db_fetch_array($res)) {
    $formats[$row['format']] = $row['name'];
  }
  return $formats;
}  

function _xbbcode_one_time_code($text) 
{ // find an internal delimiter that's guaranteed not to collide with our given text.
  do $code=md5(rand(1000,9999));
  while (preg_match("/$code/",$text));
  return $code;
}

function _xbbcode_parse_args($args) {
  $args=str_replace(array("\\\"",'\\\''),array("\"",'\''),$args);
  if (!$args) return; // return if they don't exist.
  if ($args[0]=='=') return substr($args,1); // the whole string is one argument
  else $args=substr($args,1);
  $otc=_xbbcode_one_time_code($args);
  $args=preg_replace('/"([^"]*)"|\'([^\']*)\'/e','str_replace(\' \',"&nbsp;","$1")',$args);
  $pattern='/([a-z]+)=([^ ]+) */i';
  $replace='$1 = $2'."[$otc]";
  $args = explode("[$otc]",preg_replace($pattern,$replace,$args));
  foreach ($args as $line) {
    if (!preg_match('/^([a-z]+) = (.*)$/',$line,$match)) continue;
    $parsed[$match[1]] = $match[2];
  }
  return $parsed;
}

function _xbbcode_filter_from_format($format = -1) {
  $tags = _xbbcode_get_tags($format);
  return new XBBCodeFilter($tags, $format);
}

function _xbbcode_revert_tags($text) {
  return preg_replace('/\[([^\]]+)-[0-9]+-\]/i', '[$1]', $text);
}


