<?php

namespace Drupal\asu_search\Plugin\Block;

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
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }
    $output_links = array();
    // Add a link for the "Overview" of this node.
    $variables['nodeid'] = $nid;
    $url = Url::fromRoute('<current>', array());
    $link = Link::fromTextAndUrl(t('Overview'), $url);
    dpm($url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    // Add a link to the "View full metadata" anchor for this node.
    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid . '/full-metadata');
    $link = Link::fromTextAndUrl(t('View full metadata'), $url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    // Add a link to get the Permalink for this node. Could this be a javascript
    // event that will send the current node's URL to the copy buffer?
    $url = Url::fromRoute('<current>', array(), array('fragment' => 'permalink'));
    $link = Link::fromTextAndUrl(t('Permalink'), $url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    return [
      '#markup' => 
        (count($output_links) > 0) ? 
        "<ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul>" :
        "",
    ];
  }

}