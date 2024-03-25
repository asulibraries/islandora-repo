<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\asu_islandora_utils\AsuUtils;
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
   * The AsuUtils definition.
   *
   * @var \Drupal\asu_islandora_utils\AsuUtils
   */
  protected $asuUtils;

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
   * @param \Drupal\asu_islandora_utils\AsuUtils $asu_utils
   *   The ASU Utils service.
   * @param mixed $media_source_service
   *   MediaSourceService Utility class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entityTypeManager, AccountProxy $current_user, $islandora_utils, AsuUtils $asu_utils, $media_source_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
    $this->islandoraUtils = $islandora_utils;
    $this->asuUtils = $asu_utils;
    $this->mediaSourceService = $media_source_service;
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
      $container->get('asu_utils'),
      $container->get('islandora.media_source_service')
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
    $all_files = [];

    $default_config = \Drupal::config('asu_default_fields.settings');
    $user_roles = $this->currentUser->getRoles();

    if (array_key_exists('origfile', $block_config)) {
      $origfile = $block_config['origfile'];
    }
    else {
      $origfile_term = $default_config->get('original_file_taxonomy_term');
      $origfile = $this->entityTypeManager->getStorage('media')->loadByProperties([
        'field_media_use' => ['target_id' => $origfile_term],
        'field_media_of' => ['target_id' => $nid],
      ]);
      if (count($origfile) > 0) {
        $origfile = reset($origfile);
      }
      else {
        $origfile = NULL;
      }
    }

    if (array_key_exists('servicefile', $block_config)) {
      $servicefile_term = $default_config->get('service_file_taxonomy_term');
      $servicefile = $this->entityTypeManager->getStorage('media')->loadByProperties([
        'field_media_use' => ['target_id' => $servicefile_term],
        'field_media_of' => ['target_id' => $nid],
      ]);
      if (count($servicefile) > 0) {
        $servicefile = reset($servicefile);
      }
      else {
        $servicefile = NULL;
      }
    }
    $presfile_term =
    $default_config->get('preservation_master_taxonomy_term');
    $presfile = $this->entityTypeManager->getStorage('media')->loadByProperties([
      'field_media_use' => ['target_id' => $presfile_term],
      'field_media_of' => ['target_id' => $nid],
    ]);
    if (count($presfile) > 0) {
      $presfile = reset($presfile);
    }
    else {
      $presfile = NULL;
    }

    if ($origfile && $origfile->bundle() <> 'remote_video') {
      $all_files["original"] = $this->getFileDetails($origfile, "original");
      $download_info = $all_files["original"]["mime_type"];
      $file_size = $all_files["original"]["file_size"];
    }
    if ($servicefile && ($servicefile->bundle() <> 'remote_video' && $servicefile->bundle() <> "audio" && $servicefile->bundle() <> "video")) {
      $all_files["derivative"] = $this->getFileDetails($servicefile, "derivative");
    }
    if ($presfile && $presfile->bundle() <> 'remote_video') {
      $all_files["preservation"] = $this->getFileDetails($presfile, "preservation");
    }

    $markup = '';
    $links = array_filter($all_files, function ($v) {
      return $v["access"];
    });

    // Downloads are restrictd. Display the appropriate messages.
    if ($links == [] && in_array('anonymous', $user_roles)) {
      $asu_only_links = array_filter($all_files, function ($v) {
        return $v["perms"] == "ASU Only";
      });
      if (count($asu_only_links) > 0) {
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('cas')) {
          $url = Url::fromRoute('cas.login')->toString();
        }
        else {
          $url = "/user/login";
        }
        $currentPath = \Drupal::service('path.current')->getPath();
        $markup = "<i class='fas fa-lock'></i> Download restricted. Please <a href='" . $url . "?returnto=" . $currentPath . "'>sign in</a>.";
      }
      else {
        $markup = "<i class='fas fa-lock'></i> Download restricted.";
      }
      // Add the collection-level statement if it exists.
      $collections = array_filter($this->entityTypeManager->getStorage('node')->loadMultiple($this->islandoraUtils->findAncestors($node)), function ($a) {
        return ($a->bundle() == 'collection' && $a->hasField('field_restrictions_statement') && !$a->get('field_restrictions_statement')->isEmpty());
      });
      // Allows both collection and sub-collection statements.
      foreach ($collections as $c) {
        if (!$c->get('field_restrictions_statement')->isEmpty()) {
          $statement = $c->field_restrictions_statement->view();
          $markup .= \Drupal::service('renderer')->renderRoot($statement);
        }
      }
    }

    $date = new \DateTime();
    $today = $date->format("c");
    if ($node->hasField('field_embargo_release_date') && $node->get('field_embargo_release_date') && $node->get('field_embargo_release_date')->value != NULL && $node->get('field_embargo_release_date')->value != 'T23:59:59' && $node->get('field_embargo_release_date')->value >= $today) {
      // If its embargoed, remove the download options entirely.
      $markup = "<i class='fas fa-lock'></i> Download restricted until " . $node->get('field_embargo_release_date')->date->format('Y-m-d') . ".";
    }

    $node_language = $node->get('field_language')->entity;
    $link_hreflang = [];
    if ($node_language) {
      if ($node_language->hasField('field_langcode_2digits') && $node_language->get('field_langcode_2digits')->value) {
        $link_hreflang = ['hreflang' => $node_language->get('field_langcode_2digits')->value];
      }
    }
    $asuUtils = $this->asuUtils;
    $links = array_map(function ($v) use ($link_hreflang, $asuUtils) {
      return Link::fromTextAndUrl($v['ext'] . " (" . $asuUtils->formatBytes($v['file_size'], 1) . ")", Url::fromUri($v['link'], [
        'attributes' => array_merge($link_hreflang, [
          'class' => ['btn btn-md btn-gray resource_engagement_link'],
          'title' => $this->t('Download %type file %ext', [
            '%type' => $v['type'],
            '%ext' => $v['ext'],
          ]),
        ]),
      ]))->toRenderable();
    }, $links);

    $return = [
      '#asu_download_info' => $download_info ?? '',
      '#asu_download_restricted' => ['#markup' => $markup],
      '#asu_download_links' => $links,
      '#file_size' => $file_size ?? 0,
      '#theme' => 'asu_item_extras_downloads_block',
      '#cache' => [
        'tags' => ["node:$nid"],
      ],
    ];

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
