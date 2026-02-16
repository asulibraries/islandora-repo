<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Downloads' Block.
 *
 * @Block(
 *   id = "downloads_block",
 *   admin_label = @Translation("Downloads block"),
 *   category = @Translation("Views"),
 * )
 */
class DownloadsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Module handler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * IslandoraUtils class.
   *
   * @var mixed
   */
  protected $islandoraUtils;

  /**
   * MediaSourceService class.
   *
   * @var mixed
   */
  protected $mediaSourceService;

  /**
   * Renderer definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

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
   * @param mixed $media_source_service
   *   MediaSourceService Utility class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entityTypeManager, AccountProxy $current_user, $islandora_utils, $media_source_service, $module_handler, $renderer, $current_path,) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
    $this->islandoraUtils = $islandora_utils;
    $this->moduleHandler = $module_handler;
    $this->mediaSourceService = $media_source_service;
    $this->renderer = $renderer;
    $this->currentPath = $current_path;
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
      $container->get('renderer'),
      $container->get('path.current'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_config = BlockBase::getConfiguration();
    if (is_array($block_config) && array_key_exists('child_node_id', $block_config)) {
      $nid = $block_config['child_node_id'];
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
    }
    else {
      if ($this->routeMatch->getParameter('node')) {
        $node = $this->routeMatch->getParameter('node');
        $nid = (is_string($node) ? $node : $node->id());
        if (is_string($node)) {
          $node = $this->entityTypeManager->getStorage('node')->load($nid);
        }
      }
    }

    $markup = '';

    $all_media = $this->islandoraUtils->getMedia($node);

    $all_media = array_filter($all_media, function ($media) {
      // Filter out media without a media use term, thumbnails, and FITS files.
      return (
        $media->hasField('field_media_use') &&
        !$media->get('field_media_use')->isEmpty() &&
        !in_array($media->get('field_media_use')->entity->field_external_uri->uri, [
          'http://pcdm.org/use#ThumbnailImage', 'https://projects.iq.harvard.edu/fits',
        ]));
    });
    $accessible_media = array_filter($all_media, function ($m) {
      return $m->access('view');
    });
    // At least some downloads are restrictd. Display the appropriate messages.
    if (count($all_media) > count($accessible_media)) {
      $restriction = FALSE;
      $asu_only = FALSE;
      foreach ($all_media as $m) {
        switch ($m->field_access_terms?->entity?->label()) {
          case "ASU Only":
            $asu_only = TRUE;
          case "Private":
            $restriction = TRUE;
            break;
        }
        if ($m->field_access_terms?->entity?->label() == "Private") {
          $restriction = TRUE;
        }
        elseif ($m->field_access_terms?->entity?->label() == "ASU Only") {
          $restriction = TRUE;
          $asu_only = TRUE;
        }
      }
      if ($restriction) {
        if (count($accessible_media) > 0) {
          $markup = "<i class='fas fa-lock'></i> Some downloads are restricted.";
        }
        else {
          $markup = "<i class='fas fa-lock'></i> Download restricted.";
        }
        if ($asu_only) {
          if ($this->moduleHandler->moduleExists('cas')) {
            $url = Url::fromRoute('cas.login')->toString();
          }
          else {
            $url = "/user/login";
          }
          $currentPath = $this->currentPath->getPath();
          $markup .= " Please <a href='$url?returnto=$currentPath'>sign in</a>.";
        }
        // Add the collection-level statement if it exists.
        $collections = array_filter($this->entityTypeManager->getStorage('node')->loadMultiple($this->islandoraUtils->findAncestors($node)), function ($a) {
          return ($a->bundle() == 'collection' && $a->hasField('field_restrictions_statement') && !$a->get('field_restrictions_statement')->isEmpty());
        });
        // Allows both collection and sub-collection statements.
        foreach ($collections as $c) {
          if (!$c->get('field_restrictions_statement')->isEmpty()) {
            $statement = $c->field_restrictions_statement->view();
            $markup .= $this->renderer->renderRoot($statement);
          }
        }
      }
    }

    $date = new \DateTime();
    $today = $date->format("c");
    if ($node->hasField('field_embargo_release_date') && $node->get('field_embargo_release_date') && $node->get('field_embargo_release_date')->value != NULL && $node->get('field_embargo_release_date')->value != 'T23:59:59' && $node->get('field_embargo_release_date')->value >= $today) {
      // If its embargoed, remove the download options entirely.
      $markup = "<i class='fas fa-lock'></i> Download restricted until " . $node->get('field_embargo_release_date')->date->format('Y-m-d') . ".";
    }

    foreach ($accessible_media as $media) {
      if (!$media?->access('view')) {
        continue;
      }

      // Filter out media that doesn't have a file.
      $source_field = $this->mediaSourceService->getSourceFieldName($media->bundle());
      if (empty($source_field) || !$media->hasField($source_field) || $media->get($source_field)->isEmpty()) {
        continue;
      }

      $downloads[] = [
        // File Name with link to download.
        [
          'data' => Link::fromTextAndUrl($media->name->value, Url::fromUri($this->islandoraUtils->getDownloadUrl($media->get($this->mediaSourceService->getSourceFieldName($media->bundle()))->entity), [
            'attributes' => [
              'class' => ['download-counter'],
              'target' => '_blank',
              'rel' => 'noopener noreferrer',
            ],
          ]))->toRenderable(),
        ],
        // Media Use Type.
        [
          'data' => ($media->hasField('field_media_use') && !$media->get('field_media_use')->isEmpty()) ? $media->get('field_media_use')->entity->label() : '',
        ],
        // File Type with icon.
        [
          'data' => ($media->field_mime_type) ? $media->field_mime_type->view([
            'type' => 'mime_type_icon',
            'label' => 'hidden',
            'settings' => [
              'size' => 'fa-lg',
              'fixed_width' => 'fa-fw',
              'link_to_entity' => FALSE,
            ],
          ]) : ['#markup' => 'Unknown type'],
        ],
        // File Size.
        [
          'data' => ($media->field_file_size) ? $media->field_file_size->view([
            'type' => 'file_size',
            'label' => 'hidden',
          ]) : ['#markup' => 'Unknown size'],
        ],
      ];

      // Add captions from audio/video if we haven't yet.
      if (in_array($media->bundle(), ['audio', 'video']) && !isset($captions_added) && $captions = $media->get('field_track')?->entity) {
        $downloads[] = [
            // File Name with link to download.
            [
              'data' => Link::fromTextAndUrl($captions->filename->value, Url::fromUri($this->islandoraUtils->getDownloadUrl($captions), [
                'attributes' => [
                  'class' => ['download-counter'],
                  'target' => '_blank',
                  'rel' => 'noopener noreferrer',
                ],
              ]))->toRenderable(),
            ],
            // Media Use Type.
            [
              'data' => 'Captions',
            ],
            // File Type with icon.
            [
              'data' => $captions->filemime->view([
                'type' => 'mime_type_icon',
                'label' => 'hidden',
                'settings' => [
                  'size' => 'fa-lg',
                  'fixed_width' => 'fa-fw',
                  'link_to_entity' => FALSE,
                ],
              ]) ?? ['#markup' => 'Unknown type'],
            ],
            // File Size.
            [
              'data' => $captions->filesize->view([
                'type' => 'file_size',
                'label' => 'hidden',
              ]) ?? ['#markup' => 'Unknown size'],
            ],
        ];
        $captions_added = TRUE;
      }
    }

    $return = [
      '#asu_download_restricted' => ['#markup' => $markup],
      '#theme' => 'asu_item_extras_downloads_block',
      '#cache' => [
        'tags' => ["node:$nid"],
      ],
      '#attached' => [
        'library' => [
          'asu_item_analytics/download-counter',
        ],
      ],
    ];

    if (!empty($downloads)) {
      $return['#asu_download_links'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Type'),
          $this->t('Format'),
          $this->t('Size'),
        ],
        '#rows' => $downloads,
      ];
    }

    return $return;
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

  /**
   * Extracts file and media info from the objects.
   */
  protected function getFileDetails($file, $type) {
    $source_field = $this->mediaSourceService->getSourceFieldName($file->bundle());
    if (!empty($source_field)) {
      $of_file = ($file->hasField($source_field) && (is_object($file->get($source_field)) && $file->get($source_field)->referencedEntities() != NULL) ? $file->get($source_field)->referencedEntities()[0] : FALSE);
      if ($of_file) {
        $file_path_info = pathinfo($of_file->getFilename());
        return [
          "file_size" => $file->get('field_file_size')->value,
          "mime_type" => $file->get('field_mime_type')->value,
          "ext" => $file_path_info['extension'],
          "dimensions" => ($dimensions ?? NULL),
          "link" => $this->islandoraUtils->getDownloadUrl($of_file),
          "access" => $file->access('view', $this->currentUser),
          "perms" => count($file->get('field_access_terms')->referencedEntities()) > 0 ? $file->get('field_access_terms')->referencedEntities()[0]->label() : "Public",
          "type" => $type,
        ];
      }
    }
  }

}
