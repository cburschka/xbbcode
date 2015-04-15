<?php

/**
 * @file
 * Data interface for creating, reading, updating and deleting records.
 */

/**
 * Check if a custom tag with a certain name exists.
 *
 * @param $name
 *   The tag name for which to search.
 *
 * @return
 *   1 if it exists, 0 if it does not.
 */
function xbbcode_custom_tag_exists($name) {
  $query = db_select('xbbcode_custom_tag', 'tag')->fields('tag')->condition('name', $name)->countQuery()->execute()->fetchCol(0);
  return $query[0];
}

/**
 * Load a custom tag.
 *
 * @param $name
 *   (optional) if passed, return the custom tag with this name.
 *   Otherwise, return all.
 *
 * @return
 *   Either a data object, or an array of objects indexed by name.
 */
function xbbcode_custom_tag_load($name = NULL) {
  $tags = &drupal_static(__FUNCTION__, array());
  if (empty($tags)) {
    $rows = db_select('xbbcode_custom_tag', 'tag')->fields('tag')->execute()->fetchAll();
    foreach ($rows as $row) {
      $row->options = unserialize($row->options);
      $tags[$row->name] = $row;
    }
  }
  if ($name) {
    return isset($tags[$name]) ? $tags[$name] : NULL;
  }
  else {
    return $tags;
  }
}

/**
 * Delete custom tags from the database.
 *
 * @param $tags
 *   An array of the names of all tags to be deleted.
 */
function xbbcode_custom_tag_delete($tags) {
  db_delete('xbbcode_custom_tag')->condition('name', $tags, 'IN')->execute();
}

/**
 * Save a custom tag.
 *
 * @param $tag
 *   A full tag object, with the options in a single array property.
 *
 * @return
 *   The result of the merge query.
 */
function xbbcode_custom_tag_save($tag) {
  $query = db_merge('xbbcode_custom_tag')->fields(array(
      'markup' => "$tag->markup",
      'description' => "$tag->description",
      'sample' => "$tag->sample",
      'options' => serialize($tag->options),
    ))->key(array('name' => "$tag->name"))->execute();
  return $query;
}
