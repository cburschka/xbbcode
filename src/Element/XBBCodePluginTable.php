<?php

namespace Drupal\xbbcode\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Table;

/**
 * Provides an xbbcode table element.
 *
 * @RenderElement("xbbcode_plugin_table")
 */
class XBBCodePluginTable extends Table {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#theme'] = 'table';

    array_unshift($info['#pre_render'], [$this, 'tablePreRender']);
    return $info;
  }

  /**
   * Prepare the plugin table for rendering.
   *
   * @param array $elements
   *   The table element.
   *
   * @return array
   *   The processed elements.
   */
  public static function tablePreRender(array $elements) {
    // Determine the colspan to use for region rows, by checking the number of
    // columns in the headers.
    $columns_count = 0;
    foreach ((array) $elements['#header'] as $header) {
      $columns_count += (is_array($header) && isset($header['colspan']) ? $header['colspan'] : 1);
    }

    $i = 0;

    foreach (Element::children($elements) as $region) {
      $rows = $elements[$region];
      unset($elements[$region]);

      if (isset($rows['#title'])) {
        $elements[$i++] = [
          [
            '#attributes' => ['no_striping' => TRUE],
            '#wrapper_attributes' => ['colspan' => $columns_count],
            'title' => [
              '#markup' => $rows['#title'],
            ],
          ],
        ];
      }

      foreach (Element::children($rows) as $row) {
        $cells = $rows[$row];
        foreach (Element::children($cells) as $cell) {
          $field = $cells[$cell];
          if (isset($field['#type']) && $field['#type'] === 'value') {
            unset($cells[$cell]);
          }
        }
        $elements[$i++] = $cells;
      }
    }

    return $elements;
  }

}
