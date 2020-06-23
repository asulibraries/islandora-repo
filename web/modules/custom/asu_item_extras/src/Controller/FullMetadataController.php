<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Full metadata view page.
 */
class FullMetadataController extends ControllerBase {

  /**
   * Constructs a FullMetadataController object.
   *
   */
  public function __construct() {
    // DO NOTHING SPECIAL?
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Title callback for the asu_item_extras.user_tab route.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return string
   *   The title.
   */
  public function getTitle(UserInterface $user) {
    return $user->getDisplayName();
  }

  /**
   * Builds content for the asu_item_extras controllers.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   (optional) The user account.
   *
   * @return array
   *   The render array.
   */
  public function buildContent(UserInterface $user = NULL) {
    $nid = \Drupal::routeMatch()->getParameter('node');
    if ($nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $content_type = $node->getType();
      return ($content_type == 'asu_repository_item') ? node_view($node, 'full_metadata') : array();
    } else {
      return array();
    }
  }
}
