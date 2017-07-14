<?php

namespace Drupal\xbbcode_standard\Plugin\XBBCode;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\xbbcode\Parser\Tree\TagElementInterface;
use Drupal\xbbcode\Plugin\RenderTagPlugin;

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
class ImageTagPlugin extends RenderTagPlugin {

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getDefaultSample() {
    return $this->t('[{{ name }} width=57 height=66]@url[/{{ name }}]', [
      '@url' => Url::fromUri('base:core/themes/bartik/logo.svg')->toString(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(TagElementInterface $tag) {
    $style = [];
    $dimensions = explode('x', $tag->getOption());
    if (count($dimensions) === 2) {
      list($width, $height) = $dimensions;
    }
    else {
      $width = (string) $tag->getAttribute('width');
      $height = (string) $tag->getAttribute('height');
    }
    if (is_numeric($width)) {
      $style[] = "width:{$width}px";
    }
    if (is_numeric($height)) {
      $style[] = "height:{$height}px";
    }

    $src = Html::decodeEntities($tag->getContent());

    return [
      '#type' => 'inline_template',
      '#template' => '<img src="{{ src }}" alt="{{ src }}" style="{{ style }};" />',
      '#context' => [
        'tag' => $tag,
        'style' => implode(';', $style),
        'src' => $src,
      ],
    ];
  }

}
