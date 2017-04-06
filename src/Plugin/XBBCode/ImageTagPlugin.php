<?php

namespace Drupal\xbbcode\Plugin\XBBCode;

use Drupal;
use Drupal\Core\Url;
use Drupal\xbbcode\Parser\ElementInterface;
use Drupal\xbbcode\Plugin\TagPluginBase;

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
class ImageTagPlugin extends TagPluginBase {

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
  public function process(ElementInterface $tag) {
    $style = [];
    if ($width = $tag->getAttribute('width')) {
      $style[] = "width:{$width}px";
    }
    if ($height = $tag->getAttribute('height')) {
      $style[] = "height:{$height}px";
    }

    $element = [
      '#type' => 'inline_template',
      '#template' => '<img src="{{ tag.content }}" alt="{{ tag.content }}" style="{{ style }}" />',
      '#context' => [
        'tag' => $tag,
        'style' => implode(';', $style),
      ],
    ];

    return Drupal::service('renderer')->render($element);
  }

}
