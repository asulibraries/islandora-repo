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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $currentRequest, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRequest = $currentRequest;
    $this->routeMatch = $route_match;
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->routeMatch->getParameter('node');
    if ($node) {
      $nid = $node->id();
    }
    else {
      $nid = 0;
    }
    if (isset($node)) {
      $cid = $this->getCollectionParent($node);
    } else {
      $cid = 0;
    }
    $url_base = $this->currentRequest->getSchemeAndHttpHost();
    $class = 'btn btn-primary';
    if ($cid == $nid) {
      $feedback_url = Url::fromUri($url_base . '/form/feedback?source_entity_type=node&source_entity_id=' . $nid . '&collection=' . $cid . '&primary_element=collection');
    }
    else {
      $feedback_url = Url::fromUri($url_base . '/form/feedback?source_entity_type=node&source_entity_id=' . $nid . '&item=' . $nid . '&collection=' . $cid . '&primary_element=item');
    }
    $link = Link::fromTextAndUrl($this->t('<i class="fas fa-comments"></i> Feedback'), $feedback_url)->toRenderable();
    $link['#attributes'] = ['class' => $class];
    $markup = [
      '#markup' => render($link),
    ];
    return $markup;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = $this->routeMatch->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
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
      if ($parent->bundle() == 'collection') {
        return $parent->id();
      }
      elseif ($parent->bundle() == 'asu_repository_item') {
        return $this->getCollectionParent($parent);
      }
      else {
        return NULL;
      }
    }
  }

}
