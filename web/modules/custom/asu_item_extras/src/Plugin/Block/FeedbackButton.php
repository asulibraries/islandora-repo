<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'Feedback' Block.
 *
 * @Block(
 *   id = "asu_feedback_button",
 *   admin_label = @Translation("Feedback Button"),
 *   category = @Translation("Views"),
 * )
 */
class FeedbackButton extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for Feedback Button Block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $currentRequest
   *   The current request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $currentRequest, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRequest = $currentRequest;
    $this->routeMatch = $route_match;
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
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->routeMatch->getParameter('node');
    if ($node) {
      $nid = (is_string($node) ? $node : $node->id());
      $node = is_string($node) ? $this->entityTypeManager->getStorage('node')->load($node) : $node;
    }
    else {
      $nid = 0;
    }
    if (isset($node)) {
      $cid = $this->getCollectionParent($node);
    }
    else {
      $cid = 0;
    }
    $url_base = $this->currentRequest->getSchemeAndHttpHost();
    $class = 'btn btn-md btn-gray';
    if ($cid == $nid) {
      $feedback_url = Url::fromUri($url_base . '/form/feedback?source_entity_type=node&source_entity_id=' . $nid . '&collection=' . $cid . '&primary_element=collection');
    }
    else {
      $feedback_url = Url::fromUri($url_base . '/form/feedback?source_entity_type=node&source_entity_id=' . $nid . '&item=' . $nid . '&collection=' . $cid . '&primary_element=item');
    }
    $link = Link::fromTextAndUrl($this->t('<i class="fas fa-comments"></i> Feedback'), $feedback_url)->toRenderable();
    $link['#attributes'] = ['class' => $class, 'title' => $this->t('Feedback')];
    $markup = [
      '#markup' => \Drupal::service('renderer')->render($link),
    ];
    return $markup;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = $this->routeMatch->getParameter('node')) {
      $nid = is_string($node) ? $node : $node->id();
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

  /**
   * A helper function to walk the member_of tree up to a collection node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   */
  public function getCollectionParent(NodeInterface $node) {
    // If the node is a collection itself, the code here should return the
    // id() value of the collection itself.
    if ($node->bundle() == 'collection') {
      return $node->id();
    }
    if (!$node->get('field_member_of')->isEmpty()) {
      $parent = $node->get('field_member_of')->entity;
      if (isset($parent)) {
        if ($parent->bundle() == 'collection') {
          return $parent->id();
        }
        elseif ($parent->bundle() == 'asu_repository_item') {
          return $this->getCollectionParent($parent);
        }
      }
      return NULL;
    }
  }

}
