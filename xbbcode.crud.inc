<?php
// $Id$

/**
 * @file
 * Data interface for creating, reading, updating and deleting records. 
 */

/**
 * Check if a custom tag with a certain name exists.
 * @param $name
 *   The tag name for which to search.
 * @return 
 *   1 if it exists, 0 if it does not.
 */
function xbbcode_custom_tag_exists($name) {
  $query = db_select('xbbcode_custom_tag', 'tag')
    ->fields('tag')
    ->condition('name', $name)
    ->countQuery()
    ->execute()
    ->fetchCol(0);
   return $query[0];
}

/**
 * Load a custom tag.
 * @param $name
 *   The name of the tag.
 * @return
 *   A full tag object.
 */
function xbbcode_custom_tag_load($name) {
  return _xbbcode_custom_tag_load($name);
}

/**
 * Generate a list of all custom tags.
 * @return
 *   A list of names.
 */
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

/**
 * Delete custom tags from the database.
 * @param $tags
 *   An array of the names of all tags to be deleted. 
 */
function xbbcode_custom_tag_delete($tags) {
  db_delete('xbbcode_custom_tag')
    ->condition('name', $tags, 'IN')
    ->execute();
}

/**
 * Save a custom tag.
 * @param $tag
 *   A full tag object, with the options in a single array property.
 * @return 
 *   The result of the merge query.
 */
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

/**
 * List the names of all handlers, that is, all tags (including non-custom)
 * that are provided by modules.
 * @return
 *   An array of names.
 */
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

/**
 * Create or change a handler, assigning it to a new module.
 * @param $handler
 *   A full handler object with a module, enabled, name and weight property.
 * @param $format
 *   An optional text format ID.
 */
function xbbcode_handler_save($handler, $format = 0) {
  $query = db_merge('xbbcode_handler')
    ->fields(array(
      'module' => "$handler->module",
      'enabled' => (int)$handler->enabled,
      'weight' => (int)$handler->weight,
      'format' => $format,
    ))
    ->key(array('name' => "$handler->name"))
    ->execute();
  return $query;
}

/**
 * List all text formats, specifying which of them use specific settings.
 * @return
 *   An array with two keys:
 *     - 'global': All text formats that depend on the global settings.
 *     - 'specific': All text formats that use specific settings.
 */
function xbbcode_formats() {
  $formats = &drupal_static(__FUNCTION__, array());
  if (empty($formats)) {
    $query = db_query("SELECT DISTINCT format FROM {xbbcode_handler}");
    while ($col = $query->fetchCol()) {
      $specific[$col[0]] = TRUE;
    }
    foreach (filter_formats() as $format) {
      $formats[isset($specific[$format->format]) ? 'specific' : 'global'][$format->format] = $format->name;
    }
  }
  return $formats;
}