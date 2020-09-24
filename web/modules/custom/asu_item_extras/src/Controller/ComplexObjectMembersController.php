<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Controller for Complex Object Members "Included in this item" view page.
 */
class ComplexObjectMembersController extends ControllerBase {

  /**
   * Constructs a ComplexObjectMembersController object.
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
    $build_output = [];
    if ($nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      // What is the type for this node?
      $content_type = $node->getType();

      // What is the model for this node?
      $field_model_tid = $node->get('field_model')->getString();
      $field_model_term = Term::load($field_model_tid);
      $field_model = (isset($field_model_term) && is_object($field_model_term)) ?
        $field_model_term->getName() : '';

      // check that the model for this node is set to "Complex Object".
      if (($content_type == 'asu_repository_item') && $field_model == 'Complex Object') {
        $children = asu_item_extras_get_complex_object_child_nodes($nid);
        foreach ($children as $child_obj) {
          if ($child_obj->entity_id) {
            $node = \Drupal::entityTypeManager()->getStorage('node')->load($child_obj->entity_id);
            $build_output[] = node_view($node, 'complex_object_child_box');
          }
        }
      }
    }
    return [
      '#markup' => '<h2>' . t('Included in this item'),
      '#cache' => ['max-age' => 0],
      $build_output
    ];
  }
}
