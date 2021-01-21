<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'About this item' Block.
 *
 * @Block(
 *   id = "about_this_item_block",
 *   admin_label = @Translation("About this item"),
 *   category = @Translation("Views"),
 * )
 */
class AboutThisItemBlock extends BlockBase {

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
     *  - View full metadata
     *  - Permalink
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    // TODO - use dependency injection.
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
    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/items/' . $nid);
    $link = Link::fromTextAndUrl($this->t('Overview'), $url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    // The link to "Full metadata" has been taken out of this block and moved
    // to the bottom of the page - and as long as there are tabs for nodes to
    // "View" and "Full metadata", the link in this block is extra.
    // Add a link to get the Permalink for this node. Could this be a javascript
    // event that will send the current node's URL to the copy buffer?
    if ($node->hasField('field_handle') && $node->get('field_handle')->value != NULL) {
      $hdl = $node->get('field_handle')->value;
      $output_links[] = '<span class="copy_permalink_link" title="' . $hdl . '">Permalink</span>&nbsp; <span class="far fa-copy fa-lg copy_permalink_link" title="' . $hdl . '">&nbsp;</span>';
    }

    return [
      '#cache' => ['max-age' => 0],
      '#markup' =>
      (count($output_links) > 0) ?
      "<nav><ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul></nav>" :
      "",
      '#attached' => [
        'library' => [
          'asu_item_extras/interact',
        ],
      ],
    ];
  }

}
