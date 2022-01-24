<?php

namespace Drupal\asu_default_fields\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Makes a node example.
 *
 * @Action(
 * id = "test_node_action",
 * label = @Translation("Make selected content example"),
 * type = "node"
 * )
 */
class TestNodeAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    \Drupal::logger('test node action')->info("this should only fire once");
    /* example stuff */
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('edit', $account, $return_as_object);
  }

}
