<?php

namespace Drupal\asu_landing_site\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class Thumbnail.
 *
 * @ViewsField("keep_thumbnail")
 */
class Thumbnail extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $thumb = $this->getValue($values);
    if ($thumb) {
      if (!str_starts_with($thumb, 'http')) {
        $thumb = "https://keep.lib.asu.edu" . $thumb;
      }
      return [
        '#theme' => 'image',
        '#uri' => $thumb,
        '#alt' => $this->t('Item Thumbnail'),
      ];
    }
  }

}
