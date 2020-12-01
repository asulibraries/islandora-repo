<?php

namespace Drupal\asu_brand\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'CallsToActionPrismBlock' block.
 *
 * @Block(
 *  id = "calls_to_action_prism_block",
 *  admin_label = @Translation("Calls to action block (for PRISM)"),
 * )
 */
class CallsToActionPrismBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $share = '<h4>TBD</h4><p>Content coming soon.</p><br/><br/><div class="row"><div class="col-md-12"><a role="button" class="btn btn-primary" href="/self_deposit/asu_repository_item">Share your work!</a></div></div>';
    $contact = '<h4>Get in touch</h4><p>Reach out to us if you have questions or concerns about PRISM.</p><br/><br/><div class="row"><div class="col-md-12"><a role="button" class="btn btn-primary" href="/form/contact">Contact us</a></div></div>';
    $resources = '<h4>Learn more</h4><ul><li><a href="/policies">Repository Policies</a></li></ul>';
    $build['calls_to_action_block']['#markup'] = '<div class="container"><div class="row calls-to-action"><div class="col-md-4">' . $share . '</div><div class="col-md-4">' . $contact . '</div><div class="col-md-4">' . $resources . '</div></div></div>';

    return $build;
  }

}
