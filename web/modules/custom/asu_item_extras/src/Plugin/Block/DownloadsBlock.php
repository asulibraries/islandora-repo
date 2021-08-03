<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;


  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;


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
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The Drupal form builder.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, RequestStack $request_stack, EntityTypeManager $entityTypeManager, FormBuilderInterface $formBuilder, AccountProxy $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
    $this->currentUser = $current_user;
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
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('current_user')
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
    $download_info = '';
    $file_size = 0;
    $islandora_utils = \Drupal::service('islandora.utils');
    $media_source_service = \Drupal::service('islandora.media_source_service');
    $origfile_term = $islandora_utils->getTermForUri('http://pcdm.org/use#OriginalFile');
    $origfile = $islandora_utils->getMediaWithTerm($node, $origfile_term);
    $servicefile_term = $islandora_utils->getTermForUri('http://pcdm.org/use#ServiceFile');
    $servicefile = $islandora_utils->getMediaWithTerm($node, $servicefile_term);
    $masterfile_term = $islandora_utils->getTermForUri('http://pcdm.org/use#PreservationMasterFile');
    $masterfile = $islandora_utils->getMediaWithTerm($node, $masterfile_term);

    if ($origfile && $origfile->bundle() <> 'remote_video') {
      $file_entities = ($origfile->hasField('field_access_terms') ? $origfile->get('field_access_terms')->referencedEntities() : NULL);
      $of_access = ((!is_null($file_entities) && array_key_exists(0, $file_entities)) ? $file_entities[0]->label() : FALSE);
      $source_field = $media_source_service->getSourceFieldName($origfile->bundle());
      if (!empty($source_field)) {
        $of_file = ($origfile->hasField($source_field) && (is_object($origfile->get($source_field)) && $origfile->get($source_field)->referencedEntities() != NULL) ? $origfile->get($source_field)->referencedEntities()[0] : FALSE);
        if ($of_file) {
          $of_uri = $islandora_utils->getDownloadUrl($of_file);
          $of_link = Link::fromTextAndUrl($this->t('Original'), Url::fromUri($of_uri, ['attributes' => ['class' => ['dropdown-item']]]));
          $file_size = $origfile->get('field_file_size')->value;
          $download_info .= " " . $origfile->get('field_mime_type')->value;
        }
      }
      // TODO populate $download_info with the filesize in human readable format and the extension of the fiel
    }
    if ($servicefile && $servicefile->bundle() <> 'remote_video') {
      $file_entities = ($servicefile->hasField('field_access_terms') ? $servicefile->get('field_access_terms')->referencedEntities() : NULL);
      $sf_access = ((!is_null($file_entities) && array_key_exists(0, $file_entities)) ? $file_entities[0]->label() : FALSE);
      $source_field = $media_source_service->getSourceFieldName($servicefile->bundle());
      if (!empty($source_field)) {
        $sf_file = ($servicefile->hasField($source_field) && (is_object($servicefile->get($source_field)) && $servicefile->get($source_field)->referencedEntities() != NULL) ? $servicefile->get($source_field)->referencedEntities()[0] : FALSE);
        if ($sf_file) {
          $sf_uri = $islandora_utils->getDownloadUrl($sf_file);
          $sf_link = Link::fromTextAndUrl($this->t('Derivative'), Url::fromUri($sf_uri, ['attributes' => ['class' => ['dropdown-item']]]));
          // $download_info .= $servicefile->get('field_mime_type')->value;
        }
      }
    }
    if ($masterfile && $masterfile->bundle() <> 'remote_video') {
      $source_field = $media_source_service->getSourceFieldName($masterfile->bundle());
      if (!empty($source_field)) {
        $pmf_file = $masterfile->get($source_field)->referencedEntities()[0];
        $pmf_uri = $islandora_utils->getDownloadUrl($pmf_file);
        $pmf_link = Link::fromTextAndUrl($this->t('Master'), Url::fromUri($pmf_uri, ['attributes' => ['class' => ['dropdown-item']]]));
        // $download_info .= $masterfile->get('field_mime_type')->value;
      }
    }

    $user_roles = $this->currentUser->getRoles();
    $markup = '';
    $links = [];
    if (isset($of_file)) {
      $access_of_media = $origfile->access('view', $this->currentUser);
      if ($access_of_media && isset($of_link)) {
        $links[] = $of_link->toRenderable();
      }
    }
    if (isset($sf_file)) {
      $access_sf_media = $servicefile->access('view', $this->currentUser);
      if ($access_sf_media && isset($sf_link)) {
        $links[] = $sf_link->toRenderable();
      }
    }
    if (isset($masterfile)) {
      $access_pmf_media = $masterfile->access('view', $this->currentUser);
      if ($access_pmf_media && isset($pmf_link)) {
        $links[] = $pmf_link->toRenderable();
      }
    }

    if ($links == [] && in_array('anonymous', $user_roles)) {
      $markup = "<i class='fas fa-lock'></i> Download restricted. Please <a href=''>sign in.</a>";
    }
    $date = new \DateTime();
    $today = $date->format("c");
    if ($node->hasField('field_embargo_release_date') && $node->get('field_embargo_release_date') && $node->get('field_embargo_release_date')->value != NULL && $node->get('field_embargo_release_date')->value != 'T23:59:59' && $node->get('field_embargo_release_date')->value >= $today) {
    // if its embargoed, remove the download options entirely.
      $markup = "<i class='fas fa-lock'></i> Download restricted until " . $node->get('field_embargo_release_date')->date->format('Y-m-d') . ".";
    }

    $return = [
      '#asu_download_info' => $download_info,
      '#asu_download_restricted' => ['#markup' => $markup],
      '#asu_download_links' => $links,
      '#file_size' => $file_size,
      '#theme' => 'asu_item_extras_downloads_block',
    ];

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
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
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $nid]);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
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

}
