<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Complex Title' Block for page title purposes.
 *
 * @Block(
 *   id = "asu_complex_title",
 *   admin_label = @Translation("The complex title"),
 *   category = @Translation("Views"),
 * )
 */
class ASUComplexTitle extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    //
    // NOTES: this block should be set to display at the top of the "Breadcrumb"
    // region and be configured to only be used for the "Content types" of:
    //   - asu_repository_item
    //   - collection
    //
    // Since this block should be set to display on node/[nid] pages that are
    // "ASU Repository Item", or possibly "Collection", these should both have
    // the paragraph field that is used for display.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }
    $x = $node->field_title->get(0)->entity;
    $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder($x->getEntityTypeId());
    $para_render = render($view_builder->view($x, 'default'));
    return [
      'complex_title' => [
        '#type' => 'item',
        '#prefix' => '<h1 class="title">',
        '#suffix' => '</h1>',
        '#markup' => $para_render,
      ]];
  }

  public function getCacheMaxAge() {
      return 0;
  }
}
