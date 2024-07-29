<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'About this item' Block.
 *
 * @Block(
 *   id = "about_this_item_block",
 *   admin_label = @Translation("About this item"),
 *   category = @Translation("Views"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class AboutThisItemBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The routeMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for About this Collection Block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    /*
     * The title of the block could be dependant on the underlying Islandora
     * Model used. In liey of that, the title should just be "About this item".
     *
     * The links within this block should be:
     *  - Overview
     *  - View full metadata
     *  - Permalink
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    // @todo use dependency injection.
    $node = $this->getContextValue('node');
    if (!isset($node)) {
      return [];
    }
    $build = [];
    if ($node->hasField('field_work_products')) {
      $work_products = $node->get('field_work_products');
      // Grabbing a thumbnail from the first item.
      $first = $work_products->first();
      // Checking media access because checking thumbail access can return
      // a false positive result but still fail to work when the user attempts
      // to access it via the browser; although I don't know why.
      if ($first->entity?->access('view') && $thumbnail = $first->entity->get('thumbnail')) {
        $build['content'][] = $thumbnail->view(['label' => 'hidden']);
      }

      // Build a list of the other items.
      $media_source_service = \Drupal::service('islandora.media_source_service');
      $work_product_render_array = [];
      $cache_tags = ["node:{$node->id()}"];
      foreach ($work_products as $work_product_reference) {
        $item_render = ['#attributes' => ['class' => ['row']]];
        $wp = $work_product_reference->entity;
        if (!$wp) {
          continue;
        }
        $cache_tags[] = "media:{$wp->id()}";
        $icon = $this->mimeToFAIcon($wp->field_mime_type->value);
        $item_render[] = ['#type' => 'markup', '#markup' => "<i class='col far {$icon}'></i>"];
        $title = ($wp->access('view')) ?
        $wp->get($media_source_service->getSourceFieldName($wp->bundle()))->view([
          'type' => 'file_download_link',
          'label' => 'hidden',
          'settings' => [
            'link_text' => $wp->name->value,
            'new_tab' => TRUE,
            'force_download' => FALSE,
          ],
        ]) :
              $wp->name->view(['label' => 'hidden']);
        $title['#attributes']['class'][] = 'col';
        $item_render[] = $title;
        $type_size = ['#type' => 'container', '#attributes' => ['class' => ['type-size', 'col']]];
        $type_size[] = $wp->field_file_size->view([
          'type' => 'file_size',
          'label' => 'hidden',
        ]);
        $type_size[] = $wp->field_mime_type->view(['label' => 'hidden']);
        $item_render[] = $type_size;
        $work_product_render_array[] = $item_render;
      }
      if ($work_product_render_array) {
        $build[] = [
          '#theme' => 'item_list',
          '#attached' => ['library' => ['keep/scholarly-work-sidebar']],
          '#list_type' => 'ol',
          '#attributes' => ['class' => ['container']],
          '#items' => $work_product_render_array,
          '#cache' => ['contexts' => ['user.roles', 'route'], 'tags' => $cache_tags],
        ];
      }
    }
    $output_links = [];
    // Add a link to get the Permalink for this node.
    if ($node->hasField('field_handle') && $node->get('field_handle')->value != NULL) {
      $hdl = $node->get('field_handle')->value;
      $output_links[] = '<div class="permalink_button"><a class="btn btn-maroon btn-md copy_permalink_link" title="' . $hdl . '"><i class="far fa-copy fa-lg copy_permalink_link" title="' . $hdl . '"></i>&nbsp;Copy permalink</a></div>';
      $build[] = [
        '#markup' =>
        (count($output_links) > 0) ?
        "<nav class='sidebar'>" . implode("", $output_links) . "</nav>" :
        "",
        '#attached' => [
          'library' => [
            'asu_item_extras/interact',
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $node = $this->getContextValue('node');
    if (!isset($node)) {
      return parent::getCacheTags();
    }
    else {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // If you depends on \Drupal::routeMatch().
    // You must set context of this block with 'route' context tag.
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   *
   */
  private function mimeToFAIcon($mime_type) {
    // List of official MIME Types: http://www.iana.org/assignments/media-types/media-types.xhtml
    // Font Awesome 5 icons.
    // Generic cases.
    if (str_starts_with($mime_type, 'image')) {
      return 'fa-file-image';
    }
    elseif (str_starts_with($mime_type, 'audio')) {
      return 'fa-file-audio';
    }
    elseif (str_starts_with($mime_type, 'video')) {
      return 'fa-file-video';
    }

    // Application cases:
    switch ($mime_type) {
      // Documents.
      case 'application/pdf':
        return 'fa-file-pdf';

      case 'application/msword':
      case 'application/vnd.ms-word':
      case 'application/vnd.oasis.opendocument.text':
      case 'application/vnd.openxmlformats-officedocument.wordprocessingml':
        return 'fa-file-word';

      case 'application/vnd.ms-excel':
      case 'application/vnd.openxmlformats-officedocument.spreadsheetml':
      case 'application/vnd.oasis.opendocument.spreadsheet':
        return 'fa-file-excel';

      case 'application/vnd.ms-powerpoint':
      case 'application/vnd.openxmlformats-officedocument.presentationml':
      case 'application/vnd.oasis.opendocument.presentation':
        return 'fa-file-powerpoint';

      case 'text/plain':
        return 'fa-file-alt';

      case 'text/html':
      case 'application/json':
        return 'fa-file-code';

      // Archives.
      case 'application/gzip':
      case 'application/zip':
        return 'fa-file-archive';

      default:
        return 'fa-file';
    }
  }

}
