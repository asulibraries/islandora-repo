<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\islandora_matomo\IslandoraMatomoService;

/**
 * Provides a 'About this collection' Block.
 *
 * @Block(
 *   id = "about_this_collection_block",
 *   admin_label = @Translation("About this collection"),
 *   category = @Translation("Views"),
 * )
 */
class AboutThisCollectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The requestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The currentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The islandoraMatomo definition.
   *
   * @var \Drupal\islandora_matomo\IslandoraMatomoService
   */
  protected $islandoraMatomo;

  /**
   * Construct method.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The currentRouteMatch definition.
   * @param \Drupal\islandora_matomo\IslandoraMatomoService $islandoraMatomo
   *   The islandoraMatomo service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request_stack,
    EntityTypeManager $entityTypeManager,
    CurrentRouteMatch $currentRouteMatch,
    IslandoraMatomoService $islandoraMatomo
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->islandoraMatomo = $islandoraMatomo;
  }

  /**
   * Does the initialization of the block setting dependency injection vars.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The parent class object.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('islandora_matomo.default')
    );
  }

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
    $collection_node = $this->currentRouteMatch->getParameter('node');
    if ($collection_node) {
      $collection_created = $collection_node->get('revision_timestamp')->getString();
    }
    else {
      $collection_created = 0;
    }
    // This needs to only return items that are Published and related to the
    // collection, but there doesn't seem to be a way to have multiple AND / OR
    // conjunctions in a single query.
    $children_nids = asu_collection_extras_get_collection_children($collection_node);

    $collection_views = $items = $max_timestamp = 0;
    $islandora_models = $stat_box_row1 = [];
    $files = 0;
    $original_file_tid = key($this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => "Original File"]));
    foreach ($children_nids as $child_nid) {
      if ($child_nid) {
        $items++;
        // For "# file (Titles)", get media & extract original file and count.
        $files += $this->getOriginalFileCount($child_nid, $original_file_tid);
        $node = $this->entityTypeManager->getStorage('node')->load($child_nid);
        if ($node->hasField('field_resource_type') && !$node->get('field_resource_type')->isEmpty()) {
          $res_types = $node->get('field_resource_type')->referencedEntities();
          foreach ($res_types as $tp) {
            $nm = $tp->getName();
            if (array_key_exists($nm, $islandora_models)) {
              $islandora_models[$nm]++;
            }
            else {
              $islandora_models[$nm] = 1;
            }
          }
        }
        $this_revisiontimestamp = $node->get('revision_timestamp')->getString();
        $max_timestamp = ($this_revisiontimestamp > $max_timestamp) ?
          $this_revisiontimestamp : $max_timestamp;
        $node_views = $this->islandoraMatomo->getViewsForNode($child_nid);
        $collection_views += $node_views;
      }
    }
    // Calculate the "Items" box link.
    $items_url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/collections/' .
       (($collection_node) ? $collection_node->id() : 0) . '/search/?search_api_fulltext=');
    $stat_box_row1[] = $this->makeBox("<strong>" . $items . "</strong><br>items", $items_url);
    $stat_box_row1[] = $this->makeBox("<strong>" . $files . "</strong><br>files");
    $stat_box_row1[] = $this->makeBox("<strong>" . count($islandora_models) . "</strong><br>resource types");
    $stat_box_row2[] = $this->makeBox("<strong>" . $collection_views . "</strong><br>views");
    $stat_box_row2[] = $this->makeBox("<strong>" . (($collection_created) ? date('Y', $collection_created) : 'unknown') .
      "</strong><br>collection created");
    $stat_box_row2[] = $this->makeBox("<strong>" . (($max_timestamp) ? date('M d, Y', $max_timestamp) : 'unknown') .
      "</strong><br>last updates</div>");
    return [
      '#markup' =>
      (count($stat_box_row1) > 0) ?
        // ROW 1.
      '<div class="container"><div class="row">' .
      implode('', $stat_box_row1) .
      '</div>' .
        // ROW 2.
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
      ],
    ];
  }

  /**
   * Makes the markup for a given item's box for the template.
   *
   * @param string $string
   *   The inside text string for the box that will be linked.
   * @param Drupal\Core\Url $link_url
   *   The URL object for the explore link.
   *
   * @return string
   *   Markup of the box for use in the template
   */
  private function makeBox($string, Url $link_url = NULL) {
    if ($link_url) {
      // Drupal's Link class is escaping the HTML, so this must be done
      // manually.
      return '<div class="stats_box col-4 stats_pointer_box"><a href="' . $link_url->toString() . '" title="Explore items">' .
        '<div class="stats_border_box">' . $string . '</div></a></div>';
    }
    else {
      return '<div class="stats_box col-4"><div class="stats_border_box">' . $string . '</div></div>';
    }
  }

  /**
   * To recursively (including children) query for the node's original files.
   *
   * @param int $related_nid
   *   The node's id() value.
   * @param int $original_file_tid
   *   The taxonomy term id.
   *
   * @return int
   *   The count of files for the given file.
   */
  private function getOriginalFileCount($related_nid, $original_file_tid) {
    $files = 0;
    $collection_children_nids = asu_collection_extras_get_collection_children($related_nid);

    foreach ($collection_children_nids as $collection_child_nid) {
      // Recursively call this to add counts for EACH CHILD of the top level
      // object that was referenced by $related_nid.
      $files += $this->getOriginalFileCount($collection_child_nid, $original_file_tid);
    }

    // Now, add the actual number of files that may be related to the provided
    // top level object that is referenced by $related_nid.
    $mids = $this->entityTypeManager->getStorage('media')->getQuery()
      ->condition('field_media_of', $related_nid)
      ->condition('field_media_use', $original_file_tid)
      ->execute();
    foreach ($mids as $mid) {
      $media = $this->entityTypeManager->getStorage('media')->load($mid);
      $files += (is_object($media) ? 1 : 0);
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = $this->currentRouteMatch->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
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
