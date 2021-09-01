<?php

namespace Drupal\asu_admin_toolbox\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;

/**
 * Provides an 'Admin toolbox' Block.
 *
 * @Block(
 *   id = "admin_toolbox",
 *   admin_label = @Translation("Admin toolbox"),
 *   category = @Translation("Views"),
 * )
 */
class AdminToolboxBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
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
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor for Admin Toolbox Block.
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
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        RouteMatchInterface $route_match,
        RequestStack $request_stack,
        AccountProxy $current_user,
        GroupMembershipLoaderInterface $group_membership_loader,
        EntityTypeManagerInterface $entityTypeManager
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->groupMembershipLoader = $group_membership_loader;
    $this->entityTypeManager = $entityTypeManager;
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
          $container->get('current_user'),
          $container->get('group.membership_loader'),
          $container->get('entity_type.manager')
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
    $node = $this->routeMatch->getParameter('node');
    if (!is_object($node)) {
      $nid = $this->routeMatch->getParameter('arg_0');
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if (!is_object($node)) {
        return;
      }
    }
    $is_collection = ($node->bundle() == 'collection');
    $is_complex_object = FALSE;
    $is_asu_repository_item = ($node->bundle() == 'asu_repository_item');
    $user_roles = $this->currentUser->getRoles();
    $user_is_admin_or_metadata_manager = (in_array("administrator", $user_roles) || in_array("metadata_manager", $user_roles));
    if ($is_asu_repository_item) {
      $field_model_tid = $node->get('field_model')->getString();
      $field_model_term = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->load($field_model_tid);
      $is_complex_object = (((isset($field_model_term) && is_object($field_model_term)) ?
        $field_model_term->getName() : '') == 'Complex Object');
    }
    $canUpdate = $node->access('update', $this->currentUser);
    $output_links = [];
    // Add item link.
    $use_can_add_child = ($is_complex_object || $is_collection) && $this->canAddChild();
    if ($use_can_add_child) {
      // This link is a little bit tricky... it needs to have a fragment like
      // this for example, where the value 10 is the collection id() value.
      // node/add/asu_repository_item?edit[field_member_of][widget][0][target_id]=10.
      // In the case of a complex object child, the checkbox "Complex Object
      // Child" is also checked. This is done by adding that field to the
      // GET url like: &edit[field_complex_object_child][value]=1.
      $url = Url::fromUri(
            $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
            '/node/add/asu_repository_item?edit[field_member_of][widget][0][target_id]=' .
            $node->id() . ($is_complex_object ? '&edit[field_complex_object_child][value]=1' : ''), ['attributes' => ['class' => 'nav-link']]
        );
      $config = \Drupal::config('self_deposit.selfdepositsettings');
      $deposit_config = \Drupal::config('asu_deposit_methods.depositsettings');
      if ($is_complex_object) {
        $link = Link::fromTextAndUrl($this->t('Add media &nbsp; <i class="fas fa-plus-circle"></i>'), $url);
        if ($config->get('perf_archive_default_collection')) {
          if ($node->get('field_member_of') && $node->get('field_member_of')->entity->id() == $config->get('perf_archive_default_collection')){
            $pa_url = Url::fromRoute('self_deposit.perf_archive.add_child', [
              'node_type' => 'asu_repository_item',
              'parent' => $node->id()
            ], ['attributes' => ['class' => 'nav-link']]);
            $link = Link::fromTextAndUrl($this->t('Add Performance Archive Child item &nbsp; <i class="fas fa-plus-circle"></i>'), $pa_url);
          }
        }
        if ($deposit_config->get('sheet_music_default_collection')) {
          if ($node->get('field_member_of') && $node->get('field_member_of')->entity->id() == $deposit_config->get('sheet_music_default_collection')) {
            $pa_url = Url::fromRoute('asu_deposit_methods.sheet_music.add_child', [
              'node_type' => 'asu_repository_item',
              'parent' => $node->id()
            ], ['attributes' => ['class' => 'nav-link']]);
            $link = Link::fromTextAndUrl($this->t('Add Sheet Music Child item &nbsp; <i class="fas fa-plus-circle"></i>'), $pa_url);
          }
        }
      }
      else {
        $link = Link::fromTextAndUrl($this->t('Add item &nbsp; <i class="fas fa-plus-circle"></i>'), $url);
        if ($is_collection && $config->get('perf_archive_default_collection')) {
          if ($node->id() == $config->get('perf_archive_default_collection')){
            $pa_url = Url::fromRoute('self_deposit.perf_archive.add', [
              'node_type' => 'asu_repository_item'
            ], ['attributes' => ['class' => 'nav-link']]);
            $link = Link::fromTextAndUrl($this->t('Add Performance Archive item &nbsp; <i class="fas fa-plus-circle"></i>'), $pa_url);
          }
        }
        if ($is_collection && $deposit_config->get('sheet_music_default_collection')) {
          if ($node->id() == $deposit_config->get('sheet_music_default_collection')) {
            $pa_url = Url::fromRoute('asu_deposit_methods.sheet_music.add', [
              'node_type' => 'asu_repository_item'
            ], ['attributes' => ['class' => 'nav-link']]);
            $link = Link::fromTextAndUrl($this->t('Add Sheet Music item &nbsp; <i class="fas fa-plus-circle"></i>'), $pa_url);
          }
        }
      }
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }

    if ($canUpdate) {
      // Edit link check edit-any-asu-repository-item-content or
      // edit-own-asu-repository-item-content permissions.
      $url = Url::fromUri(
            $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
            '/node/' . $node->id() . '/edit', ['attributes' => ['class' => 'nav-link']]
        );
      $link = Link::fromTextAndUrl($this->t('Edit &nbsp; <i class="fas fa-pencil-alt"></i>'), $url);
      $link = $link->toRenderable();
      $output_links[] = render($link);
      if ($canUpdate && $is_complex_object) {
        // Reorder items
        $url = Url::fromUri(
              $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
              '/node/' . $node->id() . '/members/reorder', ['attributes' => ['class' => 'nav-link']]
          );
        $link = Link::fromTextAndUrl($this->t('Reorder items &nbsp; <i class="fas fa-sort"></i>'), $url);
        $link = $link->toRenderable();
        $output_links[] = render($link);
      }
      if ($is_collection) {
        // Statistics link.
        $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/collections/' . $node->id() . '/statistics', ['attributes' => ['class' => 'nav-link']]);
        $link = Link::fromTextAndUrl($this->t('Statistics &nbsp; <i class="fas fa-chart-bar"></i>'), $url);
        $link = $link->toRenderable();
        $output_links[] = render($link);
      }
      if ($node->hasField('field_model') && $node->get('field_model')->entity != NULL
      ) {
        $output_links[] = "<a class='nav-link disabled field--label-inline'><div class='field__label'>Model</div>: " . $node->get('field_model')->entity->getName() . "</a>";
      }
      if (!($is_complex_object) && (!$is_collection)) {
        $url = Url::fromUri(
              $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
              '/node/' . $node->id() . '/media/add', ['attributes' => ['class' => 'nav-link']]
          );
        $link = Link::fromTextAndUrl($this->t('Add media &nbsp; <i class="fas fa-plus-circle"></i>'), $url);
        $link = $link->toRenderable();
        $output_links[] = render($link);
      }
    }

    if ($user_is_admin_or_metadata_manager && ($is_collection || $is_asu_repository_item)) {
      $route_part = ($is_collection) ? 'collections' : 'items';
      $url = Url::fromUri(
            $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
            '/' . $route_part . '/' . $node->id() . '/csv', ['attributes' => ['class' => 'nav-link']]
        );
      $link = Link::fromTextAndUrl($this->t('Download CSV &nbsp; <i class="fas fa-file-export"></i>'), $url);
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }
    if ($user_is_admin_or_metadata_manager && $is_asu_repository_item) {
      // Legacy item link... look up the node's field_pid value and if the
      // first character is not an "a"
      // If both of these are true, then the link would be to:
      // repository.asu.edu/items/{node.field_pid}
      $field_pid = $node->get('field_pid')->getString();
      if ($field_pid && (strtolower(substr($field_pid, 0, 1)) <> "a")) {
        $legacy_uri = "https://repository.asu.edu/items/" . $field_pid;
        $url = Url::fromUri($legacy_uri, ['attributes' => ['target' => '_blank', 'rel' => 'noopener', 'class' => 'nav-link']]);
        $link = Link::fromTextAndUrl($this->t('Legacy URI<span class="visually-hidden">, opens in a new window</span> &nbsp; <i class="fas fa-external-link-alt"></i>'), $url);
        $link = $link->toRenderable();
        $output_links[] = render($link);
      }
    }
    if (in_array('administrator', $this->currentUser->getRoles())) {
      $mapper = \Drupal::service('islandora.entity_mapper');
      $flysystem_config = Settings::get('flysystem');
      $fedora_root = $flysystem_config['fedora']['config']['root'];
      $fedora_root = rtrim($fedora_root, '/');
      $path = $mapper->getFedoraPath($node->uuid());
      $path = trim($path, '/');
      $fedora_uri = "$fedora_root/$path";
      $url = Url::fromUri($fedora_uri, ['attributes' => ['target' => '_blank', 'rel' =>'noopener', 'class' => 'nav-link']]);
      $link = Link::fromTextAndUrl($this->t('Fedora URI<span class="visually-hidden">, opens in a new window</span> &nbsp; <i class="fas fa-external-link-alt"></i>'), $url);
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }
    return [
      '#markup' => (count($output_links) > 0) ?
      "<div class='pseudo_block'><h2>Admin toolbox</h2><nav class='sidebar'>".implode('', $output_links)."</nav></div>" :
      "",
      '#attached' => [
        'library' => [
          'asu_admin_toolbox/style',
        ],
      ],
    ];
  }

  /**
   * Checks whether the current user can add a child to the collection.
   *
   * @return bool
   *   Returns whether the user can add a child to the object.
   */
  public function canAddChild() {
    $grps = $this->groupMembershipLoader->loadByUser($this->currentUser);
    $access = FALSE;
    $plugin_id = 'group_node:asu_repository_item';
    foreach ($grps as $grp) {
      if ($grp) {
        $access |= ($grp->hasPermission("create $plugin_id entity", $this->currentUser));
      }
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = $this->routeMatch->getParameter('node')) {
      if (is_string($node)) {
        $nid = $node;
      }
      else {
        $nid = $node->id();
      }
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $nid]);
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

