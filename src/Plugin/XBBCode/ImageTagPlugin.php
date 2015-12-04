<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Plugin\XBBCode\TableTagPlugin.
 */

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal;
use Drupal\Core\Url;
use Drupal\xbbcode\ElementInterface;
use Drupal\xbbcode\Plugin\TagPlugin;

/**
 * Inserts an image.
 *
 * @XBBCodeTag(
 *   id = "image",
 *   label = @Translation("Image"),
 *   description = @Translation("Inserts an image."),
 *   name = "img",
 * )
 */
class ImageTagPlugin extends TagPlugin {
  private $renderer;

  /**
   * Get the rendering service.
   */
  private function renderer() {
    if (!$this->renderer) {
      $this->renderer = Drupal::service('renderer');
    }
    return $this->renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSample() {
    return $this->t('[{{ name }} width=57 height=66]@url[/{{ name }}]', [
      '@url' => Url::fromUri('base:core/themes/bartik/logo.svg')->toString(),
    ]);
  }
  /**
   * {@inheritdoc}
   */
  public function process(ElementInterface $tag) {
    $style = [];
    if ($width = $tag->attr('width')) {
      $style[] = "width:{$width}px";
    }
    if ($height = $tag->attr('height')) {
      $style[] = "height:{$height}px";
    }

    $element = [
      '#type' => 'inline_template',
      '#template' => '<img src="{{ content }}" alt="{{ content }}" style="{{ style }}" />',
      '#context' => [
        'content' => $tag->content(),
        'style' => implode(';', $style),
      ],
    ];

    return $this->renderer()->render($element);
  }

}
