<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'About this collection' Sidebar Block.
 *
 * @Block(
 *   id = "about_this_collection_sidebar_block",
 *   admin_label = @Translation("About this collection sidebar"),
 *   category = @Translation("Views"),
 * )
 */
class AboutThisCollectionSidebarBlock extends BlockBase {
  // TODO add cache tags based on the node id


  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The title of the block could be dependant on the underlying Islandora
     * Model used. In liey of that, the title should just be "About this item".
     *
     * The links within this block should be:
     *  - Overview
     *  - Permalink
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    // TODO - use dependency injection
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    }
    else {
      $nid = 0;
    }
    $output_links = [];
    // Add a link for the "Overview" of this node.
    $variables['nodeid'] = $nid;
    $url = Url::fromRoute('<current>', []);
    $link = Link::fromTextAndUrl(t('Overview'), $url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    // Add a link to get the Permalink for this node. Could this be a javascript
    // event that will send the current node's URL to the copy buffer?
    if ($node->hasField('field_handle') && $node->get('field_handle')->value != NULL) {
      $hdl = $node->get('field_handle')->value;
      $output_links[] = '<a href="' . $hdl . '">Permalink</a> <span class="fa fa-link fa-flip-horizontal fa-lg copy_permalink_link" title="' . $hdl . '">&nbsp;</span>';
    }
    else {
      $url_str = \Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid;
      $url = Url::fromUri($url_str);
      $output_links[] = '<a href="' . $url_str . '">Permalink</a> <span class="fa fa-link fa-flip-horizontal fa-lg copy_permalink_link" title="' . $url_str .
          '">&nbsp;</span>';
    }
    return [
      '#markup' => (count($output_links) > 0) ?
        "<nav><ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul></nav>" :
        "",
      'permalink' => [
        '#type' => 'hidden',
        '#id' => 'permalink_about_editbox',
        '#attached' => [
          'library' => [
            'asu_item_extras/interact',
          ],
        ],
        '#attributes' => [
          'class' => array('disabled_small_prompt'),
          'readonly' => TRUE,
        ],
        '#value' => $url->toString(),
      ],
    ];
  }

}
