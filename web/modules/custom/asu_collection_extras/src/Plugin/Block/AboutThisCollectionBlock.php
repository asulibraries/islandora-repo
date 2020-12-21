<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\media\Entity\Media;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

/**
 * Provides a 'About this collection' Block.
 *
 * @Block(
 *   id = "about_this_collection_block",
 *   admin_label = @Translation("About this collection"),
 *   category = @Translation("Views"),
 * )
 */
class AboutThisCollectionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The sections in this block should be statistics on the children of the
     * collection using the "Is Member Of" relationship -- not just the "Is
     * Primary Member Of" and these should link to a canned-search for the
     * specific statistic when possible:
     *
     *  - # of child items
     *  - # of resource types
     *  - # of titles

     *  - usage
     *  - collection created
     *  - collection last updated (by looking at all of the children)
     *
     * When needing to check a node's content type:
     *    $is_collection = ($node->bundle() == 'collection');
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    $collection_node = \Drupal::routeMatch()->getParameter('node');
    if ($collection_node) {
      $collection_created = $collection_node->get('revision_timestamp')->getString();
    } else {
      $collection_created = 0;
    }
    // This needs to only return items that are Published and related to the
    // collection, but there doesn't seem to be a way to have multiple AND / OR
    // conjunctions in a single query.
    $children_nids = getAllCollectionChildren($collection_node);

    $collection_views = $items = $max_timestamp = 0;
    $nodes = $islandora_models = $stat_box_row1 = $stats_box_row2 = [];
    $files = 0;
    $original_file_tid = key(\Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => "Original File"]));
    foreach ($children_nids as $child_nid) {
      if ($child_nid) {
        // @todo load complex objects for this node -- if any... and inner-loop
        // these if deemed to be needed.
        $items++;
        // For "# file (Titles)", get media - extract the and count the original files.
        $files += $this->getOriginalFileCount($child_nid, $original_file_tid);
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($child_nid);
        if ($node->hasField('field_resource_type') && !$node->get('field_resource_type')->isEmpty()) {
          $res_types = $node->get('field_resource_type')->referencedEntities();
          foreach ($res_types as $tp) {
            $nm = $tp->getName();
            if (array_key_exists($nm, $islandora_models)) {
              $islandora_models[$nm]++;
            } else {
              $islandora_models[$nm] = 1;
            }
          }
        }
        $this_revisiontimestamp = $node->get('revision_timestamp')->getString();
        $max_timestamp = ($this_revisiontimestamp > $max_timestamp) ?
          $this_revisiontimestamp : $max_timestamp;
        $node_views = \Drupal::service('islandora_matomo.default')->getViewsForNode($child_nid);
        $collection_views += $node_views;
      }
    }
    // Calculate the "Items" box link.
    $items_url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/collections/' .
       (($collection_node) ? $collection_node->id() : 0) . '/search/?search_api_fulltext=');
    $stat_box_row1[] = $this->makeBox("<strong>" . $items . "</strong><br>items", $items_url);
    // Until I find a way to pass a wildcard filter on resource_type for the files box...
    //    $files_url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/collections/' .
    //       (($collection_node) ? $collection_node->id() : 0) . '/search/?search_api_fulltext=&f[0]=resource_type:Image');
    $stat_box_row1[] = $this->makeBox("<strong>" . $files . "</strong><br>files");
    $stat_box_row1[] = $this->makeBox("<strong>" . count($islandora_models) . "</strong><br>resource types");
    $stat_box_row2[] = $this->makeBox("<strong>" . $collection_views . "</strong><br>usage");
    $stat_box_row2[] = $this->makeBox("<strong>" . (($collection_created) ? date('Y', $collection_created): 'unknown') .
      "</strong><br>collection created");
    $stat_box_row2[] = $this->makeBox("<strong>" . (($max_timestamp) ? date('M d, Y', $max_timestamp): 'unknown') .
      "</strong><br>last updates</div>");
    return [
      '#markup' =>
        (count($stat_box_row1) > 0) ?
        // ROW 1
        '<div class="container"><div class="row">' .
        implode('', $stat_box_row1) .
        '</div>' .
        // ROW 2
        '<div class="row">' .
        implode('', $stat_box_row2) .
        '</div>' :
        "",
      'lib' => [
        '#attached' => [
          'library' => [
            'asu_collection_extras/style',
          ],
        ],
      ]
    ];
  }

  private function makeBox($string, $link_url = NULL) {
    if ($link_url) {
      // Drupal's Link class is escaping the HTML, so this must be done manually.
      return '<div class="stats_box col-4 stats_pointer_box"><a href="' . $link_url->toString() . '" title="Explore items">' .
        '<div class="stats_border_box">' . $string . '</div></a></div>';
    }
    else {
      return '<div class="stats_box col-4"><div class="stats_border_box">' . $string . '</div></div>';
    }
}

  private function getOriginalFileCount($related_nid, $original_file_tid) {
    $files = 0;
    $collection_children_nids = getAllCollectionChildren($related_nid);

    foreach ($collection_children_nids as $collection_child_nid) {
      // recursively call this to add counts for EACH CHILD of the top level
      // object that was referenced by $related_nid.
      $files += $this->getOriginalFileCount($collection_child_nid, $original_file_tid);
    }

    // Now, add the actual number of files that may be related to the provided
    // top level object that is referenced by $related_nid.
    $mids = \Drupal::entityQuery('media')
      ->condition('field_media_of', $related_nid)
      ->condition('field_media_use', $original_file_tid)
      ->execute();
    foreach ($mids as $mid) {
      $media = Media::load($mid);
      $files += (is_object($media) ? 1 : 0);
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags()
  {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    } else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts()
  {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
