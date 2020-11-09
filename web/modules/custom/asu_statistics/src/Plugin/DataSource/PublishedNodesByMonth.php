<?php

namespace Drupal\asu_statistics\Plugin\DataSource;

use Drupal\islandora_repository_reports\Plugin\DataSource\IslandoraRepositoryReportsDataSourceInterface;

/**
 * Data source plugin that gets nodes created by month.
 */
class PublishedNodesByMonth implements IslandoraRepositoryReportsDataSourceInterface {

  /**
   * An array of arrays corresponding to CSV records.
   *
   * @var string
   */
  public $csvData;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Nodes by month created');
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEntity() {
    return 'node';
  }

  /**
   * {@inheritdoc}
   */
  public function getChartType() {
    return 'bar';
  }

  /**
   * {@inheritdoc}
   */
  public function getChartTitle($total) {
    return t('@total nodes broken down by month created.', ['@total' => $total]);
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    $utilities = \Drupal::service('asu_statistics.utilities');
    $collection_id = $utilities->getFormElementDefault('asu_statistics_nodes_by_month_collection', '');
    $collection_id = trim($collection_id);

    $database = \Drupal::database();
    $query = \Drupal::database()->select('node_field_data', 'node_field_data');
    $query->join('node__field_member_of', 'node__field_member_of',
        'node__field_member_of.entity_id = node_field_data.nid');
    $query->condition('node__field_member_of.field_member_of_target_id', $collection_node_id);
    if ($collection_id) {
      $result = $database->query("SELECT created, " .
        "YEAR(FROM_UNIXTIME(node_field_data.created)) item_year, " .
        "MONTH(FROM_UNIXTIME(node_field_data.created)) item_month " .
        "FROM {node_field_data} " .
        "INNER JOIN {node__field_member_of} node__field_member_of ON node__field_member_of.entity_id = node_field_data.nid " .
        "WHERE type in (:types[]) AND status = :status AND node__field_member_of.field_member_of_target_id = :collection_id",
        [
          ':types[]' => $utilities->getSelectedContentTypes(),
          ':collection_id' => $collection_id,
          ':status' => 1,
        ]
      );
    } else {
      $result = $database->query("SELECT created, " .
        "YEAR(FROM_UNIXTIME(node_field_data.created)) item_year, " .
        "MONTH(FROM_UNIXTIME(node_field_data.created)) item_month " .
        "FROM {node_field_data} WHERE type in (:types[]) AND status = :status",
        [
          ':types[]' => $utilities->getSelectedContentTypes(),
          ':status' => 1,
        ]
      );
    }

    $created_counts = [];
    foreach ($result as $row) {
      $label = date("Y-m", $row->created);
      if (array_key_exists($label, $created_counts)) {
        $created_counts[$label]++;
      }
      else {
        $created_counts[$label] = 1;
      }
    }

    $stats_result = asu_statistics_get_stats($collection_id);
    $month_names = [];
    for ($month = 1; $month < 13; $month++) {
      $month_names[$month] = date("F", mktime(0, 0, 0, $month, 10));
    }
    $headers = array_merge([t('Year')], $month_names, [t('Total')]);
    $this->csvData[] = $headers;
    foreach ($stats_result as $year => $row) {
      $data_row = array_merge([$year], $row);
      $this->csvData[] = $data_row;
    }
    return $created_counts;
  }

}
