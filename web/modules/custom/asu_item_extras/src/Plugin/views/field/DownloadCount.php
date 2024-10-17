<?php

namespace Drupal\asu_item_extras\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for download counts.
 *
 * @ingroup views_field_handlers
 * @ViewsField("download_count")
 */
class DownloadCount extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    $count = array_sum(\Drupal::service('asu_item_analytics.query')->entityMonthly($entity) ?? []);
    if ($count > 0) {
      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['asu-item-analytics-popover']],
        'popover' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'tabindex' => '0',
            'role' => 'button',
            'data-bs-placement' => 'bottom',
            'data-bs-toggle' => 'popover',
            'data-bs-trigger' => 'focus',
            'title' => 'Information',
            'data-bs-content' => 'The repository began collecting download statistics in 2021.',
          ],
          'icon' => [
            '#type' => 'html_tag',
            '#tag' => 'i',
            '#attributes' => ['class' => 'fas fa-info-circle'],
          ],
        ],
        'count' => [
          '#type' => 'plain_text',
          '#plain_text' => 'Download count: ' . number_format($count),
        ],
          // Attach the library to the block.
        '#attached' => ['library' => ['asu_item_analytics/item_block']],
        '#cache' => ['contexts' => ['url.path']],
      ];
    }
    // No data found. Return nothing.
    return [];
  }

}
