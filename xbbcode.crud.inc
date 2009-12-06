<?php

function xbbcode_custom_tag_exists($name) {
  $query = db_select('xbbcode_custom_tag', 'tag')
    ->fields('tag')
    ->condition('name', $name)
    ->countQuery()
    ->execute()
    ->fetchCol(0);
   return $query[0];
}

function xbbcode_custom_tag_load($name) {
  return _xbbcode_custom_tag_load($name);
}

function xbbcode_custom_tag_names() {
  return _xbbcode_custom_tag_load();
}

function _xbbcode_custom_tag_load($name = NULL) {
  $tags = &drupal_static(__FUNCTION__, array());
  if (empty($tags)) {
    $rows = db_select('xbbcode_custom_tag', 'tag')->fields('tag')
    ->execute()
    ->fetchAll();
    foreach ($rows as $row) {
      $tags[$row->name] = $row;
    }
  }
  if ($name) {
    return isset($tags[$name]) ? $tags[$name] : NULL;
  }
  else {
    return array_keys($tags);
  }
}

function xbbcode_custom_tag_delete($tags) {
  db_delete('xbbcode_custom_tag')
    ->condition('name', $tags, 'IN')
    ->execute();
}

function xbbcode_custom_tag_save($tag) {
  $query = db_merge('xbbcode_custom_tag')
    ->fields(array(
      'replacewith' => "$tag->replacewith",
      'description' => "$tag->description",
      'sample' => "$tag->sample",
      'dynamic' => (int)$tag->options['dynamic'],
      'selfclosing' => (int)$tag->options['selfclosing'],
      'multiarg' => (int)$tag->options['multiarg'],
    ))
    ->key(array('name' => "$tag->name"))
    ->execute();
  return $query;
}

function xbbcode_handler_names() {
  $names = &drupal_static(__FUNCTION__, array());
  if (empty($names)) {
    $query = db_query("SELECT DISTINCT name FROM {xbbcode_handler} ORDER BY name");
    while ($col = $query->fetchCol()) {
      $names[$names] = $col;
    }
  }
  return $names;
}

function xbbcode_formats() {
  $formats = &drupal_static(__FUNCTION__, array());
  if (empty($formats)) {
    $query = db_query("SELECT DISTINCT format FROM {xbbcode_handler}");
    while ($col = $query->fetchCol()) {
      $specific[$col] = TRUE;
    }
    foreach (filter_formats() as $format) {
      $formats[isset($specific[$format->format]) ? 'specific' : 'global'][$format->format] = $format->name;
    }
  }
  return $formats;
}