<?php

namespace Drupal\asu_brand\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'CallsToActionBlock' block.
 *
 * @Block(
 *  id = "calls_to_action_block",
 *  admin_label = @Translation("Calls to action block"),
 * )
 */
class CallsToActionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // $build['#theme'] = 'calls_to_action_block';
    $share = '<h4>Contribute</h4><p>Deposit your scholarly work into KEEP for preservation and ongoing access.</p><br/><br/><div class="row"><div class="col-md-12"><a role="button" class="btn btn-primary" href="/self_deposit/asu_repository_item">Share your work!</a></div></div>';
    $contact = '<h4>Get in touch</h4><p>Reach out to us if you have questions or concerns about KEEP.</p><br/><br/><div class="row"><div class="col-md-12"><a role="button" class="btn btn-primary" href="/form/contact">Contact us</a></div></div>';
    $resources = '<h4>Learn more</h4><ul><li><a href="http://libguides.asu.edu/openaccess">Open Access at ASU</a></li><li><a href="/policies">Repository Policies</a></li></ul>';
    $build['calls_to_action_block']['#markup'] = '<div class="container"><div class="row calls-to-action"><div class="col-md-4">' . $share . '</div><div class="col-md-4">' . $contact . '</div><div class="col-md-4">' . $resources . '</div></div></div>';

    return $build;
  }

}
