<?php

namespace Drupal\content_change_digest\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

// use Drupal\views\Plugin\views\filter\Equality;
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

  public $no_operator = TRUE;


  /**
   * {@inheritdoc}
   */
  // public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
//     parent::init($view, $display, $options);
//     $this->valueTitle = t('Filter by revision not being same as initial content creation revision.');
// //    $this->definition['options callback'] = [$this, 'generateOptions'];
//     $this->currentDisplay = $view->current_display;
//   }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   An array of states and their ids.
   */
//  public function generateOptions() {
//    $states = workflow_get_workflow_state_names();
//    // You can add your custom code here to add custom labels for state transitions.
//    return $states;
//  }

  /**
   * Helper function that builds the query.
   */
  public function query() {
     \Drupal::logger('content_change_digest')->notice('in the query method for the plugin');

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
