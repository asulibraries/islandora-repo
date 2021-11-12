<?php

namespace Drupal\asu_search\Plugin\views\style;

use Drupal\facets_rest\Plugin\views\style\FacetsSerializer;

/**
 * The style plugin for serialized output formats with pager.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "serializer_with_pager",
 *   title = @Translation("Serializer with pager"),
 *   help = @Translation("Serializes views row data using the Serializer component with pager."),
 *   display_types = {"data"}
 * )
 */
class ViewsSerializerCount extends FacetsSerializer {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = $pager_info = [];

    // Create pager info if pagination is enabled in view.
    $plugin_id = $this->view->pager->getPluginId();
    if ($plugin_id == 'mini' || $plugin_id == 'full') {
      $items_per_page = $this->view->pager->options['items_per_page'];
      $count = $this->view->pager->getTotalItems();
      $pages = ceil($count / $items_per_page);
      $current_page = $this->view->pager->getCurrentPage() ? $this->view->pager->getCurrentPage() : 0;
      $next_page = $current_page + 1;
      if ($next_page == $pages || $pages == 0) {
        $next_page = 0;
      }

      $pager_info['pager'] = [
        'count' => (int) $count,
        'pages' => $pages,
        'items_per_page' => $items_per_page,
        'current_page' => $current_page,
        'next_page' => $next_page,
      ];
    }

    // If the data entity row plugin is used, this will be an array of entities
    // which will pass through serializer to one of the registered normalizers,
    // which will transform it to arrays/scalars. If the data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      // $rows[] = $this->view->rowPlugin->render($row);
      $rows['search_results'][] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    $facetsource_id = "search_api:views_rest__{$this->view->id()}__{$this->view->getDisplay()->display['id']}";
    $facets = $this->facetsManager->getFacetsByFacetSourceId($facetsource_id);
    $this->facetsManager->updateResults($facetsource_id);

    $processed_facets = [];
    foreach ($facets as $facet) {
      $processed_facets[] = $this->facetsManager->build($facet);
    }

    $rows['facets'] = array_values($processed_facets);

    $results = $rows + $pager_info;

    return $this->serializer->serialize($results, $content_type, ['views_style_plugin' => $this]);
  }

}
