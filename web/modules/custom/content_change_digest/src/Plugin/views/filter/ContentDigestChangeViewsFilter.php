<?php

namespace Drupal\content_change_digest\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filters by ContentDigestChange revision being not the same as the content created revision.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("content_change_digest_content_digest_change_views_filter")
 */
class ContentDigestChangeViewsFilter extends FilterPluginBase {

  /**
   * Helper function that builds the query.
   */
  public function query() {
    $configuration = [
      'table' => 'node_field_data',
      'field' => 'nid',
      'left_table' => 'node_field_revision',
      'left_field' => 'nid',
      'operator' => '=',
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('node_field_data_ne', $join, 'node_field_revision');
    $this->query->addWhereExpression($this->options['group'], 'node_field_data_ne.changed != node_field_revision.changed');
  }

}
