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

/**
 * Statically cached function for loading and displaying tags.
 * Do not use this; use xbbcode_custom_tag_names() and
 * xbbcode_custom_tag_load() instead.
 * @param $name
 * @return unknown_type
 */
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
      'dynamic' => (int)!empty($tag->options['dynamic']),
      'selfclosing' => (int)!empty($tag->options['selfclosing']),
      'multiarg' => (int)!empty($tag->options['multiarg']),
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
    while ($handler = $query->fetch()) {
      $names[$handler->name] = $handler->name;
    }
  }
  return $names;
}

/**
 * Load all handlers for a specific format.
 * @param $format_id
 *   Optional text format ID.
 * @return
 *   A numerically indexed array of handler objects.
 */
function xbbcode_handlers_load($format_id = 0, $disabled = FALSE) {
  $specific = xbbcode_format_is_specific($format_id);
  $cache = &drupal_static(__FUNCTION__, array());
  if (!isset($cache[$format_id]) || $disabled) {
    $query = db_select('xbbcode_handler', 'handler')
      ->fields('handler')
      ->condition('format', $specific ? $format_id : 0);
    if (!$disabled) {
      $query->condition('enabled', 1);
    }
    $all = $query->execute()->fetchAll();
    foreach ($all as $handler) {
      $handlers[$handler->name] = $handler;
    }
    if ($disabled) {
      return $handlers;
    }
    $cache[$format_id] = $handlers;
  }

  return $cache[$format_id];
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

function xbbcode_handler_update($format, $name, $module) {
  return db_update('xbbcode_handler')
    ->fields(array('module' => $module))
    ->condition('format', $format)
    ->condition('name', $name)
    ->execute();
}

function xbbcode_handlers_delete($tags) {
  return db_delete('xbbcode_handler')
    ->condition('name', $tags, 'IN')
    ->execute();
}

/**
 * List all text formats that use xbbcode, grouped into categories according to
 * whether or not they use format-specific settings.
 * @param $type
 *   Optionally, 'specific' or 'global'.
 * @return
 *   If an argument was passed, return an array of matching formats.
 *   Otherwise, an array with two keys:
 *     - 'global': All text formats that depend on the global settings.
 *     - 'specific': All text formats that use specific settings.
 */
function xbbcode_formats($type = NULL) {
  $formats = &drupal_static(__FUNCTION__, array());
  if (empty($formats)) {
    $formats = array('specific' => array(), 'global' => array());
    $query = db_query("SELECT DISTINCT format FROM {xbbcode_handler} WHERE format > 0");
    while ($col = $query->fetchCol()) {
      $specific[$col[0]] = TRUE;
    }
    foreach (filter_formats() as $format) {
      $filters = filter_list_format($format->format);
      if (in_array('xbbcode', array_keys($filters))) {
        $formats[isset($specific[$format->format]) ? 'specific' : 'global'][$format->format] = $format->name;
      }
    }
  }
  return isset($formats[$type]) ? $formats[$type] : $formats;
}

/**
 * Determine whether a text format has non-global settings.
 * @param $format_id
 *   A format ID.
 * @return
 *   TRUE if the format has specific settings. 
 */
function xbbcode_format_is_specific($format_id) {
  foreach (xbbcode_formats() as $type => $formats) {
    foreach ($formats as $id => $name) {
      if ($id == $format_id) return $type == 'specific';
    }
  }
}