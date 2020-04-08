<?php

namespace Drupal\custom_module\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

// use Drupal\views\Plugin\views\filter\Equality;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filters by ContentDigestChange revision being not the same as the content created revision.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("content_digest_change_views_filter")
 */
class ContentDigestChangeViewsFilter extends FilterPluginBase {
  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Filter by revision not being same as initial content creation revision.');
//    $this->definition['options callback'] = [$this, 'generateOptions'];
    $this->currentDisplay = $view->current_display;
  }

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
     \Drupal::logger('asu_content_change_digest')->notice('in the query method for the plugin');

    if (!empty($this->value)) {
      $configuration = [
        'table' => 'node_field_revision',
        'field' => 'vid',
        'left_table' => 'node',
        'left_field' => 'vid',
        'operator' => '!=',
      ];
      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->query->addRelationship('node_field_revision', $join, 'node');
    //  $this->query->addWhere('AND', 'node.field_phase_value', $this->value, 'IN');
    }
  }

}
