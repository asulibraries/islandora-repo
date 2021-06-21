<?php

namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Complex Title' Block for page title purposes.
 *
 * @Block(
 *   id = "asu_complex_title",
 *   admin_label = @Translation("The complex title"),
 *   category = @Translation("Views"),
 * )
 */
class ASUComplexTitle extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    //
    // NOTES: this block should be set to display at the top of the "Breadcrumb"
    // region and be configured to only be used for the "Content types" of:
    //   - asu_repository_item
    //   - collection
    //
    // Since this block should be set to display on node/[nid] pages that are
    // "ASU Repository Item", or possibly "Collection", these should both have
    // the paragraph field that is used for display.
    $current_route = \Drupal::routeMatch();
    $current_route_name = \Drupal::routeMatch()->getRouteName();
    if ($current_route->getParameter('node')) {
      $node = $current_route->getParameter('node');
    }
    elseif ($current_route->getParameter('arg_0') != NULL) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($current_route->getParameter('arg_0'));
    }
    elseif ($current_route_name <> 'view.solr_search_content.page_2') {
      return [];
    }
    if (!is_object($node) && isset($node) && $node != NULL) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node);
    }
    if (!$node) {
      return [];
    }
    if ($node->bundle() == "asu_repository_item" || $node->bundle() == "collection") {
      $asu_utils = \Drupal::service('asu_utils');
      $node_is_published = $asu_utils->isNodePublished($node);
      $titles = $node->field_title;
      $rendered_titles = [];
      foreach ($titles as $i => $title) {
        $view = ['type' => 'complex_title_formatter'];
        $first_title_view = $title->view($view);
        $para_render = trim(\Drupal::service('renderer')->render($first_title_view));
        if ($current_route_name == 'asu_statistics.collection_statistics_view') {
          $para_render .= ' Statistics';
        } elseif ($current_route_name == 'view.solr_search_content.page_2') {
          $para_render = 'Explore "' . $para_render . '"';
        }
        if ($i == 0) {
          $para_render = '<h1 class="article title' .
            ($node_is_published ? "" : " unpublished_title") . '">' . ($node_is_published ? '' : '<i class="fa fa-lock"></i>&nbsp;') . $para_render . '</h1>';
        }
        else {
          $para_render = '<h2>' . $para_render . '</h2>';
        }
        array_push($rendered_titles, $para_render);
      }
      return [
        'complex_title' => [
          '#type' => 'item',
          '#markup' => implode("<br/>", $rendered_titles),
        ],
      ];
    }
    else {
      return [];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if (!is_object($node)) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($node);
      }
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    } else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
