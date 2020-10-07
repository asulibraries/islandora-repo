<?php

namespace Drupal\asu_landing_site\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\repo_bento_search\BentoApiInterface;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Keep views query plugin wraps calls to the KEEP API to expose to views.
 *
 * @ViewsQuery(
 *   id = "keep",
 *   title = @Translation("KEEP"),
 *   help = @Translation("Data from the KEEP API")
 * )
 */
class Keep extends QueryPluginBase {
  /**
   * Drupal\repo_bento_search\BentoApiInterface definition.
   *
   * @var \Drupal\repo_bento_search\BentoApiInterface
   */
  protected $repoBentoSearchThisI8;

  /**
   * KEEP constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\repo_bento_search\BentoApiInterface $repo_bento_search_this_i8
   *   The repo bento search api service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BentoApiInterface $repo_bento_search_this_i8) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->repoBentoSearchThisI8 = $repo_bento_search_this_i8;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('repo_bento_search.this_i8')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $alias = '', $params = []) {
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $data = $this->repoBentoSearchThisI8->getRecentItems();
    $items = json_decode($data, TRUE);
    $index = 0;
    foreach ($items as $item) {
      $row['created'] = $item['created'];
      $row['changed'] = $item['changed'];
      $row['field_rich_description'] = $item['field_rich_description'];
      $row['nid'] = $item['nid'];
      $row['field_title'] = $item['field_title'];
      $row['url'] = $item['url'];
      $row['field_handle'] = $item['field_handle'];
      $row['field_model'] = $item['field_model'];
      $thumb = $item['thumbnail_url'];
      $thumb = str_replace("\n", '', $thumb);
      $row['thumbnail_url'] = str_replace(" ", '', $thumb);
      $row['index'] = $index++;
      $view->result[] = new ResultRow($row);
    }
  }

}
