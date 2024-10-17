<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\islandora\MediaSource\MediaSourceService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Downloads' Block.
 *
 * @Block(
 *   id = "downloads_side_bar_block",
 *   admin_label = @Translation("Downloads Sidebar block"),
 *   category = @Translation("Views"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class DownloadsSidebarBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The routeMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The moduleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * IslandoraUtils class.
   *
   * @var mixed
   */
  protected $islandoraUtils;

  /**
   * A MediaSourceService.
   *
   * @var \Drupal\islandora\MediaSource\MediaSourceService
   */
  private $mediaSource;

  /**
   * CurrentPathStack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathStack;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param mixed $islandora_utils
   *   IslandoraUtils Utility class.
   * @param \Drupal\islandora\MediaSource\MediaSourceService $media_source
   *   Media source service.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   ModuleHandler Utility.
   * @param \Drupal\Core\Path\CurrentPathStack $path_stack
   *   Current Path Utility.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entityTypeManager, AccountProxy $current_user, $islandora_utils, MediaSourceService $media_source, ModuleHandler $moduleHandler, CurrentPathStack $path_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
    $this->islandoraUtils = $islandora_utils;
    $this->mediaSource = $media_source;
    $this->moduleHandler = $moduleHandler;
    $this->pathStack = $path_stack;
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
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('islandora.utils'),
      $container->get('islandora.media_source_service'),
      $container->get('module_handler'),
      $container->get('path.current'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    if (!isset($node) || !$node->hasField('field_work_products') || $node->get('field_work_products')->isEmpty()) {
      return [];
    }
    $cache_tags = ["node:{$node->id()}", "user:{$this->currentUser->id()}"];

    $date = new \DateTime();
    $today = $date->format("c");
    if ($node->hasField('field_embargo_release_date') && $node->get('field_embargo_release_date') && $node->get('field_embargo_release_date')->value != NULL && $node->get('field_embargo_release_date')->value != 'T23:59:59' && $node->get('field_embargo_release_date')->value >= $today) {
      // If its embargoed, remove the download options entirely.
      $build[] = [
        '#type' => 'container',
        'lock' => ['#type' => 'html_tag', '#tag' => 'i', '#attributes' => ['class' => ['fas', 'fa-lock']]],
        'statement' => [
          '#markup' => "Public access restricted until " . $node->get('field_embargo_release_date')->date->format('Y-m-d') . ".",
        ],
      ];
    }
    else {

      $user_roles = $this->currentUser->getRoles();

      $access_policies = [];
      foreach ($node->get('field_work_products') as $media_reference) {
        if ($label = $media_reference->entity->field_access_terms?->entity?->label()) {
          if (!in_array($label, $access_policies)) {
            $access_policies[] = $label;
          }
        }
      }
      // Check ASU-Only restrictions.
      if (in_array('anonymous', $user_roles) && in_array("ASU Only", $access_policies)) {
        if ($this->moduleHandler->moduleExists('cas')) {
          $url = Url::fromRoute('cas.login')->toString();
        }
        else {
          $url = "/user/login";
        }
        $currentPath = $this->pathStack->getPath();
        $build[] = [
          '#type' => 'container',
          'lock' => ['#type' => 'html_tag', '#tag' => 'i', '#attributes' => ['class' => ['fas', 'fa-lock']]],
          'statement' => [
            '#markup' => "One or more components are restricted to ASU affiliates. Please <a href='" . $url . "?returnto=" . $currentPath . "'>sign in</a> to view the rest.",
          ],
        ];
      }
    }
    // Add the collection-level statement if it exists.
    $collections = array_filter($this->entityTypeManager->getStorage('node')->loadMultiple($this->islandoraUtils->findAncestors($node)), function ($a) {
      return ($a->bundle() == 'collection' && $a->hasField('field_restrictions_statement') && !$a->get('field_restrictions_statement')->isEmpty());
    });
    // Allows both collection and sub-collection statements.
    foreach ($collections as $c) {
      if (!$c->get('field_restrictions_statement')->isEmpty()) {
        $build[] = $c->field_restrictions_statement->view();
      }
    }

    // The simple way to display each item would be to use the
    // `entity_reference_entity_view` formatter; however, that requires
    // setting up the view mode for each media type which can lead to
    // incosistencies which is bad for the CSS we will be adding later.
    // Instead we just build our own render array.
    foreach ($node->get('field_work_products') as $media_reference) {
      if (!($media = $media_reference->entity) || !$media->access('view')) {
        continue;
      }
      // $downloads[] = [
      $build[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['d-flex', 'align-items-center'],
          // 'class' => ['row', 'd-flex', 'align-items-center'],
        ],
        'tile_box' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-9']],
          'download_link' => $media->get($this->mediaSource->getSourceFieldName($media->bundle()))->view([
            'type' => 'file_download_link',
            'label' => 'hidden',
            'settings' => [
              'link_text' => $media->name->value,
              'new_tab' => TRUE,
              'force_download' => FALSE,
              'custom_classes' => 'resource_engagement_link',
            ],
          ]),
        ],
        'type_size_box' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'col-3',
              ' d-flex',
              'flex-column',
              'align-items-center',
              'justify-content-center',
            ],
          ],
          'type_icon' => ($media->field_mime_type) ? $media->field_mime_type->view([
            'type' => 'mime_type_icon',
            'label' => 'hidden',
            'settings' => [
              'size' => 'fa-lg',
              'fixed_width' => 'fa-fw',
              'link_to_entity' => FALSE,
            ],
          ]) : ['#markup' => 'Unknown type'],
          'size' => ($media->field_file_size) ? $media->field_file_size->view([
            'type' => 'file_size',
            'label' => 'hidden',
          ]) : ['#markup' => 'Unknown size'],
        ],
      ];
    }
    // $build[] = $downloads;
    // Cache tags.
    $build['#cache'] = ['tags' => $cache_tags];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $user = $this->currentUser;
    $parentTags = parent::getCacheTags();
    $tags = Cache::mergeTags($parentTags, ['user:' . $user->id()]);
    $block_config = BlockBase::getConfiguration();
    if (is_array($block_config) && array_key_exists('child_node_id', $block_config)) {
      $nid = $block_config['child_node_id'];
    }
    else {
      if ($this->routeMatch->getParameter('node')) {
        $node = $this->routeMatch->getParameter('node');
        $nid = (is_string($node) ? $node : $node->id());
      }
    }
    if (isset($nid)) {
      // If there is node add its cachetag.
      return Cache::mergeTags($tags, ['node:' . $nid]);
    }
    return $tags;
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

}
